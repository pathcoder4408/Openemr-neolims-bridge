<?php

namespace OpenEMR\Modules\NeoLimsBridge\Service;

use OpenEMR\Modules\NeoLimsBridge\Repository\MessageRepository;
use OpenEMR\Modules\NeoLimsBridge\Transport\TransportAdapterInterface;

final class InboundService
{
    public function receive(
        TransportAdapterInterface $adapter,
        string $raw,
        ?string $requestedUuid = null
    ): array {
        $message = $adapter->normalize($raw, $requestedUuid);
        $stored = (new MessageRepository())->store($message, $raw);

        return [
            'created' => $stored['created'],
            'unchanged' => $stored['unchanged'],
            'message_uuid' => $stored['row']['message_uuid'],
            'message_type' => $stored['row']['message_type'],
            'transport' => $stored['row']['transport'],
            'status' => $stored['row']['status'],
            'native_write' => false,
        ];
    }
}
