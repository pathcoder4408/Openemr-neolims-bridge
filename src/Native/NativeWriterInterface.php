<?php

namespace OpenEMR\Modules\NeoLimsBridge\Native;

use OpenEMR\Modules\NeoLimsBridge\Canonical\CanonicalMessage;

interface NativeWriterInterface
{
    public function supports(CanonicalMessage $message): bool;
    public function write(CanonicalMessage $message): array;
}
