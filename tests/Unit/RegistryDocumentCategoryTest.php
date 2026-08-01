<?php

namespace Tests\Unit;

use App\Support\RegistryDocumentCategory;
use PHPUnit\Framework\TestCase;

final class RegistryDocumentCategoryTest extends TestCase
{
    public function test_supported_category_values_are_stable_and_complete(): void
    {
        self::assertSame([
            'maintenance_contract',
            'support_contract',
            'license',
            'manufacturer_documentation',
            'manual',
            'installation_guide',
            'network_plan',
            'firewall_approval',
            'operations_manual',
            'emergency_manual',
            'backup_recovery_documentation',
            'sop',
            'certificate',
            'data_protection',
            'information_security',
            'iso_evidence',
            'interface_documentation',
            'test_protocol',
            'other',
        ], array_column(RegistryDocumentCategory::cases(), 'value'));
    }

    public function test_every_category_has_a_localized_option_label(): void
    {
        $options = RegistryDocumentCategory::options();

        self::assertCount(19, $options);
        self::assertSame(
            ['value' => 'maintenance_contract', 'label' => 'Wartungsvertrag'],
            $options[0],
        );
        self::assertSame(
            ['value' => 'backup_recovery_documentation', 'label' => 'Backup- und Recovery-Dokumentation'],
            $options[10],
        );
        self::assertSame(['value' => 'other', 'label' => 'Sonstiges'], $options[18]);
        self::assertNotContains('', array_column($options, 'label'));
    }
}
