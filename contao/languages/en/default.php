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

$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions'] = ['Workflow Permissions', 'Define which workflow roles the user or group possesses.'];
$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions_ref'] = [
    'reviewer' => 'Reviewer (may approve/reject)',
    'publisher' => 'Publisher (may publish)',
];

$GLOBALS['TL_LANG']['tl_nc_notification']['type']['editorial_workflow'] = 'Editorial Workflow';
$GLOBALS['TL_LANG']['tl_nc_notification']['type']['editorial_workflow_review'] = ['In review', 'Sent when content is submitted for review.'];
$GLOBALS['TL_LANG']['tl_nc_notification']['type']['editorial_workflow_approved'] = ['Approval granted', 'Sent when content is approved.'];
$GLOBALS['TL_LANG']['tl_nc_notification']['type']['editorial_workflow_rejected'] = ['Approval rejected', 'Sent when content is rejected.'];
$GLOBALS['TL_LANG']['tl_nc_notification']['type']['editorial_workflow_published'] = ['Publication successful', 'Sent when content is published.'];
