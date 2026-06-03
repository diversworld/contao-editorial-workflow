<?php

namespace Diversworld\ContaoEditorialWorkflow\Workflow;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\BackendUser;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Security;

class WorkflowManager
{
    private $framework;
    private $db;
    private $security;

    public function __construct(ContaoFramework $framework, Connection $db, Security $security)
    {
        $this->framework = $framework;
        $this->db = $db;
        $this->security = $security;
    }

    public function canChangeStatus($table, $id, $newStatus): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof BackendUser) {
            return false;
        }

        if ($user->isAdmin) {
            return true;
        }

        $currentStatus = $this->getCurrentStatus($table, $id);

        // Vier-Augen-Prinzip
        if ($newStatus === WorkflowStatus::STATUS_APPROVED && $this->isAuthor($table, $id, $user->id)) {
            // Falls konfiguriert, darf der Autor nicht selbst freigeben
            // return false;
        }

        // Grundlegende Rollenprüfung (vereinfacht)
        if ($newStatus === WorkflowStatus::STATUS_APPROVED || $newStatus === WorkflowStatus::STATUS_REJECTED) {
            return $user->hasAccess('editorial_workflow', 'reviewer');
        }

        return true;
    }

    private function getCurrentStatus($table, $id): string
    {
        $status = $this->db->fetchOne("SELECT workflow_status FROM $table WHERE id = ?", [$id]);
        return $status ?: WorkflowStatus::STATUS_DRAFT;
    }

    private function isAuthor($table, $id, $userId): bool
    {
        // Prüfung, ob der User die letzte Änderung (außer Statusänderung) gemacht hat
        // Oder Prüfung via Contao Versionierung
        return false;
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
