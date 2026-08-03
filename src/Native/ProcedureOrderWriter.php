<?php
namespace OpenEMR\Modules\NeoLimsBridge\Native;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\NeoLimsBridge\Repository\OrderLinkRepository;

final class ProcedureOrderWriter
{
    public function create(array $ctx,array $order,array $codes,array $specimens): array
    {
        QueryUtils::startTransaction();
        try {
            $uuid=UuidRegistry::generateUuid();
            $id=sqlInsert('INSERT INTO procedure_order
                (uuid,date_ordered,provider_id,lab_id,date_collected,order_priority,order_status,billing_type,order_psc,specimen_fasting,clinical_hx,patient_instructions,patient_id,encounter_id,history_order,order_abn,order_diagnosis,account,account_facility,collector_id,procedure_order_type,order_intent,scheduled_date,scheduled_start,scheduled_end,performer_type,location_id,activity)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)',[
                $uuid,$order['date_ordered'],$order['provider_id'],$order['lab_id'],$order['date_collected'],$order['order_priority'],$order['order_status'],$order['billing_type'],$order['order_psc'],$order['specimen_fasting'],$order['clinical_hx'],$order['patient_instructions'],$ctx['pid'],$ctx['encounter_id'],$order['history_order'],$order['order_abn'],$order['order_diagnosis'],$order['account'],$order['account_facility'],$order['collector_id'],$order['procedure_order_type'],$order['order_intent'],$order['scheduled_date'],$order['scheduled_start'],$order['scheduled_end'],$order['performer_type'],$order['location_id']
            ]);
            $seq=1;
            foreach($codes as $c){
                sqlStatement('INSERT INTO procedure_order_code (procedure_order_id,procedure_order_seq,procedure_code,procedure_name,procedure_order_title,diagnoses,do_not_send,procedure_type,transport,reason_code,reason_description,reason_date_low,reason_date_high,reason_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[
                    $id,$seq,$c['procedure_code'],$c['procedure_name'],$c['procedure_order_title'],$c['diagnoses'],$c['do_not_send'],$c['procedure_type'],$c['transport'],$c['reason_code'],$c['reason_description'],$c['reason_date_low'],$c['reason_date_high'],$c['reason_status']
                ]);
                foreach($specimens as $sp){
                    if((int)$sp['procedure_order_seq']!==$seq) continue;
                    sqlStatement('INSERT INTO procedure_specimen (uuid,procedure_order_id,procedure_order_seq,specimen_identifier,accession_identifier,specimen_type_code,specimen_type,collection_method_code,collection_method,specimen_location_code,specimen_location,collected_date,collection_date_low,collection_date_high,volume_value,volume_unit,condition_code,specimen_condition,comments,created_by,updated_by,date_created,date_updated) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',[
                        UuidRegistry::generateUuid(),$id,$seq,$sp['specimen_identifier'],$sp['accession_identifier'],$sp['specimen_type_code'],$sp['specimen_type'],$sp['collection_method_code'],$sp['collection_method'],$sp['specimen_location_code'],$sp['specimen_location'],$sp['collected_date'],$sp['collection_date_low'],$sp['collection_date_high'],$sp['volume_value'],$sp['volume_unit'],$sp['condition_code'],$sp['specimen_condition'],$sp['comments'],$_SESSION['authUserID']??0,$_SESSION['authUserID']??0
                    ]);
                }
                $seq++;
            }
            require_once($GLOBALS['srcdir'].'/forms.inc.php');
            $lab=sqlQuery('SELECT name FROM procedure_providers WHERE ppid=?',[$order['lab_id']]);
            $name=trim((string)($lab['name']??'Lab')).'-'.$order['procedure_order_type'].'-'.$id;
            addForm($ctx['encounter_id'],$name,$id,'procedure_order',$ctx['pid'],1);
            QueryUtils::commitTransaction();
            return ['order_id'=>$id,'order_uuid'=>UuidRegistry::uuidToString($uuid)];
        } catch(\Throwable $e){ QueryUtils::rollbackTransaction(); throw $e; }
    }
}
