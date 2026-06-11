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
    public function onLabel($row, $label, DataContainer $dc, $args): string
    {
        $statusRef = $GLOBALS['TL_LANG']['MSC']['workflow_status_ref'] ?? [];

        $fromStatus = $row['from_status'] ?: 'draft';
        $toStatus = $row['to_status'];

        $fromLabel = $statusRef[$fromStatus] ?? $fromStatus;
        $toLabel = $statusRef[$toStatus] ?? $toStatus;

        $date = Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $row['tstamp']);
        $user = '';

        if ($row['user_id']) {
            $userObj = System::importStatic('Contao\UserModel')->findByPk($row['user_id']);
            if ($userObj) {
                $user = $userObj->username;
            }
        }

        return sprintf(
            '<div class="tl_content_left"><span style="color:#999">[%s]</span> %s <span style="color:#999;padding-left:10px">[%s]</span> <span style="color:#999;padding-left:10px">%s</span><br>%s &rarr; %s</div>',
            $row['id'],
            $date,
            $row['ptable'],
            $user,
            $fromLabel,
            $toLabel
        );
    }
}
