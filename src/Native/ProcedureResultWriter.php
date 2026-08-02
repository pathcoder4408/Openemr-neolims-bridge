<?php

namespace OpenEMR\Modules\NeoLimsBridge\Native;

use OpenEMR\Modules\NeoLimsBridge\Canonical\CanonicalMessage;

final class ProcedureResultWriter implements NativeWriterInterface
{
    public function supports(CanonicalMessage $message): bool
    {
        return in_array(
            $message->messageType,
            ['Observation', 'DiagnosticReport', 'ORU^R01'],
            true
        );
    }

    public function write(CanonicalMessage $message): array
    {
        throw new \LogicException(
            'Native procedure_result/report writes are feature-gated pending validated OpenEMR 8.2 mappings.'
        );
    }
}
