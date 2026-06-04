<?php
declare(strict_types=1);

namespace Diversworld\ContaoEditorialWorkflow\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowManager;

#[AsHook('getSystemMessages')]
class DashboardListener
{
    public function __construct(
        private readonly Connection $db,
        private readonly RequestStack $requestStack,
        private readonly WorkflowManager $workflowManager,
        private readonly array $enabledTables
    )
    {
    }

    public function __invoke(): string
    {
        $user = $this->workflowManager->getBackendUser();
        if (!$user) {
            return '';
        }

        $isReviewer = $this->workflowManager->hasWorkflowPermission('reviewer');
        if (!$isReviewer && !$user->isAdmin) {
            return '';
        }

        $queries = [];
        foreach ($this->enabledTables as $table) {
            if (!$user->isAdmin && !$user->hasAccess($table, 'tables')) {
                continue;
            }
            $queries[] = "SELECT id, workflow_status, '$table' as ptable FROM $table WHERE workflow_status = 'review'";
        }

        if (empty($queries)) {
            return '';
        }

        try {
            $sql = implode(' UNION ', $queries);
            $pending = $this->db->fetchAllAssociative($sql);
        } catch (\Exception $e) {
            // Database not updated yet or other SQL issue
            return '';
        }

        if (empty($pending)) {
            return '';
        }

        $count = count($pending);
        $link = 'contao?do=editorial_workflow'; // Wir müssten noch ein Modul dafür haben oder auf die Tabellen verlinken

        // Einfache Liste für die Systemnachrichten
        $html = '<div class="tl_message"><p class="tl_confirm"><strong>Editorial Workflow:</strong> Es gibt ' . $count . ' ausstehende Prüfung(en).</p></div>';

        return $html;
    }
}
