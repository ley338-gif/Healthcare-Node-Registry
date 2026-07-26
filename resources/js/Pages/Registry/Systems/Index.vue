<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search, Server } from '@lucide/vue';
import { computed, ref } from 'vue';
import SystemCreateSlideOver from '../../../Components/registry/systems/SystemCreateSlideOver.vue';
import ContentCard from '../../../Components/ui/ContentCard.vue';
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
    name: string;
    system_type: string;
    status: string;
    hostname: string | null;
    ip_address: string | null;
    vendor: string | null;
    product: string | null;
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

const props = defineProps<{
    items: PaginatedSystems;

    filters: {
        search: string;
        type: string;
        status: string;
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
const createPanelOpen = ref(false);

const hasSystems = computed(() => props.items.data.length > 0);

const applyFilters = (): void => {
    router.get(
        '/systems',
        {
            search: search.value || undefined,
            type: type.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const labelFor = (options: SelectOption[], value: string): string =>
    options.find((option) => option.value === value)?.label ?? value;
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

        <ContentCard class="mt-6" :padded="false">
            <form
                class="grid gap-3 border-b border-slate-200 p-5 md:grid-cols-[1fr_220px_220px_auto]"
                @submit.prevent="applyFilters"
            >
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

                <button
                    type="submit"
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50"
                >
                    Filtern
                </button>
            </form>

            <EmptyState
                v-if="!hasSystems"
                title="Keine Systeme vorhanden"
                description="Legen Sie das erste System der Registry an."
                :icon="Server"
            />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">System</th>

                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Typ</th>

                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">
                                Zuordnung
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Netzwerk</th>

                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="system in items.data" :key="system.public_id" class="transition hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">
                                    {{ system.name }}
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{
                                        [system.vendor, system.product].filter(Boolean).join(' · ') ||
                                        'Keine Produktangaben'
                                    }}
                                </div>
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-700">
                                {{ labelFor(systemTypes, system.system_type) }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="text-sm text-slate-800">
                                    {{ system.organization.name }}
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{
                                        [system.site?.name, system.department?.name].filter(Boolean).join(' · ') ||
                                        'Keine weitere Zuordnung'
                                    }}
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="text-sm text-slate-800">
                                    {{ system.hostname || 'Kein Hostname' }}
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ system.ip_address || 'Keine IP-Adresse' }}
                                </div>
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-700">
                                {{ labelFor(statuses, system.status) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
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
    </AppLayout>
</template>
