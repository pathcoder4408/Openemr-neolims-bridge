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
use OpenEMR\Modules\NeoLimsBridge\Controller\IdentityController;
use OpenEMR\Modules\NeoLimsBridge\Controller\OrderController;
use OpenEMR\Modules\NeoLimsBridge\Controller\ResultController;
use OpenEMR\Modules\NeoLimsBridge\Controller\OperationsController;
use OpenEMR\Modules\NeoLimsBridge\Controller\DocumentController;
use OpenEMR\Modules\NeoLimsBridge\Controller\BillingController;
use OpenEMR\Modules\NeoLimsBridge\Controller\WorkflowController;
use OpenEMR\Modules\NeoLimsBridge\Controller\ProfileController;
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
        $this->eventDispatcher->addListener(GlobalsInitializedEvent::EVENT_HANDLE, $this->addGlobalSettings(...));
        $this->eventDispatcher->addListener(RestApiScopeEvent::EVENT_TYPE_GET_SUPPORTED_SCOPES, $this->registerScopes(...));
        $this->eventDispatcher->addListener(RestApiCreateEvent::EVENT_HANDLE, $this->registerRoutes(...));
        $this->eventDispatcher->addListener(MenuEvent::MENU_UPDATE, $this->addMenu(...));
    }

    public function addGlobalSettings(GlobalsInitializedEvent $event): void
    {
        $service = $event->getGlobalsService();
        $section = xlt('NeoLIMS Hybrid Integration');
        $service->createSection($section, 'Connectors');
        foreach ($this->config->settings() as $key => $definition) {
            $value = OEGlobalsBag::getInstance()->get($key) ?? $definition['default'];
            $service->appendToSection($section, $key, new GlobalSetting(
                xlt($definition['title']), $definition['type'], $value,
                xlt($definition['description']), false
            ));
        }
    }

    public function registerScopes(RestApiScopeEvent $event): void
    {
        if ($event->getApiType() === RestApiScopeEvent::API_TYPE_STANDARD) {
            foreach ([
                ['neolims_capability', 's'], ['neolims_message', 'crus'], ['neolims_hl7', 'cs'],
                ['neolims_patient_sync', 'cs'], ['neolims_encounter_sync', 'cs'],
                ['neolims_identity_link', 'rs'], ['neolims_order_sync', 'crus'],
                ['neolims_order_link', 'rs'], ['neolims_result_sync', 'crus'],
                ['neolims_result_link', 'rs'], ['neolims_document_sync', 'crus'],
                ['neolims_document_link', 'rs'], ['neolims_billing_sync', 'crus'],
                ['neolims_billing_link', 'rs'], ['neolims_workflow', 'crus'],
                ['neolims_workflow_admin', 'uds'], ['neolims_operations', 'crus'],
                ['neolims_dead_letter', 'rus'], ['neolims_profile', 'crus'], ['neolims_profile_admin', 'cus']
            ] as [$resource, $permissions]) {
                $event->addScope('user', $resource, $permissions);
            }
            return;
        }
        if ($event->getApiType() === RestApiScopeEvent::API_TYPE_FHIR) {
            foreach (['ServiceRequest','Specimen','Observation','DiagnosticReport','Procedure','DocumentReference','Provenance'] as $resource) {
                $event->addScope('user', $resource, 'crus');
            }
        }
    }

    public function registerRoutes(RestApiCreateEvent $event): void
    {
        $inbound = new InboundController();
        $capability = new CapabilityController();
        $identity = new IdentityController();
        $orders = new OrderController();
        $results = new ResultController();
        $documents = new DocumentController();
        $billing = new BillingController();
        $workflows = new WorkflowController();
        $operations = new OperationsController();
        $profiles = new ProfileController();

        $event->addToRouteMap('GET /api/neolims/capabilities', static fn($request) => $capability->get($request));
        $event->addToRouteMap('GET /api/neolims/profiles', static fn($request) => $profiles->list($request));
        $event->addToRouteMap('GET /api/neolims/profiles/:key', static fn($key,$request) => $profiles->read($key,$request));
        $event->addToRouteMap('POST /api/neolims/profiles', static fn($request) => $profiles->save($request));
        $event->addToRouteMap('POST /api/neolims/profiles/:key/activate', static fn($key,$request) => $profiles->activate($key,$request));
        $event->addToRouteMap('GET /api/neolims/profile-capabilities', static fn($request) => $profiles->capabilities($request));
        $event->addToRouteMap('POST /api/neolims/patient/resolve', static fn($request) => $identity->resolvePatient($request));
        $event->addToRouteMap('POST /api/neolims/patient/sync', static fn($request) => $identity->syncPatient($request));
        $event->addToRouteMap('POST /api/neolims/encounter/sync', static fn($request) => $identity->syncEncounter($request));
        $event->addToRouteMap('GET /api/neolims/links/patient/:connection/:local', static fn($connection,$local,$request) => $identity->patientLink($connection,$local,$request));

        $event->addToRouteMap('POST /api/neolims/order/validate', static fn($request) => $orders->validate($request));
        $event->addToRouteMap('POST /api/neolims/order/sync', static fn($request) => $orders->sync($request));
        $event->addToRouteMap('GET /api/neolims/links/order/:connection/:local', static fn($connection,$local,$request) => $orders->orderLink($connection,$local,$request));

        $event->addToRouteMap('POST /api/neolims/result/validate', static fn($request) => $results->validate($request));
        $event->addToRouteMap('POST /api/neolims/result/sync', static fn($request) => $results->sync($request));
        $event->addToRouteMap('POST /api/neolims/result/hl7', static fn($request) => $results->hl7($request));
        $event->addToRouteMap('GET /api/neolims/links/result/:connection/:local', static fn($connection,$local,$request) => $results->resultLink($connection,$local,$request));


        $event->addToRouteMap('POST /api/neolims/document/validate', static fn($request) => $documents->validate($request));
        $event->addToRouteMap('POST /api/neolims/document/sync', static fn($request) => $documents->sync($request));
        $event->addToRouteMap('GET /api/neolims/links/document/:connection/:local', static fn($connection,$local,$request) => $documents->link($connection,$local,$request));

        $event->addToRouteMap('POST /api/neolims/billing/validate', static fn($request) => $billing->validate($request));
        $event->addToRouteMap('POST /api/neolims/billing/sync', static fn($request) => $billing->sync($request));
        $event->addToRouteMap('GET /api/neolims/links/billing/:connection/:local', static fn($connection,$local,$request) => $billing->link($connection,$local,$request));

        $event->addToRouteMap('POST /api/neolims/workflows', static fn($request) => $workflows->submit($request));
        $event->addToRouteMap('GET /api/neolims/workflows', static fn($request) => $workflows->list($request));
        $event->addToRouteMap('GET /api/neolims/workflows/:uuid', static fn($uuid,$request) => $workflows->read($uuid,$request));
        $event->addToRouteMap('POST /api/neolims/workflows/:uuid/run', static fn($uuid,$request) => $workflows->run($uuid,$request));
        $event->addToRouteMap('POST /api/neolims/workflows/:uuid/retry', static fn($uuid,$request) => $workflows->retry($uuid,$request));
        $event->addToRouteMap('POST /api/neolims/workflows/:uuid/cancel', static fn($uuid,$request) => $workflows->cancel($uuid,$request));

        $event->addToRouteMap('GET /api/neolims/operations/metrics', static fn($request) => $operations->metrics($request));
        $event->addToRouteMap('POST /api/neolims/operations/reconcile', static fn($request) => $operations->reconcile($request));
        $event->addToRouteMap('GET /api/neolims/operations/reconciliation-runs', static fn($request) => $operations->reconciliationRuns($request));
        $event->addToRouteMap('GET /api/neolims/operations/dead-letters', static fn($request) => $operations->deadLetters($request));
        $event->addToRouteMap('POST /api/neolims/operations/dead-letters/:uuid/replay', static fn($uuid,$request) => $operations->replay($uuid,$request));
        $event->addToRouteMap('POST /api/neolims/operations/dead-letters/:uuid/resolve', static fn($uuid,$request) => $operations->resolve($uuid,$request));
        $event->addToRouteMap('POST /api/neolims/operations/dead-letters/sweep', static fn($request) => $operations->sweep($request));

        $event->addToRouteMap('GET /api/neolims/messages', static fn($request) => $inbound->search($request));
        $event->addToRouteMap('GET /api/neolims/messages/:uuid', static fn($uuid,$request) => $inbound->read($uuid,$request));
        $event->addToRouteMap('POST /api/neolims/messages', static fn($request) => $inbound->standardApi($request));
        $event->addToRouteMap('POST /api/neolims/hl7', static fn($request) => $inbound->hl7($request));

        $event->addToFHIRRouteMap('POST /fhir/ServiceRequest', static fn($request) => $orders->fhir($request));
        $event->addToFHIRRouteMap('PUT /fhir/ServiceRequest/:uuid', static fn($uuid,$request) => $orders->fhir($request,$uuid));
        $event->addToFHIRRouteMap('POST /fhir/Observation', static fn($request) => $results->fhirObservation($request));
        $event->addToFHIRRouteMap('PUT /fhir/Observation/:uuid', static fn($uuid,$request) => $results->fhirObservation($request,$uuid));
        $event->addToFHIRRouteMap('POST /fhir/DiagnosticReport', static fn($request) => $results->fhirDiagnosticReport($request));
        $event->addToFHIRRouteMap('PUT /fhir/DiagnosticReport/:uuid', static fn($uuid,$request) => $results->fhirDiagnosticReport($request,$uuid));

        $event->addToFHIRRouteMap('POST /fhir/DocumentReference', static fn($request) => $documents->fhir($request));
        $event->addToFHIRRouteMap('PUT /fhir/DocumentReference/:uuid', static fn($uuid,$request) => $documents->fhir($request,$uuid));

        foreach (['Specimen','Procedure','Provenance'] as $resource) {
            $event->addToFHIRRouteMap("POST /fhir/{$resource}", static fn($request) => $inbound->fhir($request));
            $event->addToFHIRRouteMap("PUT /fhir/{$resource}/:uuid", static fn($uuid,$request) => $inbound->fhir($request,$uuid));
        }
    }

    public function addMenu(MenuEvent $event): MenuEvent
    {
        if (!$this->config->enabled(GlobalConfig::ENABLE_MENU)) return $event;
        $item = new \stdClass();
        $item->requirement=0; $item->target='mod'; $item->menu_id='neolims_bridge';
        $item->label=xlt('NeoLIMS Integration');
        $item->url='/interface/modules/custom_modules/openemr-neolims-bridge/public/index.php';
        $item->children=[]; $item->acl_req=['admin','super']; $item->global_req=[];
        $menu=$event->getMenu();
        foreach($menu as $parent){ if($parent->menu_id==='mod0'){ $parent->children[]=$item; break; } }
        $event->setMenu($menu); return $event;
    }
}
