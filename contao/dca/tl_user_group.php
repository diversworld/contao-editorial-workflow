<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_user_group']['fields']['editorial_workflow_permissions'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'options' => ['reviewer', 'publisher'],
    'reference' => &$GLOBALS['TL_LANG']['tl_user']['editorial_workflow_permissions_ref'],
    'eval' => ['multiple' => true],
    'sql' => "blob NULL",
];

PaletteManipulator::create()
    ->addLegend('editorial_workflow_legend', 'pagemounts_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('editorial_workflow_permissions', 'editorial_workflow_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group');
