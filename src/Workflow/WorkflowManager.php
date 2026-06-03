<?php

namespace Diversworld\ContaoEditorialWorkflow\Workflow;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\BackendUser;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

class WorkflowManager
{
    private $framework;
    private $db;
    private $security;
    private $fourEyesPrinciple;
    private $enabledTables;

    public function __construct(
        ContaoFramework $framework,
        Connection      $db,
        Security        $security,
        bool            $fourEyesPrinciple,
        array           $enabledTables
    )
    {
        $this->framework = $framework;
        $this->db = $db;
        $this->security = $security;
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

    private function hasWorkflowPermission(string $role): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof BackendUser) {
            return false;
        }

        $permissions = $user->editorial_workflow_permissions;
        if (!is_array($permissions)) {
            $permissions = deserialize($permissions, true);
        }

        return in_array($role, $permissions, true);
    }

    private function getCurrentStatus($table, $id): string
    {
        $status = $this->db->fetchOne("SELECT workflow_status FROM $table WHERE id = ?", [$id]);
        return $status ?: WorkflowStatus::STATUS_DRAFT;
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
    }
}
