<?php

namespace OpenEMR\Modules\NeoLimsBridge\Controller;

use OpenEMR\Modules\NeoLimsBridge\Repository\MessageRepository;
use OpenEMR\Modules\NeoLimsBridge\Service\InboundService;
use OpenEMR\Modules\NeoLimsBridge\Transport\Fhir\FhirAdapter;
use OpenEMR\Modules\NeoLimsBridge\Transport\Hl7\Hl7Adapter;
use OpenEMR\Modules\NeoLimsBridge\Transport\StandardApi\StandardApiAdapter;
use Symfony\Component\HttpFoundation\Response;

final class InboundController
{
    use ControllerTrait;

    public function fhir($request, ?string $uuid = null)
    {
        return $this->guarded(function () use ($request, $uuid) {
            $result = (new InboundService())->receive(
                new FhirAdapter(),
                $this->rawBody($request),
                $uuid
            );
            return $this->json($result, $result['created'] ? 201 : 200);
        });
    }

    public function standardApi($request)
    {
        return $this->guarded(function () use ($request) {
            $result = (new InboundService())->receive(
                new StandardApiAdapter(),
                $this->rawBody($request)
            );
            return $this->json($result, $result['created'] ? 201 : 200);
        });
    }

    public function hl7($request)
    {
        return $this->guarded(function () use ($request) {
            $raw = $this->rawBody($request);
            $adapter = new Hl7Adapter();
            $result = (new InboundService())->receive($adapter, $raw);
            $controlId = $result['message_uuid'];
            return $this->json([
                'result' => $result,
                'ack' => $adapter->ack($controlId, true, 'Accepted and queued'),
            ], 201);
        });
    }

    public function read(string $uuid, $request)
    {
        return $this->guarded(function () use ($uuid) {
            $row = (new MessageRepository())->findByUuid($uuid);
            return $row
                ? $this->json($row)
                : $this->json(['error' => 'Message not found'], Response::HTTP_NOT_FOUND);
        });
    }

    public function search($request)
    {
        return $this->guarded(function () use ($request) {
            return $this->json([
                'data' => (new MessageRepository())->search($request->query->getInt('limit', 50)),
            ]);
        });
    }
}
