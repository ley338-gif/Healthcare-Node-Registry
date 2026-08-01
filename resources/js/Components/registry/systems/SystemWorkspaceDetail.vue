<script setup lang="ts">
import {
    Activity,
    Building2,
    CircleAlert,
    CircleCheck,
    CircleHelp,
    Database,
    MapPin,
    Network,
    Pencil,
    Radio,
    Server,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DicomConnectionManager, {
    type DicomConnection,
    type DicomNodeOption,
} from '../dicom/DicomConnectionManager.vue';
import DicomNodeManager, { type DicomNode } from '../dicom/DicomNodeManager.vue';
import DicomNetworkMap, { type NetworkConnection, type NetworkNode } from '../../network/DicomNetworkMap.vue';
import ContentCard from '../../ui/ContentCard.vue';
import AuditHistoryPanel, { type AuditEvent } from '../../audit/AuditHistoryPanel.vue';
import DocumentationPanel from '../../documentation/DocumentationPanel.vue';
import type { RegistryDocumentationItem } from '../../documentation/documentationTypes';
import type { RegistryDocumentPagination } from '../../documents/RegistryDocumentList.vue';
import { systemDocumentationSections } from '../../documentation/systemDocumentationSections';

export type SelectOption = {
    value: string;
    label: string;
};

export type OrganizationOption = {
    id: number;
    name: string;
};

export type SiteOption = {
    id: number;
    organization_id: number;
    name: string;
};

export type DepartmentOption = {
    id: number;
    site_id: number;
    name: string;
};

export type SystemDetail = {
    id: number;
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

const props = withDefaults(
    defineProps<{
        system: SystemDetail;
        systemTypes: SelectOption[];
        statuses: SelectOption[];
        dicomNodes: DicomNode[];
        dicomConnections: DicomConnection[];
        dicomNodeOptions: DicomNodeOption[];
        topologyNodes?: NetworkNode[];
        topologyConnections?: NetworkConnection[];
        canManage: boolean;
        canManageDicomNodes: boolean;
        canManageDicomConnections: boolean;
        history?: {
            data: AuditEvent[];
            links: Array<{ url: string | null; label: string; active: boolean }>;
            total: number;
        };
        historyStats?: { total: number; today: number; last7Days: number; last30Days: number };
        historyFilters?: Record<string, string | undefined>;
        historyEventTypes?: string[];
        historyUsers?: Array<{ public_id: string; name: string }>;
        documentation?: RegistryDocumentationItem[];
        documents?: RegistryDocumentPagination;
        documentFilters?: Record<string, string | undefined>;
        documentUploaders?: Array<{ public_id: string; name: string }>;
        documentCategories?: Array<{ value: string; label: string }>;
        canUploadDocuments?: boolean;
        canManageDocumentVersions?: boolean;
        canDownloadDocuments?: boolean;
        canViewDocuments?: boolean;
        canUpdateDocuments?: boolean;
        canArchiveDocuments?: boolean;
    }>(),
    {
        topologyNodes: () => [],
        topologyConnections: () => [],
        history: () => ({ data: [], links: [], total: 0 }),
        historyStats: () => ({ total: 0, today: 0, last7Days: 0, last30Days: 0 }),
        historyFilters: () => ({}),
        historyEventTypes: () => [],
        historyUsers: () => [],
        documentation: () => [],
        documents: () => ({ data: [], links: [], total: 0 }),
        documentFilters: () => ({}),
        documentUploaders: () => [],
        documentCategories: () => [],
        canUploadDocuments: false,
        canManageDocumentVersions: false,
        canDownloadDocuments: false,
        canViewDocuments: false,
        canUpdateDocuments: false,
        canArchiveDocuments: false,
    },
);

const emit = defineEmits<{
    edit: [system: SystemDetail];
}>();

const activeTab = ref<TabId>('general');

const tabs: Array<{ id: TabId; label: string }> = [
    { id: 'general', label: 'Allgemein' },
    { id: 'network', label: 'Netzwerk' },
    { id: 'dicom', label: 'DICOM' },
    { id: 'hl7', label: 'HL7' },
    { id: 'documentation', label: 'Dokumentation' },
    { id: 'history', label: 'Historie' },
];

watch(
    () => props.system.public_id,
    () => {
        activeTab.value = 'general';
    },
);

const productDescription = computed(
    () =>
        [props.system.vendor, props.system.product, props.system.version].filter(Boolean).join(' · ') ||
        'Technisches oder fachliches System',
);

const documentationMasterData = computed(() => [
    { label: 'Hersteller', value: props.system.vendor },
    { label: 'Produkt', value: props.system.product },
    { label: 'Version', value: props.system.version },
    { label: 'DNS-Name', value: props.system.fqdn ?? props.system.hostname },
    { label: 'IP-Adresse', value: props.system.ip_address },
    {
        label: 'Betriebssystem',
        value: [props.system.operating_system, props.system.operating_system_version].filter(Boolean).join(' ') || null,
    },
]);

const successfulDicomNodes = computed(
    () => props.dicomNodes.filter((node) => node.last_verification_status === 'success').length,
);

const failedDicomNodes = computed(
    () =>
        props.dicomNodes.filter(
            (node) => node.last_verification_status !== null && node.last_verification_status !== 'success',
        ).length,
);

const unverifiedDicomNodes = computed(
    () => props.dicomNodes.filter((node) => node.supports_echo && node.last_verified_at === null).length,
);

const latestVerification = computed<string | null>(() => {
    const timestamps = props.dicomNodes
        .map((node) => node.last_verified_at)
        .filter((value): value is string => value !== null)
        .map((value) => new Date(value).getTime())
        .filter((value) => !Number.isNaN(value));

    if (timestamps.length === 0) {
        return null;
    }

    return new Date(Math.max(...timestamps)).toISOString();
});

const labelFor = (options: SelectOption[], value: string): string =>
    options.find((option) => option.value === value)?.label ?? value;

const displayValue = (value: string | null): string => value?.trim() || 'Nicht hinterlegt';

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

const statusClass = (value: string): string => {
    const classes: Record<string, string> = {
        active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        planned: 'bg-blue-50 text-blue-700 ring-blue-200',
        maintenance: 'bg-amber-50 text-amber-700 ring-amber-200',
        inactive: 'bg-slate-100 text-slate-600 ring-slate-200',
        retired: 'bg-slate-200 text-slate-700 ring-slate-300',
    };

    return classes[value] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
};
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div
            class="flex flex-col gap-5 border-b border-slate-200 px-6 py-5 xl:flex-row xl:items-start xl:justify-between"
        >
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                        {{ labelFor(systemTypes, system.system_type) }}
                    </span>

                    <span
                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                        :class="statusClass(system.status)"
                    >
                        {{ labelFor(statuses, system.status) }}
                    </span>
                </div>

                <h2 class="mt-3 truncate text-2xl font-semibold tracking-tight text-slate-950">
                    {{ system.name }}
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ productDescription }}
                </p>

                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5">
                        <Building2 :size="14" />
                        {{ system.organization.name }}
                    </span>
                    <span v-if="system.site" class="inline-flex items-center gap-1.5">
                        <MapPin :size="14" />
                        {{ system.site.name }}
                    </span>
                    <span v-if="system.department" class="inline-flex items-center gap-1.5">
                        <Activity :size="14" />
                        {{ system.department.name }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-mono">
                        <Network :size="14" />
                        {{ system.ip_address || system.hostname || 'Kein Netzwerkendpunkt' }}
                    </span>
                </div>
            </div>

            <button
                v-if="canManage"
                type="button"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                @click="emit('edit', system)"
            >
                <Pencil :size="17" />
                Bearbeiten
            </button>
        </div>

        <div class="grid divide-y divide-slate-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-4">
            <button type="button" class="p-4 text-left transition hover:bg-slate-50" @click="activeTab = 'dicom'">
                <div class="flex items-center justify-between">
                    <Radio :size="18" class="text-blue-600" />
                    <span class="text-[11px] font-semibold tracking-wide text-slate-400 uppercase">DICOM</span>
                </div>
                <p class="mt-3 text-xl font-semibold text-slate-950">{{ dicomNodes.length }}</p>
                <p class="mt-0.5 text-xs text-slate-500">Knoten</p>
            </button>

            <button type="button" class="p-4 text-left transition hover:bg-emerald-50/50" @click="activeTab = 'dicom'">
                <div class="flex items-center justify-between">
                    <CircleCheck :size="18" class="text-emerald-600" />
                    <span class="text-[11px] font-semibold tracking-wide text-slate-400 uppercase">C-ECHO</span>
                </div>
                <p class="mt-3 text-xl font-semibold text-emerald-700">{{ successfulDicomNodes }}</p>
                <p class="mt-0.5 text-xs text-slate-500">Erfolgreich</p>
            </button>

            <button type="button" class="p-4 text-left transition hover:bg-red-50/50" @click="activeTab = 'dicom'">
                <div class="flex items-center justify-between">
                    <CircleAlert :size="18" class="text-red-600" />
                    <span class="text-[11px] font-semibold tracking-wide text-slate-400 uppercase">Fehler</span>
                </div>
                <p
                    class="mt-3 text-xl font-semibold"
                    :class="failedDicomNodes > 0 ? 'text-red-700' : 'text-emerald-700'"
                >
                    {{ failedDicomNodes }}
                </p>
                <p class="mt-0.5 text-xs text-slate-500">Fehlgeschlagen</p>
            </button>

            <button type="button" class="p-4 text-left transition hover:bg-amber-50/50" @click="activeTab = 'dicom'">
                <div class="flex items-center justify-between">
                    <CircleHelp :size="18" class="text-amber-600" />
                    <span class="text-[11px] font-semibold tracking-wide text-slate-400 uppercase">Letzter Test</span>
                </div>
                <p class="mt-3 text-sm font-semibold text-slate-950">
                    {{ latestVerification ? formatDate(latestVerification) : 'Noch nie geprüft' }}
                </p>
                <p class="mt-0.5 text-xs text-slate-500">{{ unverifiedDicomNodes }} ungeprüft</p>
            </button>
        </div>

        <nav class="overflow-x-auto border-y border-slate-200 px-5" aria-label="Systembereiche">
            <div class="flex min-w-max gap-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    class="border-b-2 px-3 py-3 text-sm font-medium transition"
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

        <div class="p-5 lg:p-6">
            <div v-if="activeTab === 'general'" class="grid gap-5 xl:grid-cols-[2fr_1fr]">
                <div class="space-y-5">
                    <ContentCard
                        title="Allgemeine Informationen"
                        description="Stammdaten und organisatorische Zuordnung."
                    >
                        <div class="grid gap-5 sm:grid-cols-2">
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
                                <p class="mt-1 text-sm font-medium text-slate-900">{{ system.organization.name }}</p>
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
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold text-slate-500 uppercase">Hersteller</p>
                                <p class="mt-1 text-sm text-slate-900">{{ displayValue(system.vendor) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500 uppercase">Produkt</p>
                                <p class="mt-1 text-sm text-slate-900">{{ displayValue(system.product) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500 uppercase">Modell</p>
                                <p class="mt-1 text-sm text-slate-900">{{ displayValue(system.model) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500 uppercase">Version</p>
                                <p class="mt-1 text-sm text-slate-900">{{ displayValue(system.version) }}</p>
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
                        <div class="space-y-5">
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

                <ContentCard title="Metadaten">
                    <div class="space-y-5">
                        <div class="flex items-start gap-3">
                            <Server :size="18" class="mt-0.5 text-slate-400" />
                            <div>
                                <p class="text-xs text-slate-500">System-ID</p>
                                <p class="mt-1 font-mono text-xs break-all text-slate-700">{{ system.public_id }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Building2 :size="18" class="mt-0.5 text-slate-400" />
                            <div>
                                <p class="text-xs text-slate-500">Seriennummer</p>
                                <p class="mt-1 text-sm text-slate-700">{{ displayValue(system.serial_number) }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Activity :size="18" class="mt-0.5 text-slate-400" />
                            <div>
                                <p class="text-xs text-slate-500">Zuletzt geändert</p>
                                <p class="mt-1 text-sm text-slate-700">{{ formatDate(system.updated_at) }}</p>
                            </div>
                        </div>
                    </div>
                </ContentCard>
            </div>

            <div v-else-if="activeTab === 'network'" class="space-y-5">
                <ContentCard title="Netzwerk" description="Hostname, FQDN und IP-Konfiguration.">
                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">Hostname</p>
                            <p class="mt-1 font-mono text-sm text-slate-900">{{ displayValue(system.hostname) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">FQDN</p>
                            <p class="mt-1 font-mono text-sm text-slate-900">{{ displayValue(system.fqdn) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase">IP-Adresse</p>
                            <p class="mt-1 font-mono text-sm text-slate-900">{{ displayValue(system.ip_address) }}</p>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard title="Topologie" description="Direkte DICOM-Beziehungen des ausgewählten Systems.">
                    <DicomNetworkMap
                        v-if="topologyNodes.length > 0"
                        :nodes="topologyNodes"
                        :connections="topologyConnections"
                        :focus-system-public-id="system.public_id"
                        :details-enabled="false"
                        compact
                    />
                    <div
                        v-else
                        class="grid min-h-[280px] place-items-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6"
                    >
                        <div class="max-w-sm text-center">
                            <Network :size="30" class="mx-auto text-slate-300" />
                            <p class="mt-3 font-medium text-slate-900">Keine DICOM-Beziehungen vorhanden</p>
                            <p class="mt-1 text-sm text-slate-500">
                                Für dieses System wurden noch keine direkten Kommunikationspfade dokumentiert.
                            </p>
                        </div>
                    </div>
                </ContentCard>
            </div>

            <div v-else-if="activeTab === 'dicom'" class="space-y-5">
                <DicomNodeManager
                    :system-public-id="system.public_id"
                    :nodes="dicomNodes"
                    :can-manage="canManageDicomNodes"
                />
                <DicomConnectionManager
                    :connections="dicomConnections"
                    :node-options="dicomNodeOptions"
                    :can-manage="canManageDicomConnections"
                />
            </div>

            <ContentCard
                v-else-if="activeTab === 'hl7'"
                title="HL7-Verbindungen"
                description="Sender, Empfänger, Nachrichtentypen und Transport."
            >
                <div class="py-10 text-center">
                    <Database :size="32" class="mx-auto text-slate-300" />
                    <p class="mt-4 font-medium text-slate-900">Noch keine HL7-Verbindungen</p>
                    <p class="mt-1 text-sm text-slate-500">Dieses Modul wird später ergänzt.</p>
                </div>
            </ContentCard>

            <ContentCard
                v-else-if="activeTab === 'documentation'"
                title="Dokumentation"
                description="Betriebs-, Hersteller- und interne Dokumentation."
            >
                <DocumentationPanel
                    documentable-type="systems"
                    :documentable-id="system.public_id"
                    :sections="systemDocumentationSections"
                    :documentation="documentation"
                    :documents="documents"
                    :document-categories="documentCategories"
                    :can-upload-documents="canUploadDocuments"
                    :can-manage-document-versions="canManageDocumentVersions"
                    :can-download-documents="canDownloadDocuments"
                    :can-view-documents="canViewDocuments"
                    :can-update-documents="canUpdateDocuments"
                    :can-archive-documents="canArchiveDocuments"
                    :document-filters="documentFilters"
                    :document-uploaders="documentUploaders"
                    :can-manage="canManage"
                    :master-data="documentationMasterData"
                />
            </ContentCard>

            <ContentCard v-else title="Historie" description="Änderungen und sicherheitsrelevante Ereignisse.">
                <AuditHistoryPanel
                    :events="history"
                    :stats="historyStats"
                    :filters="historyFilters"
                    :event-types="historyEventTypes"
                    :users="historyUsers"
                />
            </ContentCard>
        </div>
    </section>
</template>
