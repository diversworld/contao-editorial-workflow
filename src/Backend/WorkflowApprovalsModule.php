<?php

namespace Diversworld\ContaoEditorialWorkflow\Backend;

use Contao\BackendModule;
use Diversworld\ContaoEditorialWorkflow\Dashboard\ApprovalDashboard;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowManager;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('contao.backend_module', ['module' => 'workflow_approvals'])]
class WorkflowApprovalsModule extends BackendModule
{
    private ApprovalDashboard $dashboard;
    private WorkflowManager $workflowManager;

    public function __construct(ApprovalDashboard $dashboard, WorkflowManager $workflowManager)
    {
        $this->dashboard = $dashboard;
        $this->workflowManager = $workflowManager;
    }

    /**
     * @return string
     */
    public function generate()
    {
        $this->dashboard->handleApprovalRequest();

        return $this->dashboard->render();
    }

    protected function compile(): void
    {
    }

    public function checkSendPermission(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        if ($this->workflowManager->canPublish()) {
            return sprintf('<a href="%s" title="%s" %s>%s</a>', \Contao\Backend::addToUrl($href . '&amp;id=' . $row['id']), $title, $attributes, \Contao\Image::getHtml($icon, $label));
        }

        return '';
    }
}
