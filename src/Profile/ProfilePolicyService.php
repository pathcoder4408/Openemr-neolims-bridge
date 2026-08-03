<?php

namespace OpenEMR\Modules\NeoLimsBridge\Profile;

final class ProfilePolicyService
{
    public function active(?string $connectionKey = null): array
    {
        $profile = (new ProfileRepository())->findActive($connectionKey);
        if (!$profile) {
            throw new \RuntimeException('No enabled NeoLIMS installation profile is configured.');
        }
        return $profile;
    }

    public function assertAllowed(string $resource, string $operation, ?string $connectionKey = null): array
    {
        $profile = $this->active($connectionKey);
        $definition = $profile['resources'][$resource] ?? null;
        if (!$definition || ($definition['mode'] ?? 'disabled') === 'disabled' || empty($definition['receive_enabled'])) {
            throw new \InvalidArgumentException(
                sprintf('Resource %s is disabled for installation profile %s.', $resource, $profile['profile_key'])
            );
        }
        $operations = (array)($definition['operations'] ?? []);
        if (!in_array('*', $operations, true) && !in_array($operation, $operations, true)) {
            throw new \InvalidArgumentException(
                sprintf('Operation %s is not allowed for resource %s in profile %s.', $operation, $resource, $profile['profile_key'])
            );
        }
        return $profile;
    }

    public function capabilities(?string $connectionKey = null): array
    {
        $profile = $this->active($connectionKey);
        $receive = $send = $disabled = [];
        foreach ($profile['resources'] as $name => $definition) {
            if (($definition['mode'] ?? 'disabled') === 'disabled') {
                $disabled[] = $name;
                continue;
            }
            if (!empty($definition['receive_enabled'])) $receive[$name] = $definition['operations'];
            if (!empty($definition['send_enabled'])) $send[$name] = $definition['operations'];
        }
        return [
            'profile_key' => $profile['profile_key'],
            'display_name' => $profile['display_name'],
            'connection_key' => $profile['connection_key'],
            'default_direction' => $profile['default_direction'],
            'receive' => $receive,
            'send' => $send,
            'disabled' => $disabled,
            'mappings' => $profile['mappings'],
        ];
    }
}
