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

$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions'] = ['Workflow-Berechtigungen', 'Hier können Sie festlegen, welche Workflow-Rollen der Benutzer oder die Gruppe besitzt.'];
$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions_ref'] = [
    'reviewer' => 'Prüfer (darf freigeben/ablehnen)',
    'publisher' => 'Publisher (darf veröffentlichen)',
];
