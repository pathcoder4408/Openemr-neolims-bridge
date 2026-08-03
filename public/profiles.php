<?php
require_once dirname(__DIR__, 4) . '/globals.php';
use OpenEMR\Modules\NeoLimsBridge\Profile\ProfileRepository;
if (!acl_check('admin','super')) { http_response_code(403); exit(xlt('Access denied')); }
$repo=new ProfileRepository();
if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['activate'])) { $repo->activate((string)$_POST['activate']); header('Location: profiles.php'); exit; }
$profiles=$repo->list();
?><!doctype html><html><head><meta charset="utf-8"><title>NeoLIMS Profiles</title><link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative']; ?>/bootstrap/dist/css/bootstrap.min.css"></head><body class="container-fluid py-3">
<h1><?php echo xlt('NeoLIMS Installation Profiles'); ?></h1>
<p><a class="btn btn-secondary" href="index.php"><?php echo xlt('Back to queue'); ?></a></p>
<?php foreach($profiles as $profile): ?><div class="card mb-3"><div class="card-header"><strong><?php echo text($profile['display_name']); ?></strong> <code><?php echo text($profile['profile_key']); ?></code><?php if($profile['is_default']): ?> <span class="badge bg-success">Active</span><?php endif; ?></div><div class="card-body"><p>Connection: <?php echo text($profile['connection_key']); ?></p><table class="table table-sm"><thead><tr><th>Resource</th><th>Mode</th><th>Receive</th><th>Send</th><th>Operations</th><th>Transports</th></tr></thead><tbody><?php foreach($profile['resources'] as $name=>$r): ?><tr><td><?php echo text($name); ?></td><td><?php echo text($r['mode']); ?></td><td><?php echo $r['receive_enabled']?'Yes':'No'; ?></td><td><?php echo $r['send_enabled']?'Yes':'No'; ?></td><td><?php echo text(implode(', ',$r['operations'])); ?></td><td><?php echo text(implode(', ',$r['transports'])); ?></td></tr><?php endforeach; ?></tbody></table><?php if(!$profile['is_default']): ?><form method="post"><button class="btn btn-primary" name="activate" value="<?php echo attr($profile['profile_key']); ?>">Activate</button></form><?php endif; ?></div></div><?php endforeach; ?></body></html>
