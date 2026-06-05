<?php

namespace Diversworld\ContaoEditorialWorkflow\Backend;

use Contao\BackendModule;
use Contao\System;
use Diversworld\ContaoEditorialWorkflow\Dashboard\ApprovalDashboard;

class WorkflowApprovalsModule extends BackendModule
{
    protected $strTemplate = 'be_editorial_workflow_approvals';

    protected function compile(): void
    {
        $dashboard = System::getContainer()->get(ApprovalDashboard::class);
        $dashboard->handleApprovalRequest();

        $this->Template->content = $dashboard->render();
    }
}
