<?php
namespace OpenEMR\Modules\NeoLimsBridge\Service;

use OpenEMR\Modules\NeoLimsBridge\GlobalConfig;
use OpenEMR\Modules\NeoLimsBridge\Native\BillingWriter;
use OpenEMR\Modules\NeoLimsBridge\Repository\BillingLinkRepository;
use OpenEMR\Modules\NeoLimsBridge\Repository\IdentityLinkRepository;
use OpenEMR\Modules\NeoLimsBridge\Repository\OrderLinkRepository;

final class BillingSyncService
{
    private BillingLinkRepository $links; private IdentityLinkRepository $identity; private OrderLinkRepository $orders; private GlobalConfig $config;
    public function __construct(){ $this->links=new BillingLinkRepository();$this->identity=new IdentityLinkRepository();$this->orders=new OrderLinkRepository();$this->config=new GlobalConfig($GLOBALS); }
    public function validate(array $r): array
    {
        $n=$this->normalize($r);$errors=[];
        $patient=$this->identity->patientLink($n['connection_key'],$n['local_patient_id']);
        $encounter=$this->identity->encounterLink($n['connection_key'],$n['local_encounter_id']);
        $order=$n['local_order_id']!==''?$this->orders->find($n['connection_key'],$n['local_order_id']):null;
        if(!$patient)$errors[]='Patient link not found.'; if(!$encounter)$errors[]='Encounter link not found.';
        if($patient&&$encounter&&(int)$patient['openemr_pid']!==(int)$encounter['openemr_pid'])$errors[]='Patient and encounter links refer to different patients.';
        if($n['local_order_id']!==''&&!$order)$errors[]='Order link not found.';
        if(!sqlQuery('SELECT id FROM users WHERE id=? AND active=1',[$n['charge']['provider_id']]))$errors[]='Billing provider is invalid or inactive.';
        $existing=$this->links->find($n['connection_key'],$n['local_charge_id']);
        $ext=$this->links->findByExternal($n['identifier']['system'],$n['identifier']['value']); if(count($ext)>1)$errors[]='Multiple billing links use the external identifier.';
        if($n['charge']['fee']<0)$errors[]='fee cannot be negative.'; if($n['charge']['units']<1)$errors[]='units must be at least 1.';
        return ['valid'=>$errors===[],'errors'=>$errors,'normalized'=>$n,'patient_link'=>$patient,'encounter_link'=>$encounter,'order_link'=>$order,'existing_billing_link'=>$existing,'can_create'=>$errors===[]&&!$existing];
    }
    public function sync(array $r): array
    {
        $v=$this->validate($r); if(!$v['valid'])return $v+['written'=>false];
        if($v['existing_billing_link'])return ['resolution'=>'linked','created'=>false,'written'=>false,'charge'=>$v['existing_billing_link']];
        if(!empty($r['dry_run']))return $v+['written'=>false,'dry_run'=>true];
        if(!$this->config->enabled(GlobalConfig::ENABLE_NATIVE_WRITES)||!$this->config->enabled(GlobalConfig::ENABLE_BILLING_WRITES))throw new \InvalidArgumentException('Native and billing writes must both be enabled, or use dry_run=true.');
        $n=$v['normalized'];$p=$v['patient_link'];$e=$v['encounter_link'];$hash=hash('sha256',json_encode($n,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        $created=(new BillingWriter())->create(['pid'=>(int)$p['openemr_pid'],'encounter_id'=>(int)$e['openemr_encounter_id']],$n['charge']);
        $this->links->save(['connection_key'=>$n['connection_key'],'local_charge_id'=>$n['local_charge_id'],'local_order_id'=>$n['local_order_id'],'local_report_id'=>$n['local_report_id'],'openemr_billing_id'=>$created['billing_id'],'openemr_pid'=>(int)$p['openemr_pid'],'openemr_encounter_id'=>(int)$e['openemr_encounter_id'],'code_type'=>$n['charge']['code_type'],'code'=>$n['charge']['code'],'modifier'=>$n['charge']['modifier'],'units'=>$n['charge']['units'],'fee'=>$n['charge']['fee'],'payload_hash'=>$hash,'link_source'=>'created','external_identifier_system'=>$n['identifier']['system'],'external_identifier_value'=>$n['identifier']['value']]);
        return ['resolution'=>'created','created'=>true,'written'=>true,'billing'=>$this->links->getBilling($created['billing_id'])];
    }
    private function normalize(array $r): array
    {
        foreach(['connection_key','local_patient_id','local_encounter_id','local_charge_id'] as $f)if(trim((string)($r[$f]??''))==='')throw new \InvalidArgumentException("$f is required.");
        $c=is_array($r['charge']??null)?$r['charge']:[];foreach(['code_type','code','provider_id'] as $f)if(trim((string)($c[$f]??''))==='')throw new \InvalidArgumentException("charge.$f is required.");
        $defaults=['date'=>date('Y-m-d H:i:s'),'code_text'=>'','modifier'=>'','units'=>1,'fee'=>0.0,'justify'=>'','payer_id'=>0,'target'=>'','x12_partner_id'=>0,'notecodes'=>'','external_id'=>'','pricelevel'=>'','revenue_code'=>'','chargecat'=>'','groupname'=>(string)($_SESSION['authProvider']??''),'user_id'=>(int)($_SESSION['authUserID']??0)];$c=array_merge($defaults,$c);$c['provider_id']=(int)$c['provider_id'];$c['units']=(int)$c['units'];$c['fee']=(float)$c['fee'];$c['payer_id']=(int)$c['payer_id'];$c['x12_partner_id']=(int)$c['x12_partner_id'];$c['user_id']=(int)$c['user_id'];
        if(is_array($c['justify']))$c['justify']=implode(':',array_map('strval',$c['justify']));
        $i=is_array($r['external_identifier']??null)?$r['external_identifier']:[];$system=trim((string)($i['system']??'urn:neolims:charge'));$value=trim((string)($i['value']??$r['local_charge_id']));
        if($c['external_id']==='')$c['external_id']=substr((string)$r['local_charge_id'],0,20);
        return ['connection_key'=>(string)$r['connection_key'],'local_patient_id'=>(string)$r['local_patient_id'],'local_encounter_id'=>(string)$r['local_encounter_id'],'local_order_id'=>(string)($r['local_order_id']??''),'local_report_id'=>(string)($r['local_report_id']??''),'local_charge_id'=>(string)$r['local_charge_id'],'identifier'=>['system'=>$system,'value'=>$value],'charge'=>$c];
    }
}
