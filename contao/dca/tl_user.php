<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\System;

$GLOBALS['TL_DCA']['tl_user']['fields']['editorial_workflow_permissions'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'options' => ['reviewer', 'publisher'],
    'reference' => &$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions_ref'],
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => "blob NULL",
];

$GLOBALS['TL_DCA']['tl_user']['fields']['editorial_workflow_notifications'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_notifications'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static function () {
        $connection = System::getContainer()->get('database_connection');

        try {
            $notifications = $connection->fetchAllAssociative('SELECT id, title FROM tl_nc_notification ORDER BY title');
        } catch (\Exception $e) {
            return [];
        }

        $options = [];

        foreach ($notifications as $notification) {
            $options[(int)$notification['id']] = $notification['title'];
        }

        return $options;
    },
    'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "blob NULL",
];

$fields = ['editorial_workflow_permissions'];
if (class_exists('Terminal42\NotificationCenterBundle\Terminal42NotificationCenterBundle')) {
    $fields[] = 'editorial_workflow_notifications';
}

$palettes = $GLOBALS['TL_DCA']['tl_user']['palettes'] ?? null;

if (is_array($palettes)) {
    $manipulator = PaletteManipulator::create()
        ->addLegend('editorial_workflow_legend', 'password_legend', PaletteManipulator::POSITION_AFTER)
        ->addField($fields, 'editorial_workflow_legend', PaletteManipulator::POSITION_APPEND);

    foreach (['default', 'admin', 'group', 'extend', 'custom'] as $palette) {
        if (isset($palettes[$palette])) {
            $manipulator->applyToPalette($palette, 'tl_user');
        }
    }
}
