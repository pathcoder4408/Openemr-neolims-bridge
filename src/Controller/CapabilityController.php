<?php

namespace OpenEMR\Modules\NeoLimsBridge\Controller;

use OpenEMR\Modules\NeoLimsBridge\Repository\MessageRepository;

final class CapabilityController
{
    use ControllerTrait;

    public function get($request)
    {
        return $this->json([
            'name' => 'OpenEMR NeoLIMS Hybrid Bridge',
            'version' => '0.2.0',
            'openemr_target' => '8.2',
            'transports' => [
                'fhir' => [
                    'enabled' => true,
                    'write_resources' => [
                        'ServiceRequest',
                        'Specimen',
                        'Observation',
                        'DiagnosticReport',
                        'Procedure',
                        'DocumentReference',
                        'Provenance',
                    ],
                ],
                'standard_api' => ['enabled' => true],
                'hl7_v2' => [
                    'enabled' => true,
                    'messages' => ['ORM^O01', 'ORU^R01', 'ACK'],
                ],
            ],
            'features' => [
                'canonical_message_model',
                'idempotent_upsert',
                'payload_hashing',
                'audit_history',
                'transport_fallback',
                'raw_payload_retention',
            ],
            'native_adapters' => [
                'procedure_order' => 'planned',
                'procedure_result' => 'planned',
                'procedure_report' => 'planned',
                'documents' => 'planned',
                'billing' => 'planned',
            ],
            'database_ready' => (new MessageRepository())->installed(),
        ]);
    }
}
