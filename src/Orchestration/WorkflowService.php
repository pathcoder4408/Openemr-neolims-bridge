<?php

namespace OpenEMR\Modules\NeoLimsBridge\Orchestration;

use OpenEMR\Modules\NeoLimsBridge\Profile\ProfilePolicyService;
use OpenEMR\Modules\NeoLimsBridge\Repository\WorkflowRepository;
use OpenEMR\Modules\NeoLimsBridge\Service\BillingSyncService;
use OpenEMR\Modules\NeoLimsBridge\Service\DocumentSyncService;
use OpenEMR\Modules\NeoLimsBridge\Service\IdentitySyncService;
use OpenEMR\Modules\NeoLimsBridge\Service\ProcedureOrderSyncService;
use OpenEMR\Modules\NeoLimsBridge\Service\ProcedureResultSyncService;

final class WorkflowService
{
    public function submit(array $payload): array
    {
        $connection = trim((string)($payload['connection_key'] ?? ''));
        $externalId = trim((string)($payload['external_id'] ?? ''));
        if ($connection === '' || $externalId === '') {
            throw new \InvalidArgumentException('connection_key and external_id are required.');
        }

        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return (new WorkflowRepository())->create([
            'connection_key' => $connection,
            'external_id' => $externalId,
            'accession_number' => (string)($payload['accession_number'] ?? ''),
            'payload_json' => $json,
            'payload_hash' => hash('sha256', $json),
            'max_attempts' => max(1, min(20, (int)($payload['max_attempts'] ?? 5))),
        ]);
    }

    public function process(string $uuid): array
    {
        $repo = new WorkflowRepository();
        $row = $repo->findByUuid($uuid);
        if (!$row) {
            throw new \InvalidArgumentException('Workflow not found.');
        }

        $payload = json_decode((string)$row['payload_json'], true, 512, JSON_THROW_ON_ERROR);
        $dryRun = !empty($payload['dry_run']);
        $result = [];
        $policy = new ProfilePolicyService();
        $connectionKey = (string)($payload['connection_key'] ?? $row['connection_key']);

        try {
            if (!empty($payload['patient'])) {
                $policy->assertAllowed('patient', 'sync', $connectionKey);
                $repo->step($uuid, 'patient');
                $patientRequest = $payload['patient'];
                $patientRequest['dry_run'] = $dryRun;
                $result['patient'] = (new IdentitySyncService())->syncPatient($patientRequest);
                $this->assertStep($result['patient'], 'patient');
            }

            if (!empty($payload['encounter'])) {
                $policy->assertAllowed('encounter', 'sync', $connectionKey);
                $repo->step($uuid, 'encounter');
                $encounterRequest = $payload['encounter'];
                $encounterRequest['dry_run'] = $dryRun;
                $result['encounter'] = (new IdentitySyncService())->syncEncounter($encounterRequest);
                $this->assertStep($result['encounter'], 'encounter');
            }

            if (!empty($payload['order'])) {
                $policy->assertAllowed('order', 'sync', $connectionKey);
                $repo->step($uuid, 'order');
                $orderRequest = $payload['order'];
                $orderRequest['dry_run'] = $dryRun;
                $result['order'] = (new ProcedureOrderSyncService())->sync($orderRequest);
                $this->assertStep($result['order'], 'order');
            }

            if (!empty($payload['result'])) {
                $policy->assertAllowed('result', 'sync', $connectionKey);
                $repo->step($uuid, 'result');
                $resultRequest = $payload['result'];
                $resultRequest['dry_run'] = $dryRun;
                $result['result'] = (new ProcedureResultSyncService())->sync($resultRequest);
                $this->assertStep($result['result'], 'result');
            }

            foreach ((array)($payload['documents'] ?? []) as $index => $documentRequest) {
                $policy->assertAllowed('document', 'sync', $connectionKey);
                $repo->step($uuid, 'document:' . $index);
                $documentRequest['dry_run'] = $dryRun;
                $result['documents'][$index] = (new DocumentSyncService())->sync($documentRequest);
                $this->assertStep($result['documents'][$index], 'document');
            }

            foreach ((array)($payload['charges'] ?? []) as $index => $chargeRequest) {
                $policy->assertAllowed('billing', 'sync', $connectionKey);
                $repo->step($uuid, 'billing:' . $index);
                $chargeRequest['dry_run'] = $dryRun;
                $result['charges'][$index] = (new BillingSyncService())->sync($chargeRequest);
                $this->assertStep($result['charges'][$index], 'billing');
            }

            $result['acknowledgment'] = [
                'code' => 'AA',
                'workflow_uuid' => $uuid,
                'external_id' => $row['external_id'],
                'status' => $dryRun ? 'validated' : 'completed',
                'timestamp_utc' => gmdate('c'),
            ];
            $repo->complete($uuid, $result);
            return $repo->findByUuid($uuid) + ['result' => $result];
        } catch (\Throwable $e) {
            $delay = min(3600, 30 * (2 ** max(0, ((int)$row['attempts']) - 1)));
            $repo->fail($uuid, $e->getMessage(), $delay);
            throw $e;
        }
    }

    public function processNext(): ?array
    {
        $repo = new WorkflowRepository();
        $row = $repo->claimNext();
        return $row ? $this->process($row['workflow_uuid']) : null;
    }

    private function assertStep(array $result, string $step): void
    {
        if (isset($result['valid']) && $result['valid'] === false) {
            throw new \RuntimeException($step . ' validation failed: ' . json_encode($result['errors'] ?? []));
        }
        if (($result['resolution'] ?? '') === 'conflict') {
            throw new \RuntimeException($step . ' resolution conflict.');
        }
    }
}
