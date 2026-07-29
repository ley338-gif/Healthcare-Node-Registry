<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, CircleHelp, FilterX, Network, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import DicomNetworkMap, {
    type NetworkConnection,
    type NetworkNode,
} from '../../Components/network/DicomNetworkMap.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type ServiceOption = {
    value: string;
    label: string;
};

const props = defineProps<{
    nodes: NetworkNode[];
    connections: NetworkConnection[];
    filters: {
        organization: number | null;
        site: number | null;
        department: number | null;
        service: string;
    };
    summary: {
        systems: number;
        nodes: number;
        connections: number;
        failed_nodes: number;
        unverified_nodes: number;
    };
    services: ServiceOption[];
}>();

const search = ref('');
const organization = ref('');
const site = ref('');
const systemType = ref('');
const service = ref('');
const onlyProblems = ref(false);

const organizationOptions = computed(() =>
    Array.from(
        new Set(props.nodes.map((node) => node.system.organization).filter((value): value is string => value !== null)),
    ).sort(),
);

const siteOptions = computed(() =>
    Array.from(
        new Set(
            props.nodes
                .filter((node) => organization.value === '' || node.system.organization === organization.value)
                .map((node) => node.system.site)
                .filter((value): value is string => value !== null),
        ),
    ).sort(),
);

const systemTypeOptions = computed(() =>
    Array.from(new Set(props.nodes.map((node) => node.system.system_type))).sort(),
);

const visibleNodes = computed(() =>
    props.nodes.filter((node) => {
        const term = search.value.trim().toLowerCase();

        const matchesSearch =
            term === '' ||
            [node.name, node.ae_title, node.host, node.system.name].some((value) => value.toLowerCase().includes(term));

        const matchesOrganization = organization.value === '' || node.system.organization === organization.value;

        const matchesSite = site.value === '' || node.system.site === site.value;

        const matchesSystemType = systemType.value === '' || node.system.system_type === systemType.value;

        const matchesProblem = !onlyProblems.value || node.last_verification_status !== 'success';

        return matchesSearch && matchesOrganization && matchesSite && matchesSystemType && matchesProblem;
    }),
);

const visibleNodeIds = computed(() => new Set(visibleNodes.value.map((node) => node.id)));

const visibleConnections = computed(() =>
    props.connections.filter(
        (connection) =>
            visibleNodeIds.value.has(connection.source_node_id) &&
            visibleNodeIds.value.has(connection.target_node_id) &&
            (service.value === '' || connection.service === service.value),
    ),
);

const visibleSystems = computed(() => new Set(visibleNodes.value.map((node) => node.system.public_id)).size);

const failedNodes = computed(
    () =>
        visibleNodes.value.filter(
            (node) => node.last_verification_status !== null && node.last_verification_status !== 'success',
        ).length,
);

const unverifiedNodes = computed(() => visibleNodes.value.filter((node) => node.last_verified_at === null).length);

const hasActiveFilters = computed(
    () =>
        search.value !== '' ||
        organization.value !== '' ||
        site.value !== '' ||
        systemType.value !== '' ||
        service.value !== '' ||
        onlyProblems.value,
);

const resetFilters = (): void => {
    search.value = '';
    organization.value = '';
    site.value = '';
    systemType.value = '';
    service.value = '';
    onlyProblems.value = false;
};
</script>

<template>
    <Head title="Topologie" />

    <AppLayout>
        <div>
            <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">Kommunikation</p>

            <h1 class="mt-2 text-2xl font-semibold text-slate-950">Topologie</h1>

            <p class="mt-1 text-sm text-slate-500">DICOM-Systeme, Knoten und dokumentierte Kommunikationspfade.</p>
        </div>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 xl:grid-cols-[minmax(240px,1.5fr)_1fr_1fr_1fr_1fr_auto]">
                <div class="relative">
                    <Search
                        :size="17"
                        class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400"
                    />

                    <input
                        v-model="search"
                        type="search"
                        placeholder="System, Knoten, AE Title oder Host"
                        class="w-full rounded-xl border border-slate-300 py-2.5 pr-3 pl-10 text-sm"
                    />
                </div>

                <select
                    v-model="organization"
                    class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                    @change="site = ''"
                >
                    <option value="">Alle Organisationen</option>
                    <option v-for="item in organizationOptions" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>

                <select v-model="site" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Standorte</option>
                    <option v-for="item in siteOptions" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>

                <select v-model="systemType" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Systemtypen</option>
                    <option v-for="item in systemTypeOptions" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>

                <select v-model="service" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Dienste</option>
                    <option v-for="option in services" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>

                <button
                    type="button"
                    :disabled="!hasActiveFilters"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="resetFilters"
                >
                    <FilterX :size="16" />
                    Zurücksetzen
                </button>
            </div>

            <label class="mt-3 inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                <input v-model="onlyProblems" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600" />
                Nur fehlerhafte oder ungeprüfte Knoten
            </label>
        </section>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs text-slate-500">Systeme</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ visibleSystems }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs text-slate-500">DICOM-Knoten</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ visibleNodes.length }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs text-slate-500">Verbindungen</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ visibleConnections.length }}
                </p>
            </div>

            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-xs text-red-700">Fehlerhaft</p>
                <p class="mt-1 text-xl font-semibold text-red-800">
                    {{ failedNodes }}
                </p>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-xs text-amber-700">Ungeprüft</p>
                <p class="mt-1 text-xl font-semibold text-amber-800">
                    {{ unverifiedNodes }}
                </p>
            </div>
        </div>

        <section class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
            <div v-if="visibleNodes.length === 0" class="grid min-h-[520px] place-items-center rounded-xl bg-slate-50">
                <div class="text-center">
                    <Network :size="40" class="mx-auto text-slate-300" />
                    <p class="mt-4 font-semibold text-slate-900">Keine passenden Knoten</p>
                    <p class="mt-1 text-sm text-slate-500">Passe die Filter an oder setze sie zurück.</p>
                </div>
            </div>

            <DicomNetworkMap v-else :nodes="visibleNodes" :connections="visibleConnections" />
        </section>

        <section class="mt-4 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Legende</h2>
                    <p class="mt-1 text-xs text-slate-500">Dienste und Prüfstatus der dargestellten Knoten</p>
                </div>

                <div class="flex flex-wrap gap-x-5 gap-y-3 text-xs text-slate-600">
                    <span class="inline-flex items-center gap-2">
                        <span class="h-0.5 w-6 bg-blue-600" />
                        C-STORE
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="h-0.5 w-6 bg-violet-600" />
                        Worklist
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="h-0.5 w-6 bg-cyan-600" />
                        Query
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="h-0.5 w-6 bg-amber-600" />
                        C-MOVE
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="h-0.5 w-6 bg-emerald-600" />
                        C-GET
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <CircleCheck :size="15" class="text-emerald-600" />
                        Erreichbar
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <CircleAlert :size="15" class="text-red-600" />
                        Fehlerhaft
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <CircleHelp :size="15" class="text-amber-600" />
                        Ungeprüft
                    </span>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
