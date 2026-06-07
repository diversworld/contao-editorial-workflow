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

        // Ensure tl_newsletter is enabled (since it might be missing in Configuration.php)
        try {
            $reflection = new \ReflectionClass($workflowManager);
            $property = $reflection->getProperty('enabledTables');
            $property->setAccessible(true);
            $enabledTables = $property->getValue($workflowManager);

            if (is_array($enabledTables) && !in_array('tl_newsletter', $enabledTables, true)) {
                $enabledTables[] = 'tl_newsletter';
                $property->setValue($workflowManager, $enabledTables);
            }
        } catch (\Exception $e) {
            // Fallback if reflection fails
        }
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

    #[AsCallback(table: 'tl_newsletter', target: 'list.label.label')]
    public function onLabel($row, $label, DataContainer $dc, $args): array|string
    {
        if (class_exists('tl_newsletter')) {
            $newsletter = new \tl_newsletter();
            if (method_exists($newsletter, 'listNewsletters')) {
                $labels = $newsletter->listNewsletters($row);

                $status = $row['workflow_status'] ?? WorkflowStatus::STATUS_DRAFT;
                if ($status !== WorkflowStatus::STATUS_PUBLISHED) {
                    $statusLabel = $GLOBALS['TL_LANG']['MSC']['workflow_status_ref'][$status] ?? $status;
                    $labels[0] .= sprintf(' <span style="color:#999;padding-left:3px">[%s]</span>', $statusLabel);
                }

                return $labels;
            }
        }

        return $label;
    }
}
