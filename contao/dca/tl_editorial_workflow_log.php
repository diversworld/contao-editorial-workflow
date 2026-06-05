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
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'pid' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'ptable' => [
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'user_id' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'from_status' => [
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'to_status' => [
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'comment' => [
            'sql' => "text NULL",
        ],
        'version' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'ip' => [
            'sql' => "varchar(45) NOT NULL default ''",
        ],
    ],
];
