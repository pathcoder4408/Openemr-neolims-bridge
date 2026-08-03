#!/usr/bin/env php
<?php

$openemrRoot = getenv('OPENEMR_ROOT') ?: '/var/www/openemr';
$_GET['site'] = getenv('OPENEMR_SITE') ?: 'default';
require_once $openemrRoot . '/interface/globals.php';

use OpenEMR\Modules\NeoLimsBridge\Orchestration\WorkflowService;

$limit = max(1, min(100, (int)($argv[1] ?? 10)));
$processed = 0;
for ($i = 0; $i < $limit; $i++) {
    $row = (new WorkflowService())->processNext();
    if ($row === null) {
        break;
    }
    $processed++;
    fwrite(STDOUT, ($row['workflow_uuid'] ?? 'unknown') . " completed\n");
}
fwrite(STDOUT, "Processed {$processed} workflow(s).\n");
