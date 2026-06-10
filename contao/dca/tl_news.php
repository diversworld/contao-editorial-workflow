<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Diversworld\ContaoEditorialWorkflow\EventListener\DataContainer\WorkflowFieldsListener;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowStatus;


$GLOBALS['TL_DCA']['tl_news']['fields']['workflow_status'] = [
    'label'     => &$GLOBALS['TL_LANG']['MSC']['workflow_status'],
    'exclude'   => true,
    'inputType' => 'select',
    'reference' => &$GLOBALS['TL_LANG']['MSC']['workflow_status_ref'],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => true, 'chosen' => true],
    'sql'       => "varchar(32) NOT NULL default 'draft'",
];

$GLOBALS['TL_DCA']['tl_news']['fields']['workflow_comment'] = [
    'label'     => &$GLOBALS['TL_LANG']['MSC']['workflow_comment'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => ['tl_class' => 'clr'],
    'sql'       => "text NULL",
];

$GLOBALS['TL_DCA']['tl_news']['list']['label']['label_callback'] = [WorkflowFieldsListener::class, 'onLabel'];

$manipulator = PaletteManipulator::create()
    ->addLegend('workflow_legend', 'publish_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['workflow_status', 'workflow_comment'], 'workflow_legend', PaletteManipulator::POSITION_APPEND);

foreach ($GLOBALS['TL_DCA']['tl_news']['palettes'] as $name => $palette) {
    if ($name === '__selector__') continue;
    $manipulator->applyToPalette($name, 'tl_news');
}
