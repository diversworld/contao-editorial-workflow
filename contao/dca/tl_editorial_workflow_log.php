<?php

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;

$GLOBALS['TL_DCA']['tl_editorial_workflow_log'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'closed' => true,
        'notEditable' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid,ptable' => 'index',
            ],
        ],
    ],
    // List
    'list' => array
    (
        'sorting' => array
        (
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => array('tstamp', 'id'),
            'panelLayout' => 'search,filter,sort,limit',
            'defaultSearchField' => 'text'
        ),
        'label' => array
        (
            'fields' => array('id'),
            'showColumns' => false,
        ),
        'global_operations' => array
        (
            'all' => array
            (
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations' => [
            'show',
            'delete'
        ],
    ),
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'label' => &$GLOBALS['TL_LANG']['tl_editorial_workflow_log']['tstamp'],
            'eval' => ['rgxp' => 'dateline'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'pid' => [
            'label' => &$GLOBALS['TL_LANG']['tl_editorial_workflow_log']['pid'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'ptable' => [
            'label' => &$GLOBALS['TL_LANG']['tl_editorial_workflow_log']['ptable'],
            'sorting' => true,
            'filter' => true,
            'search' => true,
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'user_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_editorial_workflow_log']['user_id'],
            'sorting' => true,
            'filter' => true,
            'search' => true,
            'foreignKey' => 'tl_user.username',
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'from_status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_editorial_workflow_log']['from_status'],
            'sorting' => true,
            'filter' => true,
            'search' => true,
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'to_status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_editorial_workflow_log']['to_status'],
            'sorting' => true,
            'filter' => true,
            'search' => true,
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'comment' => [
            'label' => &$GLOBALS['TL_LANG']['tl_editorial_workflow_log']['comment'],
            'sorting' => true,
            'filter' => true,
            'search' => true,
            'sql' => "text NULL",
        ],
        'version' => [
            'label' => &$GLOBALS['TL_LANG']['tl_editorial_workflow_log']['version'],
            'sorting' => true,
            'filter' => true,
            'search' => true,
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'ip' => [
            'label' => &$GLOBALS['TL_LANG']['tl_editorial_workflow_log']['ip'],
            'sorting' => true,
            'filter' => true,
            'search' => true,
            'sql' => "varchar(45) NOT NULL default ''",
        ],
    ],
];

class tl_editorial_workflow_log extends Backend
{
    /**
     * Colorize the log entries depending on their category
     *
     * @param array $row
     * @param string $label
     *
     * @return string
     */
    public function colorize($row, $label)
    {
        $class = 'ellipsis';
        dump($row);
        switch ($row['action']) {
            case 'CONFIGURATION':
            case 'REPOSITORY':
                $class .= ' tl_blue';
                break;

            case 'CRON':
                $class .= ' tl_green';
                break;

            case 'ERROR':
                $class .= ' tl_red';
                break;

            default:
                if (isset($GLOBALS['TL_HOOKS']['colorizeLogEntries']) && is_array($GLOBALS['TL_HOOKS']['colorizeLogEntries'])) {
                    foreach ($GLOBALS['TL_HOOKS']['colorizeLogEntries'] as $callback) {
                        $label = System::importStatic($callback[0])->{$callback[1]}($row, $label, $class);
                    }
                }
                break;
        }

        return '<div class="' . $class . '">' . $label . '</div>';
    }
}
