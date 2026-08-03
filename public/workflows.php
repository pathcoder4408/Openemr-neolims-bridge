<?php
require_once __DIR__ . '/common.php';
$status=trim((string)($_GET['status'] ?? '')); $where=[];$params=[];
if($status!==''){ $where[]='status=?';$params[]=$status; }
$sql='SELECT * FROM neolims_bridge_workflow'; if($where)$sql.=' WHERE '.implode(' AND ',$where); $sql.=' ORDER BY id DESC LIMIT 100';
$rows=[]; if(nb_table_exists('neolims_bridge_workflow')){$stmt=sqlStatement($sql,$params);while($r=sqlFetchArray($stmt))$rows[]=$r;}
nb_page_start('Workflow Queue', 'workflows.php'); ?>
<form class="row g-2 mb-3"><div class="col-auto"><select class="form-select" name="status"><option value="">All statuses</option><?php foreach(['queued','processing','retry','completed','failed','cancelled'] as $s): ?><option <?php echo $status===$s?'selected':''; ?>><?php echo text($s); ?></option><?php endforeach; ?></select></div><div class="col-auto"><button class="btn btn-primary">Filter</button></div></form>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><th>Updated</th><th>Accession</th><th>Connection</th><th>Status</th><th>Step</th><th>Attempts</th><th>Error</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?php echo text($r['updated_at']); ?></td><td><?php echo text($r['accession_number']); ?></td><td><?php echo text($r['connection_key']); ?></td><td><span class="badge bg-<?php echo $r['status']==='completed'?'success':($r['status']==='failed'?'danger':'secondary'); ?>"><?php echo text($r['status']); ?></span></td><td><?php echo text($r['current_step']); ?></td><td><?php echo (int)$r['attempts'].'/'.(int)$r['max_attempts']; ?></td><td class="text-danger small"><?php echo text((string)$r['last_error']); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php nb_page_end(); ?>
