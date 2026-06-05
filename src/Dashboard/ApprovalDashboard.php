<?php

namespace Diversworld\ContaoEditorialWorkflow\Dashboard;

use Contao\StringUtil;
use Contao\Message;
use Contao\CoreBundle\Exception\ResponseException;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowManager;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowStatus;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class ApprovalDashboard
{
    public function __construct(
        private readonly WorkflowManager $workflowManager,
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $router,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function handleApprovalRequest(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null || !$request->isMethod('POST') || $request->request->get('FORM_SUBMIT') !== 'tl_editorial_workflow_approval') {
            return;
        }

        $table = (string) $request->request->get('workflow_table');
        $id = (int) $request->request->get('workflow_id');
        $token = (string) $request->request->get('REQUEST_TOKEN');

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('contao_csrf_token', $token))) {
            Message::addError($this->getLabel('invalidToken') ?: 'Invalid CSRF token.');
            return;
        }

        if ($this->workflowManager->changeStatus($table, $id, WorkflowStatus::STATUS_APPROVED, $this->getLabel('approvedComment'))) {
            Message::addConfirmation($this->getLabel('approved'));
        } else {
            Message::addError($this->getLabel('approvalDenied'));
        }

        throw new ResponseException(new RedirectResponse($request->getUri()));
    }

    public function render(bool $compact = false): string
    {
        if (!$this->workflowManager->canReview()) {
            return '';
        }

        $records = $this->workflowManager->getPendingReviewRecords($compact ? 10 : 100);

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
                $this->renderApprovalForm($record['table'], $record['id'])
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

    private function renderApprovalForm(string $table, int $id): string
    {
        $token = $this->csrfTokenManager->getToken('contao_csrf_token')->getValue();

        return sprintf(
            '<form method="post" style="display:inline" data-turbo="false"><input type="hidden" name="FORM_SUBMIT" value="tl_editorial_workflow_approval"><input type="hidden" name="REQUEST_TOKEN" value="%s"><input type="hidden" name="workflow_table" value="%s"><input type="hidden" name="workflow_id" value="%d"><button type="submit" class="tl_submit" title="%s">%s</button></form>',
            $this->escape($token),
            $this->escape($table),
            $id,
            $this->escape($this->getLabel('approve')),
            $this->escape($this->getLabel('approve'))
        );
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

    private function getLabel(string $key): string
    {
        return $GLOBALS['TL_LANG']['MSC']['editorial_workflow_dashboard'][$key] ?? $key;
    }

    private function escape(string $value): string
    {
        return StringUtil::specialchars($value);
    }
}
