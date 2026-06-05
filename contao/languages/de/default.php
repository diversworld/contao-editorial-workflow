<?php

$GLOBALS['TL_LANG']['MSC']['workflow_legend'] = 'Editorial Workflow';
$GLOBALS['TL_LANG']['MSC']['workflow_status'] = ['Workflow-Status', 'Wählen Sie den aktuellen Status des Inhalts aus.'];
$GLOBALS['TL_LANG']['MSC']['workflow_comment'] = ['Kommentar / Review-Hinweis', 'Geben Sie einen Kommentar oder Grund für die Statusänderung an.'];

$GLOBALS['TL_LANG']['MSC']['workflow_status_ref'] = [
    'draft'     => 'Entwurf',
    'review'    => 'In Prüfung',
    'approved'  => 'Freigegeben',
    'rejected'  => 'Abgelehnt',
    'published' => 'Veröffentlicht',
    'archived'  => 'Archiviert',
];

$GLOBALS['TL_LANG']['MSC']['editorial_workflow_dashboard'] = [
    'headline' => 'Freizugebende Beiträge',
    'table' => 'Bereich',
    'record' => 'Beitrag',
    'date' => 'Geändert',
    'edit' => 'Bearbeiten',
    'approve' => 'Freigeben',
    'approved' => 'Der Beitrag wurde freigegeben.',
    'approvalDenied' => 'Der Beitrag konnte nicht freigegeben werden.',
    'approvedComment' => 'Freigabe über das Workflow-Dashboard',
    'empty' => 'Es liegen keine Beiträge zur Freigabe vor.',
    'invalidToken' => 'Das Request-Token konnte nicht validiert werden.',
];

$GLOBALS['TL_LANG']['notification_center']['token']['table'] = 'Tabelle';
$GLOBALS['TL_LANG']['notification_center']['token']['record_id'] = 'Datensatz-ID';
$GLOBALS['TL_LANG']['notification_center']['token']['from_status'] = 'Alter Status';
$GLOBALS['TL_LANG']['notification_center']['token']['to_status'] = 'Neuer Status';
$GLOBALS['TL_LANG']['notification_center']['token']['comment'] = 'Kommentar';
$GLOBALS['TL_LANG']['notification_center']['token']['user_id'] = 'Benutzer-ID';
$GLOBALS['TL_LANG']['notification_center']['token']['user_name'] = 'Benutzer-Name';
$GLOBALS['TL_LANG']['notification_center']['token']['author_name'] = 'Autor-Name (Ersteller)';
$GLOBALS['TL_LANG']['notification_center']['token']['record_label'] = 'Datensatz-Titel';
$GLOBALS['TL_LANG']['notification_center']['token']['admin_email'] = 'Administrator E-Mail';
$GLOBALS['TL_LANG']['notification_center']['token']['record_details'] = 'Datensatz-Details (record_*)';

$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions'] = ['Workflow-Berechtigungen', 'Hier können Sie festlegen, welche Workflow-Rollen der Benutzer oder die Gruppe besitzt.'];
$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions_ref'] = [
    'reviewer' => 'Prüfer (darf freigeben/ablehnen)',
    'publisher' => 'Publisher (darf veröffentlichen)',
];
