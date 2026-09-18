<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Diversworld\ContaoEditorialWorkflow\Backend\WorkflowApprovalsModule;
use Diversworld\ContaoEditorialWorkflow\EventListener\DataContainer\WorkflowFieldsListener;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowStatus;


$GLOBALS['TL_DCA']['tl_newsletter']['fields']['workflow_status'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['workflow_status'],
    'exclude' => true,
    'inputType' => 'select',
    'reference' => &$GLOBALS['TL_LANG']['MSC']['workflow_status_ref'],
    'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true, 'chosen' => true],
    'sql' => "varchar(32) NOT NULL default 'draft'",
];

$GLOBALS['TL_DCA']['tl_newsletter']['fields']['workflow_comment'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['workflow_comment'],
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => ['tl_class' => 'clr'],
    'sql' => "text NULL",
];

if (isset($GLOBALS['TL_DCA']['tl_newsletter']['list']['label']['label_callback'])) {
    $GLOBALS['TL_DCA']['tl_newsletter']['list']['label']['label_callback_orig'] = $GLOBALS['TL_DCA']['tl_newsletter']['list']['label']['label_callback'];
}
$GLOBALS['TL_DCA']['tl_newsletter']['list']['label']['label_callback'] = [WorkflowFieldsListener::class, 'onLabel'];
$GLOBALS['TL_DCA']['tl_newsletter']['list']['operations']['send']['button_callback'] = [WorkflowApprovalsModule::class, 'checkSendPermission'];

$palettes = $GLOBALS['TL_DCA']['tl_newsletter']['palettes'] ?? null;

if (is_array($palettes)) {
    $manipulator = PaletteManipulator::create()
        ->addLegend('workflow_legend', 'title_legend', PaletteManipulator::POSITION_AFTER)
        ->addField(['workflow_status', 'workflow_comment'], 'workflow_legend', PaletteManipulator::POSITION_APPEND);

    foreach ($palettes as $name => $palette) {
        if ($name === '__selector__') {
            continue;
        }
        $manipulator->applyToPalette($name, 'tl_newsletter');
    }
}

