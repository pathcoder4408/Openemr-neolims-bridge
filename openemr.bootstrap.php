<?php

namespace OpenEMR\Modules\NeoLimsBridge;

use OpenEMR\Core\OEGlobalsBag;

/** @var \OpenEMR\Core\ModulesClassLoader $classLoader */
$classLoader->registerNamespaceIfNotExists(
    'OpenEMR\\Modules\\NeoLimsBridge\\',
    __DIR__ . DIRECTORY_SEPARATOR . 'src'
);

/** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher */
$bootstrap = new Bootstrap(
    $eventDispatcher,
    OEGlobalsBag::getInstance()->getKernel()
);
$bootstrap->subscribeToEvents();
