<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowStatus;

$GLOBALS['TL_DCA']['tl_content']['fields']['workflow_status'] = [
    'label'     => &$GLOBALS['TL_LANG']['MSC']['workflow_status'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => WorkflowStatus::getStatuses(),
    'reference' => &$GLOBALS['TL_LANG']['MSC']['workflow_status_ref'],
    'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true, 'chosen' => true, 'submitOnChange' => true],
    'sql'       => "varchar(32) NOT NULL default 'draft'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['workflow_comment'] = [
    'label'     => &$GLOBALS['TL_LANG']['MSC']['workflow_comment'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval' => ['tl_class' => 'clr', 'rte' => 'tinyMCE'],
    'sql'       => "text NULL",
];

if (isset($GLOBALS['TL_DCA']['tl_content']['palettes']) && is_array($GLOBALS['TL_DCA']['tl_content']['palettes'])) {
    foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $name => $palette) {
        if ($name === '__selector__' || !is_string($palette)) {
            continue;
        }

        $pm = PaletteManipulator::create()
            ->addField(['workflow_status', 'workflow_comment'], 'workflow_legend', PaletteManipulator::POSITION_APPEND);

        if (str_contains($palette, 'publish_legend')) {
            $pm->addLegend('workflow_legend', 'publish_legend', PaletteManipulator::POSITION_BEFORE);
        } elseif (str_contains($palette, 'invisible_legend')) {
            $pm->addLegend('workflow_legend', 'invisible_legend', PaletteManipulator::POSITION_BEFORE);
        } else {
            $GLOBALS['TL_DCA']['tl_content']['palettes'][$name] .= ';{workflow_legend},workflow_status,workflow_comment';
            continue;
        }

        $pm->applyToPalette($name, 'tl_content');
    }
}
