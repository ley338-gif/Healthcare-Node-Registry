<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, ChevronDown, ChevronRight, Hospital, Pencil, Search, UsersRound } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

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

type SelectedUnit =
    | { type: 'organization'; organization: OrganizationItem }
    | { type: 'site'; organization: OrganizationItem; site: SiteItem }
    | {
          type: 'department';
          organization: OrganizationItem;
          site: SiteItem;
          department: DepartmentItem;
      };

const props = defineProps<{
    summary: {
        organizations: number;
        sites: number;
        departments: number;
    };
    organizations: OrganizationItem[];
}>();

const search = ref('');
const selected = ref<SelectedUnit | null>(
    props.organizations[0] ? { type: 'organization', organization: props.organizations[0] } : null,
);
const expandedOrganizations = ref(new Set(props.organizations.map((item) => item.public_id)));
const expandedSites = ref(
    new Set(props.organizations.flatMap((organization) => organization.sites.map((site) => site.public_id))),
);

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
    if (selected.value === null) {
        return '#';
    }

    switch (selected.value.type) {
        case 'organization':
            return '/organizations';

        case 'site':
            return '/sites';

        case 'department':
            return '/departments';

        default:
            return '#';
    }
});

const siteCount = computed(() => {
    if (selected.value?.type === 'organization') {
        return selected.value.organization.sites.length;
    }
    return selected.value ? 1 : 0;
});

const departmentCount = computed(() => {
    if (selected.value?.type === 'organization') {
        return selected.value.organization.sites.reduce((total, site) => total + site.departments.length, 0);
    }
    if (selected.value?.type === 'site') {
        return selected.value.site.departments.length;
    }
    return selected.value ? 1 : 0;
});

const children = computed(() => {
    if (selected.value?.type === 'organization') {
        return selected.value.organization.sites.map((site) => ({
            id: site.public_id,
            name: site.name,
            type: 'Standort',
            code: site.code,
            detail: site.city,
            count: site.departments.length,
            href: `/sites/${site.public_id}`,
        }));
    }

    if (selected.value?.type === 'site') {
        return selected.value.site.departments.map((department) => ({
            id: department.public_id,
            name: department.name,
            type: 'Abteilung',
            code: department.code,
            detail: department.specialty,
            count: null,
            href: `/departments/${department.public_id}`,
        }));
    }

    return [];
});

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
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">Registry</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-950">Organisationsstruktur</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Organisationen, Standorte und Abteilungen in einer gemeinsamen Hierarchie.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    href="/organizations"
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Organisationen
                </Link>
                <Link
                    href="/sites"
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Standorte
                </Link>
                <Link
                    href="/departments"
                    class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Abteilungen
                </Link>
            </div>
        </div>

        <div class="mt-6 grid min-h-[680px] gap-5 xl:grid-cols-[340px_minmax(0,1fr)]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 p-4">
                    <h2 class="font-semibold text-slate-950">Hierarchie</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ summary.organizations }} Organisationen · {{ summary.sites }} Standorte ·
                        {{ summary.departments }} Abteilungen
                    </p>
                    <div class="relative mt-4">
                        <Search :size="17" class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Hierarchie durchsuchen"
                            class="w-full rounded-xl border border-slate-300 py-2.5 pr-3 pl-10 text-sm"
                        />
                    </div>
                </header>

                <div class="max-h-[620px] overflow-y-auto p-3">
                    <div v-if="filteredOrganizations.length === 0" class="py-12 text-center text-sm text-slate-500">
                        Keine passenden Einträge gefunden.
                    </div>

                    <div v-for="organization in filteredOrganizations" :key="organization.public_id" class="mb-2">
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100"
                                @click="expandedOrganizations = toggle(expandedOrganizations, organization.public_id)"
                            >
                                <ChevronDown v-if="expandedOrganizations.has(organization.public_id)" :size="15" />
                                <ChevronRight v-else :size="15" />
                            </button>
                            <button
                                type="button"
                                class="flex min-w-0 flex-1 items-center gap-2 rounded-xl px-3 py-2.5 text-left hover:bg-slate-50"
                                @click="selected = { type: 'organization', organization }"
                            >
                                <Building2 :size="17" class="shrink-0 text-blue-600" />
                                <span class="truncate text-sm font-semibold">{{ organization.name }}</span>
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
                                        class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100"
                                        @click="expandedSites = toggle(expandedSites, site.public_id)"
                                    >
                                        <ChevronDown v-if="expandedSites.has(site.public_id)" :size="14" />
                                        <ChevronRight v-else :size="14" />
                                    </button>
                                    <button
                                        type="button"
                                        class="flex min-w-0 flex-1 items-center gap-2 rounded-xl px-3 py-2 text-left hover:bg-slate-50"
                                        @click="selected = { type: 'site', organization, site }"
                                    >
                                        <Hospital :size="16" class="shrink-0 text-slate-600" />
                                        <span class="truncate text-sm font-medium">{{ site.name }}</span>
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
                                        class="mt-1 flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left hover:bg-slate-50"
                                        @click="selected = { type: 'department', organization, site, department }"
                                    >
                                        <UsersRound :size="15" class="shrink-0 text-slate-500" />
                                        <span class="truncate text-sm">{{ department.name }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="selected" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-blue-600 uppercase">{{ typeLabel }}</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-950">{{ title }}</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ description || 'Keine Beschreibung hinterlegt.' }}
                            </p>
                        </div>
                        <Link
                            :href="editHref"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                        >
                            <Pencil :size="16" />
                            Öffnen
                        </Link>
                    </div>
                </header>

                <div class="space-y-6 p-6">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Organisationen</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-950">1</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Standorte</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ siteCount }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Abteilungen</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ departmentCount }}</p>
                        </div>
                    </div>

                    <section>
                        <h3 class="font-semibold text-slate-950">Übersicht</h3>
                        <dl class="mt-3 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                            <div
                                v-if="selected.type === 'organization'"
                                class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]"
                            >
                                <dt class="text-xs text-slate-500">Kurzname</dt>
                                <dd class="text-sm font-medium text-slate-900">
                                    {{ selected.organization.short_name || 'Nicht hinterlegt' }}
                                </dd>
                            </div>
                            <template v-if="selected.type === 'site'">
                                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                    <dt class="text-xs text-slate-500">Organisation</dt>
                                    <dd class="text-sm font-medium text-slate-900">{{ selected.organization.name }}</dd>
                                </div>
                                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                    <dt class="text-xs text-slate-500">Code</dt>
                                    <dd class="text-sm font-medium text-slate-900">
                                        {{ selected.site.code || 'Nicht hinterlegt' }}
                                    </dd>
                                </div>
                                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                    <dt class="text-xs text-slate-500">Adresse</dt>
                                    <dd class="text-sm font-medium text-slate-900">
                                        {{
                                            [
                                                selected.site.street,
                                                [selected.site.postal_code, selected.site.city]
                                                    .filter(Boolean)
                                                    .join(' '),
                                            ]
                                                .filter(Boolean)
                                                .join(', ') || 'Nicht hinterlegt'
                                        }}
                                    </dd>
                                </div>
                            </template>
                            <template v-if="selected.type === 'department'">
                                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                    <dt class="text-xs text-slate-500">Organisation</dt>
                                    <dd class="text-sm font-medium text-slate-900">{{ selected.organization.name }}</dd>
                                </div>
                                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                    <dt class="text-xs text-slate-500">Standort</dt>
                                    <dd class="text-sm font-medium text-slate-900">{{ selected.site.name }}</dd>
                                </div>
                                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                                    <dt class="text-xs text-slate-500">Fachrichtung</dt>
                                    <dd class="text-sm font-medium text-slate-900">
                                        {{ selected.department.specialty || 'Nicht hinterlegt' }}
                                    </dd>
                                </div>
                            </template>
                        </dl>
                    </section>

                    <section>
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-slate-950">Untergeordnete Einträge</h3>
                                <p class="mt-1 text-xs text-slate-500">Direkte Kinder der ausgewählten Einheit</p>
                            </div>
                            <span class="text-sm font-semibold text-slate-500">{{ children.length }}</span>
                        </div>

                        <div
                            v-if="children.length === 0"
                            class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500"
                        >
                            Keine untergeordneten Einträge vorhanden.
                        </div>

                        <div v-else class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                            <table class="w-full text-left">
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
                                    <tr v-for="row in children" :key="row.id" class="hover:bg-slate-50">
                                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ row.name }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ row.type }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-slate-600">
                                            {{ row.code || '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ row.detail || '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ row.count ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <Link
                                                :href="row.href"
                                                class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-700 hover:text-blue-800"
                                            >
                                                Öffnen
                                                <ArrowRight :size="14" />
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
