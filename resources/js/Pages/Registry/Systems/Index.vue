<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Download, FilterX, Search, Server } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import SystemCreateSlideOver from '../../../Components/registry/systems/SystemCreateSlideOver.vue';
import SystemEditSlideOver from '../../../Components/registry/systems/SystemEditSlideOver.vue';
import type { DicomConnection, DicomNodeOption } from '../../../Components/registry/dicom/DicomConnectionManager.vue';
import type { DicomNode } from '../../../Components/registry/dicom/DicomNodeManager.vue';
import SystemWorkspaceDetail, {
    type SystemDetail,
} from '../../../Components/registry/systems/SystemWorkspaceDetail.vue';
import type { NetworkConnection, NetworkNode } from '../../../Components/network/DicomNetworkMap.vue';
import EmptyState from '../../../Components/ui/EmptyState.vue';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import type { RegistryDocumentationItem } from '../../../Components/documentation/documentationTypes';
import type { RegistryDocumentPagination } from '../../../Components/documents/RegistryDocumentList.vue';

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

type SystemItem = {
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
    dicom_nodes_count: number;
    failed_dicom_nodes_count: number;
    organization: {
        name: string;
    };
    site: {
        name: string;
    } | null;
    department: {
        name: string;
    } | null;
};

type PaginatedSystems = {
    data: SystemItem[];
    total: number;
};

const props = withDefaults(
    defineProps<{
        items: PaginatedSystems;
        filters: {
            search: string;
            type: string;
            status: string;
            organization: number | null;
            site: number | null;
            department: number | null;
            selected: string | null;
        };
        systemTypes: SelectOption[];
        statuses: SelectOption[];
        organizations: OrganizationOption[];
        sites: SiteOption[];
        departments: DepartmentOption[];
        selectedSystem: SystemDetail | null;
        dicomNodes: DicomNode[];
        dicomConnections: DicomConnection[];
        dicomNodeOptions: DicomNodeOption[];
        documentation: RegistryDocumentationItem[];
        documents: RegistryDocumentPagination;
        documentFilters: Record<string, string | undefined>;
        documentUploaders: Array<{ public_id: string; name: string }>;
        documentCategories: Array<{ value: string; label: string }>;
        canUploadDocuments: boolean;
        canManageDocumentVersions: boolean;
        canDownloadDocuments: boolean;
        canViewDocuments: boolean;
        canUpdateDocuments: boolean;
        canArchiveDocuments: boolean;
        topologyNodes?: NetworkNode[];
        topologyConnections?: NetworkConnection[];
        canManage: boolean;
        canManageSelected: boolean;
        canManageDicomNodes: boolean;
        canManageDicomConnections: boolean;
    }>(),
    {
        topologyNodes: () => [],
        topologyConnections: () => [],
    },
);

const search = ref(props.filters.search);
const type = ref(props.filters.type);
const status = ref(props.filters.status);
const organization = ref<number | null>(props.filters.organization);
const site = ref<number | null>(props.filters.site);
const department = ref<number | null>(props.filters.department);

const createPanelOpen = ref(false);
const editPanelOpen = ref(false);

const focusedSystem = ref<SystemItem | null>(
    props.items.data.find((item) => item.public_id === props.selectedSystem?.public_id) ?? props.items.data[0] ?? null,
);
const actionSystem = ref<SystemDetail | SystemItem | null>(null);

const hasSystems = computed(() => props.items.data.length > 0);

const filteredSites = computed(() =>
    organization.value === null
        ? props.sites
        : props.sites.filter((item) => item.organization_id === organization.value),
);

const filteredDepartments = computed(() =>
    site.value === null ? [] : props.departments.filter((item) => item.site_id === site.value),
);

const hasActiveFilters = computed(
    () =>
        search.value !== '' ||
        type.value !== '' ||
        status.value !== '' ||
        organization.value !== null ||
        site.value !== null ||
        department.value !== null,
);

watch(organization, () => {
    if (site.value !== null && !filteredSites.value.some((item) => item.id === site.value)) {
        site.value = null;
        department.value = null;
    }
});

watch(site, () => {
    if (department.value !== null && !filteredDepartments.value.some((item) => item.id === department.value)) {
        department.value = null;
    }
});

watch(
    () => [props.items.data, props.selectedSystem?.public_id] as const,
    ([systems, selectedPublicId]) => {
        if (systems.length === 0) {
            focusedSystem.value = null;
            return;
        }

        focusedSystem.value = systems.find((system) => system.public_id === selectedPublicId) ?? systems[0];
    },
);

const applyFilters = (): void => {
    router.get(
        '/systems',
        {
            search: search.value || undefined,
            type: type.value || undefined,
            status: status.value || undefined,
            organization: organization.value ?? undefined,
            site: site.value ?? undefined,
            department: department.value ?? undefined,
            selected: props.selectedSystem?.public_id ?? undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const resetFilters = (): void => {
    search.value = '';
    type.value = '';
    status.value = '';
    organization.value = null;
    site.value = null;
    department.value = null;

    router.get(
        '/systems',
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
};

const exportUrl = (format: 'xlsx' | 'pdf'): string => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (type.value) params.set('type', type.value);
    if (status.value) params.set('status', status.value);
    if (organization.value !== null) params.set('organization', String(organization.value));
    if (site.value !== null) params.set('site', String(site.value));
    if (department.value !== null) params.set('department', String(department.value));

    return `/systems/export/${format}${params.size > 0 ? `?${params.toString()}` : ''}`;
};

const labelFor = (options: SelectOption[], value: string): string =>
    options.find((option) => option.value === value)?.label ?? value;

const statusClass = (value: string): string =>
    ({
        active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        planned: 'bg-blue-50 text-blue-700 ring-blue-200',
        maintenance: 'bg-amber-50 text-amber-700 ring-amber-200',
        inactive: 'bg-slate-100 text-slate-600 ring-slate-200',
        retired: 'bg-slate-200 text-slate-700 ring-slate-300',
    })[value] ?? 'bg-slate-100 text-slate-600 ring-slate-200';

const selectSystem = (system: SystemItem): void => {
    focusedSystem.value = system;

    router.get(
        '/systems',
        {
            search: search.value || undefined,
            type: type.value || undefined,
            status: status.value || undefined,
            organization: organization.value ?? undefined,
            site: site.value ?? undefined,
            department: department.value ?? undefined,
            selected: system.public_id,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: [
                'selectedSystem',
                'dicomNodes',
                'dicomConnections',
                'dicomNodeOptions',
                'documentation',
                'topologyNodes',
                'topologyConnections',
                'canManageSelected',
                'canManageDicomNodes',
                'canManageDicomConnections',
            ],
        },
    );
};

const openEditPanel = (system: SystemDetail | SystemItem): void => {
    actionSystem.value = system;
    editPanelOpen.value = true;
};

const closeEditPanel = (): void => {
    editPanelOpen.value = false;
    actionSystem.value = null;
};
</script>

<template>
    <Head title="Systeme" />

    <AppLayout>
        <PageHeader
            eyebrow="Registry"
            title="Systeme"
            description="Systeme durchsuchen, einordnen und im Kontext ihrer DICOM-Kommunikation prüfen."
        >
            <template #actions>
                <a
                    :href="exportUrl('xlsx')"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                >
                    <Download :size="16" />Excel
                </a>
                <a
                    :href="exportUrl('pdf')"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                >
                    <Download :size="16" />PDF
                </a>
                <a
                    v-if="canManage"
                    href="/systems/import"
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                >
                    CSV importieren
                </a>
                <button
                    type="button"
                    :disabled="!canManage"
                    class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="createPanelOpen = true"
                >
                    Neues System
                </button>
            </template>
        </PageHeader>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <form @submit.prevent="applyFilters">
                <div class="grid gap-3 xl:grid-cols-[minmax(260px,1.4fr)_180px_180px_auto_auto]">
                    <div class="relative">
                        <Search :size="18" class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" />

                        <input
                            v-model="search"
                            type="search"
                            placeholder="Name, Hostname, IP oder Hersteller"
                            class="w-full rounded-xl border border-slate-300 py-2.5 pr-3 pl-10 text-sm"
                        />
                    </div>

                    <select v-model="type" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Alle Typen</option>
                        <option v-for="option in systemTypes" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <select v-model="status" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Alle Status</option>
                        <option v-for="option in statuses" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">
                        Filtern
                    </button>

                    <button
                        type="button"
                        :disabled="!hasActiveFilters"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 disabled:opacity-40"
                        @click="resetFilters"
                    >
                        <FilterX :size="16" />
                        Zurücksetzen
                    </button>
                </div>

                <details class="mt-3">
                    <summary class="cursor-pointer text-xs font-semibold text-slate-500">Organisationsfilter</summary>

                    <div class="mt-3 grid gap-3 lg:grid-cols-3">
                        <select v-model="organization" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                            <option :value="null">Alle Organisationen</option>
                            <option v-for="item in organizations" :key="item.id" :value="item.id">
                                {{ item.name }}
                            </option>
                        </select>

                        <select v-model="site" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                            <option :value="null">Alle Standorte</option>
                            <option v-for="item in filteredSites" :key="item.id" :value="item.id">
                                {{ item.name }}
                            </option>
                        </select>

                        <select
                            v-model="department"
                            :disabled="site === null"
                            class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm disabled:bg-slate-100"
                        >
                            <option :value="null">Alle Abteilungen</option>
                            <option v-for="item in filteredDepartments" :key="item.id" :value="item.id">
                                {{ item.name }}
                            </option>
                        </select>
                    </div>
                </details>
            </form>
        </section>

        <EmptyState
            v-if="!hasSystems"
            class="mt-6"
            title="Keine Systeme gefunden"
            description="Passe die Filter an oder lege ein neues System an."
            :icon="Server"
        />

        <div v-else class="mt-6 grid min-h-[720px] gap-5 xl:grid-cols-[320px_minmax(0,1fr)]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <div>
                        <h2 class="font-semibold text-slate-950">Systemauswahl</h2>
                        <p class="text-xs text-slate-500">{{ items.total }} Systeme gefunden</p>
                    </div>
                </header>

                <div class="max-h-[760px] divide-y divide-slate-100 overflow-y-auto">
                    <button
                        v-for="system in items.data"
                        :key="system.public_id"
                        type="button"
                        class="w-full px-4 py-4 text-left transition"
                        :class="
                            focusedSystem?.public_id === system.public_id
                                ? 'bg-blue-50 ring-1 ring-blue-200 ring-inset'
                                : 'hover:bg-slate-50'
                        "
                        @click="selectSystem(system)"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full"
                                :class="
                                    system.failed_dicom_nodes_count > 0
                                        ? 'bg-red-500'
                                        : system.status === 'active'
                                          ? 'bg-emerald-500'
                                          : 'bg-amber-500'
                                "
                            />

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="truncate text-sm font-semibold text-slate-950">
                                        {{ system.name }}
                                    </p>

                                    <span
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="statusClass(system.status)"
                                    >
                                        {{ labelFor(statuses, system.status) }}
                                    </span>
                                </div>

                                <p class="mt-1 text-xs font-medium text-blue-700">
                                    {{ labelFor(systemTypes, system.system_type) }}
                                </p>

                                <p class="mt-2 truncate font-mono text-xs text-slate-500">
                                    {{ system.ip_address || system.hostname || 'Kein Netzwerkendpunkt' }}
                                </p>

                                <div class="mt-2 flex items-center gap-3 text-[11px] text-slate-500">
                                    <span>
                                        {{ system.dicom_nodes_count }}
                                        DICOM
                                    </span>

                                    <span v-if="system.failed_dicom_nodes_count > 0" class="font-semibold text-red-700">
                                        {{ system.failed_dicom_nodes_count }}
                                        auffällig
                                    </span>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
            </section>

            <SystemWorkspaceDetail
                v-if="selectedSystem"
                :system="selectedSystem"
                :system-types="systemTypes"
                :statuses="statuses"
                :dicom-nodes="dicomNodes"
                :dicom-connections="dicomConnections"
                :dicom-node-options="dicomNodeOptions"
                :topology-nodes="topologyNodes"
                :topology-connections="topologyConnections"
                :can-manage="canManageSelected"
                :can-manage-dicom-nodes="canManageDicomNodes"
                :can-manage-dicom-connections="canManageDicomConnections"
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
                @edit="openEditPanel"
            />
        </div>

        <SystemCreateSlideOver
            :open="createPanelOpen"
            :organizations="organizations"
            :sites="sites"
            :departments="departments"
            :system-types="systemTypes"
            :statuses="statuses"
            @close="createPanelOpen = false"
        />

        <SystemEditSlideOver
            :open="editPanelOpen"
            :system="actionSystem"
            :organizations="organizations"
            :sites="sites"
            :departments="departments"
            :system-types="systemTypes"
            :statuses="statuses"
            @close="closeEditPanel"
        />
    </AppLayout>
</template>
