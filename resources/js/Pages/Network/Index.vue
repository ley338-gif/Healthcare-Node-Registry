<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Network } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type NetworkNode = {
    id: number;
    public_id: string;
    name: string;
    ae_title: string;
    host: string;
    port: number;
    role: string;
    status: string;
    tls_enabled: boolean;
    last_verified_at: string | null;
    last_verification_status: string | null;
    last_verification_duration_ms: number | null;
    system: {
        public_id: string;
        name: string;
        system_type: string;
        status: string;
        organization: string | null;
        site: string | null;
        department: string | null;
    };
};

type NetworkConnection = {
    public_id: string;
    name: string;
    service: string;
    status: string;
    source_node_id: number;
    target_node_id: number;
    destination_node_id: number | null;
    calling_ae_title: string;
    called_ae_title: string;
    port: number | null;
    tls_enabled: boolean;
    test_enabled: boolean;
};

defineProps<{
    nodes: NetworkNode[];
    connections: NetworkConnection[];
    summary: {
        systems: number;
        nodes: number;
        connections: number;
        failed_nodes: number;
        unverified_nodes: number;
    };
}>();
</script>

<template>
    <Head title="DICOM Network Map" />

    <AppLayout>
        <div>
            <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">Communication</p>

            <h1 class="mt-2 text-2xl font-semibold text-slate-950">DICOM Network Map</h1>

            <p class="mt-1 text-sm text-slate-500">
                Topologische Ansicht der dokumentierten DICOM-Kommunikationspfade.
            </p>
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

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid min-h-96 place-items-center rounded-xl border border-dashed border-slate-300 bg-slate-50">
                <div class="text-center">
                    <Network :size="40" class="mx-auto text-slate-300" />

                    <p class="mt-4 font-semibold text-slate-900">Datenquelle für die Network Map ist bereit</p>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ nodes.length }} Knoten und {{ connections.length }} Verbindungen geladen.
                    </p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
