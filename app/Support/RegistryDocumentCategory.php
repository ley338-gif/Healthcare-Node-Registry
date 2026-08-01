<?php

namespace App\Support;

enum RegistryDocumentCategory: string
{
    case MaintenanceContract = 'maintenance_contract';
    case SupportContract = 'support_contract';
    case License = 'license';
    case ManufacturerDocumentation = 'manufacturer_documentation';
    case Manual = 'manual';
    case InstallationGuide = 'installation_guide';
    case NetworkPlan = 'network_plan';
    case FirewallApproval = 'firewall_approval';
    case OperationsManual = 'operations_manual';
    case EmergencyManual = 'emergency_manual';
    case BackupRecoveryDocumentation = 'backup_recovery_documentation';
    case Sop = 'sop';
    case Certificate = 'certificate';
    case DataProtection = 'data_protection';
    case InformationSecurity = 'information_security';
    case IsoEvidence = 'iso_evidence';
    case InterfaceDocumentation = 'interface_documentation';
    case TestProtocol = 'test_protocol';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MaintenanceContract => 'Wartungsvertrag',
            self::SupportContract => 'Supportvertrag',
            self::License => 'Lizenz',
            self::ManufacturerDocumentation => 'Herstellerdokumentation',
            self::Manual => 'Handbuch',
            self::InstallationGuide => 'Installationsanleitung',
            self::NetworkPlan => 'Netzwerkplan',
            self::FirewallApproval => 'Firewallfreigabe',
            self::OperationsManual => 'Betriebshandbuch',
            self::EmergencyManual => 'Notfallhandbuch',
            self::BackupRecoveryDocumentation => 'Backup- und Recovery-Dokumentation',
            self::Sop => 'SOP',
            self::Certificate => 'Zertifikat',
            self::DataProtection => 'Datenschutz',
            self::InformationSecurity => 'Informationssicherheit',
            self::IsoEvidence => 'ISO-Nachweis',
            self::InterfaceDocumentation => 'Schnittstellendokumentation',
            self::TestProtocol => 'Testprotokoll',
            self::Other => 'Sonstiges',
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            static fn (self $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
            ],
            self::cases(),
        );
    }
}
