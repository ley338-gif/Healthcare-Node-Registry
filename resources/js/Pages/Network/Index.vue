<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Boxes, Cable, CircleAlert, CircleCheck, CircleHelp, FilterX, Network, RotateCcw, Search } from '@lucide/vue';
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

type LayoutMode = 'wide' | 'balanced';

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
    focusNodePublicId: string | null;
    focusConnectionPublicId: string | null;
}>();

const search = ref('');
const organization = ref('');
const site = ref('');
const systemType = ref('');
const service = ref('');
const onlyProblems = ref(false);
const layoutMode = ref<LayoutMode>('wide');

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
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">Kommunikation</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-950">Netzwerkübersicht / Topologie</h1>
                <p class="mt-1 text-sm text-slate-500">
                    DICOM-Systeme, Endpunkte und dokumentierte Kommunikationspfade zentral betrachten.
                </p>
            </div>
        </div>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-3 p-4 xl:grid-cols-[minmax(260px,1.6fr)_1fr_1fr_1fr_1fr_auto]">
                <div class="relative">
                    <Search
                        :size="17"
                        class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="System, Knoten, AE Title oder Host"
                        class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pr-3 pl-10 text-sm transition outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                    />
                </div>

                <select
                    v-model="organization"
                    class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm transition outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                    @change="site = ''"
                >
                    <option value="">Alle Organisationen</option>
                    <option v-for="item in organizationOptions" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>

                <select
                    v-model="site"
                    class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm transition outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                >
                    <option value="">Alle Standorte</option>
                    <option v-for="item in siteOptions" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>

                <select
                    v-model="systemType"
                    class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm transition outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                >
                    <option value="">Alle Systemtypen</option>
                    <option v-for="item in systemTypeOptions" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>

                <select
                    v-model="service"
                    class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm transition outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                >
                    <option value="">Alle Dienste</option>
                    <option v-for="option in services" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>

                <button
                    type="button"
                    :disabled="!hasActiveFilters"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="resetFilters"
                >
                    <RotateCcw :size="16" />
                    Zurücksetzen
                </button>
            </div>

            <div
                class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                    <input
                        v-model="onlyProblems"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    />
                    Nur fehlerhafte oder ungeprüfte Knoten
                </label>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span v-if="organization" class="rounded-full bg-slate-100 px-2.5 py-1">
                            {{ organization }}
                        </span>
                        <span v-if="site" class="rounded-full bg-slate-100 px-2.5 py-1">
                            {{ site }}
                        </span>
                        <span v-if="systemType" class="rounded-full bg-slate-100 px-2.5 py-1">
                            {{ systemType }}
                        </span>
                        <span v-if="service" class="rounded-full bg-slate-100 px-2.5 py-1">
                            {{ services.find((item) => item.value === service)?.label }}
                        </span>
                    </div>

                    <label class="flex items-center gap-2 text-xs font-medium text-slate-500">
                        Layout
                        <select
                            v-model="layoutMode"
                            class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                        >
                            <option value="wide">Breite Ansicht</option>
                            <option value="balanced">Ausgewogen</option>
                        </select>
                    </label>
                </div>
            </div>
        </section>

        <section class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-violet-50 text-violet-700">
                        <Boxes :size="18" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Systeme</p>
                        <p class="mt-0.5 text-xl font-semibold text-slate-950">{{ visibleSystems }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-blue-700">
                        <Network :size="18" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">DICOM-Knoten</p>
                        <p class="mt-0.5 text-xl font-semibold text-slate-950">{{ visibleNodes.length }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-cyan-50 text-cyan-700">
                        <Cable :size="18" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Verbindungen</p>
                        <p class="mt-0.5 text-xl font-semibold text-slate-950">{{ visibleConnections.length }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-white text-red-600 ring-1 ring-red-100">
                        <CircleAlert :size="18" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-red-700">Fehlerhaft</p>
                        <p class="mt-0.5 text-xl font-semibold text-red-900">{{ failedNodes }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                <div class="flex items-center gap-3">
                    <div
                        class="grid h-9 w-9 place-items-center rounded-xl bg-white text-amber-600 ring-1 ring-amber-100"
                    >
                        <CircleHelp :size="18" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-amber-700">Ungeprüft</p>
                        <p class="mt-0.5 text-xl font-semibold text-amber-900">{{ unverifiedNodes }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header
                class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="font-semibold text-slate-950">Topologie</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Systeme, DICOM-Endpunkte und ihre dokumentierten Kommunikationspfade.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                        {{ visibleSystems }} Systeme
                    </span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ visibleNodes.length }} Knoten
                    </span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ visibleConnections.length }} Verbindungen
                    </span>
                </div>
            </header>

            <div v-if="visibleNodes.length === 0" class="grid min-h-[610px] place-items-center bg-slate-50">
                <div class="max-w-sm text-center">
                    <div
                        class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"
                    >
                        <Network :size="26" class="text-slate-400" />
                    </div>
                    <p class="mt-4 font-semibold text-slate-900">Keine passenden Knoten</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Passe die Filter an oder setze die aktuelle Auswahl zurück.
                    </p>
                    <button
                        type="button"
                        class="mt-5 inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                        @click="resetFilters"
                    >
                        <FilterX :size="16" />
                        Filter zurücksetzen
                    </button>
                </div>
            </div>

            <DicomNetworkMap
                v-else
                :nodes="visibleNodes"
                :connections="visibleConnections"
                :layout-mode="layoutMode"
                :focus-node-public-id="focusNodePublicId"
                :focus-connection-public-id="focusConnectionPublicId"
            />

            <footer class="border-t border-slate-200 bg-white px-5 py-3">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-700">Legende</p>
                        <p class="mt-0.5 text-[11px] text-slate-400">Dienste und Prüfstatus</p>
                    </div>

                    <div class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-600">
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
                            <CircleCheck :size="14" class="text-emerald-600" />
                            Erreichbar
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <CircleAlert :size="14" class="text-red-600" />
                            Fehlerhaft
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <CircleHelp :size="14" class="text-amber-600" />
                            Ungeprüft
                        </span>
                    </div>
                </div>
            </footer>
        </section>
    </AppLayout>
</template>
