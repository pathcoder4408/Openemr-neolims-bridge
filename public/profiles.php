<?php
require_once __DIR__ . '/common.php';
use OpenEMR\Modules\NeoLimsBridge\Profile\ProfileRepository;
$repo = new ProfileRepository();
$message='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!empty($_POST['activate'])) { $repo->activate((string)$_POST['activate']); $message='Profile activated.'; }
    elseif (!empty($_POST['save_profile'])) {
        $key=(string)$_POST['profile_key']; $existing=$repo->findByKey($key);
        $resources=[];
        foreach (['patient','insurance','encounter','billing','document','order','result','hl7'] as $name) {
            $mode=(string)($_POST['resource'][$name]['mode'] ?? 'disabled');
            $operations=array_values(array_filter(array_map('trim', explode(',', (string)($_POST['resource'][$name]['operations'] ?? '')))));
            $transports=array_values(array_filter(array_map('trim', explode(',', (string)($_POST['resource'][$name]['transports'] ?? '')))));
            $resources[$name]=['mode'=>$mode,'receive_enabled'=>!empty($_POST['resource'][$name]['receive']),'send_enabled'=>!empty($_POST['resource'][$name]['send']),'operations'=>$operations,'transports'=>$transports,'config'=>$existing['resources'][$name]['config'] ?? []];
        }
        $repo->upsert(['profile_key'=>$key,'display_name'=>(string)$_POST['display_name'],'connection_key'=>(string)$_POST['connection_key'],'enabled'=>!empty($_POST['enabled']),'is_default'=>!empty($_POST['is_default']),'default_direction'=>(string)$_POST['default_direction'],'resources'=>$resources,'mappings'=>$existing['mappings'] ?? []]);
        $message='Profile saved.';
    }
}
$profiles=$repo->list();
nb_page_start('Installation Profiles', 'profiles.php');
if ($message) echo '<div class="alert alert-success">'.text($message).'</div>';
foreach($profiles as $profile): ?>
<form method="post" class="card shadow-sm mb-4"><div class="card-header d-flex justify-content-between"><div><strong><?php echo text($profile['display_name']); ?></strong> <code><?php echo text($profile['profile_key']); ?></code></div><?php if($profile['is_default']): ?><span class="badge bg-success">Active</span><?php endif; ?></div><div class="card-body">
<input type="hidden" name="profile_key" value="<?php echo attr($profile['profile_key']); ?>">
<div class="row g-3 mb-3"><div class="col-md-4"><label class="form-label">Display name</label><input class="form-control" name="display_name" value="<?php echo attr($profile['display_name']); ?>"></div><div class="col-md-4"><label class="form-label">Connection key</label><input class="form-control" name="connection_key" value="<?php echo attr($profile['connection_key']); ?>"></div><div class="col-md-2"><label class="form-label">Default direction</label><select class="form-select" name="default_direction"><?php foreach(['receive','send','bidirectional'] as $d): ?><option <?php echo $profile['default_direction']===$d?'selected':''; ?>><?php echo text($d); ?></option><?php endforeach; ?></select></div><div class="col-md-2 pt-4"><label class="me-3"><input type="checkbox" name="enabled" value="1" <?php echo $profile['enabled']?'checked':''; ?>> Enabled</label><label><input type="checkbox" name="is_default" value="1" <?php echo $profile['is_default']?'checked':''; ?>> Default</label></div></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Resource</th><th>Mode</th><th>Receive</th><th>Send</th><th>Operations (comma-separated)</th><th>Transports</th></tr></thead><tbody>
<?php foreach(['patient','insurance','encounter','billing','document','order','result','hl7'] as $name): $r=$profile['resources'][$name] ?? ['mode'=>'disabled','receive_enabled'=>false,'send_enabled'=>false,'operations'=>[],'transports'=>[]]; ?><tr><td class="fw-bold"><?php echo text(ucfirst($name)); ?></td><td><select class="form-select form-select-sm" name="resource[<?php echo attr($name); ?>][mode]"><?php foreach(['disabled','receive','send','bidirectional','fallback'] as $mode): ?><option value="<?php echo attr($mode); ?>" <?php echo $r['mode']===$mode?'selected':''; ?>><?php echo text($mode); ?></option><?php endforeach; ?></select></td><td class="text-center"><input type="checkbox" name="resource[<?php echo attr($name); ?>][receive]" value="1" <?php echo $r['receive_enabled']?'checked':''; ?>></td><td class="text-center"><input type="checkbox" name="resource[<?php echo attr($name); ?>][send]" value="1" <?php echo $r['send_enabled']?'checked':''; ?>></td><td><input class="form-control form-control-sm" name="resource[<?php echo attr($name); ?>][operations]" value="<?php echo attr(implode(',',$r['operations'])); ?>"></td><td><input class="form-control form-control-sm" name="resource[<?php echo attr($name); ?>][transports]" value="<?php echo attr(implode(',',$r['transports'])); ?>"></td></tr><?php endforeach; ?>
</tbody></table></div><button class="btn btn-primary" name="save_profile" value="1">Save profile</button><?php if(!$profile['is_default']): ?><button class="btn btn-outline-success" name="activate" value="<?php echo attr($profile['profile_key']); ?>">Activate</button><?php endif; ?></div></form>
<?php endforeach; nb_page_end(); ?>
