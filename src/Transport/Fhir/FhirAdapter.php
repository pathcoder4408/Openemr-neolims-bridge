<?php

namespace OpenEMR\Modules\NeoLimsBridge\Transport\Fhir;

use OpenEMR\Modules\NeoLimsBridge\Canonical\CanonicalMessage;
use OpenEMR\Modules\NeoLimsBridge\Transport\TransportAdapterInterface;

final class FhirAdapter implements TransportAdapterInterface
{
    private const SUPPORTED = [
        'ServiceRequest',
        'Specimen',
        'Observation',
        'DiagnosticReport',
        'Procedure',
        'DocumentReference',
        'Provenance',
    ];

    public function normalize(string $raw, ?string $requestedUuid = null): CanonicalMessage
    {
        $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('FHIR body must be a JSON object.');
        }

        $type = trim((string)($payload['resourceType'] ?? ''));
        if (!in_array($type, self::SUPPORTED, true)) {
            throw new \InvalidArgumentException("Unsupported FHIR resourceType: {$type}");
        }

        [$system, $value] = $this->identifier($payload);

        return new CanonicalMessage(
            $type,
            'fhir',
            $system,
            $value,
            $payload,
            $payload['subject']['reference'] ?? null,
            $payload['encounter']['reference'] ?? null,
            $requestedUuid
        );
    }

    private function identifier(array $payload): array
    {
        foreach ((array)($payload['identifier'] ?? []) as $identifier) {
            if (!is_array($identifier)) {
                continue;
            }
            $system = trim((string)($identifier['system'] ?? ''));
            $value = trim((string)($identifier['value'] ?? ''));
            if ($system !== '' && $value !== '') {
                return [$system, $value];
            }
        }
        throw new \InvalidArgumentException('FHIR identifier.system and identifier.value are required.');
    }
}
