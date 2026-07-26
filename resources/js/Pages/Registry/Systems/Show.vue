<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Database, FileText, Network, Server } from '@lucide/vue';
import ContentCard from '../../../Components/ui/ContentCard.vue';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

type SelectOption = {
    value: string;
    label: string;
};

type SystemDetail = {
    public_id: string;
    name: string;
    system_type: string;
    status: string;
    hostname: string | null;
    fqdn: string | null;
    ip_address: string | null;
    vendor: string | null;
    product: string | null;
    model: string | null;
    version: string | null;
    operating_system: string | null;
    operating_system_version: string | null;
    serial_number: string | null;
    inventory_number: string | null;
    description: string | null;
    notes: string | null;
    archived_at: string | null;
    created_at: string;
    updated_at: string;
    organization: {
        public_id: string;
        name: string;
    };
    site: {
        public_id: string;
        name: string;
    } | null;
    department: {
        public_id: string;
        name: string;
    } | null;
};

defineProps<{
    system: SystemDetail;
    systemTypes: SelectOption[];
    statuses: SelectOption[];
    canManage: boolean;
}>();

const labelFor = (options: SelectOption[], value: string): string =>
    options.find((option) => option.value === value)?.label ?? value;

const displayValue = (value: string | null): string => value?.trim() || 'Nicht hinterlegt';
</script>

<template>
    <Head :title="system.name" />

    <AppLayout>
        <Link
            href="/systems"
            class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-blue-700"
        >
            <ArrowLeft :size="17" />
            Zurück zu Systeme
        </Link>

        <PageHeader
            eyebrow="System Registry"
            :title="system.name"
            :description="
                [system.vendor, system.product, system.version].filter(Boolean).join(' · ') ||
                'Technisches oder fachliches System'
            "
        >
            <template #actions>
                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700">
                    {{ labelFor(statuses, system.status) }}
                </span>
            </template>
        </PageHeader>

        <div class="mt-6 grid gap-6 xl:grid-cols-[2fr_1fr]">
            <div class="space-y-6">
                <ContentCard title="Allgemeine Informationen" description="Stammdaten und organisatorische Zuordnung.">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Systemtyp</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ labelFor(systemTypes, system.system_type) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Status</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ labelFor(statuses, system.status) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Organisation</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ system.organization.name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Standort</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ system.site?.name || 'Nicht zugeordnet' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Abteilung</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ system.department?.name || 'Nicht zugeordnet' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Inventarnummer</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ displayValue(system.inventory_number) }}
                            </p>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard title="Netzwerk" description="Hostname, FQDN und IP-Konfiguration.">
                    <div class="grid gap-6 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Hostname</p>
                            <p class="mt-1 font-mono text-sm text-slate-900">
                                {{ displayValue(system.hostname) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">FQDN</p>
                            <p class="mt-1 font-mono text-sm text-slate-900">
                                {{ displayValue(system.fqdn) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">IP-Adresse</p>
                            <p class="mt-1 font-mono text-sm text-slate-900">
                                {{ displayValue(system.ip_address) }}
                            </p>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard
                    title="Produkt und Plattform"
                    description="Hersteller-, Produkt- und Betriebssysteminformationen."
                >
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Hersteller</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ displayValue(system.vendor) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Produkt</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ displayValue(system.product) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Modell</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ displayValue(system.model) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Version</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ displayValue(system.version) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Betriebssystem</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ displayValue(system.operating_system) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Betriebssystemversion</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ displayValue(system.operating_system_version) }}
                            </p>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard
                    title="Beschreibung und Notizen"
                    description="Ergänzende technische und organisatorische Informationen."
                >
                    <div class="space-y-6">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Beschreibung</p>
                            <p class="mt-2 text-sm leading-6 whitespace-pre-line text-slate-700">
                                {{ displayValue(system.description) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Interne Notizen</p>
                            <p class="mt-2 text-sm leading-6 whitespace-pre-line text-slate-700">
                                {{ displayValue(system.notes) }}
                            </p>
                        </div>
                    </div>
                </ContentCard>
            </div>

            <div class="space-y-6">
                <ContentCard title="Module" description="Erweiterbare Fachbereiche dieses Systems.">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                            <Network :size="19" class="text-blue-600" />
                            <div>
                                <p class="text-sm font-medium text-slate-900">DICOM-Knoten</p>
                                <p class="text-xs text-slate-500">Noch nicht konfiguriert</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                            <Database :size="19" class="text-blue-600" />
                            <div>
                                <p class="text-sm font-medium text-slate-900">HL7-Verbindungen</p>
                                <p class="text-xs text-slate-500">Noch nicht konfiguriert</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                            <FileText :size="19" class="text-blue-600" />
                            <div>
                                <p class="text-sm font-medium text-slate-900">Dokumentation</p>
                                <p class="text-xs text-slate-500">Noch nicht konfiguriert</p>
                            </div>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard title="Metadaten">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <Server :size="18" class="mt-0.5 text-slate-400" />
                            <div>
                                <p class="text-xs text-slate-500">System-ID</p>
                                <p class="mt-1 font-mono text-xs break-all text-slate-700">
                                    {{ system.public_id }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <Building2 :size="18" class="mt-0.5 text-slate-400" />
                            <div>
                                <p class="text-xs text-slate-500">Seriennummer</p>
                                <p class="mt-1 text-sm text-slate-700">
                                    {{ displayValue(system.serial_number) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </ContentCard>
            </div>
        </div>
    </AppLayout>
</template>
