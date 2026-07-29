<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CircleAlert,
    CircleCheck,
    FilterX,
    Hospital,
    MapPin,
    Network,
    Pencil,
    Search,
    Server,
    UsersRound,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import SystemArchiveDialog from '../../../Components/registry/systems/SystemArchiveDialog.vue';
import SystemCreateSlideOver from '../../../Components/registry/systems/SystemCreateSlideOver.vue';
import SystemEditSlideOver from '../../../Components/registry/systems/SystemEditSlideOver.vue';
import DicomNetworkMap, {
    type NetworkConnection,
    type NetworkNode,
} from '../../../Components/network/DicomNetworkMap.vue';
import EmptyState from '../../../Components/ui/EmptyState.vue';
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
        };
        systemTypes: SelectOption[];
        statuses: SelectOption[];
        organizations: OrganizationOption[];
        sites: SiteOption[];
        departments: DepartmentOption[];
        topologyNodes?: NetworkNode[];
        topologyConnections?: NetworkConnection[];
        canManage: boolean;
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
const archiveDialogOpen = ref(false);
const archiveProcessing = ref(false);

const focusedSystem = ref<SystemItem | null>(props.items.data[0] ?? null);
const actionSystem = ref<SystemItem | null>(null);

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

const currentProduct = computed(() => {
    if (focusedSystem.value === null) {
        return 'Keine Produktangaben';
    }

    return (
        [focusedSystem.value.vendor, focusedSystem.value.product].filter(Boolean).join(' · ') || 'Keine Produktangaben'
    );
});

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
    () => props.items.data,
    (systems) => {
        if (systems.length === 0) {
            focusedSystem.value = null;
            return;
        }

        const currentStillExists = systems.some((system) => system.public_id === focusedSystem.value?.public_id);

        if (!currentStillExists) {
            focusedSystem.value = systems[0];
        }
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
};

const openEditPanel = (system: SystemItem): void => {
    actionSystem.value = system;
    editPanelOpen.value = true;
};

const closeEditPanel = (): void => {
    editPanelOpen.value = false;
    actionSystem.value = null;
};

const closeArchiveDialog = (): void => {
    if (archiveProcessing.value) {
        return;
    }

    archiveDialogOpen.value = false;
    actionSystem.value = null;
};

const archiveSystem = (): void => {
    if (actionSystem.value === null) {
        return;
    }

    archiveProcessing.value = true;

    router.post(
        `/systems/${actionSystem.value.public_id}/archive`,
        {},
        {
            preserveScroll: true,
            onSuccess: closeArchiveDialog,
            onFinish: () => {
                archiveProcessing.value = false;
            },
        },
    );
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

        <div v-else class="mt-6 grid min-h-[680px] gap-4 xl:grid-cols-[320px_minmax(420px,1fr)_minmax(340px,0.9fr)]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <div>
                        <h2 class="font-semibold text-slate-950">Systemauswahl</h2>
                        <p class="text-xs text-slate-500">{{ items.total }} Systeme gefunden</p>
                    </div>
                </header>

                <div class="max-h-[620px] divide-y divide-slate-100 overflow-y-auto">
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

            <section
                v-if="focusedSystem"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <header class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    {{ labelFor(systemTypes, focusedSystem.system_type) }}
                                </span>

                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                    :class="statusClass(focusedSystem.status)"
                                >
                                    {{ labelFor(statuses, focusedSystem.status) }}
                                </span>
                            </div>

                            <h2 class="mt-3 truncate text-2xl font-semibold text-slate-950">
                                {{ focusedSystem.name }}
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                {{ currentProduct }}
                            </p>

                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                                <span class="inline-flex items-center gap-1.5">
                                    <Building2 :size="14" />
                                    {{ focusedSystem.organization.name }}
                                </span>

                                <span v-if="focusedSystem.site" class="inline-flex items-center gap-1.5">
                                    <Hospital :size="14" />
                                    {{ focusedSystem.site.name }}
                                </span>

                                <span v-if="focusedSystem.department" class="inline-flex items-center gap-1.5">
                                    <UsersRound :size="14" />
                                    {{ focusedSystem.department.name }}
                                </span>
                            </div>
                        </div>

                        <div class="flex shrink-0 gap-2">
                            <button
                                v-if="canManage"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                                @click="openEditPanel(focusedSystem)"
                            >
                                <Pencil :size="16" />
                                Bearbeiten
                            </button>

                            <Link
                                :href="`/systems/${focusedSystem.public_id}`"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                            >
                                System öffnen
                                <ArrowRight :size="16" />
                            </Link>
                        </div>
                    </div>
                </header>

                <div class="space-y-6 p-6">
                    <!-- Systemstatus -->
                    <section>
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-slate-950">Systemstatus</h3>
                                <p class="mt-0.5 text-xs text-slate-500">Technischer Zustand und Netzwerkdaten</p>
                            </div>

                            <div
                                class="flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold"
                                :class="
                                    focusedSystem.failed_dicom_nodes_count > 0
                                        ? 'bg-red-50 text-red-700'
                                        : 'bg-emerald-50 text-emerald-700'
                                "
                            >
                                <CircleAlert v-if="focusedSystem.failed_dicom_nodes_count > 0" :size="15" />

                                <CircleCheck v-else :size="15" />

                                {{
                                    focusedSystem.failed_dicom_nodes_count > 0
                                        ? 'Handlungsbedarf'
                                        : 'Keine bekannten Fehler'
                                }}
                            </div>
                        </div>

                        <dl class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                <dt class="flex items-center gap-2 text-xs text-slate-500">
                                    <Server :size="15" />
                                    Hostname
                                </dt>
                                <dd class="font-mono text-sm font-medium text-slate-900">
                                    {{ focusedSystem.hostname || 'Nicht hinterlegt' }}
                                </dd>
                            </div>

                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                <dt class="flex items-center gap-2 text-xs text-slate-500">
                                    <Network :size="15" />
                                    IP-Adresse
                                </dt>
                                <dd class="font-mono text-sm font-medium text-slate-900">
                                    {{ focusedSystem.ip_address || 'Nicht hinterlegt' }}
                                </dd>
                            </div>

                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                <dt class="flex items-center gap-2 text-xs text-slate-500">
                                    <Network :size="15" />
                                    DICOM-Knoten
                                </dt>
                                <dd class="text-sm font-semibold text-slate-900">
                                    {{ focusedSystem.dicom_nodes_count }}
                                </dd>
                            </div>

                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                <dt class="flex items-center gap-2 text-xs text-slate-500">
                                    <CircleAlert :size="15" />
                                    Auffällige Knoten
                                </dt>
                                <dd
                                    class="text-sm font-semibold"
                                    :class="
                                        focusedSystem.failed_dicom_nodes_count > 0 ? 'text-red-700' : 'text-emerald-700'
                                    "
                                >
                                    {{ focusedSystem.failed_dicom_nodes_count }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <!-- Warnung -->
                    <section
                        v-if="focusedSystem.failed_dicom_nodes_count > 0"
                        class="rounded-2xl border border-red-200 bg-red-50"
                    >
                        <div class="flex items-start gap-3 px-4 py-4">
                            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-red-100 text-red-700">
                                <CircleAlert :size="18" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-red-900">
                                        DICOM-Prüfungen benötigen Aufmerksamkeit
                                    </p>

                                    <span
                                        class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700"
                                    >
                                        {{ focusedSystem.failed_dicom_nodes_count }}
                                    </span>
                                </div>

                                <p class="mt-1 text-sm text-red-700">
                                    Mindestens ein DICOM-Knoten besitzt eine fehlgeschlagene letzte Prüfung.
                                </p>

                                <Link
                                    :href="`/systems/${focusedSystem.public_id}`"
                                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-red-800 hover:underline"
                                >
                                    DICOM-Knoten prüfen
                                    <ArrowRight :size="14" />
                                </Link>
                            </div>
                        </div>
                    </section>

                    <section
                        v-else
                        class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4"
                    >
                        <div
                            class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-700"
                        >
                            <CircleCheck :size="18" />
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-emerald-900">Keine bekannten DICOM-Fehler</p>
                            <p class="mt-1 text-sm text-emerald-700">
                                Für dieses System liegen aktuell keine fehlgeschlagenen letzten Prüfungen vor.
                            </p>
                        </div>
                    </section>

                    <!-- Stammdaten -->
                    <section>
                        <div class="mb-3">
                            <h3 class="font-semibold text-slate-950">Stammdaten</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Organisatorische und produktbezogene Zuordnung</p>
                        </div>

                        <dl class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                <dt class="text-xs text-slate-500">Organisation</dt>
                                <dd class="text-sm font-medium text-slate-900">
                                    {{ focusedSystem.organization.name }}
                                </dd>
                            </div>

                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                <dt class="text-xs text-slate-500">Standort</dt>
                                <dd class="text-sm font-medium text-slate-900">
                                    {{ focusedSystem.site?.name || 'Nicht zugeordnet' }}
                                </dd>
                            </div>

                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                <dt class="text-xs text-slate-500">Abteilung</dt>
                                <dd class="text-sm font-medium text-slate-900">
                                    {{ focusedSystem.department?.name || 'Nicht zugeordnet' }}
                                </dd>
                            </div>

                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                <dt class="text-xs text-slate-500">Hersteller</dt>
                                <dd class="text-sm font-medium text-slate-900">
                                    {{ focusedSystem.vendor || 'Nicht hinterlegt' }}
                                </dd>
                            </div>

                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                <dt class="text-xs text-slate-500">Produkt</dt>
                                <dd class="text-sm font-medium text-slate-900">
                                    {{ focusedSystem.product || 'Nicht hinterlegt' }}
                                </dd>
                            </div>

                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                <dt class="text-xs text-slate-500">FQDN</dt>
                                <dd class="font-mono text-sm font-medium break-all text-slate-900">
                                    {{ focusedSystem.fqdn || 'Nicht hinterlegt' }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>
            </section>

            <section
                v-if="focusedSystem"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <header class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-slate-950">Netzwerkübersicht</h2>
                    </div>

                    <MapPin :size="18" class="text-blue-600" />
                </header>

                <div class="p-5">
                    <DicomNetworkMap
                        v-if="topologyNodes.length > 0"
                        :nodes="topologyNodes"
                        :connections="topologyConnections"
                        :focus-system-public-id="focusedSystem.public_id"
                        :details-enabled="false"
                        compact
                    />

                    <div
                        v-else
                        class="grid min-h-[390px] place-items-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6"
                    >
                        <div class="max-w-xs text-center">
                            <div
                                class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-blue-100 text-blue-700"
                            >
                                <Network :size="25" />
                            </div>

                            <h3 class="mt-4 font-semibold text-slate-950">Keine DICOM-Beziehungen vorhanden</h3>

                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                Für dieses System wurden noch keine Knoten oder direkten Verbindungen dokumentiert.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <p class="mb-3 text-xs font-semibold tracking-wide text-slate-500 uppercase">Legende</p>

                        <div class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-600">
                            <span class="inline-flex items-center gap-2">
                                <span class="h-0.5 w-5 rounded-full bg-blue-600" />
                                C-STORE
                            </span>

                            <span class="inline-flex items-center gap-2">
                                <span class="h-0.5 w-5 rounded-full bg-violet-600" />
                                Worklist
                            </span>

                            <span class="inline-flex items-center gap-2">
                                <span class="h-0.5 w-5 rounded-full bg-cyan-600" />
                                Query
                            </span>

                            <span class="inline-flex items-center gap-2">
                                <span class="h-0.5 w-5 rounded-full bg-amber-600" />
                                C-MOVE
                            </span>

                            <span class="inline-flex items-center gap-2">
                                <span class="h-0.5 w-5 rounded-full bg-emerald-600" />
                                C-GET
                            </span>

                            <span class="inline-flex items-center gap-2">
                                <span class="h-0.5 w-5 rounded-full bg-slate-500" />
                                C-ECHO
                            </span>
                        </div>
                    </div>

                    <Link
                        href="/network"
                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                    >
                        Gesamte Topologie öffnen
                        <ArrowRight :size="15" />
                    </Link>
                </div>
            </section>
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

        <SystemArchiveDialog
            :open="archiveDialogOpen"
            :system-name="actionSystem?.name ?? ''"
            :processing="archiveProcessing"
            @close="closeArchiveDialog"
            @confirm="archiveSystem"
        />
    </AppLayout>
</template>
