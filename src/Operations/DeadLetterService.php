<?php

namespace OpenEMR\Modules\NeoLimsBridge\Operations;

use OpenEMR\Modules\NeoLimsBridge\Repository\OperationsRepository;
use OpenEMR\Modules\NeoLimsBridge\Repository\WorkflowRepository;

final class DeadLetterService
{
    public function sweep(): int
    {
        $repo = new WorkflowRepository();
        $ops = new OperationsRepository();
        $count = 0;

        foreach ($repo->list(200, 'failed') as $workflow) {
            if ((int)$workflow['attempts'] >= (int)$workflow['max_attempts']) {
                $ops->addDeadLetter($workflow);
                sqlStatement(
                    "UPDATE neolims_bridge_workflow
                        SET status='dead_letter', current_step='dead_letter', updated_at=NOW()
                      WHERE workflow_uuid=?",
                    [$workflow['workflow_uuid']]
                );
                $repo->event($workflow['workflow_uuid'], 'dead_letter', 'Moved to dead-letter queue');
                $count++;
            }
        }
        return $count;
    }

    public function replay(string $uuid): array
    {
        $ops = new OperationsRepository();
        $row = $ops->deadLetter($uuid);
        if (!$row) {
            throw new \InvalidArgumentException('Dead-letter workflow not found.');
        }

        sqlStatement(
            "UPDATE neolims_bridge_workflow
                SET status='retry', current_step='replay', attempts=0,
                    next_attempt_at=NOW(), last_error=NULL, failed_at=NULL, updated_at=NOW()
              WHERE workflow_uuid=?",
            [$uuid]
        );
        $ops->markDeadLetter($uuid, 'replayed', 'Replay requested');
        (new WorkflowRepository())->event($uuid, 'replay', 'Dead-letter replay requested');
        return (new WorkflowRepository())->findByUuid($uuid) ?: [];
    }

    public function resolve(string $uuid, string $note): array
    {
        $ops = new OperationsRepository();
        if (!$ops->deadLetter($uuid)) {
            throw new \InvalidArgumentException('Dead-letter workflow not found.');
        }
        $ops->markDeadLetter($uuid, 'resolved', $note);
        return $ops->deadLetter($uuid) ?: [];
    }
}
