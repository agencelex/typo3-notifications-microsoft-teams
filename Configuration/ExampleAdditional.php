<?php declare(strict_types=1);

use Lex\Notifications\MicrosoftTeams\Extension;

// EXAMPLE ONLY; copy the relevant array into config/system/additional.php
// (this file is NOT loaded automatically by the extension).
// Use secret injection (environment variables, protected config or Key Vault).

$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Extension::KEY] = [
    'webhooks' => [
        'monitoring' => getenv('TEAMS_WEBHOOK_MONITORING') ?: '',
        'fulfilment' => getenv('TEAMS_WEBHOOK_FULFILMENT') ?: '',
    ],
];
