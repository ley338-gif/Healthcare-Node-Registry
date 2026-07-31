<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Activity,
    Cable,
    CheckCircle2,
    ChevronDown,
    CircleAlert,
    Clock3,
    Database,
    Download,
    Eye,
    FlaskConical,
    LoaderCircle,
    Network,
    Play,
    Radio,
    RotateCcw,
    Search,
    Settings2,
    Stethoscope,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Pagination from '../../Components/Pagination.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type NamedContext = { public_id: string; name: string };

type DiagnosticStep = {
    name: string;
    label: string;
    status: string;
    durationMilliseconds: number;
    message: string;
    details: Record<string, unknown>;
};

type DiagnosticResult = {
    testId: string;
    testType: string;
    status: string;
    startedAt: string;
    finishedAt: string;
    durationMilliseconds: number;
    summary: string;
    target: {
        host: string;
        port: number;
        dicomNodePublicId: string | null;
    };
    steps: DiagnosticStep[];
    details: Record<string, unknown>;
    warnings: string[];
    errors: string[];
};

type HistoryRun = {
    public_id: string;
    test_type: string;
    status: string;
    started_at: string;
    finished_at: string;
    duration_ms: number;
    result_count: number | null;
    summary: string;
    steps: DiagnosticStep[];
    details: Record<string, unknown>;
    warnings: string[];
    errors: string[];
    dicom_node: NamedContext;
    system: NamedContext;
    user: NamedContext | null;
};

type HistoryFilters = {
    history_from?: string;
    history_to?: string;
    history_node?: string;
    history_type?: string;
    history_status?: string;
    history_user?: string;
};

type TestNode = {
    public_id: string;
    name: string;
    ae_title: string;
    host: string;
    port: number;
    role: string;
    status: string;
    tls_enabled: boolean;
    supports_echo: boolean;
    supports_store: boolean;
    supports_query: boolean;
    supports_retrieve: boolean;
    supports_worklist: boolean;
    last_verified_at: string | null;
    last_verification_status: string | null;
    last_verification_duration_ms: number | null;
    last_verification_message: string | null;
    system: {
        public_id: string;
        name: string;
        system_type: string;
        status: string;
        organization: NamedContext | null;
        site: NamedContext | null;
        department: NamedContext | null;
    };
};

const props = defineProps<{
    nodes: TestNode[];
    canRunEcho: boolean;
    canRunNetwork: boolean;
    latestResult: DiagnosticResult | null;
    history: {
        data: HistoryRun[];
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    historyFilters: HistoryFilters;
    historyUsers: NamedContext[];
}>();

const search = ref('');
const selectedNodeId = ref<string | null>(props.nodes[0]?.public_id ?? null);
const echoProcessing = ref(false);
const networkProcessing = ref(false);
const resultExpanded = ref(true);
const selectedHistoryRun = ref<HistoryRun | null>(null);
const historyFrom = ref(props.historyFilters.history_from ?? '');
const historyTo = ref(props.historyFilters.history_to ?? '');
const historyNode = ref(props.historyFilters.history_node ?? '');
const historyType = ref(props.historyFilters.history_type ?? '');
const historyStatus = ref(props.historyFilters.history_status ?? '');
const historyUser = ref(props.historyFilters.history_user ?? '');

const selectedNode = computed(() => props.nodes.find((node) => node.public_id === selectedNodeId.value) ?? null);

const filteredNodes = computed(() => {
    const term = search.value.trim().toLocaleLowerCase('de-DE');

    if (term === '') return props.nodes;

    return props.nodes.filter((node) =>
        [
            node.name,
            node.ae_title,
            node.host,
            node.system.name,
            node.system.organization?.name,
            node.system.site?.name,
            node.system.department?.name,
        ]
            .filter(Boolean)
            .some((value) => value!.toLocaleLowerCase('de-DE').includes(term)),
    );
});

const configuredServices = computed(() => {
    const node = selectedNode.value;
    if (node === null) return [];

    return [
        node.supports_echo ? 'C-ECHO' : null,
        node.supports_store ? 'C-STORE' : null,
        node.supports_query ? 'Query' : null,
        node.supports_retrieve ? 'Retrieve' : null,
        node.supports_worklist ? 'Worklist' : null,
    ].filter((service): service is string => service !== null);
});

const verificationLabel = computed(() => {
    const status = selectedNode.value?.last_verification_status;
    if (status === null || status === undefined) return 'Noch nicht geprüft';

    return (
        {
            success: 'Erfolgreich',
            timeout: 'Timeout',
            unreachable: 'Nicht erreichbar',
            failed: 'Fehlgeschlagen',
            error: 'Fehler',
        }[status] ?? status
    );
});

const verificationSuccessful = computed(() => selectedNode.value?.last_verification_status === 'success');

const canStartEcho = computed(
    () => selectedNode.value !== null && selectedNode.value.supports_echo && props.canRunEcho && !echoProcessing.value,
);

const canStartNetwork = computed(() => selectedNode.value !== null && props.canRunNetwork && !networkProcessing.value);

const displayedDiagnosticResult = computed(() => {
    if (props.latestResult?.target.dicomNodePublicId !== selectedNode.value?.public_id) return null;

    return props.latestResult;
});

watch(filteredNodes, (nodes) => {
    if (nodes.length === 0) return;

    if (!nodes.some((node) => node.public_id === selectedNodeId.value)) {
        selectedNodeId.value = nodes[0].public_id;
    }
});

const runEcho = (): void => {
    if (!canStartEcho.value || selectedNode.value === null) return;

    echoProcessing.value = true;

    router.post(
        `/dicom-nodes/${selectedNode.value.public_id}/verify`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                echoProcessing.value = false;
            },
        },
    );
};

const runNetworkTest = (): void => {
    if (!canStartNetwork.value || selectedNode.value === null) return;

    networkProcessing.value = true;

    router.post(
        `/tests/network/${selectedNode.value.public_id}`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                networkProcessing.value = false;
            },
        },
    );
};

const formatDate = (value: string | null): string => {
    if (value === null) return 'Noch nicht ausgeführt';

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const applyHistoryFilters = (): void => {
    router.get(
        '/tests',
        {
            history_from: historyFrom.value || undefined,
            history_to: historyTo.value || undefined,
            history_node: historyNode.value || undefined,
            history_type: historyType.value || undefined,
            history_status: historyStatus.value || undefined,
            history_user: historyUser.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const resetHistoryFilters = (): void => {
    historyFrom.value = '';
    historyTo.value = '';
    historyNode.value = '';
    historyType.value = '';
    historyStatus.value = '';
    historyUser.value = '';
    applyHistoryFilters();
};

const prepareRerun = (run: HistoryRun): void => {
    selectedNodeId.value = run.dicom_node.public_id;
    selectedHistoryRun.value = null;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
</script>

<template>
    <Head title="Tests" />

    <AppLayout>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">Diagnose</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-950">Tests</h1>
                <p class="mt-1 text-sm text-slate-500">Netzwerk-, DICOM- und Worklist-Verbindungen zentral prüfen.</p>
            </div>

            <button
                type="button"
                disabled
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white opacity-50"
            >
                <Play :size="16" />
                Alle Schnelltests
            </button>
        </div>

        <div class="mt-6 grid gap-5 xl:grid-cols-[340px_minmax(0,1fr)]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 shadow-sm">
                <header class="border-b border-slate-200 bg-white p-4">
                    <h2 class="font-semibold text-slate-950">DICOM-Knoten auswählen</h2>
                    <p class="mt-1 text-xs text-slate-500">Tests werden ausschließlich durch das Backend ausgeführt.</p>

                    <div class="relative mt-4">
                        <Search :size="17" class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Knoten, AE Title, Host oder System"
                            class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pr-3 pl-10 text-sm"
                        />
                    </div>
                </header>

                <div class="max-h-[690px] space-y-2 overflow-y-auto p-3">
                    <button
                        v-for="node in filteredNodes"
                        :key="node.public_id"
                        type="button"
                        :class="[
                            'w-full rounded-xl border px-3 py-3 text-left transition',
                            selectedNodeId === node.public_id
                                ? 'border-blue-200 bg-blue-50 ring-1 ring-blue-100'
                                : 'border-transparent bg-white hover:border-slate-200 hover:bg-slate-50',
                        ]"
                        @click="selectedNodeId = node.public_id"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-700"
                            >
                                <Radio :size="17" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="truncate text-sm font-semibold text-slate-950">
                                        {{ node.name }}
                                    </p>
                                    <span
                                        class="mt-1 h-2 w-2 shrink-0 rounded-full"
                                        :class="
                                            node.last_verification_status === 'success'
                                                ? 'bg-emerald-500'
                                                : node.last_verification_status
                                                  ? 'bg-red-500'
                                                  : 'bg-amber-400'
                                        "
                                    />
                                </div>
                                <p class="mt-0.5 truncate font-mono text-xs text-slate-500">
                                    {{ node.ae_title }} · {{ node.host }}:{{ node.port }}
                                </p>
                                <p class="mt-2 truncate text-[11px] text-slate-500">
                                    {{ node.system.organization?.name || 'Ohne Organisation' }}
                                    <template v-if="node.system.site">· {{ node.system.site.name }}</template>
                                </p>
                            </div>
                        </div>
                    </button>
                </div>
            </section>

            <div v-if="selectedNode" class="space-y-5">
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <header
                        class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700"
                                >
                                    DICOM-Knoten
                                </span>
                                <span
                                    class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"
                                >
                                    {{ selectedNode.system.name }}
                                </span>
                            </div>
                            <h2 class="mt-3 text-2xl font-semibold text-slate-950">
                                {{ selectedNode.name }}
                            </h2>
                            <p class="mt-1 font-mono text-sm text-slate-500">
                                {{ selectedNode.ae_title }} · {{ selectedNode.host }}:{{ selectedNode.port }}
                            </p>
                        </div>

                        <span
                            class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset"
                            :class="
                                verificationSuccessful
                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                    : selectedNode.last_verification_status
                                      ? 'bg-red-50 text-red-700 ring-red-200'
                                      : 'bg-amber-50 text-amber-700 ring-amber-200'
                            "
                        >
                            <CheckCircle2 v-if="verificationSuccessful" :size="14" />
                            <CircleAlert v-else :size="14" />
                            {{ verificationLabel }}
                        </span>
                    </header>

                    <div class="grid gap-3 bg-slate-50/70 p-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs text-slate-500">Organisation</p>
                            <p class="mt-1 truncate text-sm font-semibold text-slate-900">
                                {{ selectedNode.system.organization?.name || 'Nicht zugeordnet' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs text-slate-500">Standort</p>
                            <p class="mt-1 truncate text-sm font-semibold text-slate-900">
                                {{ selectedNode.system.site?.name || 'Nicht zugeordnet' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs text-slate-500">Abteilung</p>
                            <p class="mt-1 truncate text-sm font-semibold text-slate-900">
                                {{ selectedNode.system.department?.name || 'Nicht zugeordnet' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs text-slate-500">Konfigurierte Dienste</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                {{ configuredServices.length }}
                            </p>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-3">
                        <h2 class="font-semibold text-slate-950">Schnelltests</h2>
                        <p class="mt-1 text-sm text-slate-500">Geeignete Prüfungen für den ausgewählten Knoten.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
                        <article
                            class="flex min-h-[220px] flex-col rounded-2xl border border-cyan-200 bg-white p-5 shadow-sm"
                        >
                            <div class="self-start rounded-xl bg-cyan-50 p-2.5 text-cyan-700"><Cable :size="20" /></div>
                            <h3 class="mt-5 font-semibold text-slate-950">Netzwerkverbindung</h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                                DNS-Auflösung, TCP-Verbindung, Port und Antwortzeit prüfen.
                            </p>
                            <button
                                type="button"
                                :disabled="!canStartNetwork"
                                class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                                @click="runNetworkTest"
                            >
                                <LoaderCircle v-if="networkProcessing" :size="16" class="animate-spin" />
                                <Cable v-else :size="16" />
                                {{ networkProcessing ? 'TCP-Verbindung wird geprüft' : 'Verbindung testen' }}
                            </button>
                        </article>

                        <article
                            class="flex min-h-[220px] flex-col rounded-2xl border border-blue-200 bg-white p-5 shadow-sm"
                        >
                            <div class="flex items-start justify-between">
                                <div class="rounded-xl bg-blue-50 p-2.5 text-blue-700"><Activity :size="20" /></div>
                                <span
                                    class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"
                                >
                                    Verfügbar
                                </span>
                            </div>
                            <h3 class="mt-5 font-semibold text-slate-950">DICOM Echo</h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                                Association und Verification SOP Class gegen den registrierten Knoten testen.
                            </p>
                            <button
                                type="button"
                                :disabled="!canStartEcho"
                                class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                                @click="runEcho"
                            >
                                <LoaderCircle v-if="echoProcessing" :size="16" class="animate-spin" />
                                <Stethoscope v-else :size="16" />
                                C-ECHO starten
                            </button>
                        </article>

                        <article
                            class="flex min-h-[220px] flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div class="self-start rounded-xl bg-violet-50 p-2.5 text-violet-700">
                                <FlaskConical :size="20" />
                            </div>
                            <h3 class="mt-5 font-semibold text-slate-950">Modality Worklist</h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                                C-FIND-Abfrage mit Datum, Modalität, Station AE und Patientenkriterien.
                            </p>
                            <button
                                disabled
                                class="mt-5 rounded-xl border px-4 py-2.5 text-sm font-semibold text-slate-500 opacity-50"
                            >
                                Geplant
                            </button>
                        </article>

                        <article
                            class="flex min-h-[220px] flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div class="self-start rounded-xl bg-amber-50 p-2.5 text-amber-700">
                                <Database :size="20" />
                            </div>
                            <h3 class="mt-5 font-semibold text-slate-950">PACS Query</h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                                Study-Root-C-FIND nach Patient, Accession Number oder Study UID.
                            </p>
                            <button
                                disabled
                                class="mt-5 rounded-xl border px-4 py-2.5 text-sm font-semibold text-slate-500 opacity-50"
                            >
                                Geplant
                            </button>
                        </article>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between px-5 py-4 text-left"
                        @click="resultExpanded = !resultExpanded"
                    >
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-slate-100 p-2 text-slate-600">
                                <Settings2 :size="18" />
                            </div>
                            <div>
                                <h2 class="font-semibold text-slate-950">Letztes Testergebnis</h2>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{
                                        formatDate(
                                            displayedDiagnosticResult?.finishedAt ?? selectedNode.last_verified_at,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                        <ChevronDown :size="18" :class="{ 'rotate-180': resultExpanded }" />
                    </button>

                    <div v-if="resultExpanded" class="border-t border-slate-200 p-5">
                        <div v-if="displayedDiagnosticResult" class="space-y-4">
                            <div>
                                <p
                                    class="text-sm font-semibold"
                                    :class="
                                        displayedDiagnosticResult.status === 'success'
                                            ? 'text-emerald-700'
                                            : 'text-red-700'
                                    "
                                >
                                    {{ displayedDiagnosticResult.summary }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Netzwerk · {{ displayedDiagnosticResult.durationMilliseconds }} ms
                                </p>
                            </div>
                            <ol class="space-y-2">
                                <li
                                    v-for="step in displayedDiagnosticResult.steps"
                                    :key="step.name"
                                    class="rounded-xl border border-slate-200 p-3"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-slate-900">{{ step.label }}</p>
                                        <span class="text-xs text-slate-500">{{ step.durationMilliseconds }} ms</span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-600">{{ step.message }}</p>
                                    <details
                                        v-if="Object.keys(step.details).length"
                                        class="mt-2 text-xs text-slate-500"
                                    >
                                        <summary class="cursor-pointer font-semibold">Technische Details</summary>
                                        <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-950 p-3 text-slate-100">{{
                                            JSON.stringify(step.details, null, 2)
                                        }}</pre>
                                    </details>
                                </li>
                            </ol>
                        </div>
                        <div v-else-if="selectedNode.last_verified_at" class="grid gap-4 lg:grid-cols-[1fr_280px]">
                            <div>
                                <p
                                    class="text-sm font-semibold"
                                    :class="verificationSuccessful ? 'text-emerald-700' : 'text-red-700'"
                                >
                                    {{ verificationLabel }}
                                </p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    {{
                                        selectedNode.last_verification_message ||
                                        'Keine zusätzliche Meldung gespeichert.'
                                    }}
                                </p>
                            </div>
                            <dl class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm">
                                <div>
                                    <dt class="text-xs text-slate-500">Dienst</dt>
                                    <dd class="mt-1 font-semibold">C-ECHO</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Dauer</dt>
                                    <dd class="mt-1 font-semibold">
                                        {{
                                            selectedNode.last_verification_duration_ms !== null
                                                ? `${selectedNode.last_verification_duration_ms} ms`
                                                : '—'
                                        }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Called AE</dt>
                                    <dd class="mt-1 font-mono text-xs">{{ selectedNode.ae_title }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Ziel</dt>
                                    <dd class="mt-1 font-mono text-xs">
                                        {{ selectedNode.host }}:{{ selectedNode.port }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                        <div v-else class="py-6 text-center">
                            <Clock3 :size="28" class="mx-auto text-slate-300" />
                            <p class="mt-3 text-sm font-semibold text-slate-800">Noch kein Test ausgeführt</p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <header class="border-b border-slate-200 p-5">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-slate-100 p-2.5 text-slate-600"><Network :size="20" /></div>
                            <div>
                                <h2 class="font-semibold text-slate-950">Testverlauf</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ history.total }} gespeicherte Testläufe</p>
                            </div>
                        </div>

                        <form
                            class="mt-4 grid gap-3 md:grid-cols-3 xl:grid-cols-6"
                            @submit.prevent="applyHistoryFilters"
                        >
                            <input
                                v-model="historyFrom"
                                type="date"
                                class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            />
                            <input
                                v-model="historyTo"
                                type="date"
                                class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            />
                            <select v-model="historyNode" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Alle Knoten</option>
                                <option v-for="node in nodes" :key="node.public_id" :value="node.public_id">
                                    {{ node.name }}
                                </option>
                            </select>
                            <select v-model="historyType" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Alle Testtypen</option>
                                <option value="network">Netzwerk</option>
                                <option value="dicom_echo">C-ECHO</option>
                            </select>
                            <select
                                v-model="historyStatus"
                                class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            >
                                <option value="">Alle Status</option>
                                <option value="success">Erfolgreich</option>
                                <option value="warning">Warnung</option>
                                <option value="failed">Fehlgeschlagen</option>
                                <option value="timeout">Timeout</option>
                            </select>
                            <select
                                v-if="historyUsers.length"
                                v-model="historyUser"
                                class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            >
                                <option value="">Alle Benutzer</option>
                                <option v-for="user in historyUsers" :key="user.public_id" :value="user.public_id">
                                    {{ user.name }}
                                </option>
                            </select>
                            <div class="flex gap-2 md:col-span-3 xl:col-span-6">
                                <button
                                    type="submit"
                                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                                >
                                    Filter anwenden
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600"
                                    @click="resetHistoryFilters"
                                >
                                    Zurücksetzen
                                </button>
                            </div>
                        </form>
                    </header>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                                <tr>
                                    <th class="px-4 py-3">Zeitpunkt</th>
                                    <th class="px-4 py-3">Knoten / System</th>
                                    <th class="px-4 py-3">Testtyp</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Dauer</th>
                                    <th class="px-4 py-3">Treffer</th>
                                    <th class="px-4 py-3">Benutzer</th>
                                    <th class="px-4 py-3">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="run in history.data" :key="run.public_id">
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                        {{ formatDate(run.started_at) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-900">{{ run.dicom_node.name }}</p>
                                        <p class="text-xs text-slate-500">{{ run.system.name }}</p>
                                    </td>
                                    <td class="px-4 py-3">{{ run.test_type === 'network' ? 'Netzwerk' : 'C-ECHO' }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="rounded-full px-2 py-1 text-xs font-semibold"
                                            :class="
                                                run.status === 'success'
                                                    ? 'bg-emerald-50 text-emerald-700'
                                                    : 'bg-red-50 text-red-700'
                                            "
                                            >{{ run.status }}</span
                                        >
                                    </td>
                                    <td class="px-4 py-3">{{ run.duration_ms }} ms</td>
                                    <td class="px-4 py-3">{{ run.result_count ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ run.user?.name ?? 'System' }}</td>
                                    <td class="px-4 py-3">
                                        <button
                                            type="button"
                                            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                                            title="Details"
                                            @click="selectedHistoryRun = run"
                                        >
                                            <Eye :size="17" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="history.data.length === 0">
                                    <td colspan="8" class="px-4 py-10 text-center text-slate-500">
                                        Keine Testläufe für diese Filter.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="history.links.length > 3" class="border-t border-slate-200 p-4">
                        <Pagination :links="history.links" />
                    </div>
                </section>
            </div>
        </div>

        <div v-if="selectedHistoryRun" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                class="absolute inset-0 bg-slate-950/40"
                aria-label="Details schließen"
                @click="selectedHistoryRun = null"
            />
            <aside class="absolute inset-y-0 right-0 w-full max-w-xl overflow-y-auto bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-blue-600 uppercase">Testergebnis</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">
                            {{ selectedHistoryRun.dicom_node.name }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">{{ formatDate(selectedHistoryRun.started_at) }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                        @click="selectedHistoryRun = null"
                    >
                        <X :size="20" />
                    </button>
                </div>
                <div class="mt-6 rounded-xl bg-slate-50 p-4">
                    <p class="font-semibold text-slate-900">{{ selectedHistoryRun.summary }}</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ selectedHistoryRun.duration_ms }} ms · {{ selectedHistoryRun.status }}
                    </p>
                </div>
                <ol class="mt-5 space-y-3">
                    <li
                        v-for="step in selectedHistoryRun.steps"
                        :key="step.name"
                        class="rounded-xl border border-slate-200 p-4"
                    >
                        <div class="flex justify-between gap-3">
                            <p class="font-semibold text-slate-900">{{ step.label }}</p>
                            <span class="text-xs text-slate-500">{{ step.durationMilliseconds }} ms</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600">{{ step.message }}</p>
                    </li>
                </ol>
                <details v-if="Object.keys(selectedHistoryRun.details).length" class="mt-5">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-700">Technische Details</summary>
                    <pre class="mt-3 overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{
                        JSON.stringify(selectedHistoryRun.details, null, 2)
                    }}</pre>
                </details>
                <div class="mt-6 flex gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
                        @click="prepareRerun(selectedHistoryRun)"
                    >
                        <RotateCcw :size="16" />Test erneut vorbereiten</button
                    ><button
                        type="button"
                        disabled
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-500 opacity-50"
                    >
                        <Download :size="16" />JSON-Export geplant
                    </button>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>
