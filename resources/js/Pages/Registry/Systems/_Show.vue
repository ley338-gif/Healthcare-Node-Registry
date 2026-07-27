<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, ArrowLeft, Building2, Database, FileText, History, Network, Pencil, Server } from '@lucide/vue';
import { computed, ref } from 'vue';
import SystemEditSlideOver from '../../../Components/registry/systems/SystemEditSlideOver.vue';
import ContentCard from '../../../Components/ui/ContentCard.vue';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

type SelectOption = {
    value: string;
    label: string;
};

type OrganizationOption = {
    id: number;
    name: string;
};

type SiteOption = {
    id: number;
    organization_id: number;
    name: string;
};

type DepartmentOption = {
    id: number;
    site_id: number;
    name: string;
};

type SystemDetail = {
    public_id: string;
    organization_id: number;
    site_id: number | null;
    department_id: number | null;
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

type TabId = 'general' | 'network' | 'dicom' | 'hl7' | 'documentation' | 'history';

const props = defineProps<{
    system: SystemDetail;
    systemTypes: SelectOption[];
    statuses: SelectOption[];
    organizations: OrganizationOption[];
    sites: SiteOption[];
    departments: DepartmentOption[];
    canManage: boolean;
}>();

const activeTab = ref<TabId>('general');
const editPanelOpen = ref(false);

const tabs: Array<{
    id: TabId;
    label: string;
    enabled: boolean;
}> = [
    { id: 'general', label: 'Allgemein', enabled: true },
    { id: 'network', label: 'Netzwerk', enabled: true },
    { id: 'dicom', label: 'DICOM', enabled: true },
    { id: 'hl7', label: 'HL7', enabled: true },
    { id: 'documentation', label: 'Dokumentation', enabled: true },
    { id: 'history', label: 'Historie', enabled: true },
];

const productDescription = computed(
    () =>
        [props.system.vendor, props.system.product, props.system.version].filter(Boolean).join(' · ') ||
        'Technisches oder fachliches System',
);

const labelFor = (options: SelectOption[], value: string): string =>
    options.find((option) => option.value === value)?.label ?? value;

const displayValue = (value: string | null): string => value?.trim() || 'Nicht hinterlegt';

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
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

        <PageHeader eyebrow="System Registry" :title="system.name" :description="productDescription">
            <template #actions>
                <div class="flex items-center gap-3">
                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700">
                        {{ labelFor(statuses, system.status) }}
                    </span>

                    <button
                        v-if="canManage"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                        @click="editPanelOpen = true"
                    >
                        <Pencil :size="17" />
                        Bearbeiten
                    </button>
                </div>
            </template>
        </PageHeader>

        <nav class="mt-6 overflow-x-auto border-b border-slate-200" aria-label="Systembereiche">
            <div class="flex min-w-max gap-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    :disabled="!tab.enabled"
                    class="border-b-2 px-4 py-3 text-sm font-medium transition"
                    :class="
                        activeTab === tab.id
                            ? 'border-blue-600 text-blue-700'
                            : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800'
                    "
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                </button>
            </div>
        </nav>

        <div v-if="activeTab === 'general'" class="mt-6 grid gap-6 xl:grid-cols-[2fr_1fr]">
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

                <ContentCard
                    title="Produkt und Plattform"
                    description="Hersteller-, Produkt- und Betriebssysteminformationen."
                >
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Hersteller</p>
                            <p class="mt-1 text-sm text-slate-900">
                                {{ displayValue(system.vendor) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Produkt</p>
                            <p class="mt-1 text-sm text-slate-900">
                                {{ displayValue(system.product) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Modell</p>
                            <p class="mt-1 text-sm text-slate-900">
                                {{ displayValue(system.model) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Version</p>
                            <p class="mt-1 text-sm text-slate-900">
                                {{ displayValue(system.version) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Betriebssystem</p>
                            <p class="mt-1 text-sm text-slate-900">
                                {{ displayValue(system.operating_system) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Betriebssystemversion</p>
                            <p class="mt-1 text-sm text-slate-900">
                                {{ displayValue(system.operating_system_version) }}
                            </p>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard title="Beschreibung und Notizen">
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
                <ContentCard title="Metadaten">
                    <div class="space-y-5">
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

                        <div class="flex items-start gap-3">
                            <Activity :size="18" class="mt-0.5 text-slate-400" />
                            <div>
                                <p class="text-xs text-slate-500">Zuletzt geändert</p>
                                <p class="mt-1 text-sm text-slate-700">
                                    {{ formatDate(system.updated_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </ContentCard>
            </div>
        </div>

        <div v-else-if="activeTab === 'network'" class="mt-6">
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
        </div>

        <div v-else-if="activeTab === 'dicom'" class="mt-6">
            <ContentCard title="DICOM-Knoten" description="AE Titles, Hosts, Ports und unterstützte DICOM-Dienste.">
                <div class="py-10 text-center">
                    <Network :size="32" class="mx-auto text-slate-300" />
                    <p class="mt-4 font-medium text-slate-900">Noch keine DICOM-Knoten</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Dieses Modul wird im nächsten Entwicklungsschritt ergänzt.
                    </p>
                </div>
            </ContentCard>
        </div>

        <div v-else-if="activeTab === 'hl7'" class="mt-6">
            <ContentCard title="HL7-Verbindungen" description="Sender, Empfänger, Nachrichtentypen und Transport.">
                <div class="py-10 text-center">
                    <Database :size="32" class="mx-auto text-slate-300" />
                    <p class="mt-4 font-medium text-slate-900">Noch keine HL7-Verbindungen</p>
                    <p class="mt-1 text-sm text-slate-500">Dieses Modul wird später ergänzt.</p>
                </div>
            </ContentCard>
        </div>

        <div v-else-if="activeTab === 'documentation'" class="mt-6">
            <ContentCard title="Dokumentation" description="Betriebs-, Hersteller- und interne Dokumentation.">
                <div class="py-10 text-center">
                    <FileText :size="32" class="mx-auto text-slate-300" />
                    <p class="mt-4 font-medium text-slate-900">Noch keine Dokumente</p>
                </div>
            </ContentCard>
        </div>

        <div v-else class="mt-6">
            <ContentCard title="Historie" description="Änderungen und sicherheitsrelevante Ereignisse.">
                <div class="py-10 text-center">
                    <History :size="32" class="mx-auto text-slate-300" />
                    <p class="mt-4 font-medium text-slate-900">Audit-Historie wird vorbereitet</p>
                </div>
            </ContentCard>
        </div>

        <SystemEditSlideOver
            :open="editPanelOpen"
            :system="system"
            :organizations="organizations"
            :sites="sites"
            :departments="departments"
            :system-types="systemTypes"
            :statuses="statuses"
            @close="editPanelOpen = false"
        />
    </AppLayout>
</template>
