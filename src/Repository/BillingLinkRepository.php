<?php
namespace OpenEMR\Modules\NeoLimsBridge\Repository;

final class BillingLinkRepository
{
    public function find(string $connection, string $localChargeId): ?array
    {
        $row=sqlQuery('SELECT * FROM neolims_bridge_billing_link WHERE connection_key=? AND local_charge_id=? LIMIT 1',[$connection,$localChargeId]);
        return $row?:null;
    }
    public function findByExternal(string $system,string $value): array
    {
        $st=sqlStatement('SELECT * FROM neolims_bridge_billing_link WHERE external_identifier_system=? AND external_identifier_value=?',[$system,$value]);
        $rows=[]; while($r=sqlFetchArray($st)){$rows[]=$r;} return $rows;
    }
    public function save(array $d): void
    {
        sqlStatement('INSERT INTO neolims_bridge_billing_link
            (connection_key,local_charge_id,local_order_id,local_report_id,openemr_billing_id,openemr_pid,openemr_encounter_id,code_type,code,modifier,units,fee,payload_hash,link_source,external_identifier_system,external_identifier_value,created_at,updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
            ON DUPLICATE KEY UPDATE openemr_billing_id=VALUES(openemr_billing_id),openemr_pid=VALUES(openemr_pid),openemr_encounter_id=VALUES(openemr_encounter_id),code_type=VALUES(code_type),code=VALUES(code),modifier=VALUES(modifier),units=VALUES(units),fee=VALUES(fee),payload_hash=VALUES(payload_hash),link_source=VALUES(link_source),updated_at=NOW()',[
            $d['connection_key'],$d['local_charge_id'],$d['local_order_id'],$d['local_report_id'],$d['openemr_billing_id'],$d['openemr_pid'],$d['openemr_encounter_id'],$d['code_type'],$d['code'],$d['modifier'],$d['units'],$d['fee'],$d['payload_hash'],$d['link_source'],$d['external_identifier_system'],$d['external_identifier_value']]);
    }
    public function getBilling(int $id): ?array { $r=sqlQuery('SELECT * FROM billing WHERE id=? LIMIT 1',[$id]); return $r?:null; }
}
