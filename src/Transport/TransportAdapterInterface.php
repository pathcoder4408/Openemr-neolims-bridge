<?php

namespace OpenEMR\Modules\NeoLimsBridge\Transport;

use OpenEMR\Modules\NeoLimsBridge\Canonical\CanonicalMessage;

interface TransportAdapterInterface
{
    public function normalize(string $raw, ?string $requestedUuid = null): CanonicalMessage;
}
