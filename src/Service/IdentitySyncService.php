<?php
namespace OpenEMR\Modules\NeoLimsBridge\Service;

use OpenEMR\Modules\NeoLimsBridge\GlobalConfig;
use OpenEMR\Modules\NeoLimsBridge\Repository\IdentityLinkRepository;
use OpenEMR\Services\EncounterService;
use OpenEMR\Services\PatientService;
use OpenEMR\Validators\ProcessingResult;

final class IdentitySyncService
{
    private IdentityLinkRepository $links;
    private GlobalConfig $config;

    public function __construct()
    {
        $this->links=new IdentityLinkRepository();
        $this->config=new GlobalConfig($GLOBALS);
    }

    public function resolvePatient(array $r): array
    {
        [$connection,$local,$patient,$identifier]=$this->patientInput($r);
        if ($link=$this->links->patientLink($connection,$local)) {
            return ['resolution'=>'linked','match_source'=>'permanent_link','match_count'=>1,'patient'=>['uuid'=>$link['openemr_patient_uuid'],'pid'=>(int)$link['openemr_pid']],'can_create'=>false];
        }
        if ($identifier['system']!=='' && $identifier['value']!=='') {
            $matches=$this->links->patientByExternal($identifier['system'],$identifier['value']);
            if (count($matches)>1) return $this->conflict('external_identifier',$matches);
            if (count($matches)===1) return ['resolution'=>'matched','match_source'=>'external_identifier','match_count'=>1,'patient'=>['uuid'=>$matches[0]['openemr_patient_uuid'],'pid'=>(int)$matches[0]['openemr_pid']],'can_create'=>false];
        }
        $matches=$this->links->patientsByDemographics($patient['fname'],$patient['lname'],$patient['DOB']);
        if (count($matches)>1) return $this->conflict('exact_demographics',$matches);
        if (count($matches)===1) return ['resolution'=>'matched','match_source'=>'exact_demographics','match_count'=>1,'patient'=>['uuid'=>$matches[0]['uuid'],'pid'=>(int)$matches[0]['pid']],'can_create'=>false];
        return ['resolution'=>'not_found','match_source'=>null,'match_count'=>0,'patient'=>null,'can_create'=>true];
    }

    public function syncPatient(array $r): array
    {
        [$connection,$local,$patient,$identifier]=$this->patientInput($r);
        $resolved=$this->resolvePatient($r);
        if ($resolved['resolution']==='conflict' || !empty($r['dry_run'])) return $resolved+['written'=>false,'dry_run'=>!empty($r['dry_run'])];
        $this->assertWrites();
        $service=new PatientService();
        $data=$this->allow($patient,['title','fname','mname','lname','suffix','DOB','sex','street','street_line_2','city','state','postal_code','country_code','phone_home','phone_cell','phone_biz','email','status','providerID','facility_id','pubpid']);
        if ($resolved['resolution']==='not_found') {
            $out=$this->result($service->insert($data));
            $record=$out[0]??$out;
            $uuid=(string)$record['uuid']; $pid=(int)$record['pid']; $created=true; $source='created';
        } else {
            $uuid=(string)$resolved['patient']['uuid']; $pid=(int)$resolved['patient']['pid']; $created=false; $source=(string)$resolved['match_source'];
            if (!empty($r['update_existing'])) $this->result($service->update($uuid,$data));
        }
        $this->links->savePatientLink(['connection_key'=>$connection,'local_patient_id'=>$local,'openemr_patient_uuid'=>$uuid,'openemr_pid'=>$pid,'link_source'=>$source,'external_identifier_system'=>$identifier['system'],'external_identifier_value'=>$identifier['value']]);
        return ['resolution'=>$created?'created':'linked','created'=>$created,'updated'=>!$created&&!empty($r['update_existing']),'written'=>true,'patient'=>['uuid'=>$uuid,'pid'=>$pid]];
    }

    public function syncEncounter(array $r): array
    {
        foreach (['connection_key','local_patient_id','local_encounter_id'] as $f) if (trim((string)($r[$f]??''))==='') throw new \InvalidArgumentException("{$f} is required.");
        $patient=$this->links->patientLink((string)$r['connection_key'],(string)$r['local_patient_id']);
        if (!$patient) throw new \InvalidArgumentException('Sync the patient before the encounter.');
        $encounter=is_array($r['encounter']??null)?$r['encounter']:[];
        $existing=$this->links->encounterLink((string)$r['connection_key'],(string)$r['local_encounter_id']);
        $external=trim((string)($r['external_identifier']??$encounter['external_id']??''));
        if (!$existing && $external!=='') {
            $matches=$this->links->encountersByExternal((int)$patient['openemr_pid'],$external);
            if (count($matches)>1) return $this->conflict('encounter_external_identifier',$matches)+['written'=>false];
            if (count($matches)===1) $existing=['openemr_encounter_uuid'=>$matches[0]['uuid'],'openemr_encounter_id'=>$matches[0]['encounter'],'link_source'=>'external_identifier'];
        }
        if (!empty($r['dry_run'])) return ['resolution'=>$existing?'matched':'not_found','encounter'=>$existing,'can_create'=>!$existing,'written'=>false,'dry_run'=>true];
        $this->assertWrites();
        $data=$this->allow($encounter,['date','reason','onset_date','sensitivity','billing_note','pc_catid','facility_id','billing_facility','provider_id','referring_provider_id','ordering_provider_id','pos_code','class_code','discharge_disposition']);
        $data['external_id']=$external; $data['date']=$data['date']??date('Y-m-d H:i:s'); $data['reason']=$data['reason']??'NeoLIMS laboratory encounter';
        $service=new EncounterService(); $puuid=(string)$patient['openemr_patient_uuid'];
        if ($existing) {
            $euuid=(string)$existing['openemr_encounter_uuid']; $eid=(int)$existing['openemr_encounter_id']; $created=false; $source=(string)($existing['link_source']??'permanent_link');
            if (!empty($r['update_existing'])) $this->result($service->updateEncounter($puuid,$euuid,$data));
        } else {
            $data['user']=$_SESSION['authUser']??''; $data['group']=$_SESSION['authProvider']??'';
            $out=$this->result($service->insertEncounter($puuid,$data)); $record=$out[0]??$out;
            $euuid=(string)($record['uuid']??$record['euuid']??''); $eid=(int)($record['encounter']??$record['eid']??0);
            if ($euuid==='' || $eid<=0) throw new \RuntimeException('OpenEMR did not return encounter identifiers.');
            $created=true; $source='created';
        }
        $this->links->saveEncounterLink(['connection_key'=>(string)$r['connection_key'],'local_encounter_id'=>(string)$r['local_encounter_id'],'local_patient_id'=>(string)$r['local_patient_id'],'openemr_encounter_uuid'=>$euuid,'openemr_encounter_id'=>$eid,'openemr_patient_uuid'=>$puuid,'openemr_pid'=>(int)$patient['openemr_pid'],'link_source'=>$source,'external_identifier'=>$external]);
        return ['resolution'=>$created?'created':'linked','created'=>$created,'updated'=>!$created&&!empty($r['update_existing']),'written'=>true,'encounter'=>['uuid'=>$euuid,'encounter'=>$eid,'patient_uuid'=>$puuid,'pid'=>(int)$patient['openemr_pid']]];
    }

    private function patientInput(array $r): array
    {
        $connection=trim((string)($r['connection_key']??'')); $local=trim((string)($r['local_patient_id']??'')); $patient=$r['patient']??[];
        if ($connection===''||$local===''||!is_array($patient)) throw new \InvalidArgumentException('connection_key, local_patient_id, and patient are required.');
        foreach (['fname','lname','DOB'] as $f) if (trim((string)($patient[$f]??''))==='') throw new \InvalidArgumentException("patient.{$f} is required.");
        $date=date_create((string)$patient['DOB']); if (!$date) throw new \InvalidArgumentException('patient.DOB is invalid.'); $patient['DOB']=$date->format('Y-m-d');
        $i=is_array($r['external_identifier']??null)?$r['external_identifier']:[];
        return [$connection,$local,$patient,['system'=>trim((string)($i['system']??'')),'value'=>trim((string)($i['value']??''))]];
    }

    private function assertWrites(): void
    {
        if (!$this->config->enabled(GlobalConfig::ENABLE_NATIVE_WRITES)) throw new \InvalidArgumentException('Native writes are disabled; use dry_run=true or enable the module setting.');
    }
    private function result(ProcessingResult $r): array
    {
        if ($r->hasErrors()) throw new \InvalidArgumentException(json_encode(['validationErrors'=>$r->getValidationMessages(),'internalErrors'=>$r->getInternalErrors()]));
        return $r->getData();
    }
    private function allow(array $data,array $fields): array { return array_intersect_key($data,array_flip($fields)); }
    private function conflict(string $source,array $matches): array { return ['resolution'=>'conflict','match_source'=>$source,'match_count'=>count($matches),'matches'=>$matches,'can_create'=>false,'message'=>'Multiple matches; write blocked.']; }
}
