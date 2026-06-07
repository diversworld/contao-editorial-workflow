<?php

namespace Diversworld\ContaoEditorialWorkflow\Backend;

use Contao\BackendModule;
use Contao\System;
use Diversworld\ContaoEditorialWorkflow\Dashboard\ApprovalDashboard;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowManager;

class WorkflowApprovalsModule extends BackendModule
{
    public function generate()
    {
        $dashboard = System::getContainer()->get(ApprovalDashboard::class);
        $dashboard->handleApprovalRequest();

        return $dashboard->render();
    }

    protected function compile(): void
    {
    }

    public function checkSendPermission(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        $workflowManager = System::getContainer()->get(WorkflowManager::class);

        if ($workflowManager->canPublish()) {
            return sprintf('<a href="%s" title="%s" %s>%s</a>', \Contao\Backend::addToUrl($href . '&amp;id=' . $row['id']), $title, $attributes, \Contao\Image::getHtml($icon, $label));
        }

        return '';
    }
}
