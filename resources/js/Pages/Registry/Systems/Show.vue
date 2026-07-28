<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    ArrowLeft,
    Building2,
    CircleAlert,
    CircleCheck,
    CircleHelp,
    Database,
    FileText,
    History,
    MapPin,
    Network,
    Pencil,
    Radio,
    Server,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import DicomNodeManager, { type DicomNode } from '../../../Components/registry/dicom/DicomNodeManager.vue';
import SystemEditSlideOver from '../../../Components/registry/systems/SystemEditSlideOver.vue';
import ContentCard from '../../../Components/ui/ContentCard.vue';
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
    dicomNodes: DicomNode[];
    canManage: boolean;
    canManageDicomNodes: boolean;
}>();

const activeTab = ref<TabId>('general');
const editPanelOpen = ref(false);

const tabs: Array<{
    id: TabId;
    label: string;
}> = [
    { id: 'general', label: 'Allgemein' },
    { id: 'network', label: 'Netzwerk' },
    { id: 'dicom', label: 'DICOM' },
    { id: 'hl7', label: 'HL7' },
    { id: 'documentation', label: 'Dokumentation' },
    { id: 'history', label: 'Historie' },
];

const productDescription = computed(
    () =>
        [props.system.vendor, props.system.product, props.system.version].filter(Boolean).join(' · ') ||
        'Technisches oder fachliches System',
);

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

const openEditPanel = (): void => {
    editPanelOpen.value = true;
};

const closeEditPanel = (): void => {
    editPanelOpen.value = false;
};
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

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div
                class="flex flex-col gap-6 border-b border-slate-200 px-6 py-6 xl:flex-row xl:items-start xl:justify-between"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700"
                        >
                            {{ labelFor(systemTypes, system.system_type) }}
                        </span>

                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                            :class="statusClass(system.status)"
                        >
                            {{ labelFor(statuses, system.status) }}
                        </span>
                    </div>

                    <h1 class="mt-4 truncate text-3xl font-semibold tracking-tight text-slate-950">
                        {{ system.name }}
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">
                        {{ productDescription }}
                    </p>

                    <div class="mt-5 flex flex-wrap gap-x-6 gap-y-3 text-sm text-slate-600">
                        <div class="flex items-center gap-2">
                            <Server :size="17" class="text-slate-400" />
                            <span class="font-mono">
                                {{ system.hostname || 'Kein Hostname' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <Network :size="17" class="text-slate-400" />
                            <span class="font-mono">
                                {{ system.ip_address || 'Keine IP-Adresse' }}
                            </span>
                        </div>
                    </div>
                </div>

                <button
                    v-if="canManage"
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                    @click="openEditPanel"
                >
                    <Pencil :size="17" />
                    Bearbeiten
                </button>
            </div>

            <div class="grid divide-y divide-slate-200 md:grid-cols-3 md:divide-x md:divide-y-0">
                <div class="flex items-start gap-3 px-6 py-4">
                    <Building2 :size="18" class="mt-0.5 shrink-0 text-blue-600" />
                    <div>
                        <p class="text-xs text-slate-500">Organisation</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ system.organization.name }}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 px-6 py-4">
                    <MapPin :size="18" class="mt-0.5 shrink-0 text-blue-600" />
                    <div>
                        <p class="text-xs text-slate-500">Standort</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ system.site?.name || 'Nicht zugeordnet' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 px-6 py-4">
                    <Activity :size="18" class="mt-0.5 shrink-0 text-blue-600" />
                    <div>
                        <p class="text-xs text-slate-500">Abteilung</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ system.department?.name || 'Nicht zugeordnet' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <button
                type="button"
                class="rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-blue-300"
                @click="activeTab = 'dicom'"
            >
                <div class="flex items-center justify-between">
                    <Radio :size="19" class="text-blue-600" />
                    <span class="text-xs text-slate-400"> DICOM </span>
                </div>
                <p class="mt-4 text-2xl font-semibold text-slate-950">
                    {{ dicomNodes.length }}
                </p>
                <p class="mt-1 text-sm text-slate-500">Knoten</p>
            </button>

            <button
                type="button"
                class="rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-emerald-300"
                @click="activeTab = 'dicom'"
            >
                <div class="flex items-center justify-between">
                    <CircleCheck :size="19" class="text-emerald-600" />
                    <span class="text-xs text-slate-400"> C-ECHO </span>
                </div>
                <p class="mt-4 text-2xl font-semibold text-emerald-700">
                    {{ successfulDicomNodes }}
                </p>
                <p class="mt-1 text-sm text-slate-500">Erfolgreich geprüft</p>
            </button>

            <button
                type="button"
                class="rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-red-300"
                @click="activeTab = 'dicom'"
            >
                <div class="flex items-center justify-between">
                    <CircleAlert :size="19" class="text-red-600" />
                    <span class="text-xs text-slate-400"> Handlungsbedarf </span>
                </div>
                <p
                    class="mt-4 text-2xl font-semibold"
                    :class="failedDicomNodes > 0 ? 'text-red-700' : 'text-emerald-700'"
                >
                    {{ failedDicomNodes }}
                </p>
                <p class="mt-1 text-sm text-slate-500">Fehlgeschlagene Prüfungen</p>
            </button>

            <button
                type="button"
                class="rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-amber-300"
                @click="activeTab = 'dicom'"
            >
                <div class="flex items-center justify-between">
                    <CircleHelp :size="19" class="text-amber-600" />
                    <span class="text-xs text-slate-400"> Letzte Prüfung </span>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-950">
                    {{ latestVerification ? formatDate(latestVerification) : 'Noch nie geprüft' }}
                </p>
                <p class="mt-1 text-sm text-slate-500">{{ unverifiedDicomNodes }} Knoten ungeprüft</p>
            </button>
        </div>

        <nav class="mt-6 overflow-x-auto border-b border-slate-200" aria-label="Systembereiche">
            <div class="flex min-w-max gap-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
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
            <DicomNodeManager
                :system-public-id="system.public_id"
                :nodes="dicomNodes"
                :can-manage="canManageDicomNodes"
            />
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
            @close="closeEditPanel"
        />
    </AppLayout>
</template>
