<?php

namespace OpenEMR\Modules\NeoLimsBridge\Controller;

use OpenEMR\Modules\NeoLimsBridge\Profile\ProfilePolicyService;
use OpenEMR\Modules\NeoLimsBridge\Repository\MessageRepository;

final class CapabilityController
{
    use ControllerTrait;

    public function get($request)
    {
        return $this->guarded(function() use ($request) {
            $connection = (string)$request->query->get('connection_key', '');
            return $this->json([
                'name' => 'OpenEMR NeoLIMS Hybrid Bridge',
                'version' => '0.11.0',
                'openemr_target' => '8.2',
                'database_ready' => (new MessageRepository())->installed(),
                'installation_profile' => (new ProfilePolicyService())->capabilities($connection ?: null),
                'features' => [
                    'installation_profiles', 'resource_direction_control', 'profile_enforcement',
                    'capability_negotiation', 'idempotent_upsert', 'audit_history',
                    'workflow_orchestration', 'reconciliation', 'dead_letter_replay',
                ],
            ]);
        });
    }
}
