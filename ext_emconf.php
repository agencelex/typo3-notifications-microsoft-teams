<?php

/**
 * The ext_emconf.php is used in legacy installations not based on Composer to supply information about an extension in
 * the Admin Tools > Extensions module. In these installations the ordering of installed extensions and their dependencies
 * are loaded from this file as well.
 */

$EM_CONF[$_EXTKEY] = [
    'title'                 => 'Laravel-style Notification System - Microsoft Teams Channel',
    'description'           => 'Microsoft Teams channel provider for the Laravel-style Notification System (lex_notifications). Allows sending real-time messages and rich adaptive cards directly to Microsoft Teams channels from any PHP code via the Notifiable trait.',
    'category'              => 'services',
    'version'               => '1.0.0',
    'state'                 => 'stable',
    'author'                => 'Agence Lex',
    'author_email'          => 'contact@agencelex.com',
    'author_company'        => 'Agence Lex',

    'autoload' => [
        'psr-4' => [
            'Lex\\Notifications\\MicrosoftTeams\\' => 'Classes'
        ]
    ],

    'constraints' => [
        'depends' => [
            'lex_notifications' => '1.3.0-1.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];