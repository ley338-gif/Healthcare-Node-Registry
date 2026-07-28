<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, CircleAlert, FilterX, Network, Pencil, Search, Server } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import SystemArchiveDialog from '../../../Components/registry/systems/SystemArchiveDialog.vue';
import SystemCreateSlideOver from '../../../Components/registry/systems/SystemCreateSlideOver.vue';
import SystemEditSlideOver from '../../../Components/registry/systems/SystemEditSlideOver.vue';
import ContentCard from '../../../Components/ui/ContentCard.vue';
import EmptyState from '../../../Components/ui/EmptyState.vue';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

type SelectOption = { value: string; label: string };
type OrganizationOption = { id: number; name: string };
type SiteOption = { id: number; organization_id: number; name: string };
type DepartmentOption = { id: number; site_id: number; name: string };

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
    organization: { name: string };
    site: { name: string } | null;
    department: { name: string } | null;
};

type PaginatedSystems = { data: SystemItem[]; total: number };

const props = defineProps<{
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
    canManage: boolean;
}>();

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
const selectedSystem = ref<SystemItem | null>(null);

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
        { preserveState: true, replace: true },
    );
};

const resetFilters = (): void => {
    search.value = '';
    type.value = '';
    status.value = '';
    organization.value = null;
    site.value = null;
    department.value = null;
    router.get('/systems', {}, { preserveState: true, replace: true });
};

const labelFor = (options: SelectOption[], value: string): string =>
    options.find((option) => option.value === value)?.label ?? value;

const statusClass = (value: string): string =>
    ({
        active: 'bg-emerald-50 text-emerald-700',
        planned: 'bg-blue-50 text-blue-700',
        maintenance: 'bg-amber-50 text-amber-700',
        inactive: 'bg-slate-100 text-slate-600',
        retired: 'bg-slate-200 text-slate-700',
    })[value] ?? 'bg-slate-100 text-slate-600';

const openEditPanel = (system: SystemItem): void => {
    selectedSystem.value = system;
    editPanelOpen.value = true;
};
const closeEditPanel = (): void => {
    editPanelOpen.value = false;
    selectedSystem.value = null;
};
const openArchiveDialog = (system: SystemItem): void => {
    selectedSystem.value = system;
    archiveDialogOpen.value = true;
};
const closeArchiveDialog = (): void => {
    if (archiveProcessing.value) return;
    archiveDialogOpen.value = false;
    selectedSystem.value = null;
};
const archiveSystem = (): void => {
    if (selectedSystem.value === null) return;
    archiveProcessing.value = true;
    router.post(
        `/systems/${selectedSystem.value.public_id}/archive`,
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
            description="Technische und fachliche Systeme der Healthcare-IT-Infrastruktur."
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

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase">Gefundene Systeme</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ items.total }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase">DICOM-Knoten auf dieser Seite</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">
                    {{ items.data.reduce((sum, item) => sum + item.dicom_nodes_count, 0) }}
                </p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase">Auffällige DICOM-Knoten</p>
                <p class="mt-2 text-2xl font-semibold text-red-600">
                    {{ items.data.reduce((sum, item) => sum + item.failed_dicom_nodes_count, 0) }}
                </p>
            </div>
        </div>

        <ContentCard class="mt-6" :padded="false">
            <form class="border-b border-slate-200 p-5" @submit.prevent="applyFilters">
                <div class="grid gap-3 xl:grid-cols-[minmax(260px,1.4fr)_180px_180px]">
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
                </div>

                <div class="mt-3 grid gap-3 xl:grid-cols-[1fr_1fr_1fr_auto_auto]">
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
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">
                        Filtern
                    </button>
                    <button
                        type="button"
                        :disabled="!hasActiveFilters"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium disabled:opacity-40"
                        @click="resetFilters"
                    >
                        <FilterX :size="16" />
                        Zurücksetzen
                    </button>
                </div>
            </form>

            <EmptyState
                v-if="!hasSystems"
                title="Keine Systeme gefunden"
                description="Passe die Filter an oder lege ein neues System an."
                :icon="Server"
            />

            <div v-else class="divide-y divide-slate-100">
                <article
                    v-for="system in items.data"
                    :key="system.public_id"
                    class="grid gap-4 px-5 py-5 transition hover:bg-slate-50 xl:grid-cols-[1.4fr_1fr_1fr_180px_auto]"
                >
                    <div class="min-w-0">
                        <Link
                            :href="`/systems/${system.public_id}`"
                            class="font-semibold text-slate-950 transition hover:text-blue-700"
                        >
                            {{ system.name }}
                        </Link>
                        <p class="mt-1 truncate text-xs text-slate-500">
                            {{ [system.vendor, system.product].filter(Boolean).join(' · ') || 'Keine Produktangaben' }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                {{ labelFor(systemTypes, system.system_type) }}
                            </span>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="statusClass(system.status)"
                            >
                                {{ labelFor(statuses, system.status) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase">Zuordnung</p>
                        <p class="mt-2 text-sm font-medium text-slate-800">{{ system.organization.name }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{
                                [system.site?.name, system.department?.name].filter(Boolean).join(' · ') ||
                                'Keine weitere Zuordnung'
                            }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase">Netzwerk</p>
                        <p class="mt-2 text-sm font-medium text-slate-800">{{ system.hostname || 'Kein Hostname' }}</p>
                        <p class="mt-1 font-mono text-xs text-slate-500">
                            {{ system.ip_address || 'Keine IP-Adresse' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase">DICOM</p>
                        <div class="mt-2 flex items-center gap-2">
                            <Network :size="16" class="text-slate-400" />
                            <span class="text-sm font-medium text-slate-800"
                                >{{ system.dicom_nodes_count }} Knoten</span
                            >
                        </div>
                        <div
                            v-if="system.failed_dicom_nodes_count > 0"
                            class="mt-2 flex items-center gap-2 text-xs font-medium text-red-700"
                        >
                            <CircleAlert :size="15" />
                            {{ system.failed_dicom_nodes_count }} auffällig
                        </div>
                        <p v-else class="mt-2 text-xs text-emerald-700">Keine bekannten Fehler</p>
                    </div>

                    <div class="flex items-start justify-end gap-1">
                        <Link
                            :href="`/systems/${system.public_id}`"
                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
                        >
                            Öffnen
                        </Link>
                        <template v-if="canManage">
                            <button
                                type="button"
                                :aria-label="`${system.name} bearbeiten`"
                                class="rounded-lg p-2 text-slate-500 hover:bg-blue-50 hover:text-blue-700"
                                @click="openEditPanel(system)"
                            >
                                <Pencil :size="17" />
                            </button>
                            <button
                                type="button"
                                :aria-label="`${system.name} archivieren`"
                                class="rounded-lg p-2 text-slate-500 hover:bg-amber-50 hover:text-amber-700"
                                @click="openArchiveDialog(system)"
                            >
                                <Archive :size="17" />
                            </button>
                        </template>
                    </div>
                </article>
            </div>
        </ContentCard>

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
            :system="selectedSystem"
            :organizations="organizations"
            :sites="sites"
            :departments="departments"
            :system-types="systemTypes"
            :statuses="statuses"
            @close="closeEditPanel"
        />
        <SystemArchiveDialog
            :open="archiveDialogOpen"
            :system-name="selectedSystem?.name ?? ''"
            :processing="archiveProcessing"
            @close="closeArchiveDialog"
            @confirm="archiveSystem"
        />
    </AppLayout>
</template>
