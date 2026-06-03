<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowStatus;

$table = 'tl_faq';

$GLOBALS['TL_DCA'][$table]['fields']['workflow_status'] = [
    'label'     => &$GLOBALS['TL_LANG']['MSC']['workflow_status'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => WorkflowStatus::getStatuses(),
    'reference' => &$GLOBALS['TL_LANG']['MSC']['workflow_status_ref'],
    'eval'      => ['tl_class' => 'w50', 'includeBlankOption' => true, 'chosen' => true],
    'sql'       => "varchar(32) NOT NULL default 'draft'",
];

$GLOBALS['TL_DCA'][$table]['fields']['workflow_comment'] = [
    'label'     => &$GLOBALS['TL_LANG']['MSC']['workflow_comment'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => ['tl_class' => 'clr'],
    'sql'       => "text NULL",
];

if (isset($GLOBALS['TL_DCA'][$table]['palettes']) && is_array($GLOBALS['TL_DCA'][$table]['palettes'])) {
    foreach ($GLOBALS['TL_DCA'][$table]['palettes'] as $name => $palette) {
        if (is_string($palette)) {
            PaletteManipulator::create()
                ->addLegend('workflow_legend', 'publish_legend', PaletteManipulator::POSITION_BEFORE)
                ->addField(['workflow_status', 'workflow_comment'], 'workflow_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalette($name, $table);
        }
    }
}
