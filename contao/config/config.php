<?php

/*
 * This file is part of Editorial Workflow.
 *
 * (c) Eckhard Becker <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-editorial-workflow
 */

use Diversworld\ContaoEditorialWorkflow\Backend\WorkflowApprovalsModule;
use Diversworld\ContaoEditorialWorkflow\EventListener\DashboardListener;

$GLOBALS['BE_MOD']['system']['editorial_workflow_approvals'] = [
    'callback' => WorkflowApprovalsModule::class,
    'disablePermissionChecks' => true,
];

$GLOBALS['BE_MOD']['system']['editorial_workflow_log'] = [
    'tables' => ['tl_editorial_workflow_log'],
];

$GLOBALS['TL_HOOKS']['parseBackendTemplate'][] = [DashboardListener::class, 'onParseBackendTemplate'];
