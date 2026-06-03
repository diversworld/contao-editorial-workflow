<?php

namespace Diversworld\ContaoEditorialWorkflow\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowManager;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowStatus;

class WorkflowFieldsListener
{
    private $workflowManager;

    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    #[AsCallback(table: 'tl_page', target: 'fields.workflow_status.save')]
    #[AsCallback(table: 'tl_article', target: 'fields.workflow_status.save')]
    #[AsCallback(table: 'tl_content', target: 'fields.workflow_status.save')]
    #[AsCallback(table: 'tl_news', target: 'fields.workflow_status.save')]
    #[AsCallback(table: 'tl_calendar_events', target: 'fields.workflow_status.save')]
    #[AsCallback(table: 'tl_faq', target: 'fields.workflow_status.save')]
    public function onStatusSave($value, DataContainer $dc)
    {
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
