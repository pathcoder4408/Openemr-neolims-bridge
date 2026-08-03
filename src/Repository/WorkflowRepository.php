<?php

namespace OpenEMR\Modules\NeoLimsBridge\Repository;

use OpenEMR\Common\Uuid\UuidRegistry;

final class WorkflowRepository
{
    public function create(array $data): array
    {
        $existing = $this->findByExternal($data['connection_key'], $data['external_id']);
        if ($existing) {
            return $existing;
        }

        $uuid = UuidRegistry::uuidToString(UuidRegistry::generateUuid());
        sqlStatement(
            'INSERT INTO neolims_bridge_workflow
                (workflow_uuid, connection_key, external_id, accession_number,
                 status, current_step, payload_json, payload_hash, attempts,
                 max_attempts, next_attempt_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW(), NOW())',
            [
                $uuid,
                $data['connection_key'],
                $data['external_id'],
                $data['accession_number'],
                'queued',
                'queued',
                $data['payload_json'],
                $data['payload_hash'],
                $data['max_attempts'],
            ]
        );

        $this->event($uuid, 'queued', 'Workflow accepted');
        return $this->findByUuid($uuid);
    }

    public function findByUuid(string $uuid): ?array
    {
        $row = sqlQuery(
            'SELECT * FROM neolims_bridge_workflow WHERE workflow_uuid = ? LIMIT 1',
            [$uuid]
        );
        return $row ?: null;
    }

    public function findByExternal(string $connectionKey, string $externalId): ?array
    {
        $row = sqlQuery(
            'SELECT * FROM neolims_bridge_workflow
              WHERE connection_key = ? AND external_id = ? LIMIT 1',
            [$connectionKey, $externalId]
        );
        return $row ?: null;
    }

    public function claimNext(): ?array
    {
        sqlBeginTrans();
        try {
            $row = sqlQuery(
                "SELECT * FROM neolims_bridge_workflow
                  WHERE status IN ('queued','retry')
                    AND next_attempt_at <= NOW()
                    AND attempts < max_attempts
                  ORDER BY created_at
                  LIMIT 1 FOR UPDATE"
            );
            if (!$row) {
                sqlCommitTrans();
                return null;
            }

            sqlStatement(
                "UPDATE neolims_bridge_workflow
                    SET status='processing', attempts=attempts+1,
                        started_at=COALESCE(started_at,NOW()), updated_at=NOW()
                  WHERE id=?",
                [(int)$row['id']]
            );
            sqlCommitTrans();
            return $this->findByUuid($row['workflow_uuid']);
        } catch (\Throwable $e) {
            sqlRollbackTrans();
            throw $e;
        }
    }

    public function step(string $uuid, string $step, string $status = 'processing'): void
    {
        sqlStatement(
            'UPDATE neolims_bridge_workflow
                SET current_step=?, status=?, updated_at=NOW()
              WHERE workflow_uuid=?',
            [$step, $status, $uuid]
        );
        $this->event($uuid, $step, $status);
    }

    public function complete(string $uuid, array $result): void
    {
        sqlStatement(
            "UPDATE neolims_bridge_workflow
                SET status='completed', current_step='completed',
                    result_json=?, completed_at=NOW(), updated_at=NOW(),
                    last_error=NULL
              WHERE workflow_uuid=?",
            [json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $uuid]
        );
        $this->event($uuid, 'completed', 'Workflow completed');
    }

    public function fail(string $uuid, string $error, int $retryDelaySeconds): void
    {
        $row = $this->findByUuid($uuid);
        $retry = $row && (int)$row['attempts'] < (int)$row['max_attempts'];
        $status = $retry ? 'retry' : 'failed';

        sqlStatement(
            "UPDATE neolims_bridge_workflow
                SET status=?, current_step=?, last_error=?,
                    next_attempt_at=DATE_ADD(NOW(), INTERVAL ? SECOND),
                    failed_at=IF(?='failed',NOW(),failed_at), updated_at=NOW()
              WHERE workflow_uuid=?",
            [$status, $status, $error, $retryDelaySeconds, $status, $uuid]
        );
        $this->event($uuid, $status, $error);
    }

    public function retry(string $uuid): ?array
    {
        sqlStatement(
            "UPDATE neolims_bridge_workflow
                SET status='retry', current_step='retry', next_attempt_at=NOW(),
                    last_error=NULL, updated_at=NOW()
              WHERE workflow_uuid=? AND status IN ('failed','retry')",
            [$uuid]
        );
        $this->event($uuid, 'retry', 'Manual retry requested');
        return $this->findByUuid($uuid);
    }

    public function cancel(string $uuid): ?array
    {
        sqlStatement(
            "UPDATE neolims_bridge_workflow
                SET status='cancelled', current_step='cancelled', updated_at=NOW()
              WHERE workflow_uuid=? AND status NOT IN ('completed','cancelled')",
            [$uuid]
        );
        $this->event($uuid, 'cancelled', 'Workflow cancelled');
        return $this->findByUuid($uuid);
    }

    public function list(int $limit = 50, ?string $status = null): array
    {
        $limit = max(1, min(200, $limit));
        $params = [];
        $where = '';
        if ($status !== null && $status !== '') {
            $where = ' WHERE status = ?';
            $params[] = $status;
        }
        $statement = sqlStatement(
            'SELECT * FROM neolims_bridge_workflow' . $where .
            ' ORDER BY updated_at DESC LIMIT ' . $limit,
            $params
        );
        $rows = [];
        while ($row = sqlFetchArray($statement)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function events(string $uuid): array
    {
        $statement = sqlStatement(
            'SELECT * FROM neolims_bridge_workflow_event
              WHERE workflow_uuid=? ORDER BY id',
            [$uuid]
        );
        $rows = [];
        while ($row = sqlFetchArray($statement)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function event(string $uuid, string $event, string $detail): void
    {
        sqlStatement(
            'INSERT INTO neolims_bridge_workflow_event
                (workflow_uuid, event_name, detail, created_at)
             VALUES (?, ?, ?, NOW())',
            [$uuid, $event, $detail]
        );
    }
}
