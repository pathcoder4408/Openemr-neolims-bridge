<?php

namespace OpenEMR\Modules\NeoLimsBridge\Controller;

use OpenEMR\Modules\NeoLimsBridge\Orchestration\WorkflowService;
use OpenEMR\Modules\NeoLimsBridge\Repository\WorkflowRepository;
use Symfony\Component\HttpFoundation\Response;

final class WorkflowController
{
    use ControllerTrait;

    public function submit($request)
    {
        return $this->guarded(function () use ($request) {
            $payload = json_decode($this->rawBody($request), true, 512, JSON_THROW_ON_ERROR);
            $row = (new WorkflowService())->submit($payload);
            return $this->json($row, Response::HTTP_ACCEPTED);
        });
    }

    public function run(string $uuid, $request)
    {
        return $this->guarded(fn() => $this->json((new WorkflowService())->process($uuid)));
    }

    public function read(string $uuid, $request)
    {
        return $this->guarded(function () use ($uuid) {
            $repo = new WorkflowRepository();
            $row = $repo->findByUuid($uuid);
            if (!$row) {
                return $this->json(['error' => 'Workflow not found'], Response::HTTP_NOT_FOUND);
            }
            $row['events'] = $repo->events($uuid);
            return $this->json($row);
        });
    }

    public function list($request)
    {
        return $this->guarded(fn() => $this->json([
            'data' => (new WorkflowRepository())->list(
                $request->query->getInt('limit', 50),
                $request->query->get('status')
            ),
        ]));
    }

    public function retry(string $uuid, $request)
    {
        return $this->guarded(fn() => $this->json((new WorkflowRepository())->retry($uuid) ?? []));
    }

    public function cancel(string $uuid, $request)
    {
        return $this->guarded(fn() => $this->json((new WorkflowRepository())->cancel($uuid) ?? []));
    }
}
