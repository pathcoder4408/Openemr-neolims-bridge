<?php
require_once __DIR__ . '/common.php';
use OpenEMR\Modules\NeoLimsBridge\Profile\ProfileRepository;
use OpenEMR\Modules\NeoLimsBridge\Repository\OperationsRepository;

$profile = null;
try { $profile = (new ProfileRepository())->findActive(); } catch (Throwable $e) {}
$metrics = [];
try { if (nb_table_exists('neolims_bridge_workflow')) $metrics = (new OperationsRepository())->metrics(); } catch (Throwable $e) {}

nb_page_start('NeoLIMS Integration Dashboard', 'index.php');
?>
<div class="alert alert-info">
  <strong>Active installation:</strong>
  <?php echo $profile ? text($profile['display_name']) . ' <code>' . text($profile['connection_key']) . '</code>' : 'No active profile'; ?>
</div>
<div class="row g-3 mb-4">
<?php
$cards = [
 ['Profiles', nb_count('neolims_bridge_profile'), 'profiles.php'],
 ['Queued workflows', nb_count('neolims_bridge_workflow', "status IN ('queued','retry')"), 'workflows.php'],
 ['Failed workflows', nb_count('neolims_bridge_workflow', "status='failed'"), 'workflows.php?status=failed'],
 ['Open dead letters', nb_count('neolims_bridge_dead_letter', "status='open'"), 'dead_letters.php'],
 ['Inbound messages', nb_count('neolims_bridge_message'), 'messages.php'],
 ['Billing links', nb_count('neolims_bridge_billing_link'), 'diagnostics.php'],
];
foreach ($cards as [$label,$value,$url]): ?>
<div class="col-12 col-sm-6 col-lg-2"><a class="text-decoration-none" href="<?php echo attr(nb_url($url)); ?>"><div class="card metric-card shadow-sm"><div class="card-body"><div class="text-muted small"><?php echo text($label); ?></div><div class="display-6 text-dark"><?php echo (int)$value; ?></div></div></div></a></div>
<?php endforeach; ?>
</div>
<div class="row g-3">
<div class="col-lg-7"><div class="card shadow-sm"><div class="card-header fw-bold">Resource policy</div><div class="card-body">
<?php if (!$profile): ?><p>No active profile. Open Installation Profiles to activate one.</p><?php else: ?>
<table class="table table-sm"><thead><tr><th>Resource</th><th>Mode</th><th>Receive</th><th>Send</th><th>Operations</th></tr></thead><tbody>
<?php foreach ($profile['resources'] as $name=>$r): ?><tr><td><?php echo text($name); ?></td><td><span class="badge bg-<?php echo $r['mode']==='disabled'?'secondary':'primary'; ?>"><?php echo text($r['mode']); ?></span></td><td><?php echo $r['receive_enabled']?'Yes':'No'; ?></td><td><?php echo $r['send_enabled']?'Yes':'No'; ?></td><td><?php echo text(implode(', ', $r['operations'])); ?></td></tr><?php endforeach; ?>
</tbody></table><?php endif; ?>
</div></div></div>
<div class="col-lg-5"><div class="card shadow-sm"><div class="card-header fw-bold">Safety state</div><div class="card-body"><dl class="row mb-0">
<dt class="col-7">Native writes</dt><dd class="col-5"><?php echo !empty($GLOBALS['neolims_bridge_enable_native_writes'])?'Enabled':'Disabled'; ?></dd>
<dt class="col-7">Billing writes</dt><dd class="col-5"><?php echo !empty($GLOBALS['neolims_bridge_enable_billing_writes'])?'Enabled':'Disabled'; ?></dd>
<dt class="col-7">FHIR routes</dt><dd class="col-5"><?php echo !empty($GLOBALS['neolims_bridge_enable_fhir'])?'Enabled':'Disabled'; ?></dd>
<dt class="col-7">Standard API</dt><dd class="col-5"><?php echo !empty($GLOBALS['neolims_bridge_enable_standard_api'])?'Enabled':'Disabled'; ?></dd>
<dt class="col-7">HL7 v2</dt><dd class="col-5"><?php echo !empty($GLOBALS['neolims_bridge_enable_hl7'])?'Enabled':'Disabled'; ?></dd>
</dl><a class="btn btn-outline-primary mt-3" href="<?php echo attr(nb_url('settings.php')); ?>">Review settings</a></div></div></div>
</div>
<?php nb_page_end(); ?>
