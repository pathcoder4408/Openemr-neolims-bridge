<?php
require_once dirname(__DIR__, 4) . '/globals.php';

if (!acl_check('admin', 'super')) {
    http_response_code(403);
    exit(xlt('Access denied'));
}

function nb_url(string $page): string
{
    return $GLOBALS['webroot'] . '/interface/modules/custom_modules/openemr-neolims-bridge/public/' . ltrim($page, '/');
}

function nb_nav(string $active): void
{
    $items = [
        'index.php' => 'Dashboard',
        'profiles.php' => 'Installation Profiles',
        'mappings.php' => 'Mappings',
        'workflows.php' => 'Workflow Queue',
        'dead_letters.php' => 'Dead Letters',
        'messages.php' => 'Messages',
        'diagnostics.php' => 'Diagnostics',
        'testing.php' => 'Testing',
        'settings.php' => 'Settings',
    ];
    echo '<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3 border rounded px-2"><a class="navbar-brand" href="' . attr(nb_url('index.php')) . '">NeoLIMS Bridge</a><div class="navbar-nav flex-wrap">';
    foreach ($items as $file => $label) {
        $class = $file === $active ? 'nav-link active fw-bold' : 'nav-link';
        echo '<a class="' . attr($class) . '" href="' . attr(nb_url($file)) . '">' . text($label) . '</a>';
    }
    echo '</div></nav>';
}

function nb_page_start(string $title, string $active): void
{
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . text($title) . '</title>';
    echo '<link rel="stylesheet" href="' . attr($GLOBALS['assets_static_relative']) . '/bootstrap/dist/css/bootstrap.min.css">';
    echo '<style>body{background:#f7f8fa}.metric-card{min-height:120px}.status-dot{display:inline-block;width:.65rem;height:.65rem;border-radius:50%;margin-right:.35rem}.code-wrap{white-space:pre-wrap;word-break:break-word}.table td,.table th{vertical-align:middle}</style></head><body><div class="container-fluid py-3">';
    nb_nav($active);
    echo '<h1 class="h3 mb-3">' . text($title) . '</h1>';
}

function nb_page_end(): void
{
    echo '</div></body></html>';
}

function nb_table_exists(string $table): bool
{
    try {
        return !empty(sqlQuery('SHOW TABLES LIKE ?', [$table]));
    } catch (Throwable) {
        return false;
    }
}

function nb_count(string $table, string $where = '1=1'): int
{
    if (!nb_table_exists($table)) return 0;
    $row = sqlQuery("SELECT COUNT(*) AS c FROM {$table} WHERE {$where}");
    return (int)($row['c'] ?? 0);
}
