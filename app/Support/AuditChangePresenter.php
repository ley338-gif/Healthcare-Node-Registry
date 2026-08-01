<?php

namespace App\Support;

final class AuditChangePresenter
{
    /** @var array<string, string> */
    private const FIELD_LABELS = [
        'ae_title' => 'AE Title', 'calling_ae_title' => 'Calling AE Title', 'called_ae_title' => 'Called AE Title',
        'host' => 'IP-Adresse / Host', 'hostname' => 'Hostname', 'ip_address' => 'IP-Adresse',
        'port' => 'Port', 'port_override' => 'Port', 'status' => 'Status', 'name' => 'Name',
        'title' => 'Titel', 'description' => 'Beschreibung', 'system_type' => 'Systemtyp',
        'tls_enabled' => 'TLS', 'test_enabled' => 'Tests', 'role' => 'Rolle', 'service' => 'Dienst',
        'vendor' => 'Hersteller', 'product' => 'Produkt', 'version' => 'Version',
        'valid_from' => 'Gültig ab', 'valid_until' => 'Gültig bis', 'visibility' => 'Sichtbarkeit',
        'category' => 'Kategorie', 'archived_at' => 'Archiviert am',
    ];

    /**
     * @param  array<string, mixed>  $metadata
     * @return list<array{field: string, label: string, before: mixed, after: mixed}>
     */
    public function changes(array $metadata): array
    {
        $before = is_array($metadata['before'] ?? null) ? $metadata['before'] : [];
        $after = is_array($metadata['after'] ?? null) ? $metadata['after'] : [];
        $fields = is_array($metadata['changed_fields'] ?? null)
            ? array_values(array_filter($metadata['changed_fields'], 'is_string'))
            : array_values(array_unique([...array_keys($before), ...array_keys($after)]));

        return array_map(fn (string $field): array => [
            'field' => $field,
            'label' => self::FIELD_LABELS[$field] ?? str($field)->replace('_', ' ')->title()->toString(),
            'before' => $before[$field] ?? null,
            'after' => $after[$field] ?? null,
        ], $fields);
    }
}
