<?php

namespace OpenEMR\Modules\NeoLimsBridge\Transport\StandardApi;

use OpenEMR\Modules\NeoLimsBridge\Canonical\CanonicalMessage;
use OpenEMR\Modules\NeoLimsBridge\Transport\TransportAdapterInterface;

final class StandardApiAdapter implements TransportAdapterInterface
{
    public function normalize(string $raw, ?string $requestedUuid = null): CanonicalMessage
    {
        $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('API body must be a JSON object.');
        }

        $type = trim((string)($payload['message_type'] ?? $payload['resource_type'] ?? ''));
        $system = trim((string)($payload['identifier_system'] ?? ''));
        $value = trim((string)($payload['identifier_value'] ?? ''));

        if ($type === '' || $system === '' || $value === '') {
            throw new \InvalidArgumentException(
                'message_type, identifier_system, and identifier_value are required.'
            );
        }

        return new CanonicalMessage(
            $type,
            'standard_api',
            $system,
            $value,
            $payload,
            $payload['patient_reference'] ?? null,
            $payload['encounter_reference'] ?? null,
            $requestedUuid
        );
    }
}
