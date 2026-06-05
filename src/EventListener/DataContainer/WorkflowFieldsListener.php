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
    #[AsCallback(table: 'tl_newsletter', target: 'fields.workflow_status.save')]
    public function onStatusSave($value, DataContainer $dc)
    {
        $oldValue = $dc->activeRecord ? $dc->activeRecord->workflow_status : null;

        if ($oldValue !== $value) {
            $this->workflowManager->logStatusChange(
                $dc->table,
                $dc->id,
                $oldValue ?? '',
                $value,
                $dc->activeRecord ? ($dc->activeRecord->workflow_comment ?: '') : ''
            );
        }

        return $value;
    }

    #[AsCallback(table: 'tl_page', target: 'fields.workflow_status.options')]
    #[AsCallback(table: 'tl_article', target: 'fields.workflow_status.options')]
    #[AsCallback(table: 'tl_content', target: 'fields.workflow_status.options')]
    #[AsCallback(table: 'tl_news', target: 'fields.workflow_status.options')]
    #[AsCallback(table: 'tl_calendar_events', target: 'fields.workflow_status.options')]
    #[AsCallback(table: 'tl_faq', target: 'fields.workflow_status.options')]
    #[AsCallback(table: 'tl_newsletter', target: 'fields.workflow_status.options')]
    public function onStatusOptions(DataContainer $dc): array
    {
        $options = [
            WorkflowStatus::STATUS_DRAFT,
            WorkflowStatus::STATUS_REVIEW,
        ];

        if ($this->workflowManager->canChangeStatus($dc->table, $dc->id, WorkflowStatus::STATUS_APPROVED)) {
            $options[] = WorkflowStatus::STATUS_APPROVED;
            $options[] = WorkflowStatus::STATUS_REJECTED;
        }

        if ($this->workflowManager->canChangeStatus($dc->table, $dc->id, WorkflowStatus::STATUS_PUBLISHED)) {
            $options[] = WorkflowStatus::STATUS_PUBLISHED;
        }

        // Immer den aktuellen Status hinzufügen, falls er nicht in der Liste ist
        $currentStatus = $dc->activeRecord ? $dc->activeRecord->workflow_status : null;
        if ($currentStatus && !in_array($currentStatus, $options, true)) {
            $options[] = $currentStatus;
        }

        // Archiviert für alle (oder nach Bedarf einschränken)
        $options[] = WorkflowStatus::STATUS_ARCHIVED;

        return array_unique($options);
    }
}
