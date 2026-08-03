<?php
namespace OpenEMR\Modules\NeoLimsBridge\Repository;

use OpenEMR\Common\Uuid\UuidRegistry;

final class IdentityLinkRepository
{
    public function patientLink(string $connection, string $localId): ?array
    {
        $row = sqlQuery('SELECT * FROM neolims_bridge_patient_link WHERE connection_key=? AND local_patient_id=? LIMIT 1', [$connection, $localId]);
        return $row ?: null;
    }

    public function patientByExternal(string $system, string $value): array
    {
        return $this->rows(sqlStatement('SELECT * FROM neolims_bridge_patient_link WHERE external_identifier_system=? AND external_identifier_value=?', [$system, $value]));
    }

    public function patientsByDemographics(string $fname, string $lname, string $dob): array
    {
        $rows = $this->rows(sqlStatement(
            'SELECT pid,uuid,pubpid,fname,mname,lname,DOB,sex,email,phone_cell FROM patient_data WHERE LOWER(TRIM(fname))=LOWER(TRIM(?)) AND LOWER(TRIM(lname))=LOWER(TRIM(?)) AND DOB=? ORDER BY pid',
            [$fname, $lname, $dob]
        ));
        foreach ($rows as &$row) {
            if (!empty($row['uuid'])) {
                $row['uuid'] = UuidRegistry::uuidToString($row['uuid']);
            }
        }
        return $rows;
    }

    public function savePatientLink(array $d): void
    {
        sqlStatement(
            'INSERT INTO neolims_bridge_patient_link (connection_key,local_patient_id,openemr_patient_uuid,openemr_pid,link_source,external_identifier_system,external_identifier_value,created_at,updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE openemr_patient_uuid=VALUES(openemr_patient_uuid),openemr_pid=VALUES(openemr_pid),link_source=VALUES(link_source),external_identifier_system=VALUES(external_identifier_system),external_identifier_value=VALUES(external_identifier_value),updated_at=NOW()',
            [$d['connection_key'],$d['local_patient_id'],$d['openemr_patient_uuid'],$d['openemr_pid'],$d['link_source'],$d['external_identifier_system'],$d['external_identifier_value']]
        );
    }

    public function encounterLink(string $connection, string $localId): ?array
    {
        $row = sqlQuery('SELECT * FROM neolims_bridge_encounter_link WHERE connection_key=? AND local_encounter_id=? LIMIT 1', [$connection, $localId]);
        return $row ?: null;
    }

    public function encountersByExternal(int $pid, string $externalId): array
    {
        $rows = $this->rows(sqlStatement('SELECT encounter,uuid,pid,date,reason,external_id FROM form_encounter WHERE pid=? AND external_id=? ORDER BY encounter', [$pid, $externalId]));
        foreach ($rows as &$row) {
            if (!empty($row['uuid'])) {
                $row['uuid'] = UuidRegistry::uuidToString($row['uuid']);
            }
        }
        return $rows;
    }

    public function saveEncounterLink(array $d): void
    {
        sqlStatement(
            'INSERT INTO neolims_bridge_encounter_link (connection_key,local_encounter_id,local_patient_id,openemr_encounter_uuid,openemr_encounter_id,openemr_patient_uuid,openemr_pid,link_source,external_identifier,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE local_patient_id=VALUES(local_patient_id),openemr_encounter_uuid=VALUES(openemr_encounter_uuid),openemr_encounter_id=VALUES(openemr_encounter_id),openemr_patient_uuid=VALUES(openemr_patient_uuid),openemr_pid=VALUES(openemr_pid),link_source=VALUES(link_source),external_identifier=VALUES(external_identifier),updated_at=NOW()',
            [$d['connection_key'],$d['local_encounter_id'],$d['local_patient_id'],$d['openemr_encounter_uuid'],$d['openemr_encounter_id'],$d['openemr_patient_uuid'],$d['openemr_pid'],$d['link_source'],$d['external_identifier']]
        );
    }

    private function rows($statement): array
    {
        $rows=[];
        while ($row=sqlFetchArray($statement)) { $rows[]=$row; }
        return $rows;
    }
}
