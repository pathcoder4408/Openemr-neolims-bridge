<?php

namespace OpenEMR\Modules\NeoLimsBridge\Canonical;

final class CanonicalMessage
{
    public function __construct(
        public readonly string $messageType,
        public readonly string $transport,
        public readonly string $identifierSystem,
        public readonly string $identifierValue,
        public readonly array $payload,
        public readonly ?string $patientReference = null,
        public readonly ?string $encounterReference = null,
        public readonly ?string $requestedUuid = null
    ) {
    }

    public function canonicalJson(): string
    {
        $payload = $this->payload;
        self::sortRecursive($payload);
        return json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    private static function sortRecursive(array &$value): void
    {
        ksort($value);
        foreach ($value as &$item) {
            if (is_array($item)) {
                self::sortRecursive($item);
            }
        }
    }
}
