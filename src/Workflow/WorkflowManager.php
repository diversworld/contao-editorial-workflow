<?php
declare(strict_types=1);

namespace Diversworld\ContaoEditorialWorkflow\Workflow;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\BackendUser;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class WorkflowManager
{
    public function __construct(
        private readonly ContaoFramework    $framework,
        private readonly Connection         $db,
        private readonly Security           $security,
        private readonly NotificationCenter $notificationCenter,
        private readonly bool               $fourEyesPrinciple,
        private readonly array              $enabledTables
    )
    {
    }

    public function canChangeStatus(string $table, $id, string $newStatus): bool
    {
        if (!in_array($table, $this->enabledTables, true)) {
            return true;
        }

        $user = $this->getBackendUser();
        if (!$user) {
            return false;
        }

        if ($user->isAdmin) {
            return true;
        }

        $currentStatus = $this->getCurrentStatus($table, $id);

        // Standard-Statusänderungen erlauben (Entwurf -> Prüfung)
        if ($newStatus === WorkflowStatus::STATUS_REVIEW && ($currentStatus === WorkflowStatus::STATUS_DRAFT || $currentStatus === WorkflowStatus::STATUS_REJECTED)) {
            return true;
        }

        // Status transition logic
        // Only Reviewers can approve/reject
        if (($newStatus === WorkflowStatus::STATUS_APPROVED || $newStatus === WorkflowStatus::STATUS_REJECTED)) {
            if (!$this->hasWorkflowPermission('reviewer')) {
                return false;
            }
        }

        // Only Publishers can publish
        if ($newStatus === WorkflowStatus::STATUS_PUBLISHED) {
            if (!$this->hasWorkflowPermission('publisher')) {
                return false;
            }
        }

        // Vier-Augen-Prinzip
        if ($this->fourEyesPrinciple && $newStatus === WorkflowStatus::STATUS_APPROVED) {
            if ($this->isAuthor($table, $id, (int)$user->id)) {
                return false;
            }
        }

        return true;
    }

    public function hasWorkflowPermission(string $role): bool
    {
        $user = $this->getBackendUser();
        if (!$user) {
            return false;
        }

        $permissions = $user->editorial_workflow_permissions;
        if (!is_array($permissions)) {
            $permissions = StringUtil::deserialize($permissions, true);
        }

        return in_array($role, $permissions, true);
    }

    public function getBackendUser(): ?BackendUser
    {
        $user = $this->security->getUser();
        return $user instanceof BackendUser ? $user : null;
    }

    private function getCurrentStatus($table, $id): string
    {
        try {
            $status = $this->db->fetchOne("SELECT workflow_status FROM $table WHERE id = ?", [$id]);
            return $status ?: WorkflowStatus::STATUS_DRAFT;
        } catch (\Exception $e) {
            return WorkflowStatus::STATUS_DRAFT;
        }
    }

    private function isAuthor($table, $id, $userId): bool
    {
        // Prüfung via Contao Versionierung (wer hat die letzte Version erstellt)
        $authorId = $this->db->fetchOne("SELECT userid FROM tl_version WHERE fromTable = ? AND pid = ? ORDER BY version DESC LIMIT 1", [$table, $id]);

        return (int)$authorId === $userId;
    }

    public function logStatusChange(string $table, $id, string $fromStatus, string $toStatus, string $comment = '', int $version = 0): void
    {
        $user = $this->getBackendUser();
        $userId = $user ? $user->id : 0;

        try {
            $this->db->insert('tl_editorial_workflow_log', [
                'tstamp' => time(),
                'pid' => $id,
                'ptable' => $table,
                'user_id' => $userId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'comment' => $comment,
                'version' => $version,
            ]);
        } catch (\Exception $e) {
            // Log table might not exist
        }

        $this->sendNotification($table, $id, $toStatus, $comment, $user);
    }

    private function sendNotification($table, $id, $status, $comment, $user)
    {
        $notificationId = 0;
        $tokens = [
            'workflow_table' => $table,
            'workflow_id' => $id,
            'workflow_status' => $status,
            'workflow_comment' => $comment,
            'workflow_user' => $user ? $user->username : 'System',
        ];

        // Fetch object details for more tokens if possible
        try {
            $row = $this->db->fetchAssociative("SELECT * FROM $table WHERE id = ?", [$id]);
            if ($row) {
                foreach ($row as $key => $val) {
                    $tokens['workflow_data_' . $key] = $val;
                }
            }
        } catch (\Exception $e) {
            // Table might not exist or column might be missing
        }

        // Determine which notification to send based on status
        // These IDs would normally be configured in Contao or we trigger a specific type
        // For NC 2.x we should use the notification type.
        // However, the user asked to integrate it, so we'll trigger it for the defined status changes.

        $type = 'editorial_workflow_' . $status;

        // Find notification IDs for this type
        $notifications = $this->notificationCenter->getNotificationsForNotificationType($type);

        foreach (array_keys($notifications) as $notificationId) {
            $this->notificationCenter->sendNotification((int)$notificationId, $tokens);
        }
    }
}
