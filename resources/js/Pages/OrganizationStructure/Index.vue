<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    ChevronDown,
    ChevronRight,
    Cable,
    CheckCircle2,
    FileText,
    History,
    Hospital,
    Layers3,
    MapPin,
    MonitorCog,
    Network,
    Pencil,
    Search,
    UsersRound,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import AuditHistoryPanel, { type AuditEvent } from '../../Components/audit/AuditHistoryPanel.vue';
import DocumentationPanel from '../../Components/documentation/DocumentationPanel.vue';
import type { RegistryDocumentationItem } from '../../Components/documentation/documentationTypes';
import type { RegistryDocumentItem } from '../../Components/documents/RegistryDocumentList.vue';
import {
    departmentDocumentationSections,
    organizationDocumentationSections,
    siteDocumentationSections,
} from '../../Components/documentation/organizationStructureDocumentationSections';

type DepartmentItem = {
    id: number;
    public_id: string;
    site_id: number;
    name: string;
    code: string | null;
    specialty: string | null;
    description: string | null;
};

type SiteItem = {
    id: number;
    public_id: string;
    organization_id: number;
    name: string;
    code: string | null;
    street: string | null;
    postal_code: string | null;
    city: string | null;
    country_code: string | null;
    timezone: string | null;
    description: string | null;
    departments: DepartmentItem[];
};

type OrganizationItem = {
    id: number;
    public_id: string;
    name: string;
    short_name: string | null;
    description: string | null;
    sites: SiteItem[];
};

type SystemItem = {
    public_id: string;
    organization_id: number;
    site_id: number | null;
    department_id: number | null;
    name: string;
    system_type: string;
    status: string;
    hostname: string | null;
    ip_address: string | null;
    vendor: string | null;
    product: string | null;
    dicom_nodes_count: number;
    connection_count: number;
    verified_nodes_count: number;
    latest_verified_at: string | null;
};

type SelectedUnit =
    | { type: 'organization'; organization: OrganizationItem }
    | { type: 'site'; organization: OrganizationItem; site: SiteItem }
    | {
          type: 'department';
          organization: OrganizationItem;
          site: SiteItem;
          department: DepartmentItem;
      };

type WorkspaceTab = 'overview' | 'general' | 'systems' | 'documents' | 'history';

const props = defineProps<{
    summary: {
        organizations: number;
        sites: number;
        departments: number;
    };
    organizations: OrganizationItem[];
    systems: SystemItem[];
    selectedContext: { type: 'organization' | 'site' | 'department'; public_id: string } | null;
    history: {
        data: AuditEvent[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    } | null;
    historyStats: { total: number; today: number; last7Days: number; last30Days: number };
    historyFilters: Record<string, string | undefined>;
    historyEventTypes: string[];
    historyUsers: Array<{ public_id: string; name: string }>;
    documentation: RegistryDocumentationItem[];
    documents: RegistryDocumentItem[];
    canManageDocumentation: boolean;
}>();

const search = ref('');
const systemSearch = ref('');
const systemStatusFilter = ref('');
const systemTypeFilter = ref('');
const systemSort = ref<'name' | 'type' | 'status'>('name');
const activeTab = ref<WorkspaceTab>('overview');
const initialSelection = (): SelectedUnit | null => {
    const context = props.selectedContext;
    if (context === null) return null;

    for (const organization of props.organizations) {
        if (context.type === 'organization' && organization.public_id === context.public_id) {
            return { type: 'organization', organization };
        }
        for (const site of organization.sites) {
            if (context.type === 'site' && site.public_id === context.public_id) {
                return { type: 'site', organization, site };
            }
            const department = site.departments.find((item) => item.public_id === context.public_id);
            if (context.type === 'department' && department) {
                return { type: 'department', organization, site, department };
            }
        }
    }

    return null;
};
const selected = ref<SelectedUnit | null>(initialSelection());
watch(selected, () => {
    activeTab.value = 'overview';
});
const expandedOrganizations = ref(new Set(props.organizations.map((item) => item.public_id)));
const expandedSites = ref(
    new Set(props.organizations.flatMap((organization) => organization.sites.map((site) => site.public_id))),
);

const tabs: Array<{ id: WorkspaceTab; label: string; icon: typeof Building2 }> = [
    { id: 'overview', label: 'Übersicht', icon: Layers3 },
    { id: 'general', label: 'Allgemein', icon: Building2 },
    { id: 'systems', label: 'Systeme', icon: MonitorCog },
    { id: 'documents', label: 'Dokumentation', icon: FileText },
    { id: 'history', label: 'Historie', icon: History },
];

const documentationSections = computed(() => {
    if (selected.value?.type === 'site') return siteDocumentationSections;
    if (selected.value?.type === 'department') return departmentDocumentationSections;
    return organizationDocumentationSections;
});
const documentationType = computed<'organizations' | 'sites' | 'departments'>(() => {
    if (selected.value?.type === 'site') return 'sites';
    if (selected.value?.type === 'department') return 'departments';
    return 'organizations';
});
const documentationMasterData = computed<Array<{ label: string; value: string | null }>>(() => {
    const unit = selected.value;
    if (unit?.type === 'organization') {
        return [
            { label: 'Name', value: unit.organization.name },
            { label: 'Kurzname', value: unit.organization.short_name },
            { label: 'Beschreibung', value: unit.organization.description },
        ];
    }
    if (unit?.type === 'site') {
        return [
            { label: 'Name', value: unit.site.name },
            { label: 'Code', value: unit.site.code },
            {
                label: 'Adresse',
                value: [unit.site.street, unit.site.postal_code, unit.site.city].filter(Boolean).join(', ') || null,
            },
            { label: 'Zeitzone', value: unit.site.timezone },
        ];
    }
    if (unit?.type === 'department') {
        return [
            { label: 'Name', value: unit.department.name },
            { label: 'Code', value: unit.department.code },
            { label: 'Fachrichtung', value: unit.department.specialty },
            { label: 'Standort', value: unit.site.name },
        ];
    }
    return [];
});

const filteredOrganizations = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (term === '') {
        return props.organizations;
    }

    return props.organizations
        .map((organization) => {
            const organizationMatches = [organization.name, organization.short_name]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(term));

            const sites = organization.sites
                .map((site) => ({
                    ...site,
                    departments: site.departments.filter((department) =>
                        [department.name, department.code, department.specialty]
                            .filter(Boolean)
                            .some((value) => String(value).toLowerCase().includes(term)),
                    ),
                }))
                .filter(
                    (site) =>
                        [site.name, site.code, site.city]
                            .filter(Boolean)
                            .some((value) => String(value).toLowerCase().includes(term)) || site.departments.length > 0,
                );

            if (!organizationMatches && sites.length === 0) {
                return null;
            }

            return {
                ...organization,
                sites: organizationMatches ? organization.sites : sites,
            };
        })
        .filter((organization): organization is OrganizationItem => organization !== null);
});

const title = computed(() => {
    if (selected.value === null) return 'Keine Auswahl';
    if (selected.value.type === 'organization') return selected.value.organization.name;
    if (selected.value.type === 'site') return selected.value.site.name;
    return selected.value.department.name;
});

const typeLabel = computed(() => {
    if (selected.value === null) return '';

    return {
        organization: 'Organisation',
        site: 'Standort',
        department: 'Abteilung',
    }[selected.value.type];
});

const description = computed(() => {
    if (selected.value === null) return null;
    if (selected.value.type === 'organization') return selected.value.organization.description;
    if (selected.value.type === 'site') return selected.value.site.description;
    return selected.value.department.description;
});

const editHref = computed(() => {
    if (selected.value === null) return '#';
    if (selected.value.type === 'organization') return '/organizations';
    if (selected.value.type === 'site') return '/sites';
    return '/departments';
});

const breadcrumb = computed(() => {
    if (selected.value === null) return [];
    if (selected.value.type === 'organization') return [selected.value.organization.name];
    if (selected.value.type === 'site') return [selected.value.organization.name, selected.value.site.name];

    return [selected.value.organization.name, selected.value.site.name, selected.value.department.name];
});

const selectedCode = computed(() => {
    if (selected.value === null) return null;
    if (selected.value.type === 'organization') return selected.value.organization.short_name;
    if (selected.value.type === 'site') return selected.value.site.code;
    return selected.value.department.code;
});

const selectedLocation = computed(() => {
    if (selected.value === null) return null;
    if (selected.value.type === 'organization') return null;

    const site = selected.value.site;

    return [site.street, [site.postal_code, site.city].filter(Boolean).join(' ')].filter(Boolean).join(', ') || null;
});

const selectedSpecialty = computed(() => {
    if (selected.value?.type !== 'department') return null;
    return selected.value.department.specialty;
});

const siteCount = computed(() => {
    if (selected.value?.type === 'organization') return selected.value.organization.sites.length;
    if (selected.value) return 1;
    return 0;
});

const departmentCount = computed(() => {
    if (selected.value?.type === 'organization') {
        return selected.value.organization.sites.reduce((total, site) => total + site.departments.length, 0);
    }

    if (selected.value?.type === 'site') return selected.value.site.departments.length;
    if (selected.value) return 1;
    return 0;
});

const parentLabel = computed(() => {
    if (selected.value === null) return '—';
    if (selected.value.type === 'organization') return 'Oberste Ebene';
    if (selected.value.type === 'site') return selected.value.organization.name;
    return selected.value.site.name;
});

const scopedSystems = computed(() => {
    const selection = selected.value;

    if (selection === null) {
        return [];
    }

    if (selection.type === 'organization') {
        return props.systems.filter((system) => system.organization_id === selection.organization.id);
    }

    if (selection.type === 'site') {
        return props.systems.filter((system) => system.site_id === selection.site.id);
    }

    return props.systems.filter((system) => system.department_id === selection.department.id);
});

const scopedDicomNodeCount = computed(() =>
    scopedSystems.value.reduce((total, system) => total + system.dicom_nodes_count, 0),
);

const scopedConnectionCount = computed(() =>
    scopedSystems.value.reduce((total, system) => total + system.connection_count, 0),
);

const scopedWarningCount = computed(
    () => scopedSystems.value.filter((system) => !['active', 'planned'].includes(system.status)).length,
);

const systemTypeOptions = computed(() =>
    [...new Set(scopedSystems.value.map((system) => system.system_type))].sort((left, right) =>
        left.localeCompare(right, 'de'),
    ),
);

const filteredScopedSystems = computed(() => {
    const term = systemSearch.value.trim().toLowerCase();

    return scopedSystems.value
        .filter((system) => {
            const matchesSearch =
                term === '' ||
                [system.name, system.hostname, system.ip_address, system.vendor, system.product, system.system_type]
                    .filter(Boolean)
                    .some((value) => String(value).toLowerCase().includes(term));
            const matchesStatus = systemStatusFilter.value === '' || system.status === systemStatusFilter.value;
            const matchesType = systemTypeFilter.value === '' || system.system_type === systemTypeFilter.value;

            return matchesSearch && matchesStatus && matchesType;
        })
        .sort((left, right) => {
            if (systemSort.value === 'type') {
                return (
                    left.system_type.localeCompare(right.system_type, 'de') || left.name.localeCompare(right.name, 'de')
                );
            }

            if (systemSort.value === 'status') {
                return left.status.localeCompare(right.status, 'de') || left.name.localeCompare(right.name, 'de');
            }

            return left.name.localeCompare(right.name, 'de');
        });
});

const resetSystemFilters = (): void => {
    systemSearch.value = '';
    systemStatusFilter.value = '';
    systemTypeFilter.value = '';
    systemSort.value = 'name';
};

const systemStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        active: 'Aktiv',
        inactive: 'Inaktiv',
        maintenance: 'Wartung',
        degraded: 'Beeinträchtigt',
        planned: 'Geplant',
    };

    return labels[status] ?? status;
};

const systemStatusClass = (status: string): string => {
    const classes: Record<string, string> = {
        active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        inactive: 'bg-slate-100 text-slate-600 ring-slate-200',
        maintenance: 'bg-amber-50 text-amber-700 ring-amber-200',
        degraded: 'bg-red-50 text-red-700 ring-red-200',
        planned: 'bg-blue-50 text-blue-700 ring-blue-200',
    };

    return classes[status] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
};

const formatDateTime = (value: string | null): string => {
    if (value === null) return 'Noch nicht geprüft';

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const childrenTitle = computed(() => {
    if (selected.value?.type === 'organization') return 'Standorte';
    if (selected.value?.type === 'site') return 'Abteilungen';
    return 'Untergeordnete Einträge';
});

const childrenDescription = computed(() => {
    if (selected.value?.type === 'organization') return 'Direkt zugeordnete Standorte dieser Organisation.';
    if (selected.value?.type === 'site') return 'Abteilungen innerhalb dieses Standorts.';
    return 'Für Abteilungen sind derzeit keine weiteren Ebenen hinterlegt.';
});

const children = computed(() => {
    if (selected.value?.type === 'organization') {
        const organizationSelection = selected.value;

        return organizationSelection.organization.sites.map((site) => ({
            id: site.public_id,
            name: site.name,
            type: 'Standort' as const,
            code: site.code,
            detail: site.city,
            count: site.departments.length,
            organization: organizationSelection.organization,
            site,
        }));
    }

    if (selected.value?.type === 'site') {
        const siteSelection = selected.value;

        return siteSelection.site.departments.map((department) => ({
            id: department.public_id,
            name: department.name,
            type: 'Abteilung' as const,
            code: department.code,
            detail: department.specialty,
            count: null,
            organization: siteSelection.organization,
            site: siteSelection.site,
            department,
        }));
    }

    return [];
});

const selectUnit = (unit: SelectedUnit): void => {
    selected.value = unit;
    activeTab.value = 'overview';
    const publicId =
        unit.type === 'organization'
            ? unit.organization.public_id
            : unit.type === 'site'
              ? unit.site.public_id
              : unit.department.public_id;
    router.get('/structure', { selected_type: unit.type, selected_id: publicId }, { preserveScroll: true });
};

const selectChild = (row: (typeof children.value)[number]): void => {
    if (row.type === 'Standort') {
        selectUnit({
            type: 'site',
            organization: row.organization,
            site: row.site,
        });

        expandedOrganizations.value = new Set([...expandedOrganizations.value, row.organization.public_id]);
        return;
    }

    selectUnit({
        type: 'department',
        organization: row.organization,
        site: row.site,
        department: row.department,
    });

    expandedOrganizations.value = new Set([...expandedOrganizations.value, row.organization.public_id]);
    expandedSites.value = new Set([...expandedSites.value, row.site.public_id]);
};

const toggle = (set: Set<string>, publicId: string): Set<string> => {
    const next = new Set(set);

    if (next.has(publicId)) {
        next.delete(publicId);
    } else {
        next.add(publicId);
    }

    return next;
};
</script>

<template>
    <Head title="Organisationsstruktur" />

    <AppLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-[0.18em] text-blue-600 uppercase">Registry</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Organisationsstruktur</h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-500">
                        Organisationen, Standorte und Abteilungen zentral verwalten und im Zusammenhang betrachten.
                    </p>
                </div>
            </header>

            <div class="grid min-h-[720px] gap-5 xl:grid-cols-[330px_minmax(0,1fr)]">
                <aside
                    class="overflow-hidden rounded-2xl border border-slate-300 bg-slate-50 shadow-sm xl:border-r-2 xl:border-r-slate-300"
                >
                    <div class="border-b border-slate-300 bg-white px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="font-semibold text-slate-950">Hierarchie</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ summary.organizations }} Organisationen · {{ summary.sites }} Standorte ·
                                    {{ summary.departments }} Abteilungen
                                </p>
                            </div>
                            <Layers3 :size="18" class="mt-0.5 text-blue-600" />
                        </div>

                        <div class="relative mt-4">
                            <Search :size="17" class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Hierarchie durchsuchen"
                                class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pr-3 pl-10 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                            />
                        </div>
                    </div>

                    <div class="max-h-[650px] overflow-y-auto bg-slate-50/80 p-3">
                        <div
                            v-if="filteredOrganizations.length === 0"
                            class="px-4 py-12 text-center text-sm text-slate-500"
                        >
                            Keine passenden Einträge gefunden.
                        </div>

                        <div v-for="organization in filteredOrganizations" :key="organization.public_id" class="mb-2">
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100"
                                    @click="
                                        expandedOrganizations = toggle(expandedOrganizations, organization.public_id)
                                    "
                                >
                                    <ChevronDown v-if="expandedOrganizations.has(organization.public_id)" :size="15" />
                                    <ChevronRight v-else :size="15" />
                                </button>

                                <button
                                    type="button"
                                    :class="[
                                        'flex min-w-0 flex-1 items-center gap-2 rounded-xl px-3 py-2.5 text-left transition',
                                        selected?.type === 'organization' &&
                                        selected.organization.public_id === organization.public_id
                                            ? 'bg-blue-50 text-blue-950 ring-1 ring-blue-200 ring-inset'
                                            : 'text-slate-700 hover:bg-slate-50',
                                    ]"
                                    @click="selectUnit({ type: 'organization', organization })"
                                >
                                    <Building2 :size="17" class="shrink-0 text-blue-600" />
                                    <span class="min-w-0 flex-1 truncate text-sm font-semibold">{{
                                        organization.name
                                    }}</span>
                                    <span
                                        class="shrink-0 rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200"
                                    >
                                        {{ organization.sites.length }}
                                    </span>
                                </button>
                            </div>

                            <div
                                v-if="expandedOrganizations.has(organization.public_id)"
                                class="ml-5 border-l border-slate-200 pl-3"
                            >
                                <div v-for="site in organization.sites" :key="site.public_id" class="mt-1">
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100"
                                            @click="expandedSites = toggle(expandedSites, site.public_id)"
                                        >
                                            <ChevronDown v-if="expandedSites.has(site.public_id)" :size="14" />
                                            <ChevronRight v-else :size="14" />
                                        </button>

                                        <button
                                            type="button"
                                            :class="[
                                                'flex min-w-0 flex-1 items-center gap-2 rounded-xl px-3 py-2 text-left transition',
                                                (selected?.type === 'site' || selected?.type === 'department') &&
                                                selected.site.public_id === site.public_id
                                                    ? 'bg-slate-100 text-slate-950'
                                                    : 'text-slate-700 hover:bg-slate-50',
                                            ]"
                                            @click="selectUnit({ type: 'site', organization, site })"
                                        >
                                            <Hospital :size="16" class="shrink-0 text-slate-600" />
                                            <span class="min-w-0 flex-1 truncate text-sm font-medium">{{
                                                site.name
                                            }}</span>
                                            <span
                                                class="shrink-0 rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200"
                                            >
                                                {{ site.departments.length }}
                                            </span>
                                        </button>
                                    </div>

                                    <div
                                        v-if="expandedSites.has(site.public_id)"
                                        class="ml-5 border-l border-slate-200 pl-3"
                                    >
                                        <button
                                            v-for="department in site.departments"
                                            :key="department.public_id"
                                            type="button"
                                            :class="[
                                                'mt-1 flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left transition',
                                                selected?.type === 'department' &&
                                                selected.department.public_id === department.public_id
                                                    ? 'bg-blue-50 font-medium text-blue-950 ring-1 ring-blue-200 ring-inset'
                                                    : 'text-slate-700 hover:bg-slate-50',
                                            ]"
                                            @click="selectUnit({ type: 'department', organization, site, department })"
                                        >
                                            <UsersRound :size="15" class="shrink-0 text-slate-500" />
                                            <span class="truncate text-sm">{{ department.name }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <section
                    v-if="selected"
                    class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-200 bg-gradient-to-br from-white to-slate-50 px-6 py-5 lg:px-7">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500">
                                    <template v-for="(crumb, index) in breadcrumb" :key="`${crumb}-${index}`">
                                        <span v-if="index > 0" class="text-slate-300">/</span>
                                        <span>{{ crumb }}</span>
                                    </template>
                                </div>

                                <div class="mt-4 flex items-start gap-3">
                                    <div class="rounded-2xl bg-blue-50 p-3 text-blue-700 ring-1 ring-blue-100">
                                        <Building2 v-if="selected.type === 'organization'" :size="22" />
                                        <Hospital v-else-if="selected.type === 'site'" :size="22" />
                                        <UsersRound v-else :size="22" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold tracking-[0.14em] text-blue-600 uppercase">
                                            {{ typeLabel }}
                                        </p>
                                        <h2 class="mt-1 truncate text-2xl font-semibold tracking-tight text-slate-950">
                                            {{ title }}
                                        </h2>
                                        <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                                            {{ description || 'Keine Beschreibung hinterlegt.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <Link
                                :href="editHref"
                                class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                            >
                                <Pencil :size="16" />
                                Bearbeiten
                            </Link>
                        </div>
                    </div>

                    <nav class="border-b border-slate-200 px-4 lg:px-6">
                        <div class="flex gap-1 overflow-x-auto">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                type="button"
                                :class="[
                                    'inline-flex shrink-0 items-center gap-2 border-b-2 px-3 py-4 text-sm font-semibold transition',
                                    activeTab === tab.id
                                        ? 'border-blue-600 text-blue-700'
                                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800',
                                ]"
                                @click="activeTab = tab.id"
                            >
                                <component :is="tab.icon" :size="15" />
                                {{ tab.label }}
                            </button>
                        </div>
                    </nav>

                    <div v-if="activeTab === 'overview'" class="space-y-6 p-5 lg:p-7">
                        <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-slate-500">Standorte</p>
                                    <MapPin :size="18" class="text-blue-600" />
                                </div>
                                <p class="mt-4 text-3xl font-semibold text-slate-950">{{ siteCount }}</p>
                                <p class="mt-1 text-xs text-slate-500">Im aktuellen Kontext</p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-slate-500">Abteilungen</p>
                                    <UsersRound :size="18" class="text-blue-600" />
                                </div>
                                <p class="mt-4 text-3xl font-semibold text-slate-950">{{ departmentCount }}</p>
                                <p class="mt-1 text-xs text-slate-500">Direkt oder untergeordnet</p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-slate-500">Systeme</p>
                                    <MonitorCog :size="18" class="text-blue-600" />
                                </div>
                                <p class="mt-4 text-3xl font-semibold text-slate-950">{{ scopedSystems.length }}</p>
                                <p class="mt-1 text-xs text-slate-500">Im aktuellen Kontext</p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-slate-500">DICOM-Knoten</p>
                                    <Layers3 :size="18" class="text-blue-600" />
                                </div>
                                <p class="mt-4 text-3xl font-semibold text-slate-950">{{ scopedDicomNodeCount }}</p>
                                <p class="mt-1 text-xs text-slate-500">Über zugeordnete Systeme</p>
                            </div>
                        </div>

                        <div class="grid gap-5 2xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
                            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="font-semibold text-slate-950">Allgemeine Informationen</h3>
                                        <p class="mt-1 text-xs text-slate-500">Stammdaten der ausgewählten Einheit</p>
                                    </div>
                                    <Building2 :size="18" class="text-slate-400" />
                                </div>

                                <dl class="mt-5 divide-y divide-slate-100 rounded-xl border border-slate-200">
                                    <div class="grid gap-1 px-4 py-3 sm:grid-cols-[160px_1fr]">
                                        <dt class="text-xs font-medium text-slate-500">Typ</dt>
                                        <dd class="text-sm font-medium text-slate-900">{{ typeLabel }}</dd>
                                    </div>
                                    <div class="grid gap-1 px-4 py-3 sm:grid-cols-[160px_1fr]">
                                        <dt class="text-xs font-medium text-slate-500">Code / Kurzname</dt>
                                        <dd class="text-sm font-medium text-slate-900">
                                            {{ selectedCode || 'Nicht hinterlegt' }}
                                        </dd>
                                    </div>
                                    <div class="grid gap-1 px-4 py-3 sm:grid-cols-[160px_1fr]">
                                        <dt class="text-xs font-medium text-slate-500">Übergeordnete Einheit</dt>
                                        <dd class="text-sm font-medium text-slate-900">{{ parentLabel }}</dd>
                                    </div>
                                    <div v-if="selectedLocation" class="grid gap-1 px-4 py-3 sm:grid-cols-[160px_1fr]">
                                        <dt class="text-xs font-medium text-slate-500">Adresse</dt>
                                        <dd class="text-sm font-medium text-slate-900">{{ selectedLocation }}</dd>
                                    </div>
                                    <div v-if="selectedSpecialty" class="grid gap-1 px-4 py-3 sm:grid-cols-[160px_1fr]">
                                        <dt class="text-xs font-medium text-slate-500">Fachrichtung</dt>
                                        <dd class="text-sm font-medium text-slate-900">{{ selectedSpecialty }}</dd>
                                    </div>
                                </dl>
                            </section>

                            <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="font-semibold text-slate-950">Verknüpfungen & Statistik</h3>
                                        <p class="mt-1 text-xs text-slate-500">Kontext der ausgewählten Einheit</p>
                                    </div>
                                    <Layers3 :size="18" class="text-slate-400" />
                                </div>

                                <div class="mt-5 space-y-3">
                                    <div
                                        class="flex items-center justify-between rounded-xl bg-white px-4 py-3 ring-1 ring-slate-200"
                                    >
                                        <span class="text-sm text-slate-600">Direkte Kinder</span>
                                        <strong class="text-sm text-slate-950">{{ children.length }}</strong>
                                    </div>
                                    <div
                                        class="flex items-center justify-between rounded-xl bg-white px-4 py-3 ring-1 ring-slate-200"
                                    >
                                        <span class="text-sm text-slate-600">Organisationen gesamt</span>
                                        <strong class="text-sm text-slate-950">{{ summary.organizations }}</strong>
                                    </div>
                                    <div
                                        class="flex items-center justify-between rounded-xl bg-white px-4 py-3 ring-1 ring-slate-200"
                                    >
                                        <span class="text-sm text-slate-600">Standorte gesamt</span>
                                        <strong class="text-sm text-slate-950">{{ summary.sites }}</strong>
                                    </div>
                                    <div
                                        class="flex items-center justify-between rounded-xl bg-white px-4 py-3 ring-1 ring-slate-200"
                                    >
                                        <span class="text-sm text-slate-600">Abteilungen gesamt</span>
                                        <strong class="text-sm text-slate-950">{{ summary.departments }}</strong>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <section>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-950">{{ childrenTitle }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ childrenDescription }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                    {{ children.length }} Einträge
                                </span>
                            </div>

                            <div
                                v-if="children.length === 0"
                                class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center text-sm text-slate-500"
                            >
                                Keine untergeordneten Einträge vorhanden.
                            </div>

                            <div
                                v-else
                                class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                            >
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[720px] text-left">
                                        <thead class="bg-slate-50 text-xs text-slate-500">
                                            <tr>
                                                <th class="px-4 py-3 font-semibold">Name</th>
                                                <th class="px-4 py-3 font-semibold">Typ</th>
                                                <th class="px-4 py-3 font-semibold">Code</th>
                                                <th class="px-4 py-3 font-semibold">Zusatz</th>
                                                <th class="px-4 py-3 font-semibold">Kinder</th>
                                                <th class="px-4 py-3" />
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr
                                                v-for="row in children"
                                                :key="row.id"
                                                class="transition hover:bg-slate-50"
                                            >
                                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                                    {{ row.name }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-slate-600">{{ row.type }}</td>
                                                <td class="px-4 py-3 font-mono text-xs text-slate-600">
                                                    {{ row.code || '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-slate-600">
                                                    {{ row.detail || '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-slate-600">{{ row.count ?? '—' }}</td>
                                                <td class="px-4 py-3 text-right">
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-700 transition hover:text-blue-900"
                                                        @click="selectChild(row)"
                                                    >
                                                        Auswählen
                                                        <ArrowRight :size="14" />
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div v-else-if="activeTab === 'systems'" class="space-y-6 p-5 lg:p-7">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-950">Zugeordnete Systeme</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    Operative Übersicht für die ausgewählte {{ typeLabel.toLowerCase() }}.
                                </p>
                            </div>
                            <Link
                                href="/systems"
                                class="inline-flex items-center gap-2 self-start rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                            >
                                Systeme verwalten
                                <ArrowRight :size="14" />
                            </Link>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div
                                class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm"
                            >
                                <div class="rounded-lg bg-slate-100 p-2 text-slate-600">
                                    <MonitorCog :size="18" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Systeme</p>
                                    <p class="mt-0.5 text-xl font-semibold text-slate-950">
                                        {{ scopedSystems.length }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50/60 px-4 py-3 shadow-sm"
                            >
                                <div class="rounded-lg bg-blue-100 p-2 text-blue-700">
                                    <Layers3 :size="18" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">
                                        DICOM-Knoten
                                    </p>
                                    <p class="mt-0.5 text-xl font-semibold text-blue-950">{{ scopedDicomNodeCount }}</p>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-3 rounded-xl border border-violet-200 bg-violet-50/60 px-4 py-3 shadow-sm"
                            >
                                <div class="rounded-lg bg-violet-100 p-2 text-violet-700">
                                    <Cable :size="18" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold tracking-wide text-violet-700 uppercase">
                                        Verbindungen
                                    </p>
                                    <p class="mt-0.5 text-xl font-semibold text-violet-950">
                                        {{ scopedConnectionCount }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50/60 px-4 py-3 shadow-sm"
                            >
                                <div class="rounded-lg bg-amber-100 p-2 text-amber-700">
                                    <History :size="18" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold tracking-wide text-amber-700 uppercase">Hinweise</p>
                                    <p class="mt-0.5 text-xl font-semibold text-amber-950">{{ scopedWarningCount }}</p>
                                </div>
                            </div>
                        </div>

                        <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_180px_180px_170px_auto]">
                                <label class="relative block">
                                    <Search
                                        :size="17"
                                        class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400"
                                    />
                                    <input
                                        v-model="systemSearch"
                                        type="search"
                                        placeholder="Systeme durchsuchen"
                                        class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pr-3 pl-10 text-sm text-slate-900 transition outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                    />
                                </label>

                                <select
                                    v-model="systemStatusFilter"
                                    class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 transition outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                >
                                    <option value="">Alle Status</option>
                                    <option value="active">Aktiv</option>
                                    <option value="planned">Geplant</option>
                                    <option value="maintenance">Wartung</option>
                                    <option value="inactive">Inaktiv</option>
                                    <option value="degraded">Beeinträchtigt</option>
                                </select>

                                <select
                                    v-model="systemTypeFilter"
                                    class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 transition outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                >
                                    <option value="">Alle Typen</option>
                                    <option
                                        v-for="systemType in systemTypeOptions"
                                        :key="systemType"
                                        :value="systemType"
                                    >
                                        {{ systemType }}
                                    </option>
                                </select>

                                <select
                                    v-model="systemSort"
                                    class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 transition outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                >
                                    <option value="name">Name sortieren</option>
                                    <option value="type">Typ sortieren</option>
                                    <option value="status">Status sortieren</option>
                                </select>

                                <button
                                    type="button"
                                    class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-white hover:text-slate-950"
                                    @click="resetSystemFilters"
                                >
                                    Zurücksetzen
                                </button>
                            </div>
                        </section>

                        <div
                            v-if="scopedSystems.length === 0"
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center"
                        >
                            <MonitorCog :size="32" class="mx-auto text-slate-400" />
                            <h4 class="mt-4 font-semibold text-slate-900">Noch keine Systeme zugeordnet</h4>
                            <p class="mt-2 text-sm text-slate-500">
                                Dieser Einheit sind derzeit keine aktiven Systeme zugeordnet.
                            </p>
                            <Link
                                href="/systems"
                                class="mt-5 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                            >
                                System anlegen
                                <ArrowRight :size="14" />
                            </Link>
                        </div>

                        <div
                            v-else-if="filteredScopedSystems.length === 0"
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center"
                        >
                            <Search :size="30" class="mx-auto text-slate-400" />
                            <h4 class="mt-4 font-semibold text-slate-900">Keine passenden Systeme</h4>
                            <p class="mt-2 text-sm text-slate-500">Passe Suche oder Filter an.</p>
                            <button
                                type="button"
                                class="mt-4 text-sm font-semibold text-blue-700 hover:text-blue-900"
                                @click="resetSystemFilters"
                            >
                                Filter zurücksetzen
                            </button>
                        </div>

                        <div v-else class="grid gap-4 xl:grid-cols-3 2xl:grid-cols-4">
                            <article
                                v-for="system in filteredScopedSystems"
                                :key="system.public_id"
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <div class="shrink-0 rounded-xl bg-blue-50 p-2.5 text-blue-700">
                                            <MonitorCog :size="19" />
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="truncate font-semibold text-slate-950">{{ system.name }}</h4>
                                            <p class="mt-1 truncate text-sm text-slate-500">
                                                {{
                                                    [system.vendor, system.product].filter(Boolean).join(' · ') ||
                                                    system.system_type
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                                            systemStatusClass(system.status),
                                        ]"
                                    >
                                        {{ systemStatusLabel(system.status) }}
                                    </span>
                                </div>

                                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                    <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                                        <p class="text-xs font-medium text-slate-500">Systemtyp</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-800">
                                            {{ system.system_type }}
                                        </p>
                                    </div>
                                    <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                                        <p class="text-xs font-medium text-slate-500">Netzwerk</p>
                                        <p class="mt-1 truncate font-mono text-xs font-semibold text-slate-800">
                                            {{ system.hostname || system.ip_address || 'Nicht hinterlegt' }}
                                        </p>
                                        <p
                                            v-if="system.hostname && system.ip_address"
                                            class="mt-1 font-mono text-xs text-slate-400"
                                        >
                                            {{ system.ip_address }}
                                        </p>
                                    </div>
                                </div>

                                <dl class="mt-4 grid grid-cols-3 gap-2">
                                    <div class="rounded-xl border border-blue-200 bg-blue-50 px-2 py-3 text-center">
                                        <Layers3 :size="16" class="mx-auto text-blue-600" />
                                        <dd class="mt-1 text-xl font-bold text-blue-950">
                                            {{ system.dicom_nodes_count }}
                                        </dd>
                                        <dt class="mt-0.5 text-[11px] font-semibold text-blue-700 uppercase">DICOM</dt>
                                    </div>
                                    <div class="rounded-xl border border-violet-200 bg-violet-50 px-2 py-3 text-center">
                                        <Cable :size="16" class="mx-auto text-violet-600" />
                                        <dd class="mt-1 text-xl font-bold text-violet-950">
                                            {{ system.connection_count }}
                                        </dd>
                                        <dt class="mt-0.5 text-[11px] font-semibold text-violet-700 uppercase">
                                            Verbindungen
                                        </dt>
                                    </div>
                                    <div
                                        class="rounded-xl border border-emerald-200 bg-emerald-50 px-2 py-3 text-center"
                                    >
                                        <CheckCircle2 :size="16" class="mx-auto text-emerald-600" />
                                        <dd class="mt-1 text-xl font-bold text-emerald-950">
                                            {{ system.verified_nodes_count }}
                                        </dd>
                                        <dt class="mt-0.5 text-[11px] font-semibold text-emerald-700 uppercase">
                                            Geprüft
                                        </dt>
                                    </div>
                                </dl>

                                <div
                                    class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-4"
                                >
                                    <div>
                                        <p class="text-xs text-slate-400">Letzte DICOM-Prüfung</p>
                                        <p class="mt-1 text-xs font-medium text-slate-600">
                                            {{ formatDateTime(system.latest_verified_at) }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Link
                                            href="/network"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                                        >
                                            <Network :size="15" />
                                            Topologie
                                        </Link>
                                        <Link
                                            :href="`/systems/${system.public_id}`"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                        >
                                            Öffnen
                                            <ArrowRight :size="14" />
                                        </Link>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'general'" class="p-5 lg:p-7">
                        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-slate-950">Allgemein</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                Dieser Bereich ist für ausführliche Stammdaten und spätere Bearbeitungsfunktionen
                                vorbereitet.
                            </p>
                        </section>
                    </div>

                    <div v-else-if="activeTab === 'documents'" class="p-5 lg:p-7">
                        <DocumentationPanel
                            v-if="selectedContext"
                            :documentable-type="documentationType"
                            :documentable-id="selectedContext.public_id"
                            :sections="documentationSections"
                            :documentation="documentation"
                            :documents="documents"
                            :can-manage="canManageDocumentation"
                            :master-data="documentationMasterData"
                        />
                    </div>

                    <div v-else-if="activeTab === 'history'" class="p-5 lg:p-7">
                        <AuditHistoryPanel
                            v-if="history && selectedContext"
                            :events="history"
                            :stats="historyStats"
                            :filters="historyFilters"
                            :event-types="historyEventTypes"
                            :users="historyUsers"
                            :query-context="{
                                selected_type: selectedContext.type,
                                selected_id: selectedContext.public_id,
                            }"
                            allow-scope-selection
                        />
                    </div>

                    <div v-else class="p-5 lg:p-7">
                        <section
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center"
                        >
                            <component
                                :is="tabs.find((tab) => tab.id === activeTab)?.icon"
                                :size="28"
                                class="mx-auto text-slate-400"
                            />
                            <h3 class="mt-4 text-lg font-semibold text-slate-900">
                                {{ tabs.find((tab) => tab.id === activeTab)?.label }}
                            </h3>
                            <p class="mt-2 text-sm text-slate-500">
                                Dieser Reiter ist für die nächste Ausbaustufe vorbereitet.
                            </p>
                        </section>
                    </div>
                </section>

                <section
                    v-else
                    class="flex min-h-[520px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center"
                >
                    <div>
                        <Building2 :size="34" class="mx-auto text-slate-400" />
                        <h2 class="mt-4 text-lg font-semibold text-slate-900">Keine Einheit ausgewählt</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            Wähle links eine Organisation, einen Standort oder eine Abteilung aus.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
