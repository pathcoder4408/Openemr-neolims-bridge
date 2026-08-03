#!/usr/bin/env php
<?php

$openemrRoot = getenv('OPENEMR_ROOT') ?: '/var/www/openemr';
$_GET['site'] = getenv('OPENEMR_SITE') ?: 'default';
require_once $openemrRoot . '/interface/globals.php';

use OpenEMR\Modules\NeoLimsBridge\Operations\DeadLetterService;
use OpenEMR\Modules\NeoLimsBridge\Operations\ReconciliationService;
use OpenEMR\Modules\NeoLimsBridge\Repository\OperationsRepository;

$command = $argv[1] ?? 'metrics';

switch ($command) {
    case 'metrics':
        $result = (new OperationsRepository())->metrics();
        break;
    case 'reconcile':
        $result = (new ReconciliationService())->run($argv[2] ?? null);
        break;
    case 'sweep':
        $result = ['moved_to_dead_letter' => (new DeadLetterService())->sweep()];
        break;
    case 'replay':
        if (empty($argv[2])) throw new RuntimeException('Workflow UUID required.');
        $result = (new DeadLetterService())->replay($argv[2]);
        break;
    default:
        throw new RuntimeException('Unknown command: ' . $command);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
