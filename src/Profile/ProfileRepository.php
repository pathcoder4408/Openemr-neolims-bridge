<?php

namespace OpenEMR\Modules\NeoLimsBridge\Profile;

final class ProfileRepository
{
    public function findActive(?string $connectionKey = null): ?array
    {
        if ($connectionKey !== null && $connectionKey !== '') {
            $row = sqlQuery(
                'SELECT * FROM neolims_bridge_profile WHERE enabled = 1 AND connection_key = ? LIMIT 1',
                [$connectionKey]
            );
            if ($row) {
                return $this->hydrate($row);
            }
        }

        $row = sqlQuery(
            'SELECT * FROM neolims_bridge_profile WHERE enabled = 1 AND is_default = 1 ORDER BY id LIMIT 1'
        );
        return $row ? $this->hydrate($row) : null;
    }

    public function findByKey(string $profileKey): ?array
    {
        $row = sqlQuery('SELECT * FROM neolims_bridge_profile WHERE profile_key = ? LIMIT 1', [$profileKey]);
        return $row ? $this->hydrate($row) : null;
    }

    public function list(): array
    {
        $statement = sqlStatement('SELECT * FROM neolims_bridge_profile ORDER BY is_default DESC, display_name');
        $rows = [];
        while ($row = sqlFetchArray($statement)) {
            $rows[] = $this->hydrate($row);
        }
        return $rows;
    }

    public function resources(int $profileId): array
    {
        $statement = sqlStatement(
            'SELECT * FROM neolims_bridge_profile_resource WHERE profile_id = ? ORDER BY resource_name',
            [$profileId]
        );
        $rows = [];
        while ($row = sqlFetchArray($statement)) {
            $rows[$row['resource_name']] = $this->hydrateResource($row);
        }
        return $rows;
    }

    public function mappings(int $profileId): array
    {
        $statement = sqlStatement(
            'SELECT mapping_key, mapping_value FROM neolims_bridge_profile_mapping WHERE profile_id = ? ORDER BY mapping_key',
            [$profileId]
        );
        $rows = [];
        while ($row = sqlFetchArray($statement)) {
            $rows[$row['mapping_key']] = $row['mapping_value'];
        }
        return $rows;
    }

    public function upsert(array $profile): array
    {
        $key = trim((string)($profile['profile_key'] ?? ''));
        $display = trim((string)($profile['display_name'] ?? ''));
        $connection = trim((string)($profile['connection_key'] ?? ''));
        if ($key === '' || $display === '' || $connection === '') {
            throw new \InvalidArgumentException('profile_key, display_name, and connection_key are required.');
        }

        if (!empty($profile['is_default'])) {
            sqlStatement('UPDATE neolims_bridge_profile SET is_default = 0');
        }

        sqlStatement(
            'INSERT INTO neolims_bridge_profile
                (profile_key, display_name, connection_key, enabled, is_default, default_direction, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                display_name = VALUES(display_name), connection_key = VALUES(connection_key),
                enabled = VALUES(enabled), is_default = VALUES(is_default),
                default_direction = VALUES(default_direction), updated_at = NOW()',
            [
                $key, $display, $connection,
                !empty($profile['enabled']) ? 1 : 0,
                !empty($profile['is_default']) ? 1 : 0,
                (string)($profile['default_direction'] ?? 'receive'),
            ]
        );

        $stored = $this->findByKey($key);
        if (!$stored) {
            throw new \RuntimeException('Profile could not be saved.');
        }
        $id = (int)$stored['id'];

        foreach ((array)($profile['resources'] ?? []) as $resource => $definition) {
            if (!is_array($definition)) continue;
            sqlStatement(
                'INSERT INTO neolims_bridge_profile_resource
                    (profile_id, resource_name, mode, receive_enabled, send_enabled,
                     operations_json, transports_json, config_json, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    mode = VALUES(mode), receive_enabled = VALUES(receive_enabled),
                    send_enabled = VALUES(send_enabled), operations_json = VALUES(operations_json),
                    transports_json = VALUES(transports_json), config_json = VALUES(config_json),
                    updated_at = NOW()',
                [
                    $id, $resource, (string)($definition['mode'] ?? 'disabled'),
                    !empty($definition['receive_enabled']) ? 1 : 0,
                    !empty($definition['send_enabled']) ? 1 : 0,
                    json_encode(array_values((array)($definition['operations'] ?? [])), JSON_THROW_ON_ERROR),
                    json_encode(array_values((array)($definition['transports'] ?? [])), JSON_THROW_ON_ERROR),
                    json_encode((array)($definition['config'] ?? []), JSON_THROW_ON_ERROR),
                ]
            );
        }

        foreach ((array)($profile['mappings'] ?? []) as $mappingKey => $mappingValue) {
            sqlStatement(
                'INSERT INTO neolims_bridge_profile_mapping
                    (profile_id, mapping_key, mapping_value, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE mapping_value = VALUES(mapping_value), updated_at = NOW()',
                [$id, (string)$mappingKey, (string)$mappingValue]
            );
        }

        return $this->findByKey($key) ?? [];
    }

    public function activate(string $profileKey): array
    {
        $profile = $this->findByKey($profileKey);
        if (!$profile) throw new \InvalidArgumentException('Profile not found.');
        sqlStatement('UPDATE neolims_bridge_profile SET is_default = 0');
        sqlStatement('UPDATE neolims_bridge_profile SET enabled = 1, is_default = 1, updated_at = NOW() WHERE profile_key = ?', [$profileKey]);
        return $this->findByKey($profileKey) ?? [];
    }

    private function hydrate(array $row): array
    {
        $row['enabled'] = (bool)$row['enabled'];
        $row['is_default'] = (bool)$row['is_default'];
        $row['resources'] = $this->resources((int)$row['id']);
        $row['mappings'] = $this->mappings((int)$row['id']);
        return $row;
    }

    private function hydrateResource(array $row): array
    {
        $row['receive_enabled'] = (bool)$row['receive_enabled'];
        $row['send_enabled'] = (bool)$row['send_enabled'];
        $row['operations'] = json_decode((string)$row['operations_json'], true) ?: [];
        $row['transports'] = json_decode((string)$row['transports_json'], true) ?: [];
        $row['config'] = json_decode((string)$row['config_json'], true) ?: [];
        unset($row['operations_json'], $row['transports_json'], $row['config_json']);
        return $row;
    }
}
