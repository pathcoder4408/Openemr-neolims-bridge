<?php

namespace OpenEMR\Modules\NeoLimsBridge\Controller;

use OpenEMR\Modules\NeoLimsBridge\Operations\DeadLetterService;
use OpenEMR\Modules\NeoLimsBridge\Operations\ReconciliationService;
use OpenEMR\Modules\NeoLimsBridge\Repository\OperationsRepository;

final class OperationsController
{
    use ControllerTrait;

    public function metrics($request)
    {
        return $this->guarded(fn() => $this->json((new OperationsRepository())->metrics()));
    }

    public function reconcile($request)
    {
        return $this->guarded(function () use ($request) {
            $payload = $this->body($request);
            return $this->json((new ReconciliationService())->run(
                $payload['connection_key'] ?? null
            ));
        });
    }

    public function reconciliationRuns($request)
    {
        return $this->guarded(fn() => $this->json([
            'data' => (new OperationsRepository())->reconciliationRuns(
                $request->query->getInt('limit', 25)
            ),
        ]));
    }

    public function deadLetters($request)
    {
        return $this->guarded(fn() => $this->json([
            'data' => (new OperationsRepository())->deadLetters(
                $request->query->getInt('limit', 50),
                (string)$request->query->get('status', 'open')
            ),
        ]));
    }

    public function replay(string $uuid, $request)
    {
        return $this->guarded(fn() => $this->json((new DeadLetterService())->replay($uuid)));
    }

    public function resolve(string $uuid, $request)
    {
        return $this->guarded(function () use ($uuid, $request) {
            $payload = $this->body($request);
            return $this->json((new DeadLetterService())->resolve(
                $uuid,
                trim((string)($payload['note'] ?? 'Resolved manually'))
            ));
        });
    }

    public function sweep($request)
    {
        return $this->guarded(fn() => $this->json([
            'moved_to_dead_letter' => (new DeadLetterService())->sweep(),
        ]));
    }

    private function body($request): array
    {
        $raw = $this->rawBody($request);
        if (trim($raw) === '') return [];
        $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Request body must be a JSON object.');
        }
        return $payload;
    }
}
