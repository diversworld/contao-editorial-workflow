<?php

use Contao\System;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_user_group']['fields']['editorial_workflow_permissions'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'options' => ['reviewer', 'publisher'],
    'reference' => &$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions_ref'],
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => "blob NULL",
];

$GLOBALS['TL_DCA']['tl_user_group']['fields']['editorial_workflow_notifications'] = [
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

PaletteManipulator::create()
    ->addLegend('editorial_workflow_legend', 'pagemounts_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(['editorial_workflow_permissions', 'editorial_workflow_notifications'], 'editorial_workflow_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group');
