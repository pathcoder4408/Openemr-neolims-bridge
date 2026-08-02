<?php

namespace OpenEMR\Modules\NeoLimsBridge;

use OpenEMR\Core\Kernel;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Events\RestApiExtend\RestApiCreateEvent;
use OpenEMR\Events\RestApiExtend\RestApiScopeEvent;
use OpenEMR\Menu\MenuEvent;
use OpenEMR\Modules\NeoLimsBridge\Controller\CapabilityController;
use OpenEMR\Modules\NeoLimsBridge\Controller\InboundController;
use OpenEMR\Services\Globals\GlobalSetting;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class Bootstrap
{
    private GlobalConfig $config;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ?Kernel $kernel = null
    ) {
        $this->config = new GlobalConfig($GLOBALS);
    }

    public function subscribeToEvents(): void
    {
        $this->eventDispatcher->addListener(
            GlobalsInitializedEvent::EVENT_HANDLE,
            $this->addGlobalSettings(...)
        );
        $this->eventDispatcher->addListener(
            RestApiScopeEvent::EVENT_TYPE_GET_SUPPORTED_SCOPES,
            $this->registerScopes(...)
        );
        $this->eventDispatcher->addListener(
            RestApiCreateEvent::EVENT_HANDLE,
            $this->registerRoutes(...)
        );
        $this->eventDispatcher->addListener(
            MenuEvent::MENU_UPDATE,
            $this->addMenu(...)
        );
    }

    public function addGlobalSettings(GlobalsInitializedEvent $event): void
    {
        $service = $event->getGlobalsService();
        $section = xlt('NeoLIMS Hybrid Integration');
        $service->createSection($section, 'Connectors');

        foreach ($this->config->settings() as $key => $definition) {
            $value = OEGlobalsBag::getInstance()->get($key) ?? $definition['default'];
            $service->appendToSection(
                $section,
                $key,
                new GlobalSetting(
                    xlt($definition['title']),
                    $definition['type'],
                    $value,
                    xlt($definition['description']),
                    false
                )
            );
        }
    }

    public function registerScopes(RestApiScopeEvent $event): void
    {
        if ($event->getApiType() === RestApiScopeEvent::API_TYPE_STANDARD) {
            $event->addScope('user', 'neolims_capability', 's');
            $event->addScope('user', 'neolims_message', 'crus');
            $event->addScope('user', 'neolims_hl7', 'cs');
            return;
        }

        if ($event->getApiType() === RestApiScopeEvent::API_TYPE_FHIR) {
            foreach ([
                'ServiceRequest',
                'Specimen',
                'Observation',
                'DiagnosticReport',
                'Procedure',
                'DocumentReference',
                'Provenance',
            ] as $resource) {
                $event->addScope('user', $resource, 'crus');
            }
        }
    }

    public function registerRoutes(RestApiCreateEvent $event): void
    {
        $controller = new InboundController();
        $capability = new CapabilityController();

        $event->addToRouteMap(
            'GET /api/neolims/capabilities',
            static fn($request) => $capability->get($request)
        );
        $event->addToRouteMap(
            'GET /api/neolims/messages',
            static fn($request) => $controller->search($request)
        );
        $event->addToRouteMap(
            'GET /api/neolims/messages/:uuid',
            static fn($uuid, $request) => $controller->read($uuid, $request)
        );
        $event->addToRouteMap(
            'POST /api/neolims/messages',
            static fn($request) => $controller->standardApi($request)
        );
        $event->addToRouteMap(
            'POST /api/neolims/hl7',
            static fn($request) => $controller->hl7($request)
        );

        foreach ([
            'ServiceRequest',
            'Specimen',
            'Observation',
            'DiagnosticReport',
            'Procedure',
            'DocumentReference',
            'Provenance',
        ] as $resource) {
            $event->addToFHIRRouteMap(
                "POST /fhir/{$resource}",
                static fn($request) => $controller->fhir($request)
            );
            $event->addToFHIRRouteMap(
                "PUT /fhir/{$resource}/:uuid",
                static fn($uuid, $request) => $controller->fhir($request, $uuid)
            );
        }
    }

    public function addMenu(MenuEvent $event): MenuEvent
    {
        if (!$this->config->enabled(GlobalConfig::ENABLE_MENU)) {
            return $event;
        }

        $item = new \stdClass();
        $item->requirement = 0;
        $item->target = 'mod';
        $item->menu_id = 'neolims_bridge';
        $item->label = xlt('NeoLIMS Integration');
        $item->url = '/interface/modules/custom_modules/openemr-neolims-bridge/public/index.php';
        $item->children = [];
        $item->acl_req = ['admin', 'super'];
        $item->global_req = [];

        $menu = $event->getMenu();
        foreach ($menu as $parent) {
            if ($parent->menu_id === 'mod0') {
                $parent->children[] = $item;
                break;
            }
        }
        $event->setMenu($menu);
        return $event;
    }
}
