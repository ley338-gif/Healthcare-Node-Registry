<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Download, FilterX, Search, Server } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import SystemCreateSlideOver from '../../../Components/registry/systems/SystemCreateSlideOver.vue';
import EmptyState from '../../../Components/ui/EmptyState.vue';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
type SelectOption = {
    value: string;
    label: string;
    is_active?: boolean;
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
    responsible: string | null;
    criticality: string | null;
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
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    items: PaginatedSystems;
    filters: {
        search: string;
        type: string;
        status: string;
        manufacturer: string;
        criticality: string;
        organization: number | null;
        site: number | null;
        department: number | null;
    };
    systemTypes: SelectOption[];
    statuses: SelectOption[];
    manufacturers: SelectOption[];
    criticalities: SelectOption[];
    organizations: OrganizationOption[];
    sites: SiteOption[];
    departments: DepartmentOption[];
    canManage: boolean;
}>();
const page = usePage();
onMounted(() => {
    const query = new URLSearchParams(page.url.split('?')[1] ?? '');
    const selected = query.get('selected');
    if (selected && /^[a-f0-9-]{36}$/i.test(selected)) {
        query.delete('selected');
        const returnTo = '/systems' + (query.size ? '?' + query.toString() : '');
        router.visit(`/systems/${selected}?return_to=${encodeURIComponent(returnTo)}`, { replace: true });
    }
});
const detailUrl = (system: SystemItem): string =>
    `/systems/${system.public_id}?return_to=${encodeURIComponent(page.url)}`;

const search = ref(props.filters.search);
const type = ref(props.filters.type);
const status = ref(props.filters.status);
const manufacturer = ref(props.filters.manufacturer);
const criticality = ref(props.filters.criticality);
const organization = ref<number | null>(props.filters.organization);
const site = ref<number | null>(props.filters.site);
const department = ref<number | null>(props.filters.department);

const createPanelOpen = ref(false);

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
        manufacturer.value !== '' ||
        criticality.value !== '' ||
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
            manufacturer: manufacturer.value || undefined,
            criticality: criticality.value || undefined,
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
    manufacturer.value = '';
    criticality.value = '';
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
    if (manufacturer.value) params.set('manufacturer', manufacturer.value);
    if (criticality.value) params.set('criticality', criticality.value);
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
                <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-4">
                    <div class="relative">
                        <Search :size="18" class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" />

                        <input
                            v-model="search"
                            type="search"
                            placeholder="Name, Hostname, IP oder Hersteller"
                            class="w-full rounded-xl border border-slate-300 py-2.5 pr-3 pl-10 text-sm"
                        />
                    </div>

                    <select
                        v-model="type"
                        aria-label="Systemtyp"
                        class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                    >
                        <option value="">Alle Typen</option>
                        <option v-for="option in systemTypes" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <select
                        v-model="status"
                        aria-label="Betriebsstatus"
                        class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                    >
                        <option value="">Alle Status</option>
                        <option v-for="option in statuses" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <select
                        v-model="manufacturer"
                        aria-label="Hersteller"
                        class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                    >
                        <option value="">Alle Hersteller</option>
                        <option v-for="option in manufacturers" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <select
                        v-model="criticality"
                        aria-label="Kritikalität"
                        class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                    >
                        <option value="">Alle Kritikalitäten</option>
                        <option v-for="option in criticalities" :key="option.value" :value="option.value">
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

        <section v-else class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-950">Systembestand</h2>
                <p class="text-sm text-slate-500">
                    {{ items.total }} Systeme · System öffnen, um Details und Schnittstellen zu bearbeiten.
                </p>
            </header>
            <div class="divide-y divide-slate-100 md:hidden">
                <Link
                    v-for="system in items.data"
                    :key="system.public_id"
                    :href="detailUrl(system)"
                    class="block px-5 py-4 hover:bg-slate-50"
                >
                    <span class="font-semibold text-blue-700">{{ system.name }}</span>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ labelFor(systemTypes, system.system_type) }} · {{ labelFor(statuses, system.status) }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ system.site?.name || system.organization.name }} ·
                        {{ system.dicom_nodes_count }} DICOM-Knoten
                    </p>
                    <p v-if="system.failed_dicom_nodes_count" class="mt-1 text-xs text-red-700">
                        {{ system.failed_dicom_nodes_count }} Prüfungen fehlgeschlagen
                    </p>
                </Link>
            </div>
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500">
                        <tr>
                            <th class="px-5 py-3">System / Produkt</th>
                            <th class="px-5 py-3">Typ</th>
                            <th class="px-5 py-3">Betriebsstatus</th>
                            <th class="px-5 py-3">Standort</th>
                            <th class="px-5 py-3">Netzwerk</th>
                            <th class="px-5 py-3">DICOM</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="system in items.data" :key="system.public_id" class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <Link :href="detailUrl(system)" class="font-semibold text-blue-700 hover:underline">{{
                                    system.name
                                }}</Link>
                                <p v-if="system.product || system.version" class="mt-1 text-xs text-slate-500">
                                    {{ [system.product, system.version].filter(Boolean).join(' · ') }}
                                </p>
                            </td>
                            <td class="px-5 py-4">{{ labelFor(systemTypes, system.system_type) }}</td>
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                    :class="statusClass(system.status)"
                                    >{{ labelFor(statuses, system.status) }}</span
                                >
                            </td>
                            <td class="px-5 py-4">
                                {{ system.site?.name || 'Nicht zugeordnet' }}
                                <p class="mt-1 text-xs text-slate-500">{{ system.organization.name }}</p>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs">
                                {{ system.ip_address || system.hostname || 'Nicht hinterlegt' }}
                            </td>
                            <td class="px-5 py-4">
                                {{ system.dicom_nodes_count }} Knoten<span
                                    v-if="system.failed_dicom_nodes_count > 0"
                                    class="mt-1 block text-xs text-red-700"
                                    >{{ system.failed_dicom_nodes_count }} fehlgeschlagen</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <nav v-if="items.links.length > 3" aria-label="Systemseiten" class="mt-4 flex flex-wrap gap-2">
            <template v-for="(link, index) in items.links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :aria-current="link.active ? 'page' : undefined"
                    class="rounded-lg border px-3 py-2 text-sm"
                    :class="
                        link.active ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-300 text-slate-600'
                    "
                    >{{ index === 0 ? 'Zurück' : index === items.links.length - 1 ? 'Weiter' : link.label }}</Link
                >
                <span v-else class="px-3 py-2 text-sm text-slate-400">{{
                    index === 0 ? 'Zurück' : index === items.links.length - 1 ? 'Weiter' : link.label
                }}</span>
            </template>
        </nav>

        <SystemCreateSlideOver
            :open="createPanelOpen"
            :organizations="organizations"
            :sites="sites"
            :departments="departments"
            :system-types="systemTypes"
            :statuses="statuses"
            :manufacturers="manufacturers"
            :criticalities="criticalities"
            @close="createPanelOpen = false"
        />
    </AppLayout>
</template>
