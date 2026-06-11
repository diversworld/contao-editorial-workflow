<?php

use Contao\DC_Table;

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
    'list' => [
        'sorting' => [
            'mode' => 2,
            'fields' => ['tstamp DESC'],
            'flag' => 6,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['id'],
            'showColumns' => false,
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'show' /*=> [
                'label' => &$GLOBALS['TL_LANG']['tl_editorial_workflow_log']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],*/
        ],
    ],
    'palettes' => [
        'default' => '{log_legend},tstamp,user_id,ip;{target_legend},ptable,pid,version;{workflow_legend},from_status,to_status,comment',
    ],
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

