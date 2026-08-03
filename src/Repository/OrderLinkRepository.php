<?php
namespace OpenEMR\Modules\NeoLimsBridge\Repository;

use OpenEMR\Common\Uuid\UuidRegistry;

final class OrderLinkRepository
{
    public function find(string $connection, string $local): ?array
    {
        $row=sqlQuery('SELECT * FROM neolims_bridge_order_link WHERE connection_key=? AND local_order_id=? LIMIT 1',[$connection,$local]);
        return $row ?: null;
    }

    public function findByIdentifier(string $system,string $value): array
    {
        $st=sqlStatement('SELECT * FROM neolims_bridge_order_link WHERE external_identifier_system=? AND external_identifier_value=?',[$system,$value]);
        $rows=[]; while($r=sqlFetchArray($st)) $rows[]=$r; return $rows;
    }

    public function save(array $d): void
    {
        sqlStatement('INSERT INTO neolims_bridge_order_link
            (connection_key,local_order_id,local_patient_id,local_encounter_id,openemr_order_uuid,openemr_order_id,openemr_pid,openemr_encounter_id,link_source,external_identifier_system,external_identifier_value,created_at,updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
            ON DUPLICATE KEY UPDATE openemr_order_uuid=VALUES(openemr_order_uuid),openemr_order_id=VALUES(openemr_order_id),openemr_pid=VALUES(openemr_pid),openemr_encounter_id=VALUES(openemr_encounter_id),link_source=VALUES(link_source),external_identifier_system=VALUES(external_identifier_system),external_identifier_value=VALUES(external_identifier_value),updated_at=NOW()',[
            $d['connection_key'],$d['local_order_id'],$d['local_patient_id'],$d['local_encounter_id'],$d['openemr_order_uuid'],$d['openemr_order_id'],$d['openemr_pid'],$d['openemr_encounter_id'],$d['link_source'],$d['external_identifier_system'],$d['external_identifier_value']
        ]);
    }

    public function getOrder(int $id): ?array
    {
        $row=sqlQuery('SELECT po.*,p.uuid puuid,e.uuid euuid,u.uuid provider_uuid,pp.uuid lab_uuid FROM procedure_order po LEFT JOIN patient_data p ON p.pid=po.patient_id LEFT JOIN form_encounter e ON e.encounter=po.encounter_id LEFT JOIN users u ON u.id=po.provider_id LEFT JOIN procedure_providers pp ON pp.ppid=po.lab_id WHERE po.procedure_order_id=?',[$id]);
        if(!$row) return null;
        foreach(['uuid','puuid','euuid','provider_uuid','lab_uuid'] as $f) if(!empty($row[$f])) $row[$f]=UuidRegistry::uuidToString($row[$f]);
        $row['order_codes']=[]; $st=sqlStatement('SELECT * FROM procedure_order_code WHERE procedure_order_id=? ORDER BY procedure_order_seq',[$id]); while($r=sqlFetchArray($st)) $row['order_codes'][]=$r;
        $row['specimens']=[]; $st=sqlStatement('SELECT * FROM procedure_specimen WHERE procedure_order_id=? ORDER BY procedure_order_seq,procedure_specimen_id',[$id]); while($r=sqlFetchArray($st)){ if(!empty($r['uuid']))$r['uuid']=UuidRegistry::uuidToString($r['uuid']); $row['specimens'][]=$r; }
        return $row;
    }
}
