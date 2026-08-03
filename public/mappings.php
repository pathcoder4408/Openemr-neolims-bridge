<?php
require_once __DIR__ . '/common.php';
use OpenEMR\Modules\NeoLimsBridge\Profile\ProfileRepository;
$repo=new ProfileRepository(); $profile=$repo->findActive(); $message='';
if ($_SERVER['REQUEST_METHOD']==='POST' && $profile) {
    $mappings=[]; foreach((array)($_POST['mapping'] ?? []) as $k=>$v) $mappings[$k]=trim((string)$v);
    $profile['mappings']=$mappings; $repo->upsert($profile); $profile=$repo->findByKey($profile['profile_key']); $message='Mappings saved.';
}
nb_page_start('Resource and Billing Mappings', 'mappings.php');
if(!$profile){echo '<div class="alert alert-warning">Activate an installation profile first.</div>'; nb_page_end(); return;}
if($message) echo '<div class="alert alert-success">'.text($message).'</div>';
$defaults=['facility_id'=>'','billing_facility_id'=>'','default_provider_id'=>'','laboratory_id'=>'','document_category_path'=>'/Lab Results','billing_code_type'=>'CPT4','billing_charge_category'=>'laboratory','billing_price_level'=>'','insurance_policy'=>'replace_if_unbilled','patient_identifier_system'=>'https://neolimsys.com/identifiers/patient'];
$values=array_merge($defaults,$profile['mappings']);
?>
<div class="alert alert-secondary">Editing mappings for <strong><?php echo text($profile['display_name']); ?></strong> (<code><?php echo text($profile['connection_key']); ?></code>).</div>
<form method="post" class="card shadow-sm"><div class="card-body"><div class="row g-3">
<?php foreach($values as $key=>$value): ?><div class="col-md-6"><label class="form-label"><?php echo text(ucwords(str_replace('_',' ',$key))); ?></label><input class="form-control" name="mapping[<?php echo attr($key); ?>]" value="<?php echo attr($value); ?>"></div><?php endforeach; ?>
</div><button class="btn btn-primary mt-3">Save mappings</button></div></form>
<?php nb_page_end(); ?>
