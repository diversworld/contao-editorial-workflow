<?php

namespace Diversworld\ContaoEditorialWorkflow\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
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

    #[AsCallback(table: 'tl_page', target: 'list.label.label')]
    #[AsCallback(table: 'tl_news', target: 'list.label.label')]
    #[AsCallback(table: 'tl_calendar_events', target: 'list.label.label')]
    #[AsCallback(table: 'tl_faq', target: 'list.label.label')]
    #[AsCallback(table: 'tl_newsletter', target: 'list.label.label')]
    public function onLabel($row, $label, DataContainer $dc, $args): array|string
    {
        // Newsletter hat eine spezielle Logik, um das Original-Label zu generieren
        if ($dc->table === 'tl_newsletter' && class_exists('tl_newsletter')) {
            $newsletter = new \tl_newsletter();
            if (method_exists($newsletter, 'listNewsletters')) {
                $label = $newsletter->listNewsletters($row);
            }
        }

        // News hat eine spezielle Logik
        if ($dc->table === 'tl_news') {
            if (class_exists('tl_news')) {
                $news = new \tl_news();
                if (method_exists($news, 'addNews')) {
                    $label = $news->addNews($row, $label, $dc, $args);
                }
            }

            // Fallback für Contao 5+, falls tl_news nicht existiert oder addNews fehlschlägt
            if ($label === '' || $label === $row['headline']) {
                $date = Date::parse(\Contao\Config::get('datimFormat'), $row['date']);
                $label = sprintf('%s <span class="label-info">[%s]</span>', $row['headline'], $date);
            }
        }

        // Kalender hat eine spezielle Logik
        if ($dc->table === 'tl_calendar_events' && class_exists('tl_calendar_events')) {
            $events = new \tl_calendar_events();
            if (method_exists($events, 'listEvents')) {
                $label = $events->listEvents($row, $label, $dc, $args);
            }
        }

        // FAQ hat eine spezielle Logik
        if ($dc->table === 'tl_faq' && class_exists('tl_faq')) {
            $faq = new \tl_faq();
            if (method_exists($faq, 'listQuestions')) {
                $label = $faq->listQuestions($row, $label, $dc, $args);
            }
        }

        return $this->appendStatusToLabel($label, $row);
    }

    #[AsCallback(table: 'tl_article', target: 'list.sorting.child_record')]
    public function onArticleChildRecord($row): string
    {
        return $this->handleChildRecord($row, 'tl_article');
    }

    #[AsCallback(table: 'tl_content', target: 'list.sorting.child_record')]
    public function onContentChildRecord($row): string
    {
        return $this->handleChildRecord($row, 'tl_content');
    }

    private function handleChildRecord($row, $table): string
    {
        $label = '';

        if (isset($GLOBALS['TL_DCA'][$table]['list']['sorting']['child_record_callback_orig'])) {
            $callback = $GLOBALS['TL_DCA'][$table]['list']['sorting']['child_record_callback_orig'];
            $label = $this->executeCallback($callback, [$row]);
        }

        // Fallback wenn kein Callback da ist
        if (empty($label)) {
            $label = $row['title'] ?? $row['headline'] ?? ($row['text'] ? substr(strip_tags($row['text']), 0, 50) : 'ID ' . $row['id']);
        }

        return $this->appendStatusToLabel($label, $row);
    }

    private function appendStatusToLabel($label, array $row): string|array
    {
        $status = $row['workflow_status'] ?? WorkflowStatus::STATUS_DRAFT;

        if ($status === WorkflowStatus::STATUS_PUBLISHED) {
            return $label;
        }

        $statusLabel = $GLOBALS['TL_LANG']['MSC']['workflow_status_ref'][$status] ?? $status;
        $statusHtml = sprintf(' <span style="color:#999;padding-left:3px">[%s]</span>', $statusLabel);

        if (is_array($label)) {
            $label[0] .= $statusHtml;
        } else {
            $label .= $statusHtml;
        }

        return $label;
    }

    private function executeCallback($callback, array $args): string
    {
        if (is_array($callback)) {
            if (is_string($callback[0])) {
                $instance = new $callback[0]();
                return call_user_func_array([$instance, $callback[1]], $args);
            }
            return call_user_func_array([$callback[0], $callback[1]], $args);
        }

        if (is_callable($callback)) {
            return call_user_func_array($callback, $args);
        }

        return '';
    }
}
