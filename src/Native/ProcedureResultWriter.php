<?php
namespace OpenEMR\Modules\NeoLimsBridge\Native;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Uuid\UuidRegistry;
final class ProcedureResultWriter
{
    public function create(int $orderId,array $report,array $results): array
    {
        QueryUtils::startTransaction();
        try {
            $reportUuid=UuidRegistry::generateUuid();
            $reportId=sqlInsert('INSERT INTO procedure_report (uuid,procedure_order_id,procedure_order_seq,date_collected,date_collected_tz,date_report,date_report_tz,report_status,report_notes,specimen_num) VALUES (?,?,?,?,?,?,?,?,?,?)',[$reportUuid,$orderId,$report['procedure_order_seq'],$report['date_collected'],$report['date_collected_tz'],$report['date_report'],$report['date_report_tz'],$report['report_status'],$report['report_notes'],$report['specimen_num']]);
            $resultIds=[];
            foreach($results as $result){$uuid=UuidRegistry::generateUuid();$id=sqlInsert('INSERT INTO procedure_result (uuid,procedure_report_id,result_data_type,result_code,result_text,date,facility,units,`range`,abnormal,result_status,result,comments,document_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$uuid,$reportId,$result['result_data_type'],$result['result_code'],$result['result_text'],$result['date'],$result['facility'],$result['units'],$result['range'],$result['abnormal'],$result['result_status'],$result['result'],$result['comments'],$result['document_id']]);$resultIds[]=['id'=>$id,'uuid'=>UuidRegistry::uuidToString($uuid)];}
            sqlStatement('UPDATE procedure_order SET order_status=? WHERE procedure_order_id=?',[$report['report_status']==='final'?'completed':'active',$orderId]);
            QueryUtils::commitTransaction();
            return ['report_id'=>$reportId,'report_uuid'=>UuidRegistry::uuidToString($reportUuid),'results'=>$resultIds];
        } catch(\Throwable $e){QueryUtils::rollbackTransaction();throw $e;}
    }
}
