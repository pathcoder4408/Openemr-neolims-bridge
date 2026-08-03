<?php

namespace OpenEMR\Modules\NeoLimsBridge\Repository;

final class OperationsRepository
{
    public function metrics(): array
    {
        $workflow = sqlQuery(
            "SELECT
                COUNT(*) total,
                SUM(status='queued') queued,
                SUM(status='processing') processing,
                SUM(status='retry') retrying,
                SUM(status='completed') completed,
                SUM(status='failed') failed,
                SUM(status='cancelled') cancelled,
                SUM(status='dead_letter') dead_letter,
                AVG(CASE WHEN completed_at IS NOT NULL
                    THEN TIMESTAMPDIFF(SECOND, created_at, completed_at) END) avg_completion_seconds
             FROM neolims_bridge_workflow"
        ) ?: [];

        $links = [];
        foreach ([
            'patient' => 'neolims_bridge_patient_link',
            'encounter' => 'neolims_bridge_encounter_link',
            'order' => 'neolims_bridge_order_link',
            'result' => 'neolims_bridge_result_link',
            'document' => 'neolims_bridge_document_link',
            'billing' => 'neolims_bridge_billing_link',
        ] as $name => $table) {
            $row = sqlQuery("SELECT COUNT(*) count FROM {$table}") ?: ['count' => 0];
            $links[$name] = (int)$row['count'];
        }

        $dlq = sqlQuery(
            "SELECT COUNT(*) total,
                    SUM(status='open') open,
                    SUM(status='replayed') replayed,
                    SUM(status='resolved') resolved
               FROM neolims_bridge_dead_letter"
        ) ?: [];

        return [
            'workflows' => array_map('intval', array_filter($workflow, fn($k) => $k !== 'avg_completion_seconds', ARRAY_FILTER_USE_KEY))
                + ['avg_completion_seconds' => isset($workflow['avg_completion_seconds']) ? (float)$workflow['avg_completion_seconds'] : null],
            'links' => $links,
            'dead_letter' => array_map('intval', $dlq),
            'generated_at_utc' => gmdate('c'),
        ];
    }

    public function addDeadLetter(array $workflow): void
    {
        sqlStatement(
            "INSERT INTO neolims_bridge_dead_letter
                (workflow_uuid, connection_key, external_id, accession_number,
                 current_step, payload_json, payload_hash, attempts,
                 last_error, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 current_step=VALUES(current_step),
                 attempts=VALUES(attempts),
                 last_error=VALUES(last_error),
                 status=IF(status='resolved','resolved','open'),
                 updated_at=NOW()",
            [
                $workflow['workflow_uuid'],
                $workflow['connection_key'],
                $workflow['external_id'],
                $workflow['accession_number'],
                $workflow['current_step'],
                $workflow['payload_json'],
                $workflow['payload_hash'],
                $workflow['attempts'],
                $workflow['last_error'],
            ]
        );
    }

    public function deadLetters(int $limit = 50, string $status = 'open'): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = sqlStatement(
            "SELECT * FROM neolims_bridge_dead_letter
              WHERE (?='' OR status=?)
              ORDER BY updated_at DESC LIMIT {$limit}",
            [$status, $status]
        );
        $rows = [];
        while ($row = sqlFetchArray($stmt)) $rows[] = $row;
        return $rows;
    }

    public function deadLetter(string $uuid): ?array
    {
        $row = sqlQuery(
            "SELECT * FROM neolims_bridge_dead_letter WHERE workflow_uuid=? LIMIT 1",
            [$uuid]
        );
        return $row ?: null;
    }

    public function markDeadLetter(string $uuid, string $status, ?string $note = null): void
    {
        sqlStatement(
            "UPDATE neolims_bridge_dead_letter
                SET status=?, resolution_note=?, updated_at=NOW(),
                    resolved_at=IF(?='resolved',NOW(),resolved_at)
              WHERE workflow_uuid=?",
            [$status, $note, $status, $uuid]
        );
    }

    public function reconciliationRuns(int $limit = 25): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = sqlStatement(
            "SELECT * FROM neolims_bridge_reconciliation_run
              ORDER BY id DESC LIMIT {$limit}"
        );
        $rows = [];
        while ($row = sqlFetchArray($stmt)) $rows[] = $row;
        return $rows;
    }

    public function recordReconciliation(array $data): int
    {
        sqlStatement(
            "INSERT INTO neolims_bridge_reconciliation_run
                (connection_key, scope, checked_count, ok_count,
                 mismatch_count, missing_count, detail_json, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['connection_key'],
                $data['scope'],
                $data['checked_count'],
                $data['ok_count'],
                $data['mismatch_count'],
                $data['missing_count'],
                json_encode($data['details'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]
        );
        return (int)sqlInsertId();
    }
}
