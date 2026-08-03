<?php
require_once __DIR__ . '/common.php';
use OpenEMR\Modules\NeoLimsBridge\Repository\MessageRepository;
$rows=[]; try{$rows=(new MessageRepository())->search(100);}catch(Throwable $e){}
nb_page_start('Inbound Messages', 'messages.php'); ?>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><th>Updated</th><th>Transport</th><th>Type</th><th>Identifier</th><th>Status</th><th>Hash</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?php echo text($r['updated_at']); ?></td><td><?php echo text($r['transport']); ?></td><td><?php echo text($r['message_type']); ?></td><td><?php echo text($r['identifier_value']); ?></td><td><?php echo text($r['status']); ?></td><td><code><?php echo text(substr($r['payload_hash'],0,12)); ?>…</code></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php nb_page_end(); ?>
