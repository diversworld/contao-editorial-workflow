<?php

use Composer\InstalledVersions;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Diversworld\ContaoEditorialWorkflow\EventListener\DataContainer\WorkflowFieldsListener;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowStatus;

$GLOBALS['TL_DCA']['tl_article']['fields']['workflow_status'] = [
    'label'     => &$GLOBALS['TL_LANG']['MSC']['workflow_status'],
    'exclude'   => true,
    'inputType' => 'select',
    'reference' => &$GLOBALS['TL_LANG']['MSC']['workflow_status_ref'],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => true, 'chosen' => true],
    'sql'       => "varchar(32) NOT NULL default 'draft'",
];

$GLOBALS['TL_DCA']['tl_article']['fields']['workflow_comment'] = [
    'label'     => &$GLOBALS['TL_LANG']['MSC']['workflow_comment'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => ['tl_class' => 'clr'],
    'sql'       => "text NULL",
];

$contaoVersion = InstalledVersions::getVersion('contao/core-bundle') ?? '0';
$contaoMajorVersion = (int) strtok($contaoVersion, '.');
$isContao6 = $contaoMajorVersion >= 6;

if ($isContao6) {
    if (isset($GLOBALS['TL_DCA']['tl_article']['list']['label']['label_callback'])) {
        $GLOBALS['TL_DCA']['tl_article']['list']['label']['label_callback_orig'] = $GLOBALS['TL_DCA']['tl_article']['list']['label']['label_callback'];
    }

    $GLOBALS['TL_DCA']['tl_article']['list']['label']['label_callback'] = [WorkflowFieldsListener::class, 'onLabel'];
} else {
    if (isset($GLOBALS['TL_DCA']['tl_article']['list']['sorting']['child_record_callback'])) {
        $GLOBALS['TL_DCA']['tl_article']['list']['sorting']['child_record_callback_orig'] = $GLOBALS['TL_DCA']['tl_article']['list']['sorting']['child_record_callback'];
    }

    $GLOBALS['TL_DCA']['tl_article']['list']['sorting']['child_record_callback'] = [WorkflowFieldsListener::class, 'onChildRecord'];
}

$palettes = $GLOBALS['TL_DCA']['tl_article']['palettes'] ?? null;

if (is_array($palettes)) {
    $manipulator = PaletteManipulator::create()
        ->addLegend('workflow_legend', 'publish_legend', PaletteManipulator::POSITION_BEFORE)
        ->addField(['workflow_status', 'workflow_comment'], 'workflow_legend', PaletteManipulator::POSITION_APPEND);

    foreach ($palettes as $name => $palette) {
        if ($name === '__selector__') {
            continue;
        }
        $manipulator->applyToPalette($name, 'tl_article');
    }
}
