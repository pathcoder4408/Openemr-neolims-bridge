<?php

namespace OpenEMR\Modules\NeoLimsBridge\Transport\Hl7;

use OpenEMR\Modules\NeoLimsBridge\Canonical\CanonicalMessage;
use OpenEMR\Modules\NeoLimsBridge\Transport\TransportAdapterInterface;

final class Hl7Adapter implements TransportAdapterInterface
{
    public function normalize(string $raw, ?string $requestedUuid = null): CanonicalMessage
    {
        $raw = str_replace(["\r\n", "\n"], "\r", trim($raw));
        $segments = array_values(array_filter(explode("\r", $raw)));
        if ($segments === [] || !str_starts_with($segments[0], 'MSH|')) {
            throw new \InvalidArgumentException('HL7 message must start with MSH.');
        }

        $parsed = [];
        foreach ($segments as $segment) {
            $fields = explode('|', $segment);
            $name = array_shift($fields);
            $parsed[$name][] = $fields;
        }

        $msh = $parsed['MSH'][0] ?? [];
        $messageControlId = trim((string)($msh[8] ?? ''));
        $messageType = trim((string)($msh[7] ?? ''));
        if ($messageControlId === '') {
            throw new \InvalidArgumentException('MSH-10 message control ID is required.');
        }

        $patientId = $parsed['PID'][0][2] ?? null;
        $orderNumber = $parsed['ORC'][0][1] ?? ($parsed['OBR'][0][1] ?? null);

        return new CanonicalMessage(
            $messageType !== '' ? $messageType : 'HL7',
            'hl7',
            'urn:hl7v2:message-control-id',
            $messageControlId,
            [
                'hl7_message_type' => $messageType,
                'message_control_id' => $messageControlId,
                'patient_id' => $patientId,
                'order_number' => $orderNumber,
                'segments' => $parsed,
                'raw' => $raw,
            ],
            $patientId ? 'Patient/' . $patientId : null,
            null,
            $requestedUuid
        );
    }

    public function ack(string $controlId, bool $accepted, string $text = ''): string
    {
        $code = $accepted ? 'AA' : 'AE';
        $timestamp = date('YmdHis');
        return "MSH|^~\\&|OPENEMR|OPENEMR|NEOLIMS|NEOLIMS|{$timestamp}||ACK|ACK-{$controlId}|P|2.5\r"
            . "MSA|{$code}|{$controlId}|{$text}\r";
    }
}
