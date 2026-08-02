<?php

namespace OpenEMR\Modules\NeoLimsBridge;

final class GlobalConfig
{
    public const ENABLE_MENU = 'neolims_bridge_enable_menu';
    public const ENABLE_FHIR = 'neolims_bridge_enable_fhir';
    public const ENABLE_STANDARD_API = 'neolims_bridge_enable_standard_api';
    public const ENABLE_HL7 = 'neolims_bridge_enable_hl7';
    public const ENABLE_NATIVE_WRITES = 'neolims_bridge_enable_native_writes';
    public const ENABLE_BILLING_WRITES = 'neolims_bridge_enable_billing_writes';
    public const STORE_RAW_PAYLOADS = 'neolims_bridge_store_raw_payloads';
    public const REQUIRE_IDENTIFIER = 'neolims_bridge_require_identifier';
    public const SHARED_SECRET = 'neolims_bridge_shared_secret';

    public function __construct(private array $globals)
    {
    }

    public function settings(): array
    {
        return [
            self::ENABLE_MENU => [
                'title' => 'Enable NeoLIMS bridge menu',
                'type' => 'bool',
                'default' => '1',
                'description' => 'Display diagnostics and queue pages.',
            ],
            self::ENABLE_FHIR => [
                'title' => 'Enable NeoLIMS FHIR routes',
                'type' => 'bool',
                'default' => '1',
                'description' => 'Enable FHIR create and update transport routes.',
            ],
            self::ENABLE_STANDARD_API => [
                'title' => 'Enable NeoLIMS Standard API routes',
                'type' => 'bool',
                'default' => '1',
                'description' => 'Enable OpenEMR Standard API transport routes.',
            ],
            self::ENABLE_HL7 => [
                'title' => 'Enable NeoLIMS HL7 v2 routes',
                'type' => 'bool',
                'default' => '1',
                'description' => 'Enable ORM and ORU inbound routes.',
            ],
            self::ENABLE_NATIVE_WRITES => [
                'title' => 'Enable native OpenEMR writes',
                'type' => 'bool',
                'default' => '0',
                'description' => 'Keep disabled until native adapters pass integration tests.',
            ],
            self::ENABLE_BILLING_WRITES => [
                'title' => 'Enable billing writes',
                'type' => 'bool',
                'default' => '0',
                'description' => 'Requires native writes and validated billing mappings.',
            ],
            self::STORE_RAW_PAYLOADS => [
                'title' => 'Store raw inbound payloads',
                'type' => 'bool',
                'default' => '1',
                'description' => 'Retain raw FHIR, API, or HL7 payloads for troubleshooting.',
            ],
            self::REQUIRE_IDENTIFIER => [
                'title' => 'Require stable external identifier',
                'type' => 'bool',
                'default' => '1',
                'description' => 'Reject messages without an idempotency identifier.',
            ],
            self::SHARED_SECRET => [
                'title' => 'HL7/API shared signing secret',
                'type' => 'text',
                'default' => '',
                'description' => 'Optional HMAC secret for direct inbound transport calls.',
            ],
        ];
    }

    public function enabled(string $key): bool
    {
        return !empty($this->globals[$key]);
    }
}
