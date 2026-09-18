<?php

$GLOBALS['TL_LANG']['MSC']['workflow_legend'] = 'Editorial Workflow';
$GLOBALS['TL_LANG']['MSC']['workflow_status'] = ['Workflow Status', 'Select the current status of the content.'];
$GLOBALS['TL_LANG']['MSC']['workflow_comment'] = ['Comment / Review note', 'Enter a comment or reason for the status change.'];

$GLOBALS['TL_LANG']['MSC']['workflow_status_ref'] = [
    'draft'     => 'Draft',
    'review'    => 'In review',
    'approved'  => 'Approved',
    'rejected'  => 'Rejected',
    'published' => 'Published',
    'archived'  => 'Archived',
];

$GLOBALS['TL_LANG']['MSC']['editorial_workflow_dashboard'] = [
    'headline' => 'Posts pending approval',
    'table' => 'Section',
    'record' => 'Record',
    'date' => 'Changed',
    'edit' => 'Edit',
    'approve' => 'Approve',
    'approved' => 'The record has been approved.',
    'approvalDenied' => 'The record could not be approved.',
    'approvedComment' => 'Approved via the workflow dashboard',
    'empty' => 'There are no records pending approval.',
    'invalidToken' => 'The request token could not be validated.',
];

$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions'] = ['Workflow Permissions', 'Define which workflow roles the user or group possesses.'];
$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions_ref'] = [
    'reviewer' => 'Reviewer (may approve/reject)',
    'publisher' => 'Publisher (may publish)',
];

$GLOBALS['TL_LANG']['notification_center']['token']['table'] = 'Table';
$GLOBALS['TL_LANG']['notification_center']['token']['record_id'] = 'Record ID';
$GLOBALS['TL_LANG']['notification_center']['token']['from_status'] = 'Old status';
$GLOBALS['TL_LANG']['notification_center']['token']['to_status'] = 'New status';
$GLOBALS['TL_LANG']['notification_center']['token']['comment'] = 'Comment';
$GLOBALS['TL_LANG']['notification_center']['token']['user_id'] = 'User ID';
$GLOBALS['TL_LANG']['notification_center']['token']['user_name'] = 'User name';
$GLOBALS['TL_LANG']['notification_center']['token']['author_name'] = 'Author name (creator)';
$GLOBALS['TL_LANG']['notification_center']['token']['record_label'] = 'Record title';
$GLOBALS['TL_LANG']['notification_center']['token']['admin_email'] = 'Administrator email';
$GLOBALS['TL_LANG']['notification_center']['token']['record_details'] = 'Record details (record_*)';
