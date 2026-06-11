<?php

namespace Diversworld\ContaoEditorialWorkflow\Dashboard;

use Contao\StringUtil;
use Contao\Message;
use Contao\CoreBundle\Exception\ResponseException;
use Doctrine\DBAL\Connection;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowManager;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowStatus;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;

class ApprovalDashboard
{
    public function __construct(
        private readonly WorkflowManager $workflowManager,
        private readonly RequestStack    $requestStack,
        private readonly RouterInterface $router,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly Connection $db,
        private readonly array $enabledTables,
        private readonly string                 $csrfTokenName,
    )
    {
    }

    public function handleApprovalRequest(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null || !$request->isMethod('POST') || !in_array($request->request->get('FORM_SUBMIT'), ['tl_editorial_workflow_approval', 'tl_editorial_workflow_publish'], true)) {
            return;
        }

        $table = (string)$request->request->get('workflow_table');
        $id = (int)$request->request->get('workflow_id');
        $token = (string)($request->request->get('REQUEST_TOKEN') ?: $request->request->get($this->csrfTokenName));

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenName, $token))) {
            Message::addError($this->getLabel('invalidToken') ?: 'Invalid CSRF token.');
            return;
        }

        $isPublish = $request->request->get('FORM_SUBMIT') === 'tl_editorial_workflow_publish';
        $newStatus = $isPublish ? WorkflowStatus::STATUS_PUBLISHED : WorkflowStatus::STATUS_APPROVED;
        $commentLabel = $isPublish ? 'publishedComment' : 'approvedComment';
        $successLabel = $isPublish ? 'published' : 'approved';

        if ($this->workflowManager->changeStatus($table, $id, $newStatus, $this->getLabel($commentLabel))) {
            Message::addConfirmation($this->getLabel($successLabel));
        } else {
            Message::addError($this->getLabel('approvalDenied'));
        }

        throw new ResponseException(new RedirectResponse($request->getUri()));
    }

    public function render(bool $compact = false): string
    {
        if (!$this->workflowManager->canReview() && !$this->workflowManager->canPublish()) {
            return '';
        }

        $records = [];

        if ($this->workflowManager->canReview()) {
            $records = $this->workflowManager->getPendingReviewRecords($compact ? 10 : 100);
        }

        if ($this->workflowManager->canPublish()) {
            $approvedRecords = $this->getApprovedRecords($compact ? 10 : 100);
            $records = array_merge($records, $approvedRecords);

            // Re-sort by tstamp
            usort($records, static fn(array $a, array $b): int => [$b['tstamp'], $b['id']] <=> [$a['tstamp'], $a['id']]);

            if ($compact && count($records) > 10) {
                $records = array_slice($records, 0, 10);
            }
        }

        if ($records === []) {
            return sprintf(
                '<div id="tl_editorial_workflow_approvals" class="tl_listing_container"><h2>%s</h2><p>%s</p></div>',
                $this->escape($this->getLabel('headline')),
                $this->escape($this->getLabel('empty'))
            );
        }

        $rows = '';

        foreach ($records as $record) {
            $rows .= sprintf(
                '<tr><td>%s</td><td>%d</td><td>%s</td><td>%s</td><td class="tl_right_nowrap">%s%s</td></tr>',
                $this->escape($this->getTableLabel($record['table'])),
                $record['id'],
                $this->escape($record['title']),
                $record['tstamp'] > 0 ? $this->escape(date('d.m.Y H:i', $record['tstamp'])) : '-',
                $this->renderEditLink($record['table'], $record['id']),
                $this->renderApprovalForm($record['table'], $record['id'], $record['status'])
            );
        }

        return sprintf(
            '<div id="tl_editorial_workflow_approvals" class="tl_listing_container"><h2>%s</h2><table class="tl_listing with-border with-padding"><thead><tr><th>%s</th><th>ID</th><th>%s</th><th>%s</th><th></th></tr></thead><tbody>%s</tbody></table></div>',
            $this->escape($this->getLabel('headline')),
            $this->escape($this->getLabel('table')),
            $this->escape($this->getLabel('record')),
            $this->escape($this->getLabel('date')),
            $rows
        );
    }

    private function renderApprovalForm(string $table, int $id, string $status): string
    {
        $canReview = $this->workflowManager->canReview();
        $canPublish = $this->workflowManager->canPublish();

        if ($status === WorkflowStatus::STATUS_REVIEW && $canReview) {
            $token = $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue();

            return sprintf(
                '<form method="post" style="display:inline" data-turbo="false"><input type="hidden" name="FORM_SUBMIT" value="tl_editorial_workflow_approval"><input type="hidden" name="REQUEST_TOKEN" value="%s"><input type="hidden" name="workflow_table" value="%s"><input type="hidden" name="workflow_id" value="%d"><button type="submit" class="tl_submit" title="%s">%s</button></form>',
                $this->escape($token),
                $this->escape($table),
                $id,
                $this->escape($this->getLabel('approve')),
                $this->escape($this->getLabel('approve'))
            );
        }

        if ($status === WorkflowStatus::STATUS_APPROVED && $canPublish) {
            $token = $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue();

            return sprintf(
                '<form method="post" style="display:inline" data-turbo="false"><input type="hidden" name="FORM_SUBMIT" value="tl_editorial_workflow_publish"><input type="hidden" name="REQUEST_TOKEN" value="%s"><input type="hidden" name="workflow_table" value="%s"><input type="hidden" name="workflow_id" value="%d"><button type="submit" class="tl_submit" title="%s">%s</button></form>',
                $this->escape($token),
                $this->escape($table),
                $id,
                $this->escape($this->getLabel('publish')),
                $this->escape($this->getLabel('publish'))
            );
        }

        return '';
    }

    private function renderEditLink(string $table, int $id): string
    {
        $module = $this->getBackendModuleForTable($table);

        if ($module === null) {
            return '';
        }

        $parameters = [
            'do' => $module,
            'act' => 'edit',
            'id' => $id,
        ];

        if (($GLOBALS['BE_MOD'] ?? []) !== [] && $this->moduleUsesMultipleTables($module)) {
            $parameters['table'] = $table;
        }

        $url = $this->router->generate('contao_backend', $parameters);

        return sprintf(
            '<a href="%s" class="tl_submit" style="margin-right:6px">%s</a>',
            $this->escape($url),
            $this->escape($this->getLabel('edit'))
        );
    }

    private function getBackendModuleForTable(string $table): ?string
    {
        foreach (($GLOBALS['BE_MOD'] ?? []) as $modules) {
            foreach ($modules as $module => $config) {
                if (in_array($table, $config['tables'] ?? [], true)) {
                    return $module;
                }
            }
        }

        return null;
    }

    private function moduleUsesMultipleTables(string $module): bool
    {
        foreach (($GLOBALS['BE_MOD'] ?? []) as $modules) {
            if (isset($modules[$module])) {
                return count($modules[$module]['tables'] ?? []) > 1;
            }
        }

        return false;
    }

    private function getTableLabel(string $table): string
    {
        foreach (($GLOBALS['BE_MOD'] ?? []) as $modules) {
            foreach ($modules as $module => $config) {
                if (in_array($table, $config['tables'] ?? [], true)) {
                    return $GLOBALS['TL_LANG']['MOD'][$module][0] ?? $module;
                }
            }
        }

        return $table;
    }

    private function getApprovedRecords(int $limit): array
    {
        $records = [];

        foreach ($this->enabledTables as $table) {
            if (!$this->workflowManager->isEnabledWorkflowTable($table)) {
                continue;
            }

            $titleColumn = $this->workflowManager->getTitleColumn($table);
            $selectTitle = $titleColumn ? sprintf('%s AS record_title', $titleColumn) : "'' AS record_title";

            $rows = $this->db->fetchAllAssociative(
                sprintf(
                    'SELECT id, workflow_status, %s, tstamp AS record_tstamp FROM %s WHERE workflow_status = ? ORDER BY tstamp DESC, id DESC',
                    $selectTitle,
                    $table
                ),
                [WorkflowStatus::STATUS_APPROVED]
            );

            foreach ($rows as $row) {
                if (!$this->workflowManager->canChangeStatus($table, (int)$row['id'], WorkflowStatus::STATUS_PUBLISHED)) {
                    continue;
                }

                $records[] = [
                    'table' => $table,
                    'id' => (int)$row['id'],
                    'status' => (string)$row['workflow_status'],
                    'title' => trim((string)($row['record_title'] ?? '')) ?: sprintf('%s #%d', $table, $row['id']),
                    'tstamp' => (int)($row['record_tstamp'] ?? 0),
                ];
            }
        }

        return $records;
    }

    private function getLabel(string $key): string
    {
        return $GLOBALS['TL_LANG']['MSC']['editorial_workflow_dashboard'][$key] ?? $key;
    }

    private function escape(string $value): string
    {
        return StringUtil::specialchars($value);
    }
}
