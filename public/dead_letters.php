<?php
require_once __DIR__ . '/common.php';
$status=trim((string)($_GET['status'] ?? 'open'));$rows=[];
if(nb_table_exists('neolims_bridge_dead_letter')){$stmt=sqlStatement('SELECT * FROM neolims_bridge_dead_letter WHERE status=? ORDER BY id DESC LIMIT 100',[$status]);while($r=sqlFetchArray($stmt))$rows[]=$r;}
nb_page_start('Dead Letter Queue', 'dead_letters.php'); ?>
<div class="mb-3"><a class="btn btn-outline-primary" href="?status=open">Open</a> <a class="btn btn-outline-secondary" href="?status=replayed">Replayed</a> <a class="btn btn-outline-success" href="?status=resolved">Resolved</a></div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><th>Updated</th><th>Workflow</th><th>Status</th><th>Error</th><th>Resolution</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?php echo text($r['updated_at']); ?></td><td><code><?php echo text($r['workflow_uuid']); ?></code></td><td><?php echo text($r['status']); ?></td><td class="text-danger"><?php echo text($r['last_error'] ?? ''); ?></td><td><?php echo text($r['resolution_note'] ?? ''); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php nb_page_end(); ?>
