<?php

namespace Diversworld\ContaoEditorialWorkflow\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Contao\System;
use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowStatus;

class WorkflowLogListener
{
    #[AsCallback(table: 'tl_editorial_workflow_log', target: 'list.label.label')]
    public function onLabel($row, $label, DataContainer $dc, $args): array
    {
        $statusRef = $GLOBALS['TL_LANG']['MSC']['workflow_status_ref'] ?? [];

        $fromStatus = $row['from_status'] ?: 'draft';
        $toStatus = $row['to_status'];

        $fromLabel = $statusRef[$fromStatus] ?? $fromStatus;
        $toLabel = $statusRef[$toStatus] ?? $toStatus;

        $args[0] = Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $row['tstamp']);
        $args[2] = sprintf(
            '<span style="color:#999">[%s]</span> %s &rarr; %s',
            $row['ptable'],
            $fromLabel,
            $toLabel
        );

        return $args;
    }
}
