<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Ban, CheckCircle2, Eye, RefreshCw, ShieldCheck, X, XCircle } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

type Port = {
    port: number;
    protocol: string;
    is_open: boolean;
    is_dicom_candidate: boolean;
    response_time_ms: number | null;
};
type DicomResult = {
    port: number;
    calling_ae: string;
    called_ae: string;
    association_successful: boolean;
    echo_successful: boolean;
    error_code: string | null;
    error_message: string | null;
    response_time_ms: number | null;
    created_at: string | null;
};
type Evidence = { rule_name: string; reason: string; weight: number };
type Host = {
    public_id: string;
    ip_address: string;
    hostname: string | null;
    is_reachable: boolean;
    ping_latency_ms: number | null;
    status: 'discovered' | 'reviewing' | 'confirmed' | 'ignored';
    confidence_score: string;
    confidence_percentage: number;
    suggested_system_type: string | null;
    system: { public_id: string; name: string } | null;
    last_seen_at: string | null;
    ports: Port[];
    dicom_results: DicomResult[];
    classification_evidence: Evidence[];
};
type Run = {
    public_id: string;
    name: string;
    location: string | null;
    department: string | null;
    ip_range: string;
    status: string;
    progress_percentage: number;
    total_ips: number;
    processed_ips: number;
    found_hosts_count: number;
    dicom_candidates_count: number;
    started_at: string | null;
    finished_at: string | null;
    error_message: string | null;
    description: string | null;
};

const props = defineProps<{
    run: Run;
    hosts: { data: Host[]; total: number; links: Array<{ url: string | null; label: string; active: boolean }> };
    filters: Record<string, string | undefined>;
    canReview: boolean;
    canCancel: boolean;
}>();

const statusLabels: Record<string, string> = {
    draft: 'Entwurf',
    pending: 'Geplant',
    running: 'Läuft',
    cancelling: 'Wird abgebrochen',
    completed: 'Abgeschlossen',
    partially_failed: 'Teilweise fehlgeschlagen',
    cancelled: 'Abgebrochen',
    failed: 'Fehlgeschlagen',
};
const confidenceLabels: Record<string, string> = {
    unknown: 'Unbekannt',
    very_low: 'Sehr niedrig',
    low: 'Niedrig',
    medium: 'Mittel',
    high: 'Hoch',
    very_high: 'Sehr hoch',
};
const reviewStatusLabels: Record<string, string> = {
    discovered: 'Ungeprüft',
    reviewing: 'In Prüfung',
    confirmed: 'Bestätigt',
    ignored: 'Ignoriert',
};

const filters = reactive({
    reachable_only: props.filters.reachable_only ?? '',
    dicom_candidates_only: props.filters.dicom_candidates_only ?? '',
    successful_echo_only: props.filters.successful_echo_only ?? '',
    status: props.filters.status ?? '',
    confidence: props.filters.confidence ?? '',
});
const applyFilters = (): void => {
    router.get(`/discovery/runs/${props.run.public_id}`, filters, {
        preserveState: true,
        replace: true,
        only: ['hosts', 'filters'],
    });
};

const isActive = computed(() => ['pending', 'running', 'cancelling'].includes(props.run.status));
let pollHandle: ReturnType<typeof setInterval> | null = null;
onMounted(() => {
    if (isActive.value) {
        pollHandle = setInterval(() => {
            router.reload({ only: ['run', 'hosts'] });
        }, 4000);
    }
});
onBeforeUnmount(() => {
    if (pollHandle) clearInterval(pollHandle);
});

const cancelRun = (): void => {
    router.post(`/discovery/runs/${props.run.public_id}/cancel`, {}, { preserveScroll: true });
};

const selectedHost = ref<Host | null>(null);
const promotionOpen = ref(false);
const promotionData = ref<{
    organizations: { id: number; name: string }[];
    sites: { id: number; organization_id: number; name: string }[];
    departments: { id: number; site_id: number; name: string }[];
    duplicates: {
        type: string;
        system: { id: number; public_id: string; name: string };
        dicom_node: { ae_title: string; host: string; port: number } | null;
    }[];
    suggested: { name: string; system_type: string | null; ae_title: string; port: number | null };
} | null>(null);

const promotionForm = useForm({
    action: 'create' as 'create' | 'update_existing',
    existing_system_id: null as number | null,
    name: '',
    system_type: '',
    vendor: '',
    model: '',
    organization_id: null as number | null,
    site_id: null as number | null,
    department_id: null as number | null,
    operational_status: 'active',
    criticality: '',
    responsible: '',
    description: '',
    notes: '',
    ae_title: '',
    port: 104,
    dicom_node_name: '',
    role: 'scp',
});

const openDetails = (host: Host): void => {
    selectedHost.value = host;
};
const closeDetails = (): void => {
    selectedHost.value = null;
};

const confirmHost = (host: Host): void => {
    router.post(`/discovery/hosts/${host.public_id}/confirm`, {}, { preserveScroll: true });
};
const ignoreHost = (host: Host): void => {
    router.post(`/discovery/hosts/${host.public_id}/ignore`, {}, { preserveScroll: true });
};
const retestHost = (host: Host): void => {
    router.post(`/discovery/hosts/${host.public_id}/retest`, {}, { preserveScroll: true });
};

const openPromotion = async (host: Host): Promise<void> => {
    selectedHost.value = host;
    const response = await fetch(`/discovery/hosts/${host.public_id}/promotion-data`, {
        headers: { Accept: 'application/json' },
    });
    promotionData.value = await response.json();
    promotionForm.reset();
    promotionForm.action = 'create';
    promotionForm.name = promotionData.value?.suggested.name ?? host.hostname ?? host.ip_address;
    promotionForm.system_type = promotionData.value?.suggested.system_type ?? 'unbekannt';
    promotionForm.ae_title = promotionData.value?.suggested.ae_title ?? '';
    promotionForm.port =
        promotionData.value?.suggested.port ?? host.ports.find((p) => p.is_dicom_candidate)?.port ?? 104;
    promotionForm.organization_id = promotionData.value?.organizations[0]?.id ?? null;
    promotionOpen.value = true;
};
const closePromotion = (): void => {
    if (promotionForm.processing) return;
    promotionOpen.value = false;
};
const submitPromotion = (): void => {
    if (!selectedHost.value) return;
    promotionForm.post(`/discovery/hosts/${selectedHost.value.public_id}/promote`, {
        onSuccess: () => (promotionOpen.value = false),
    });
};

const filteredSites = computed(
    () => promotionData.value?.sites.filter((s) => s.organization_id === promotionForm.organization_id) ?? [],
);
const filteredDepartments = computed(
    () => promotionData.value?.departments.filter((d) => d.site_id === promotionForm.site_id) ?? [],
);
</script>

<template>
    <Head :title="`Discovery: ${run.name}`" />
    <AppLayout>
        <div class="space-y-6">
            <PageHeader eyebrow="Erkennung" :title="run.name" :description="run.description ?? undefined">
                <template #actions>
                    <button
                        v-if="canCancel && ['pending', 'running'].includes(run.status)"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-300 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50"
                        @click="cancelRun"
                    >
                        <Ban :size="16" /> Lauf abbrechen
                    </button>
                </template>
            </PageHeader>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{
                            statusLabels[run.status] ?? run.status
                        }}</span>
                        <span class="font-mono text-xs text-slate-500">{{ run.ip_range }}</span>
                        <span v-if="run.location" class="text-xs text-slate-500">· {{ run.location }}</span>
                        <span v-if="run.department" class="text-xs text-slate-500">· {{ run.department }}</span>
                    </div>
                    <p class="text-xs text-slate-500">
                        {{ run.processed_ips }} / {{ run.total_ips }} IP-Adressen geprüft
                    </p>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full bg-blue-600 transition-all"
                        :style="{ width: `${run.progress_percentage}%` }"
                    />
                </div>
                <p v-if="run.error_message" class="mt-3 flex items-center gap-2 text-sm text-red-700">
                    <AlertTriangle :size="15" /> {{ run.error_message }}
                </p>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-xs text-slate-500">Gefundene Hosts</dt>
                        <dd class="font-medium">{{ run.found_hosts_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">DICOM-Kandidaten</dt>
                        <dd class="font-medium">{{ run.dicom_candidates_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Startzeit</dt>
                        <dd class="font-medium">
                            {{ run.started_at ? new Date(run.started_at).toLocaleString('de-DE') : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Ende</dt>
                        <dd class="font-medium">
                            {{ run.finished_at ? new Date(run.finished_at).toLocaleString('de-DE') : '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <label class="flex items-center gap-2"
                        ><input
                            v-model="filters.reachable_only"
                            type="checkbox"
                            true-value="1"
                            false-value=""
                            @change="applyFilters"
                        />
                        Nur erreichbare Hosts</label
                    >
                    <label class="flex items-center gap-2"
                        ><input
                            v-model="filters.dicom_candidates_only"
                            type="checkbox"
                            true-value="1"
                            false-value=""
                            @change="applyFilters"
                        />
                        Nur DICOM-Kandidaten</label
                    >
                    <label class="flex items-center gap-2"
                        ><input
                            v-model="filters.successful_echo_only"
                            type="checkbox"
                            true-value="1"
                            false-value=""
                            @change="applyFilters"
                        />
                        Nur erfolgreiche C-ECHO</label
                    >
                    <select
                        v-model="filters.status"
                        class="rounded-lg border border-slate-300 px-2 py-1.5"
                        @change="applyFilters"
                    >
                        <option value="">Alle Review-Status</option>
                        <option value="discovered">Ungeprüft</option>
                        <option value="reviewing">In Prüfung</option>
                        <option value="confirmed">Bestätigt</option>
                        <option value="ignored">Ignoriert</option>
                    </select>
                    <select
                        v-model="filters.confidence"
                        class="rounded-lg border border-slate-300 px-2 py-1.5"
                        @change="applyFilters"
                    >
                        <option value="">Alle Confidence-Stufen</option>
                        <option v-for="(label, value) in confidenceLabels" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div v-if="hosts.data.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                    Noch keine Treffer für diesen Lauf – Ergebnisse erscheinen hier, sobald der Scan Hosts findet.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">IP-Adresse</th>
                                <th class="px-4 py-3 text-left">Hostname</th>
                                <th class="px-4 py-3 text-left">Erreichbarkeit</th>
                                <th class="px-4 py-3 text-left">DICOM-Status</th>
                                <th class="px-4 py-3 text-left">Systemtyp (Vorschlag)</th>
                                <th class="px-4 py-3 text-left">Confidence</th>
                                <th class="px-4 py-3 text-left">Review-Status</th>
                                <th class="px-4 py-3 text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="host in hosts.data" :key="host.public_id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono">{{ host.ip_address }}</td>
                                <td class="px-4 py-3">{{ host.hostname ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="host.is_reachable ? 'text-emerald-700' : 'text-slate-400'">{{
                                        host.is_reachable ? 'Erreichbar' : 'Nicht erreichbar'
                                    }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="host.dicom_results.some((r) => r.echo_successful)"
                                        class="inline-flex items-center gap-1 text-emerald-700"
                                        ><CheckCircle2 :size="14" /> C-ECHO erfolgreich</span
                                    >
                                    <span
                                        v-else-if="host.ports.some((p) => p.is_dicom_candidate && p.is_open)"
                                        class="text-amber-700"
                                        >Port offen, ungetestet/fehlgeschlagen</span
                                    >
                                    <span v-else class="text-slate-400">Kein Hinweis</span>
                                </td>
                                <td class="px-4 py-3">
                                    {{ host.suggested_system_type ?? 'unbekannt' }}
                                    <span class="text-xs text-slate-400">(Vorschlag)</span>
                                </td>
                                <td class="px-4 py-3">
                                    {{ confidenceLabels[host.confidence_score] }} · {{ host.confidence_percentage }}%
                                </td>
                                <td class="px-4 py-3">{{ reviewStatusLabels[host.status] }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <button
                                            type="button"
                                            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                                            title="Details"
                                            @click="openDetails(host)"
                                        >
                                            <Eye :size="16" />
                                        </button>
                                        <template v-if="canReview">
                                            <button
                                                type="button"
                                                class="rounded-lg p-2 text-slate-500 hover:bg-blue-50 hover:text-blue-700"
                                                title="Erneut prüfen"
                                                @click="retestHost(host)"
                                            >
                                                <RefreshCw :size="16" />
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg p-2 text-slate-500 hover:bg-emerald-50 hover:text-emerald-700"
                                                title="Bestätigen"
                                                @click="confirmHost(host)"
                                            >
                                                <CheckCircle2 :size="16" />
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg p-2 text-slate-500 hover:bg-red-50 hover:text-red-700"
                                                title="Ignorieren"
                                                @click="ignoreHost(host)"
                                            >
                                                <XCircle :size="16" />
                                            </button>
                                            <button
                                                v-if="!host.system"
                                                type="button"
                                                class="rounded-lg p-2 text-slate-500 hover:bg-violet-50 hover:text-violet-700"
                                                title="Als System übernehmen"
                                                @click="openPromotion(host)"
                                            >
                                                <ShieldCheck :size="16" />
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <div v-if="hosts.total > 0" class="flex items-center justify-between">
                <p class="text-sm text-slate-500">{{ hosts.total }} Treffer</p>
                <Pagination :links="hosts.links" />
            </div>
        </div>

        <Teleport to="body">
            <div v-if="selectedHost && !promotionOpen" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
                <button
                    type="button"
                    class="absolute inset-0 bg-slate-950/40"
                    aria-label="Details schließen"
                    @click="closeDetails"
                />
                <aside class="absolute inset-y-0 right-0 w-full max-w-xl overflow-y-auto bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-blue-600 uppercase">Discovery-Fund</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-950">{{ selectedHost.ip_address }}</h2>
                            <p class="text-sm text-slate-500">
                                {{ selectedHost.hostname ?? 'Kein Hostname aufgelöst' }}
                            </p>
                        </div>
                        <button type="button" class="rounded-lg p-2 hover:bg-slate-100" @click="closeDetails">
                            <X :size="20" />
                        </button>
                    </div>

                    <section class="mt-6">
                        <h3 class="text-sm font-semibold text-slate-950">Basisinformationen</h3>
                        <dl class="mt-2 divide-y divide-slate-100 rounded-xl border border-slate-200 text-sm">
                            <div class="grid grid-cols-3 gap-3 px-4 py-2.5">
                                <dt class="text-slate-500">Antwortzeit</dt>
                                <dd class="col-span-2">{{ selectedHost.ping_latency_ms ?? '—' }} ms</dd>
                            </div>
                            <div class="grid grid-cols-3 gap-3 px-4 py-2.5">
                                <dt class="text-slate-500">Erkannt</dt>
                                <dd class="col-span-2">
                                    {{
                                        selectedHost.last_seen_at
                                            ? new Date(selectedHost.last_seen_at).toLocaleString('de-DE')
                                            : '—'
                                    }}
                                </dd>
                            </div>
                            <div class="grid grid-cols-3 gap-3 px-4 py-2.5">
                                <dt class="text-slate-500">Registry-System</dt>
                                <dd class="col-span-2">{{ selectedHost.system?.name ?? 'Noch nicht übernommen' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="mt-5">
                        <h3 class="text-sm font-semibold text-slate-950">Ports</h3>
                        <table class="mt-2 w-full text-xs">
                            <thead class="text-slate-500">
                                <tr>
                                    <th class="py-1 text-left">Port</th>
                                    <th class="text-left">Status</th>
                                    <th class="text-left">Antwortzeit</th>
                                    <th class="text-left">DICOM-Kandidat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="port in selectedHost.ports"
                                    :key="port.port"
                                    class="border-t border-slate-100"
                                >
                                    <td class="py-1.5 font-mono">{{ port.port }}</td>
                                    <td :class="port.is_open ? 'text-emerald-700' : 'text-slate-400'">
                                        {{ port.is_open ? 'Offen' : 'Geschlossen/nicht erreichbar' }}
                                    </td>
                                    <td>{{ port.response_time_ms ?? '—' }} ms</td>
                                    <td>{{ port.is_dicom_candidate ? 'Ja' : 'Nein' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <section class="mt-5">
                        <h3 class="text-sm font-semibold text-slate-950">DICOM-Prüfungen (C-ECHO)</h3>
                        <p class="mt-1 text-xs text-slate-400">
                            Ein erfolgreicher C-ECHO beweist nur die Erreichbarkeit dieses Endpunkts, keine produktive
                            Verbindung.
                        </p>
                        <div v-if="selectedHost.dicom_results.length === 0" class="mt-2 text-xs text-slate-400">
                            Keine DICOM-Prüfungen durchgeführt.
                        </div>
                        <div
                            v-for="(result, idx) in selectedHost.dicom_results"
                            :key="idx"
                            class="mt-2 rounded-lg border border-slate-200 p-3 text-xs"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-mono"
                                    >{{ result.calling_ae }} → {{ result.called_ae }}:{{ result.port }}</span
                                >
                                <span :class="result.echo_successful ? 'text-emerald-700' : 'text-red-600'">{{
                                    result.echo_successful ? 'C-ECHO erfolgreich' : 'Fehlgeschlagen'
                                }}</span>
                            </div>
                            <p class="mt-1 text-slate-500">
                                Association: {{ result.association_successful ? 'erfolgreich' : 'fehlgeschlagen' }}
                            </p>
                            <p v-if="result.error_message" class="mt-1 text-red-600">
                                {{ result.error_message }}
                                <span v-if="result.error_code">({{ result.error_code }})</span>
                            </p>
                        </div>
                    </section>

                    <section class="mt-5">
                        <h3 class="text-sm font-semibold text-slate-950">Klassifizierung</h3>
                        <p class="mt-1 text-xs text-slate-400">
                            Heuristische Bewertung ohne Anspruch auf wissenschaftliche Genauigkeit.
                        </p>
                        <p class="mt-2 text-sm font-medium">
                            {{ selectedHost.suggested_system_type ?? 'unbekannt' }} ·
                            {{ confidenceLabels[selectedHost.confidence_score] }} ({{
                                selectedHost.confidence_percentage
                            }}%)
                        </p>
                        <ul class="mt-2 space-y-1 text-xs text-slate-600">
                            <li
                                v-for="(evidence, idx) in selectedHost.classification_evidence"
                                :key="idx"
                                class="flex justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2"
                            >
                                <span>{{ evidence.reason }}</span>
                                <span class="shrink-0 font-mono text-slate-400">+{{ evidence.weight }}</span>
                            </li>
                            <li v-if="selectedHost.classification_evidence.length === 0" class="text-slate-400">
                                Keine Hinweise gefunden.
                            </li>
                        </ul>
                    </section>

                    <footer v-if="canReview" class="mt-6 flex flex-wrap gap-2 border-t border-slate-200 pt-4">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium"
                            @click="retestHost(selectedHost)"
                        >
                            Erneut prüfen
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border border-emerald-300 px-3 py-2 text-sm font-medium text-emerald-700"
                            @click="confirmHost(selectedHost)"
                        >
                            Bestätigen
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border border-red-300 px-3 py-2 text-sm font-medium text-red-700"
                            @click="ignoreHost(selectedHost)"
                        >
                            Ignorieren
                        </button>
                        <button
                            v-if="!selectedHost.system"
                            type="button"
                            class="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white"
                            @click="openPromotion(selectedHost)"
                        >
                            Als System übernehmen
                        </button>
                    </footer>
                </aside>
            </div>

            <div
                v-if="promotionOpen && selectedHost && promotionData"
                class="fixed inset-0 z-50"
                role="dialog"
                aria-modal="true"
            >
                <button
                    type="button"
                    class="absolute inset-0 bg-slate-950/40"
                    aria-label="Dialog schließen"
                    @click="closePromotion"
                />
                <aside class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col bg-white shadow-2xl">
                    <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold text-blue-600 uppercase">Übernahme in die Registry</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-950">
                                {{ selectedHost.ip_address }} übernehmen
                            </h2>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                            @click="closePromotion"
                        >
                            <X :size="20" />
                        </button>
                    </header>
                    <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submitPromotion">
                        <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                            <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900">
                                Quelle: Discovery-Lauf "{{ run.name }}" · erkannt am
                                {{
                                    selectedHost.last_seen_at
                                        ? new Date(selectedHost.last_seen_at).toLocaleString('de-DE')
                                        : '—'
                                }}
                                · ursprünglicher Confidence-Score: {{ selectedHost.confidence_percentage }}%. Bitte alle
                                Werte vor dem Speichern prüfen.
                            </div>

                            <div
                                v-if="promotionData.duplicates.length > 0"
                                class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900"
                            >
                                <p class="font-semibold">Mögliche Duplikate gefunden:</p>
                                <ul class="mt-1 space-y-1">
                                    <li v-for="(dup, idx) in promotionData.duplicates" :key="idx">
                                        {{ dup.type }}: {{ dup.system.name }}
                                    </li>
                                </ul>
                                <p class="mt-2">
                                    Wählen Sie unten "Bestehendes System aktualisieren", um ein Duplikat zu vermeiden.
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Aktion *</span>
                                    <select
                                        v-model="promotionForm.action"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    >
                                        <option value="create">Als neues System anlegen</option>
                                        <option value="update_existing">Bestehendes System aktualisieren</option>
                                    </select>
                                </label>
                                <label v-if="promotionForm.action === 'update_existing'" class="block"
                                    ><span class="text-sm font-medium text-slate-700">Bestehendes System *</span>
                                    <select
                                        v-model.number="promotionForm.existing_system_id"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    >
                                        <option :value="null">Bitte auswählen</option>
                                        <option
                                            v-for="dup in promotionData.duplicates"
                                            :key="dup.system.id"
                                            :value="dup.system.id"
                                        >
                                            {{ dup.system.name }}
                                        </option>
                                    </select>
                                </label>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Systemname *</span
                                    ><input
                                        v-model="promotionForm.name"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                /></label>
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Systemtyp *</span
                                    ><input
                                        v-model="promotionForm.system_type"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                /></label>
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Hersteller</span
                                    ><input
                                        v-model="promotionForm.vendor"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                /></label>
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Modell</span
                                    ><input
                                        v-model="promotionForm.model"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                /></label>
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Organisation *</span>
                                    <select
                                        v-model.number="promotionForm.organization_id"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    >
                                        <option
                                            v-for="org in promotionData.organizations"
                                            :key="org.id"
                                            :value="org.id"
                                        >
                                            {{ org.name }}
                                        </option>
                                    </select>
                                </label>
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Standort</span>
                                    <select
                                        v-model.number="promotionForm.site_id"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    >
                                        <option :value="null">Kein Standort</option>
                                        <option v-for="site in filteredSites" :key="site.id" :value="site.id">
                                            {{ site.name }}
                                        </option>
                                    </select>
                                </label>
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Abteilung</span>
                                    <select
                                        v-model.number="promotionForm.department_id"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    >
                                        <option :value="null">Keine Abteilung</option>
                                        <option v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">
                                            {{ dept.name }}
                                        </option>
                                    </select>
                                </label>
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Kritikalität</span>
                                    <select
                                        v-model="promotionForm.criticality"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    >
                                        <option value="">Nicht angegeben</option>
                                        <option value="low">Niedrig</option>
                                        <option value="medium">Mittel</option>
                                        <option value="high">Hoch</option>
                                        <option value="critical">Kritisch</option>
                                    </select>
                                </label>
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Verantwortlicher</span
                                    ><input
                                        v-model="promotionForm.responsible"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                /></label>
                            </div>

                            <h3 class="text-sm font-semibold text-slate-800">DICOM-Endpunkt</h3>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">AE-Titel *</span
                                    ><input
                                        v-model="promotionForm.ae_title"
                                        maxlength="16"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm uppercase"
                                /></label>
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Port *</span
                                    ><input
                                        v-model.number="promotionForm.port"
                                        type="number"
                                        min="1"
                                        max="65535"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                /></label>
                                <label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Rolle *</span>
                                    <select
                                        v-model="promotionForm.role"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    >
                                        <option value="scp">SCP</option>
                                        <option value="scu">SCU</option>
                                        <option value="both">SCU und SCP</option>
                                    </select>
                                </label>
                            </div>

                            <label class="block"
                                ><span class="text-sm font-medium text-slate-700">Beschreibung</span
                                ><textarea
                                    v-model="promotionForm.description"
                                    rows="2"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                />
                            </label>
                        </div>
                        <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                            <button
                                type="button"
                                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm"
                                @click="closePromotion"
                            >
                                Abbrechen
                            </button>
                            <button
                                type="submit"
                                :disabled="promotionForm.processing"
                                class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                            >
                                {{ promotionForm.processing ? 'Wird übernommen …' : 'System übernehmen' }}
                            </button>
                        </footer>
                    </form>
                </aside>
            </div>
        </Teleport>
    </AppLayout>
</template>
