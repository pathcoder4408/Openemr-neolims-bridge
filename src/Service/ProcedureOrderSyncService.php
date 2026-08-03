<?php
namespace OpenEMR\Modules\NeoLimsBridge\Service;

use OpenEMR\Modules\NeoLimsBridge\GlobalConfig;
use OpenEMR\Modules\NeoLimsBridge\Native\ProcedureOrderWriter;
use OpenEMR\Modules\NeoLimsBridge\Repository\IdentityLinkRepository;
use OpenEMR\Modules\NeoLimsBridge\Repository\OrderLinkRepository;

final class ProcedureOrderSyncService
{
    private IdentityLinkRepository $identity; private OrderLinkRepository $links; private GlobalConfig $config;
    public function __construct(){ $this->identity=new IdentityLinkRepository(); $this->links=new OrderLinkRepository(); $this->config=new GlobalConfig($GLOBALS); }

    public function validate(array $r): array
    {
        $n=$this->normalize($r); $errors=[];
        $patient=$this->identity->patientLink($n['connection_key'],$n['local_patient_id']);
        $encounter=$this->identity->encounterLink($n['connection_key'],$n['local_encounter_id']);
        if(!$patient)$errors[]='Patient link not found.';
        if(!$encounter)$errors[]='Encounter link not found.';
        if($patient&&$encounter&&(int)$patient['openemr_pid']!==(int)$encounter['openemr_pid'])$errors[]='Patient and encounter links refer to different OpenEMR patients.';
        if(!sqlQuery('SELECT id FROM users WHERE id=? AND active=1',[$n['order']['provider_id']]))$errors[]='Ordering provider is invalid or inactive.';
        if(!sqlQuery('SELECT ppid FROM procedure_providers WHERE ppid=?',[$n['order']['lab_id']]))$errors[]='Procedure provider/lab is invalid.';
        foreach($n['codes'] as $i=>$c){ if($c['procedure_code']===''||$c['procedure_name']==='')$errors[]="codes[$i] requires procedure_code and procedure_name."; }
        $existing=$this->links->find($n['connection_key'],$n['local_order_id']);
        $external=$this->links->findByIdentifier($n['identifier']['system'],$n['identifier']['value']);
        if(count($external)>1)$errors[]='Multiple order links use the external identifier.';
        return ['valid'=>$errors===[],'errors'=>$errors,'normalized'=>$n,'patient_link'=>$patient,'encounter_link'=>$encounter,'existing_order_link'=>$existing,'can_create'=>$errors===[]&&!$existing];
    }

    public function sync(array $r): array
    {
        $v=$this->validate($r); if(!$v['valid']) return $v+['written'=>false];
        if($v['existing_order_link']) return ['resolution'=>'linked','created'=>false,'written'=>false,'order'=>$v['existing_order_link']];
        if(!empty($r['dry_run'])) return $v+['written'=>false,'dry_run'=>true];
        if(!$this->config->enabled(GlobalConfig::ENABLE_NATIVE_WRITES)) throw new \InvalidArgumentException('Native writes are disabled; use dry_run=true or enable the module setting.');
        $n=$v['normalized']; $patient=$v['patient_link']; $enc=$v['encounter_link'];
        $created=(new ProcedureOrderWriter())->create(['pid'=>(int)$patient['openemr_pid'],'encounter_id'=>(int)$enc['openemr_encounter_id']],$n['order'],$n['codes'],$n['specimens']);
        $this->links->save(['connection_key'=>$n['connection_key'],'local_order_id'=>$n['local_order_id'],'local_patient_id'=>$n['local_patient_id'],'local_encounter_id'=>$n['local_encounter_id'],'openemr_order_uuid'=>$created['order_uuid'],'openemr_order_id'=>$created['order_id'],'openemr_pid'=>(int)$patient['openemr_pid'],'openemr_encounter_id'=>(int)$enc['openemr_encounter_id'],'link_source'=>'created','external_identifier_system'=>$n['identifier']['system'],'external_identifier_value'=>$n['identifier']['value']]);
        return ['resolution'=>'created','created'=>true,'written'=>true,'order'=>$this->links->getOrder($created['order_id'])];
    }

    public function fromFhir(array $f,?string $uuid=null): array
    {
        $id=$f['identifier'][0]??[]; $subject=$this->ref($f['subject']['reference']??''); $enc=$this->ref($f['encounter']['reference']??'');
        $code=$f['code']['coding'][0]??[];
        return [
            'connection_key'=>'fhir','local_order_id'=>(string)($id['value']??$uuid??''),'local_patient_id'=>$subject,'local_encounter_id'=>$enc,
            'external_identifier'=>['system'=>(string)($id['system']??'urn:neolims:order'),'value'=>(string)($id['value']??$uuid??'')],
            'order'=>['provider_id'=>(int)($f['requester']['identifier']['value']??0),'lab_id'=>(int)($f['performer'][0]['identifier']['value']??0),'date_ordered'=>substr((string)($f['authoredOn']??date('c')),0,10),'order_status'=>(string)($f['status']??'active'),'order_priority'=>(string)($f['priority']??'routine'),'order_intent'=>(string)($f['intent']??'order'),'clinical_hx'=>(string)($f['note'][0]['text']??''),'procedure_order_type'=>(string)($f['category'][0]['coding'][0]['display']??'Laboratory')],
            'codes'=>[['procedure_code'=>(string)($code['code']??''),'procedure_name'=>(string)($code['display']??$f['code']['text']??''),'procedure_order_title'=>(string)($f['code']['text']??$code['display']??''),'diagnoses'=>'']],
            'specimens'=>[]
        ];
    }

    private function normalize(array $r): array
    {
        foreach(['connection_key','local_order_id','local_patient_id','local_encounter_id'] as $f) if(trim((string)($r[$f]??''))==='') throw new \InvalidArgumentException("$f is required.");
        $o=is_array($r['order']??null)?$r['order']:[]; foreach(['provider_id','lab_id'] as $f) if((int)($o[$f]??0)<=0) throw new \InvalidArgumentException("order.$f is required.");
        $defaults=['date_ordered'=>date('Y-m-d'),'date_collected'=>null,'order_priority'=>'routine','order_status'=>'active','billing_type'=>'','order_psc'=>0,'specimen_fasting'=>'','clinical_hx'=>'','patient_instructions'=>'','history_order'=>'','order_abn'=>'','order_diagnosis'=>'','account'=>'','account_facility'=>0,'collector_id'=>0,'procedure_order_type'=>'Laboratory','order_intent'=>'order','scheduled_date'=>null,'scheduled_start'=>null,'scheduled_end'=>null,'performer_type'=>'','location_id'=>0]; $o=array_merge($defaults,$o); $o['provider_id']=(int)$o['provider_id'];$o['lab_id']=(int)$o['lab_id'];
        $codes=$r['codes']??[]; if(!is_array($codes)||$codes===[]) throw new \InvalidArgumentException('At least one code is required.'); $out=[]; foreach(array_values($codes) as $c){$out[]=array_merge(['procedure_code'=>'','procedure_name'=>'','procedure_order_title'=>'','diagnoses'=>'','do_not_send'=>0,'procedure_type'=>'','transport'=>'','reason_code'=>'','reason_description'=>'','reason_date_low'=>null,'reason_date_high'=>null,'reason_status'=>''],is_array($c)?$c:[]);} 
        $spec=[]; foreach((array)($r['specimens']??[]) as $sp){$spec[]=array_merge(['procedure_order_seq'=>1,'specimen_identifier'=>'','accession_identifier'=>'','specimen_type_code'=>'','specimen_type'=>'','collection_method_code'=>'','collection_method'=>'','specimen_location_code'=>'','specimen_location'=>'','collected_date'=>null,'collection_date_low'=>null,'collection_date_high'=>null,'volume_value'=>null,'volume_unit'=>'','condition_code'=>'','specimen_condition'=>'','comments'=>''],is_array($sp)?$sp:[]);} 
        $i=is_array($r['external_identifier']??null)?$r['external_identifier']:[]; $system=trim((string)($i['system']??'urn:neolims:order'));$value=trim((string)($i['value']??$r['local_order_id']));
        return ['connection_key'=>(string)$r['connection_key'],'local_order_id'=>(string)$r['local_order_id'],'local_patient_id'=>(string)$r['local_patient_id'],'local_encounter_id'=>(string)$r['local_encounter_id'],'identifier'=>['system'=>$system,'value'=>$value],'order'=>$o,'codes'=>$out,'specimens'=>$spec];
    }
    private function ref(string $r): string { $p=explode('/',trim($r,'/')); return (string)end($p); }
}
