<?php

namespace OpenEMR\Modules\NeoLimsBridge\Controller;

use OpenEMR\RestControllers\RestControllerHelper;
use Symfony\Component\HttpFoundation\Response;

trait ControllerTrait
{
    private function json(array $payload, int $status = Response::HTTP_OK)
    {
        return RestControllerHelper::responseHandler($payload, null, $status);
    }

    private function rawBody($request): string
    {
        $raw = (string)$request->getContent();
        return $raw !== '' ? $raw : (string)file_get_contents('php://input');
    }

    private function guarded(callable $callback)
    {
        try {
            return $callback();
        } catch (\InvalidArgumentException|\JsonException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            error_log('NeoLIMS bridge: ' . $e->getMessage());
            return $this->json(
                ['error' => 'NeoLIMS bridge request failed', 'detail' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
