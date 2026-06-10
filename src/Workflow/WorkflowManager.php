<?php

namespace Diversworld\ContaoEditorialWorkflow\Workflow;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use Contao\StringUtil;
use Contao\Config;
use Symfony\Component\HttpFoundation\RequestStack;

class WorkflowManager
{
    private $framework;
    private $db;
    private $security;
    private $notificationCenter;
    private $requestStack;
    private bool $fourEyesPrinciple;
    private array $enabledTables;
    private array $tableColumns = [];

    public function __construct(
        ContaoFramework $framework,
        Connection      $db,
        Security        $security,
        NotificationCenter $notificationCenter,
        RequestStack    $requestStack,
        bool            $fourEyesPrinciple,
        array           $enabledTables
    )
    {
        $this->framework = $framework;
        $this->db = $db;
        $this->security = $security;
        $this->notificationCenter = $notificationCenter;
        $this->requestStack = $requestStack;
        $this->fourEyesPrinciple = $fourEyesPrinciple;
        $this->enabledTables = $enabledTables;
    }

    public function canChangeStatus($table, $id, $newStatus): bool
    {
        if (!in_array($table, $this->enabledTables, true)) {
            return true;
        }

        $user = $this->security->getUser();
        if (!$user instanceof BackendUser) {
            return false;
        }

        if ($user->isAdmin) {
            return true;
        }

        $currentStatus = $this->getCurrentStatus($table, $id);

        // Vier-Augen-Prinzip
        if ($this->fourEyesPrinciple && $newStatus === WorkflowStatus::STATUS_APPROVED) {
            if ($this->isAuthor($table, $id, (int)$user->id)) {
                return false;
            }
        }

        // Rollenprüfung
        if ($newStatus === WorkflowStatus::STATUS_APPROVED || $newStatus === WorkflowStatus::STATUS_REJECTED) {
            return $this->hasWorkflowPermission('reviewer');
        }

        if ($newStatus === WorkflowStatus::STATUS_PUBLISHED) {
            return $this->hasWorkflowPermission('publisher');
        }

        return true;
    }

    public function canReview(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof BackendUser && ($user->isAdmin || $this->hasWorkflowPermission('reviewer'));
    }

    public function canPublish(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof BackendUser && ($user->isAdmin || $this->hasWorkflowPermission('publisher'));
    }

    public function changeStatus(string $table, int $id, string $newStatus, string $comment = ''): bool
    {
        if (!$this->isEnabledWorkflowTable($table) || !$this->canChangeStatus($table, $id, $newStatus)) {
            return false;
        }

        $oldStatus = $this->getCurrentStatus($table, $id);

        if ($oldStatus === $newStatus) {
            return true;
        }

        $data = [
            'workflow_status' => $newStatus,
        ];

        if ($this->tableHasColumn($table, 'tstamp')) {
            $data['tstamp'] = time();
        }

        if ($comment !== '' && $this->tableHasColumn($table, 'workflow_comment')) {
            $data['workflow_comment'] = $comment;
        }

        $this->db->update($table, $data, ['id' => $id]);
        $this->logStatusChange($table, $id, $oldStatus, $newStatus, $comment);

        return true;
    }

    public function getPendingReviewRecords(int $limit = 100): array
    {
        $records = [];

        foreach ($this->enabledTables as $table) {
            if (!$this->isEnabledWorkflowTable($table) || !$this->tableHasColumn($table, 'workflow_status')) {
                continue;
            }

            $titleColumn = $this->getTitleColumn($table);
            $selectTitle = $titleColumn ? sprintf('%s AS record_title', $titleColumn) : "'' AS record_title";

            $rows = $this->db->fetchAllAssociative(
                sprintf(
                    'SELECT id, workflow_status, %s, %s AS record_tstamp FROM %s WHERE workflow_status = ? ORDER BY %s DESC, id DESC',
                    $selectTitle,
                    $this->tableHasColumn($table, 'tstamp') ? 'tstamp' : '0',
                    $table,
                    $this->tableHasColumn($table, 'tstamp') ? 'tstamp' : 'id'
                ),
                [WorkflowStatus::STATUS_REVIEW]
            );

            foreach ($rows as $row) {
                if (!$this->canChangeStatus($table, (int)$row['id'], WorkflowStatus::STATUS_APPROVED)) {
                    continue;
                }

                $records[] = [
                    'table' => $table,
                    'id' => (int)$row['id'],
                    'status' => (string)$row['workflow_status'],
                    'title' => trim((string)($row['record_title'] ?? '')) ?: sprintf('%s #%d', $table, $row['id']),
                    'tstamp' => (int)($row['record_tstamp'] ?? 0),
                ];
            }
        }

        usort($records, static fn(array $a, array $b): int => [$b['tstamp'], $b['id']] <=> [$a['tstamp'], $a['id']]);

        return array_slice($records, 0, $limit);
    }

    public function hasWorkflowPermission(string $role): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof BackendUser) {
            return false;
        }

        $permissions = $user->editorial_workflow_permissions;
        if (!is_array($permissions)) {
            $permissions = StringUtil::deserialize($permissions, true);
        }

        if (in_array($role, $permissions, true)) {
            return true;
        }

        $groups = $user->groups;
        if (!is_array($groups)) {
            $groups = StringUtil::deserialize($groups, true);
        }

        if ($groups === []) {
            return false;
        }

        $time = time();
        $groupRows = $this->db->fetchAllAssociative(
            'SELECT editorial_workflow_permissions FROM tl_user_group WHERE id IN (?) AND disable != ? AND (start = ? OR start <= ?) AND (stop = ? OR stop > ?)',
            [$groups, '1', '', $time, '', $time],
            [Connection::PARAM_INT_ARRAY]
        );

        foreach ($groupRows as $group) {
            $groupPermissions = StringUtil::deserialize($group['editorial_workflow_permissions'] ?? null, true);

            if (in_array($role, $groupPermissions, true)) {
                return true;
            }
        }

        return false;
    }

    private function getCurrentStatus($table, $id): string
    {
        $status = $this->db->fetchOne("SELECT workflow_status FROM $table WHERE id = ?", [$id]);
        return $status ?: WorkflowStatus::STATUS_DRAFT;
    }

    public function isEnabledWorkflowTable(string $table): bool
    {
        return in_array($table, $this->enabledTables, true) && preg_match('/^tl_[a-z0-9_]+$/', $table) === 1;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return isset($this->getTableColumns($table)[$column]);
    }

    public function getTitleColumn(string $table): ?string
    {
        foreach (['headline', 'title', 'name', 'subject', 'question', 'alias', 'type'] as $column) {
            if ($this->tableHasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function getTableColumns(string $table): array
    {
        if (isset($this->tableColumns[$table])) {
            return $this->tableColumns[$table];
        }

        try {
            $columns = $this->db->createSchemaManager()->listTableColumns($table);
        } catch (\Throwable) {
            return $this->tableColumns[$table] = [];
        }

        return $this->tableColumns[$table] = array_change_key_case($columns, CASE_LOWER);
    }

    private function isAuthor($table, $id, $userId): bool
    {
        // Prüfung via Contao Versionierung (wer hat die letzte Version erstellt)
        $authorId = $this->db->fetchOne("SELECT userid FROM tl_version WHERE fromTable = ? AND pid = ? ORDER BY version DESC LIMIT 1", [$table, $id]);

        return (int)$authorId === $userId;
    }

    public function logStatusChange($table, $id, $fromStatus, $toStatus, $comment = '', $version = 0)
    {
        $user = $this->security->getUser();
        $userId = $user ? $user->id : 0;
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request ? $request->getClientIp() : '';

        $this->db->insert('tl_editorial_workflow_log', [
            'tstamp' => time(),
            'pid' => $id,
            'ptable' => $table,
            'user_id' => $userId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
            'version' => $version,
            'ip' => $ip,
        ]);

        $this->dispatchNotifications($table, $id, $fromStatus, $toStatus, $comment, $userId);
    }

    private function dispatchNotifications($table, $id, $fromStatus, $toStatus, $comment, $userId): void
    {
        $notificationType = 'workflow_status_change';

        if ($toStatus === WorkflowStatus::STATUS_REVIEW) {
            $notificationType = 'workflow_review_requested';
        } elseif ($toStatus === WorkflowStatus::STATUS_APPROVED) {
            $notificationType = 'workflow_approved';
        } elseif ($toStatus === WorkflowStatus::STATUS_REJECTED) {
            $notificationType = 'workflow_rejected';
        } elseif ($toStatus === WorkflowStatus::STATUS_PUBLISHED) {
            $notificationType = 'workflow_published';
        }

        $userName = '';
        if ($userId > 0) {
            $userName = $this->db->fetchOne("SELECT name FROM tl_user WHERE id = ?", [$userId]) ?: '';
        }

        $tokens = [
            'table' => $table,
            'record_id' => $id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
            'user_id' => $userId,
            'user_name' => $userName,
            'author_name' => '',
            'record_label' => (string)$id,
            'admin_email' => $this->getAdminEmail(),
        ];

        // Datensatz-Details laden (z.B. Titel)
        try {
            $record = $this->db->fetchAssociative("SELECT * FROM $table WHERE id = ?", [$id]);
            if ($record) {
                foreach ($record as $k => $v) {
                    if ($v !== null && !is_array($v) && !is_object($v)) {
                        $tokens['record_' . $k] = (string)$v;

                        // Datum/Zeit-Konvertierung für bekannte Felder
                        if (in_array($k, ['date', 'time', 'startTime', 'endTime', 'startDate', 'endDate', 'tstamp'], true) && is_numeric($v) && (int)$v > 0) {
                            $tokens['record_' . $k . '_formatted'] = date($this->getDateFormat($k), (int)$v);
                        }
                    }
                }

                // Record Label setzen
                $titleCol = $this->getTitleColumn($table);
                if ($titleCol && isset($record[$titleCol])) {
                    $tokens['record_label'] = (string)$record[$titleCol];
                }

                // Autor finden (Ersteller der ersten Version)
                $authorId = $this->db->fetchOne("SELECT userid FROM tl_version WHERE fromTable = ? AND pid = ? ORDER BY version LIMIT 1", [$table, $id]);
                if ($authorId) {
                    $tokens['author_name'] = $this->db->fetchOne("SELECT name FROM tl_user WHERE id = ?", [$authorId]) ?: '';
                }
            }
        } catch (\Exception $e) {
            // Ignorieren falls Tabelle nicht existiert o.ä.
        }

        // Benutzer finden, die diese Benachrichtigung abonniert haben
        $users = $this->db->fetchAllAssociative("SELECT id, editorial_workflow_notifications FROM tl_user WHERE disable != '1' AND (start = '' OR start <= ?) AND (stop = '' OR stop > ?)", [time(), time()]);

        $notificationIds = [];

        foreach ($users as $user) {
            if ($user['editorial_workflow_notifications']) {
                $ids = StringUtil::deserialize($user['editorial_workflow_notifications'], true);
                foreach ($ids as $nid) {
                    $notificationIds[(int)$nid][] = $user['id'];
                }
            }
        }

        // Gruppen-Benachrichtigungen ebenfalls prüfen
        $groups = $this->db->fetchAllAssociative("SELECT id, editorial_workflow_notifications FROM tl_user_group WHERE disable != '1' AND (start = '' OR start <= ?) AND (stop = '' OR stop > ?)", [time(), time()]);
        foreach ($groups as $group) {
            if ($group['editorial_workflow_notifications']) {
                $ids = StringUtil::deserialize($group['editorial_workflow_notifications'], true);
                // Finde alle Benutzer in dieser Gruppe
                $groupUsers = $this->db->fetchAllAssociative("SELECT id FROM tl_user WHERE groups LIKE ?", ['%:"' . $group['id'] . '";%']);
                foreach ($ids as $nid) {
                    foreach ($groupUsers as $gu) {
                        $notificationIds[(int)$nid][] = $gu['id'];
                    }
                }
            }
        }

        // Benachrichtigungen versenden
        foreach ($notificationIds as $nid => $uids) {
            // Prüfen ob die Benachrichtigung vom richtigen Typ ist
            $type = $this->db->fetchOne("SELECT type FROM tl_nc_notification WHERE id = ?", [$nid]);
            if ($type === $notificationType) {
                // Hier könnten wir theoretisch an spezifische Empfänger filtern,
                // aber NC sendet meist an die in der Nachricht konfigurierten Adressen.
                // Wir übergeben die Empfänger-IDs als Token, falls das NC-Gateways das unterstützen.
                $tokens['recipient_ids'] = implode(',', array_unique($uids));
                $this->notificationCenter->sendNotification($nid, $tokens);
            }
        }
    }

    private function getAdminEmail(): string
    {
        if ($this->framework->isInitialized()) {
            $email = $this->framework->getAdapter(Config::class)->get('adminEmail');
            if ($email) {
                return (string)$email;
            }
        }

        return '';
    }

    private function getDateFormat(string $column): string
    {
        if (!$this->framework->isInitialized()) {
            return 'Y-m-d H:i:s';
        }

        $config = $this->framework->getAdapter(Config::class);

        return match ($column) {
            'date', 'startDate', 'endDate' => $config->get('dateFormat') ?: 'Y-m-d',
            'time', 'startTime', 'endTime' => $config->get('timeFormat') ?: 'H:i',
            default => $config->get('datimFormat') ?: 'Y-m-d H:i',
        };
    }
}
