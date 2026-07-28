<script setup lang="ts">
import {
    Activity,
    Building2,
    CircleAlert,
    CircleCheck,
    CircleHelp,
    LockKeyhole,
    Network,
    Server,
    X,
} from '@lucide/vue';
import type { NetworkNode } from './DicomNetworkMap.vue';

defineProps<{
    open: boolean;
    node: NetworkNode | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const formatDate = (value: string | null): string => {
    if (value === null) {
        return 'Noch nie geprüft';
    }

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const verificationLabel = (status: string | null): string => {
    if (status === 'success') {
        return 'Erfolgreich';
    }

    if (status === null) {
        return 'Ungeprüft';
    }

    return status;
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open && node"
            class="fixed inset-0 z-[70]"
            role="dialog"
            aria-modal="true"
            aria-labelledby="dicom-node-detail-title"
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
                        <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">DICOM-Knoten</p>
                        <h2 id="dicom-node-detail-title" class="mt-1 truncate text-xl font-semibold text-slate-950">
                            {{ node.name }}
                        </h2>
                        <p class="mt-1 font-mono text-sm text-slate-500">
                            {{ node.ae_title }}
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
                        <div class="flex items-start gap-3">
                            <div
                                class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-100 text-blue-700"
                            >
                                <Server :size="19" />
                            </div>

                            <div>
                                <p class="text-xs text-slate-500">Zugehöriges System</p>
                                <p class="mt-1 font-semibold text-slate-950">
                                    {{ node.system.name }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ node.system.system_type }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-sm font-semibold text-slate-950">Endpunkt</h3>

                        <dl class="mt-3 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                            <div class="grid grid-cols-[130px_1fr] gap-3 px-4 py-3">
                                <dt class="text-xs text-slate-500">AE Title</dt>
                                <dd class="font-mono text-sm text-slate-900">
                                    {{ node.ae_title }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[130px_1fr] gap-3 px-4 py-3">
                                <dt class="text-xs text-slate-500">Host</dt>
                                <dd class="font-mono text-sm text-slate-900">
                                    {{ node.host }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[130px_1fr] gap-3 px-4 py-3">
                                <dt class="text-xs text-slate-500">Port</dt>
                                <dd class="font-mono text-sm text-slate-900">
                                    {{ node.port }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[130px_1fr] gap-3 px-4 py-3">
                                <dt class="text-xs text-slate-500">Rolle</dt>
                                <dd class="text-sm text-slate-900 uppercase">
                                    {{ node.role }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[130px_1fr] gap-3 px-4 py-3">
                                <dt class="text-xs text-slate-500">TLS</dt>
                                <dd class="text-sm text-slate-900">
                                    <span
                                        v-if="node.tls_enabled"
                                        class="inline-flex items-center gap-1 font-medium text-emerald-700"
                                    >
                                        <LockKeyhole :size="14" />
                                        Aktiviert
                                    </span>
                                    <span v-else>Nicht aktiviert</span>
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section>
                        <h3 class="text-sm font-semibold text-slate-950">Letzte C-ECHO-Prüfung</h3>

                        <div
                            class="mt-3 rounded-2xl border p-4"
                            :class="
                                node.last_verification_status === 'success'
                                    ? 'border-emerald-200 bg-emerald-50'
                                    : node.last_verification_status !== null
                                      ? 'border-red-200 bg-red-50'
                                      : 'border-slate-200 bg-slate-50'
                            "
                        >
                            <div class="flex items-start gap-3">
                                <CircleCheck
                                    v-if="node.last_verification_status === 'success'"
                                    :size="20"
                                    class="mt-0.5 text-emerald-600"
                                />
                                <CircleAlert
                                    v-else-if="node.last_verification_status !== null"
                                    :size="20"
                                    class="mt-0.5 text-red-600"
                                />
                                <CircleHelp v-else :size="20" class="mt-0.5 text-slate-400" />

                                <div>
                                    <p class="font-semibold text-slate-900">
                                        {{ verificationLabel(node.last_verification_status) }}
                                    </p>

                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ formatDate(node.last_verified_at) }}
                                    </p>

                                    <p
                                        v-if="node.last_verification_duration_ms !== null"
                                        class="mt-1 text-sm text-slate-600"
                                    >
                                        Laufzeit:
                                        {{ node.last_verification_duration_ms }}
                                        ms
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-sm font-semibold text-slate-950">Organisationskontext</h3>

                        <div class="mt-3 space-y-3">
                            <div class="flex items-start gap-3">
                                <Building2 :size="17" class="mt-0.5 text-slate-400" />
                                <div>
                                    <p class="text-xs text-slate-500">Organisation</p>
                                    <p class="mt-0.5 text-sm text-slate-800">
                                        {{ node.system.organization ?? 'Nicht zugeordnet' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <Network :size="17" class="mt-0.5 text-slate-400" />
                                <div>
                                    <p class="text-xs text-slate-500">Standort / Abteilung</p>
                                    <p class="mt-0.5 text-sm text-slate-800">
                                        {{
                                            [node.system.site, node.system.department].filter(Boolean).join(' · ') ||
                                            'Nicht zugeordnet'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <footer class="border-t border-slate-200 px-6 py-4">
                    <a
                        :href="`/systems/${node.system.public_id}`"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >
                        <Activity :size="17" />
                        System öffnen
                    </a>
                </footer>
            </aside>
        </div>
    </Teleport>
</template>
