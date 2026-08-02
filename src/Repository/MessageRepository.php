<?php

namespace OpenEMR\Modules\NeoLimsBridge\Repository;

use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\NeoLimsBridge\Canonical\CanonicalMessage;

final class MessageRepository
{
    public function installed(): bool
    {
        return !empty(sqlQuery("SHOW TABLES LIKE 'neolims_bridge_message'"));
    }

    public function store(CanonicalMessage $message, string $raw): array
    {
        if (!$this->installed()) {
            throw new \RuntimeException('NeoLIMS bridge tables are not installed.');
        }

        $json = $message->canonicalJson();
        $hash = hash('sha256', $json);
        $existing = $this->findByIdentifier(
            $message->messageType,
            $message->identifierSystem,
            $message->identifierValue
        );

        if ($existing && hash_equals((string)$existing['payload_hash'], $hash)) {
            return ['row' => $existing, 'created' => false, 'unchanged' => true];
        }

        $uuid = $existing['message_uuid']
            ?? $message->requestedUuid
            ?? UuidRegistry::uuidToString(UuidRegistry::generateUuid());

        sqlStatement(
            "INSERT INTO neolims_bridge_message
                (message_uuid, message_type, transport, identifier_system,
                 identifier_value, patient_reference, encounter_reference,
                 status, payload_json, raw_payload, payload_hash,
                 created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'queued', ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 transport = VALUES(transport),
                 patient_reference = VALUES(patient_reference),
                 encounter_reference = VALUES(encounter_reference),
                 status = 'queued',
                 payload_json = VALUES(payload_json),
                 raw_payload = VALUES(raw_payload),
                 payload_hash = VALUES(payload_hash),
                 updated_at = NOW()",
            [
                $uuid,
                $message->messageType,
                $message->transport,
                $message->identifierSystem,
                $message->identifierValue,
                $message->patientReference,
                $message->encounterReference,
                $json,
                $raw,
                $hash,
            ]
        );

        $this->audit($uuid, $existing ? 'update' : 'create', $hash, null);
        return [
            'row' => $this->findByUuid($uuid),
            'created' => !$existing,
            'unchanged' => false,
        ];
    }

    public function findByUuid(string $uuid): ?array
    {
        $row = sqlQuery(
            'SELECT * FROM neolims_bridge_message WHERE message_uuid = ? LIMIT 1',
            [$uuid]
        );
        return $row ?: null;
    }

    public function findByIdentifier(string $type, string $system, string $value): ?array
    {
        $row = sqlQuery(
            'SELECT * FROM neolims_bridge_message
              WHERE message_type = ?
                AND identifier_system = ?
                AND identifier_value = ?
              LIMIT 1',
            [$type, $system, $value]
        );
        return $row ?: null;
    }

    public function search(int $limit = 50): array
    {
        $statement = sqlStatement(
            'SELECT * FROM neolims_bridge_message ORDER BY updated_at DESC LIMIT ' . max(1, min(200, $limit))
        );
        $rows = [];
        while ($row = sqlFetchArray($statement)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function audit(string $uuid, string $action, string $hash, ?string $detail): void
    {
        sqlStatement(
            'INSERT INTO neolims_bridge_audit
                (message_uuid, action, payload_hash, detail, actor_user_id, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [$uuid, $action, $hash, $detail, $_SESSION['authUserID'] ?? null]
        );
    }
}
