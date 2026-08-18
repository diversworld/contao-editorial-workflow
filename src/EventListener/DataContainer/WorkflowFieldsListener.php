<?php

namespace Diversworld\ContaoEditorialWorkflow\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Contao\DC_Table;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowManager;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowStatus;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WorkflowFieldsListener
{
    private $workflowManager;

    public function __construct(
        WorkflowManager $workflowManager,
        private readonly ContainerInterface $container,
    )
    {
        $this->workflowManager = $workflowManager;

        // Ensure tl_newsletter is enabled
        $workflowManager->addEnabledTable('tl_newsletter');
    }

    #[AsCallback(table: 'tl_page', target: 'fields.workflow_status.save')]
    #[AsCallback(table: 'tl_article', target: 'fields.workflow_status.save')]
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
    #[AsCallback(table: 'tl_newsletter', target: 'list.label.label')]
    #[AsCallback(table: 'tl_news', target: 'list.label.label')]
    #[AsCallback(table: 'tl_calendar_events', target: 'list.label.label')]
    public function onLabel($row, $label, DataContainer $dc, ...$args): array|string
    {
        $label_callback_orig_called = false;

        // Call original callback if exists
        if (isset($GLOBALS['TL_DCA'][$dc->table]['list']['label']['label_callback_orig'])) {
            $callback = $GLOBALS['TL_DCA'][$dc->table]['list']['label']['label_callback_orig'];
            $params = array_merge([$row, $label, $dc], $args);

            $res = $this->executeCallback($callback, $params);

            if (!empty($res)) {
                $label = $res;
                $label_callback_orig_called = true;
            }
        }

        // Newsletter hat eine spezielle Logik, um das Original-Label zu generieren
        if (!$label_callback_orig_called && $dc->table === 'tl_newsletter' && class_exists('tl_newsletter')) {
            $newsletter = new \tl_newsletter();
            if (method_exists($newsletter, 'listNewsletters')) {
                $label = $newsletter->listNewsletters($row);
            }
        }

        // News hat eine spezielle Logik
        if (!$label_callback_orig_called && $dc->table === 'tl_news') {
            if (class_exists('tl_news')) {
                $news = new \tl_news();
                if (method_exists($news, 'addNews')) {
                    $label = $news->addNews($row, $label, $dc, $args);
                }
            }

            // Fallback für Contao 5+, falls tl_news nicht existiert oder addNews fehlschlägt
            if ($label === '' || $label === $row['headline']) {
                $date = Date::parse(Config::get('datimFormat'), $row['date']);
                $time = Date::parse(Config::get('timeFormat'), $row['time']);
                $label = sprintf('%s <span class="label-info">[%s %s]</span>', $row['headline'], $date, $time);
            }
        }

        // Kalender hat eine spezielle Logik
        if (!$label_callback_orig_called && $dc->table === 'tl_calendar_events') {
            if (class_exists('tl_calendar_events')) {
                $events = new \tl_calendar_events();
                if (method_exists($events, 'listEvents')) {
                    $label = $events->listEvents($row, $label, $dc, $args);
                }
            }

            // Fallback für Contao 5+, falls tl_calendar_events nicht existiert oder listEvents fehlschlägt
            if ($label === '' || $label === $row['title']) {
                $date = Date::parse(Config::get('dateFormat'), $row['startTime']);
                $label = sprintf('%s  <span class="label-info" >[%s] </span >', $row['title'], $date);
            }
        }

        // FAQ hat eine spezielle Logik
        if (!$label_callback_orig_called && $dc->table === 'tl_faq' && class_exists('tl_faq')) {
            $faq = new \tl_faq();
            if (method_exists($faq, 'listQuestions')) {
                $label = $faq->listQuestions($row, $label, $dc, $args);
            }
        }

        // The original callback must finish the label first. This is
        // especially important for tl_page: Contao adds the page icon, link
        // and optional record ID there, and the exact markup differs between
        // Contao versions. Appending the workflow status afterwards preserves
        // the complete native label in both Contao 5.7 and 6.
        return $this->appendStatusToLabel($label, $row);
    }

    #[AsCallback(table: 'tl_article', target: 'list.sorting.child_record')]
    public function onChildRecord($row, ?DataContainer $dc = null): string
    {
        return $this->handleChildRecord($row, 'tl_article', $dc);
    }

    #[AsCallback(table: 'tl_news', target: 'list.sorting.child_record')]
    public function onNewsChildRecord($row, ?DataContainer $dc = null): string
    {
        return $this->handleChildRecord($row, 'tl_news', $dc);
    }

    #[AsCallback(table: 'tl_calendar_events', target: 'list.sorting.child_record')]
    public function onCalendarEventsChildRecord($row, ?DataContainer $dc = null): string
    {
        return $this->handleChildRecord($row, 'tl_calendar_events', $dc);
    }

    #[AsCallback(table: 'tl_faq', target: 'list.sorting.child_record')]
    public function onFaqChildRecord($row, ?DataContainer $dc = null): string
    {
        return $this->handleChildRecord($row, 'tl_faq', $dc);
    }

    private function handleChildRecord($row, $table, ?DataContainer $dc = null): string
    {
        $label = '';

        // Ensure $dc is a DataContainer instance to satisfy type hints in callbacks.
        // Contao 5's child_record_callback for mode 4 tables sometimes only passes the row,
        // but many existing callbacks (like label_callback or Cgoit's) require a DataContainer.
        if ($dc === null && class_exists(DC_Table::class)) {
            $dc = new DC_Table($table);
        }

        if (isset($GLOBALS['TL_DCA'][$table]['list']['sorting']['child_record_callback_orig'])) {
            $callback = $GLOBALS['TL_DCA'][$table]['list']['sorting']['child_record_callback_orig'];

            // Standard Contao news/calendar callbacks expect ($row, $label) where $label is a string.
            // If we pass $dc as the second argument, it might cause a string conversion error in some versions.
            if (is_array($callback) && is_string($callback[0]) && (str_starts_with($callback[0], 'tl_') || str_contains($callback[0], 'Calendar'))) {
                $label = $this->executeCallback($callback, [$row, '']);
            } else {
                $label = $this->executeCallback($callback, [$row, $dc]);
            }
        } elseif (isset($GLOBALS['TL_DCA'][$table]['list']['label']['label_callback']) && $table !== 'tl_article') {
            $callback = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'];

            // Special handling for callbacks that expect standard label_callback arguments:
            // ($row, $label, DataContainer $dc, $args)
            if ($table === 'tl_calendar_events') {
                $label = $this->executeCallback($callback, [$row, '', $dc, []]);
            } else {
                $label = $this->executeCallback($callback, [$row, '', $dc, null]);
            }
        }

        // Fallback wenn kein Callback da ist
        if (empty($label)) {
            if ($table === 'tl_news') {
                $date = Date::parse(Config::get('datimFormat'), $row['date']);
                $time = Date::parse(Config::get('timeFormat'), $row['time']);
                $label = sprintf('%s <span class="label-info">[%s %s]</span>', $row['headline'], $date, $time);
            } elseif ($table === 'tl_faq') {
                $label = $row['question'];
            } elseif ($table === 'tl_calendar_events') {
                if (class_exists('tl_calendar_events')) {
                    $events = new \tl_calendar_events();
                    if (method_exists($events, 'listEvents')) {
                        $label = $events->listEvents($row, $label);
                    }
                }
                if (empty($label) || $label === $row['title']) {
                    $date = Date::parse(Config::get('dateFormat'), $row['startTime']);
                    $label = sprintf('%s <span class="label-info">[%s]</span>', $row['title'], $date);
                }
            } else {
                $label = $row['title'] ?? $row['headline'] ?? ($row['text'] ? substr(strip_tags($row['text']), 0, 50) : 'ID ' . $row['id']);
            }
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
        $statusHtml = sprintf(' <span class="tl_gray">[%s]</span>', $statusLabel);

        if (is_array($label)) {
            $label[0] .= $statusHtml;
        } else {
            $label .= $statusHtml;
        }

        return $label;
    }

    private function executeCallback($callback, array $args): array|string
    {
        if (is_array($callback)) {
            if (is_string($callback[0])) {
                $instance = $this->resolveCallbackInstance($callback[0]);

                if ($instance === null) {
                    return '';
                }

                return call_user_func_array([$instance, $callback[1]], $args);
            }
            return call_user_func_array([$callback[0], $callback[1]], $args);
        }

        if (is_string($callback)) {
            // Invokable service callback (e.g. a class registered via #[AsCallback])
            $instance = $this->resolveCallbackInstance($callback);

            if ($instance === null) {
                return '';
            }

            return call_user_func_array($instance, $args);
        }

        if (is_callable($callback)) {
            return call_user_func_array($callback, $args);
        }

        return '';
    }

    /**
     * Resolve a callback class either from the Contao service container (for
     * services registered via #[AsCallback] that have constructor dependencies)
     * or by instantiating it directly as a fallback.
     */
    private function resolveCallbackInstance(string $class): ?object
    {
        // Modern Contao callbacks are services and often have constructor
        // dependencies. Always let Symfony create them when they are available.
        if ($this->container->has($class)) {
            return $this->container->get($class);
        }

        // Keep supporting legacy callback classes without dependencies. If an
        // optional extension is absent (or does not expose its callback as a
        // service), the caller will use the regular label fallback instead.
        if (!class_exists($class)) {
            return null;
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (!$reflection->isInstantiable() || ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0)) {
            return null;
        }

        return $reflection->newInstance();
    }
}
