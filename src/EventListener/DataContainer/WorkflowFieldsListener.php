<?php
declare(strict_types=1);

namespace Diversworld\ContaoEditorialWorkflow\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowManager;

class WorkflowFieldsListener
{
    public function __construct(private readonly WorkflowManager $workflowManager)
    {
    }

    #[AsCallback(table: 'tl_page', target: 'config.onload')]
    #[AsCallback(table: 'tl_article', target: 'config.onload')]
    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    #[AsCallback(table: 'tl_news', target: 'config.onload')]
    #[AsCallback(table: 'tl_calendar_events', target: 'config.onload')]
    #[AsCallback(table: 'tl_faq', target: 'config.onload')]
    public function onLoad(DataContainer $dc): void
    {
        $user = $this->workflowManager->getBackendUser();

        // Admins see everything
        if ($user && $user->isAdmin) {
            return;
        }

        // If not admin, check for workflow roles or alexf permission
        if (isset($GLOBALS['TL_DCA'][$dc->table]['fields']['workflow_status'])) {
            $isReviewer = $this->workflowManager->hasWorkflowPermission('reviewer');
            $isPublisher = $this->workflowManager->hasWorkflowPermission('publisher');

            // If user has a workflow role, we force the fields to be visible (ignoring alexf)
            if ($isReviewer || $isPublisher) {
                $GLOBALS['TL_DCA'][$dc->table]['fields']['workflow_status']['exclude'] = false;
                $GLOBALS['TL_DCA'][$dc->table]['fields']['workflow_comment']['exclude'] = false;
                return;
            }

            // For others, if they don't have alexf permission, we hide it completely
            if (!$user || !$user->hasAccess($dc->table . '::workflow_status', 'alexf')) {
                $GLOBALS['TL_DCA'][$dc->table]['fields']['workflow_status']['eval']['doNotShow'] = true;
                $GLOBALS['TL_DCA'][$dc->table]['fields']['workflow_comment']['eval']['doNotShow'] = true;
            }
        }
    }

    #[AsCallback(table: 'tl_page', target: 'fields.workflow_status.save')]
    #[AsCallback(table: 'tl_article', target: 'fields.workflow_status.save')]
    #[AsCallback(table: 'tl_content', target: 'fields.workflow_status.save')]
    #[AsCallback(table: 'tl_news', target: 'fields.workflow_status.save')]
    #[AsCallback(table: 'tl_calendar_events', target: 'fields.workflow_status.save')]
    #[AsCallback(table: 'tl_faq', target: 'fields.workflow_status.save')]
    public function onStatusSave(mixed $value, DataContainer $dc): mixed
    {
        if (!$this->workflowManager->canChangeStatus($dc->table, $dc->id, $value)) {
            throw new \Contao\CoreBundle\Exception\AccessDeniedException('You are not allowed to change the workflow status to ' . $value);
        }

        $oldValue = $dc->activeRecord->workflow_status;

        if ($oldValue !== $value) {
            $this->workflowManager->logStatusChange(
                $dc->table,
                $dc->id,
                $oldValue,
                $value,
                $dc->activeRecord->workflow_comment ?: ''
            );
        }

        return $value;
    }
}
