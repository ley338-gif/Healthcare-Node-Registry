<script setup lang="ts">
import { ArrowRight, Cable, LockKeyhole, Network, Radio, Server, X } from '@lucide/vue';
import type { NetworkConnection, NetworkNode } from './DicomNetworkMap.vue';

defineProps<{
    open: boolean;
    connection: NetworkConnection | null;
    sourceNode: NetworkNode | null;
    targetNode: NetworkNode | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const serviceLabels: Record<string, string> = {
    echo: 'C-ECHO',
    store: 'C-STORE',
    worklist: 'Worklist',
    query: 'Query',
    move: 'C-MOVE',
    get: 'C-GET',
};

const serviceLabel = (service: string): string => serviceLabels[service] ?? service.toUpperCase();

const endpoint = (node: NetworkNode | null): string => {
    if (node === null) {
        return 'Nicht verfügbar';
    }

    return `${node.host}:${node.port}`;
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open && connection"
            class="fixed inset-0 z-[70]"
            role="dialog"
            aria-modal="true"
            aria-labelledby="dicom-connection-detail-title"
        >
            <button
                type="button"
                aria-label="Detailansicht schließen"
                class="absolute inset-0 bg-slate-950/40"
                @click="emit('close')"
            />

            <aside class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">DICOM-Verbindung</p>
                        <h2
                            id="dicom-connection-detail-title"
                            class="mt-1 truncate text-xl font-semibold text-slate-950"
                        >
                            {{ connection.name }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ serviceLabel(connection.service) }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100"
                        @click="emit('close')"
                    >
                        <X :size="20" />
                    </button>
                </header>

                <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
                    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="grid items-center gap-3 sm:grid-cols-[1fr_auto_1fr]">
                            <div class="min-w-0">
                                <p class="text-xs text-slate-500">Quelle</p>
                                <p class="mt-1 truncate font-semibold text-slate-950">
                                    {{ sourceNode?.name ?? 'Nicht verfügbar' }}
                                </p>
                                <p class="mt-1 truncate font-mono text-xs text-slate-600">
                                    {{ sourceNode?.ae_title ?? connection.calling_ae_title }}
                                </p>
                            </div>

                            <div class="grid h-9 w-9 place-items-center rounded-full bg-blue-100 text-blue-700">
                                <ArrowRight :size="18" />
                            </div>

                            <div class="min-w-0 sm:text-right">
                                <p class="text-xs text-slate-500">Ziel</p>
                                <p class="mt-1 truncate font-semibold text-slate-950">
                                    {{ targetNode?.name ?? 'Nicht verfügbar' }}
                                </p>
                                <p class="mt-1 truncate font-mono text-xs text-slate-600">
                                    {{ targetNode?.ae_title ?? connection.called_ae_title }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-sm font-semibold text-slate-950">Kommunikation</h3>

                        <dl class="mt-3 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                            <div class="grid grid-cols-[145px_1fr] gap-3 px-4 py-3">
                                <dt class="flex items-center gap-2 text-xs text-slate-500">
                                    <Cable :size="14" />
                                    Dienst
                                </dt>
                                <dd class="text-sm font-medium text-slate-900">
                                    {{ serviceLabel(connection.service) }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[145px_1fr] gap-3 px-4 py-3">
                                <dt class="flex items-center gap-2 text-xs text-slate-500">
                                    <Radio :size="14" />
                                    Calling AE
                                </dt>
                                <dd class="font-mono text-sm text-slate-900">
                                    {{ connection.calling_ae_title }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[145px_1fr] gap-3 px-4 py-3">
                                <dt class="flex items-center gap-2 text-xs text-slate-500">
                                    <Radio :size="14" />
                                    Called AE
                                </dt>
                                <dd class="font-mono text-sm text-slate-900">
                                    {{ connection.called_ae_title }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[145px_1fr] gap-3 px-4 py-3">
                                <dt class="flex items-center gap-2 text-xs text-slate-500">
                                    <Network :size="14" />
                                    Quelle
                                </dt>
                                <dd class="font-mono text-sm text-slate-900">
                                    {{ endpoint(sourceNode) }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[145px_1fr] gap-3 px-4 py-3">
                                <dt class="flex items-center gap-2 text-xs text-slate-500">
                                    <Server :size="14" />
                                    Ziel
                                </dt>
                                <dd class="font-mono text-sm text-slate-900">
                                    {{
                                        targetNode
                                            ? `${targetNode.host}:${connection.port ?? targetNode.port}`
                                            : 'Nicht verfügbar'
                                    }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[145px_1fr] gap-3 px-4 py-3">
                                <dt class="flex items-center gap-2 text-xs text-slate-500">
                                    <LockKeyhole :size="14" />
                                    TLS
                                </dt>
                                <dd class="text-sm text-slate-900">
                                    {{ connection.tls_enabled ? 'Aktiviert' : 'Nicht aktiviert' }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section>
                        <h3 class="text-sm font-semibold text-slate-950">Status</h3>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="text-xs text-slate-500">Verbindung</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">
                                    {{ connection.status }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="text-xs text-slate-500">Testfreigabe</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">
                                    {{ connection.test_enabled ? 'Aktiv' : 'Inaktiv' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-4 sm:col-span-2">
                                <p class="text-xs text-slate-500">Nachweis-Status (Linienstil in der Topologie)</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">
                                    {{
                                        {
                                            confirmed: 'Bestätigt (durchgezogen)',
                                            technically_tested: 'Technisch getestet, nicht bestätigt (gepunktet)',
                                            suspected: 'Vermutet, nicht bestätigt (gestrichelt)',
                                            manually_documented: 'Manuell dokumentiert (durchgezogen)',
                                            failed_last_test: 'Letzter Test fehlgeschlagen (rot, ⚠)',
                                        }[connection.evidence_status] ?? connection.evidence_status
                                    }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Ein erfolgreicher C-ECHO beweist nur die Erreichbarkeit dieses Endpunkts, keine
                                    produktive Verbindung. Nur der Benutzer kann eine Verbindung als bestätigt
                                    kennzeichnen.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>

                <footer class="border-t border-slate-200 px-6 py-4">
                    <button
                        type="button"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        @click="emit('close')"
                    >
                        Schließen
                    </button>
                </footer>
            </aside>
        </div>
    </Teleport>
</template>
