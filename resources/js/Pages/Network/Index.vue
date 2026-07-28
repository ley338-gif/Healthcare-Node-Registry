<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FilterX, Network } from '@lucide/vue';
import { ref } from 'vue';
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

const service = ref(props.filters.service);

const applyFilters = (): void => {
    router.get(
        '/network',
        {
            service: service.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const resetFilters = (): void => {
    service.value = '';

    router.get(
        '/network',
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
};
</script>

<template>
    <Head title="DICOM Network Map" />

    <AppLayout>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">Communication</p>

                <h1 class="mt-2 text-2xl font-semibold text-slate-950">DICOM Network Map</h1>

                <p class="mt-1 text-sm text-slate-500">
                    Topologische Ansicht der dokumentierten DICOM-Kommunikationspfade.
                </p>
            </div>

            <form class="flex items-center gap-2" @submit.prevent="applyFilters">
                <select v-model="service" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                    <option value="">Alle Dienste</option>
                    <option v-for="option in services" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>

                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">
                    Filtern
                </button>

                <button
                    type="button"
                    class="rounded-xl border border-slate-300 p-2.5 text-slate-600 hover:bg-slate-50"
                    aria-label="Filter zurücksetzen"
                    @click="resetFilters"
                >
                    <FilterX :size="18" />
                </button>
            </form>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Systeme</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ summary.systems }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">DICOM-Knoten</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ summary.nodes }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Verbindungen</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ summary.connections }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Fehlerhaft</p>
                <p class="mt-2 text-2xl font-semibold text-red-600">
                    {{ summary.failed_nodes }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Ungeprüft</p>
                <p class="mt-2 text-2xl font-semibold text-amber-600">
                    {{ summary.unverified_nodes }}
                </p>
            </div>
        </div>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
            <div v-if="nodes.length === 0" class="grid min-h-96 place-items-center rounded-xl bg-slate-50">
                <div class="text-center">
                    <Network :size="40" class="mx-auto text-slate-300" />
                    <p class="mt-4 font-semibold text-slate-900">Keine DICOM-Knoten vorhanden</p>
                    <p class="mt-1 text-sm text-slate-500">Erfasse zunächst Knoten und Verbindungen.</p>
                </div>
            </div>

            <DicomNetworkMap v-else :nodes="nodes" :connections="connections" />
        </section>

        <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-600">
            <span>C-STORE: Blau</span>
            <span>Worklist: Violett</span>
            <span>Query: Türkis</span>
            <span>C-MOVE: Orange</span>
            <span>C-GET: Grün</span>
        </div>
    </AppLayout>
</template>
