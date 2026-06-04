<?php
declare(strict_types=1);

namespace Diversworld\ContaoEditorialWorkflow\EventListener;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowManager;

class DashboardListener
{
    public function __construct(
        private readonly Connection $db,
        private readonly RequestStack $requestStack,
        private readonly WorkflowManager $workflowManager,
        private readonly array $enabledTables
    ) {
    }

    public function onGetDashboard(): array
    {
        $user = $this->workflowManager->getBackendUser();
        if (!$user) {
            return [];
        }

        $isReviewer = $this->workflowManager->hasWorkflowPermission('reviewer');
        if (!$isReviewer && !$user->isAdmin) {
            return [];
        }

        $queries = [];
        foreach ($this->enabledTables as $table) {
            // Check if user has access to this table in general
            if (!$user->isAdmin && !$user->hasAccess($table, 'tables')) {
                continue;
            }
            $queries[] = "SELECT id, workflow_status, '$table' as ptable FROM $table WHERE workflow_status = 'review'";
        }

        if (empty($queries)) {
            return [];
        }

        $sql = implode(' UNION ', $queries);
        $pending = $this->db->fetchAllAssociative($sql);

        return $pending;
    }
}
