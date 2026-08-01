<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Activity,
    Archive,
    Cable,
    CheckCircle2,
    ChevronDown,
    CircleAlert,
    Clock3,
    Database,
    Download,
    Eye,
    FileSearch,
    FlaskConical,
    LoaderCircle,
    Network,
    Pencil,
    Play,
    Plus,
    Radio,
    RotateCcw,
    Search,
    Send,
    Settings2,
    Stethoscope,
    TableProperties,
    X,
} from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
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
    run?: string;
};

type WorklistItem = {
    patientName: string | null;
    patientId: string | null;
    patientBirthDate: string | null;
    accessionNumber: string | null;
    modality: string | null;
    scheduledStationAeTitle: string | null;
    scheduledDate: string | null;
    scheduledTime: string | null;
    scheduledDescription: string | null;
    requestedProcedureId: string | null;
    scheduledProcedureStepId: string | null;
};

type TestProfile = {
    public_id: string;
    name: string;
    description: string | null;
    test_type: string;
    calling_ae_title: string | null;
    configuration: Record<string, string | null>;
    timeout_seconds: number;
    enabled: boolean;
    dicom_node: NamedContext;
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

type FileAnalysis = {
    successful: boolean;
    summary: Record<string, unknown>;
    warnings: string[];
    errors: string[];
    dump: string;
};

const props = defineProps<{
    nodes: TestNode[];
    canRunEcho: boolean;
    canRunNetwork: boolean;
    canRunWorklist: boolean;
    canRunPacsQuery: boolean;
    canRunStorage: boolean;
    canAnalyzeFile: boolean;
    canExport: boolean;
    fileAnalysis: FileAnalysis | null;
    latestResult: DiagnosticResult | null;
    history: {
        data: HistoryRun[];
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    historyFilters: HistoryFilters;
    historyUsers: NamedContext[];
    profiles: TestProfile[];
    canManageProfiles: boolean;
}>();

const search = ref('');
const selectedNodeId = ref<string | null>(props.nodes[0]?.public_id ?? null);
const echoProcessing = ref(false);
const networkProcessing = ref(false);
const resultExpanded = ref(true);
const selectedHistoryRun = ref<HistoryRun | null>(
    props.history.data.find((run) => run.public_id === props.historyFilters.run) ?? null,
);
const worklistDialogOpen = ref(false);
const pacsDialogOpen = ref(false);
const profileDialogOpen = ref(false);
const storageDialogOpen = ref(false);
const capabilityDialogOpen = ref(false);
const fileAnalysisDialogOpen = ref(props.fileAnalysis !== null);
const fileAnalysisResult = ref<FileAnalysis | null>(props.fileAnalysis);
const fileAnalysisResultElement = ref<HTMLElement | null>(null);
const editingProfileId = ref<string | null>(null);
const historyFrom = ref(props.historyFilters.history_from ?? '');
const historyTo = ref(props.historyFilters.history_to ?? '');
const historyNode = ref(props.historyFilters.history_node ?? '');
const historyType = ref(props.historyFilters.history_type ?? '');
const historyStatus = ref(props.historyFilters.history_status ?? '');
const historyUser = ref(props.historyFilters.history_user ?? '');
const today = new Intl.DateTimeFormat('en-CA').format(new Date());
const worklistForm = useForm({
    calling_ae_title: 'NODE_REGISTRY',
    called_ae_title: '',
    scheduled_station_ae_title: '',
    examination_date: today,
    examination_date_to: '',
    modality: '',
    patient_name: '',
    patient_id: '',
    accession_number: '',
});
const pacsForm = useForm({
    calling_ae_title: 'NODE_REGISTRY',
    called_ae_title: '',
    patient_name: '',
    patient_id: '',
    accession_number: '',
    study_instance_uid: '',
    modality: '',
    study_date: '',
    study_date_to: '',
    study_description: '',
});
const profileForm = useForm({
    name: '',
    description: '',
    test_type: 'network',
    dicom_node_public_id: '',
    calling_ae_title: 'NODE_REGISTRY',
    configuration: {} as Record<string, string>,
    timeout_seconds: 15,
    enabled: true,
});
const storageForm = useForm({ confirmed: false, calling_ae_title: 'NODE_REGISTRY', called_ae_title: '' });
const capabilityForm = useForm({ calling_ae_title: 'NODE_REGISTRY', called_ae_title: '' });
const fileAnalysisForm = useForm<{ dicom_file: File | null }>({ dicom_file: null });

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

const worklistResults = computed<WorklistItem[]>(() => {
    if (displayedDiagnosticResult.value?.testType !== 'worklist') return [];

    const results = displayedDiagnosticResult.value.details.results;

    return Array.isArray(results) ? (results as WorklistItem[]) : [];
});

type CapabilityCell = { sopClass: string; transferSyntax: string; status: string; verification: string };
const capabilityMatrix = computed<CapabilityCell[]>(() => {
    if (displayedDiagnosticResult.value?.testType !== 'dicom_capability_matrix') return [];
    const matrix = displayedDiagnosticResult.value.details.matrix;
    return Array.isArray(matrix) ? (matrix as CapabilityCell[]) : [];
});
const capabilitySopClasses = computed<Record<string, { label: string; uid: string }>>(
    () => (displayedDiagnosticResult.value?.details.sopClasses ?? {}) as Record<string, { label: string; uid: string }>,
);
const capabilityTransferSyntaxes = computed<Record<string, { label: string; uid: string }>>(
    () =>
        (displayedDiagnosticResult.value?.details.transferSyntaxes ?? {}) as Record<
            string,
            { label: string; uid: string }
        >,
);
const capabilityStatus = (sopClass: string, transferSyntax: string): string =>
    capabilityMatrix.value.find((cell) => cell.sopClass === sopClass && cell.transferSyntax === transferSyntax)
        ?.status ?? 'not_tested';

const exportCapabilityMatrix = (): void => {
    const rows = ['SOP Class,Transfer Syntax,Status,Prüfart'];
    for (const cell of capabilityMatrix.value) {
        rows.push(
            [
                capabilitySopClasses.value[cell.sopClass]?.label ?? cell.sopClass,
                capabilityTransferSyntaxes.value[cell.transferSyntax]?.label ?? cell.transferSyntax,
                cell.status,
                'Presentation Context',
            ]
                .map((value) => `"${value.replaceAll('"', '""')}"`)
                .join(','),
        );
    }
    const url = URL.createObjectURL(new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `dicom-capabilities-${selectedNode.value?.public_id ?? 'result'}.csv`;
    link.click();
    URL.revokeObjectURL(url);
};

const selectDicomFile = (event: Event): void => {
    fileAnalysisForm.dicom_file = (event.target as HTMLInputElement).files?.[0] ?? null;
    fileAnalysisResult.value = null;
};

const analyzeDicomFile = (): void => {
    fileAnalysisForm.post('/tests/dicom-file-analysis', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => fileAnalysisForm.reset(),
    });
};

watch(
    () => props.fileAnalysis,
    (analysis) => {
        if (analysis === null) return;
        fileAnalysisResult.value = analysis;
        fileAnalysisDialogOpen.value = true;
        void nextTick(() => fileAnalysisResultElement.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
    },
);

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

const openWorklistTest = (): void => {
    if (selectedNode.value === null || !props.canRunWorklist || !selectedNode.value.supports_worklist) return;

    worklistForm.called_ae_title = selectedNode.value.ae_title;
    worklistDialogOpen.value = true;
};

const resetWorklistFilters = (): void => {
    worklistForm.scheduled_station_ae_title = '';
    worklistForm.examination_date = today;
    worklistForm.examination_date_to = '';
    worklistForm.modality = '';
    worklistForm.patient_name = '';
    worklistForm.patient_id = '';
    worklistForm.accession_number = '';
    worklistForm.clearErrors();
};

const runWorklistTest = (): void => {
    if (selectedNode.value === null) return;

    worklistForm.post(`/tests/worklist/${selectedNode.value.public_id}`, {
        preserveScroll: true,
        onSuccess: () => {
            worklistDialogOpen.value = false;
            resultExpanded.value = true;
        },
    });
};

const openPacsQuery = (): void => {
    if (!selectedNode.value?.supports_query || !props.canRunPacsQuery) return;
    pacsForm.called_ae_title = selectedNode.value.ae_title;
    pacsDialogOpen.value = true;
};

const runPacsQuery = (): void => {
    if (selectedNode.value === null) return;
    pacsForm.post(`/tests/pacs-query/${selectedNode.value.public_id}`, {
        preserveScroll: true,
        onSuccess: () => {
            pacsDialogOpen.value = false;
            resultExpanded.value = true;
        },
    });
};

const profileConfiguration = (type: string): Record<string, string> => {
    if (type === 'worklist') return { ...worklistForm.data(), called_ae_title: worklistForm.called_ae_title };
    if (type === 'pacs_query') return { ...pacsForm.data(), called_ae_title: pacsForm.called_ae_title };
    return {};
};

const openNewProfile = (): void => {
    if (selectedNode.value === null) return;
    editingProfileId.value = null;
    profileForm.reset();
    profileForm.dicom_node_public_id = selectedNode.value.public_id;
    profileDialogOpen.value = true;
};

const editProfile = (profile: TestProfile): void => {
    editingProfileId.value = profile.public_id;
    profileForm.name = profile.name;
    profileForm.description = profile.description ?? '';
    profileForm.test_type = profile.test_type;
    profileForm.dicom_node_public_id = profile.dicom_node.public_id;
    profileForm.calling_ae_title = profile.calling_ae_title ?? 'NODE_REGISTRY';
    profileForm.configuration = Object.fromEntries(
        Object.entries(profile.configuration).map(([key, value]) => [key, value ?? '']),
    );
    profileForm.timeout_seconds = profile.timeout_seconds;
    profileForm.enabled = profile.enabled;
    profileDialogOpen.value = true;
};

const saveProfile = (): void => {
    if (editingProfileId.value === null) profileForm.configuration = profileConfiguration(profileForm.test_type);
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            profileDialogOpen.value = false;
        },
    };
    if (editingProfileId.value) profileForm.put(`/tests/profiles/${editingProfileId.value}`, options);
    else profileForm.post('/tests/profiles', options);
};

const executeProfile = (profile: TestProfile): void => {
    if (!profile.enabled) return;
    selectedNodeId.value = profile.dicom_node.public_id;
    router.post(`/tests/profiles/${profile.public_id}/execute`, {}, { preserveScroll: true });
};

const archiveProfile = (profile: TestProfile): void => {
    if (window.confirm(`Testprofil „${profile.name}“ archivieren?`))
        router.post(`/tests/profiles/${profile.public_id}/archive`, {}, { preserveScroll: true });
};

const openStorageTest = (): void => {
    if (!selectedNode.value?.supports_store || !props.canRunStorage) return;
    storageForm.reset();
    storageForm.called_ae_title = selectedNode.value.ae_title;
    storageDialogOpen.value = true;
};

const runStorageTest = (): void => {
    if (selectedNode.value === null) return;
    storageForm.post(`/tests/storage/${selectedNode.value.public_id}`, {
        preserveScroll: true,
        onSuccess: () => {
            storageDialogOpen.value = false;
            resultExpanded.value = true;
        },
    });
};

const openCapabilityTest = (): void => {
    if (!selectedNode.value?.supports_store || !props.canRunStorage) return;
    capabilityForm.reset();
    capabilityForm.called_ae_title = selectedNode.value.ae_title;
    capabilityDialogOpen.value = true;
};

const runCapabilityTest = (): void => {
    if (selectedNode.value === null) return;
    capabilityForm.post(`/tests/capabilities/${selectedNode.value.public_id}`, {
        preserveScroll: true,
        onSuccess: () => {
            capabilityDialogOpen.value = false;
            resultExpanded.value = true;
        },
    });
};

const formatDate = (value: string | null): string => {
    if (value === null) return 'Noch nicht ausgeführt';

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const testTypeLabel = (type: string): string =>
    ({
        network: 'Netzwerk',
        dicom_echo: 'C-ECHO',
        worklist: 'Worklist',
        pacs_query: 'PACS Query',
        dicom_storage: 'DICOM Storage',
        dicom_capability_matrix: 'Capability-Matrix',
    })[type] ?? type;

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

const exportRun = (run: HistoryRun, format: 'json' | 'csv'): void => {
    if (!props.canExport) return;
    window.location.href = `/tests/history/${run.public_id}/export/${format}`;
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

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    :disabled="!canAnalyzeFile"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-40"
                    @click="fileAnalysisDialogOpen = true"
                >
                    <FileSearch :size="16" />DICOM-Datei analysieren
                </button>
                <button
                    type="button"
                    disabled
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white opacity-50"
                >
                    <Play :size="16" />Alle Schnelltests
                </button>
            </div>
        </div>

        <div class="mt-6 grid gap-5 xl:grid-cols-[340px_minmax(0,1fr)]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 shadow-sm">
                <div class="border-b border-slate-200 bg-slate-950 p-4 text-white">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold">Testprofile</h2>
                            <p class="mt-0.5 text-xs text-slate-400">{{ profiles.length }} aktive Profile</p>
                        </div>
                        <button
                            v-if="canManageProfiles"
                            type="button"
                            class="rounded-lg bg-blue-600 p-2 hover:bg-blue-500"
                            title="Profil anlegen"
                            @click="openNewProfile"
                        >
                            <Plus :size="16" />
                        </button>
                    </div>
                    <div v-if="profiles.length" class="mt-3 space-y-2">
                        <div v-for="profile in profiles" :key="profile.public_id" class="rounded-xl bg-slate-900 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <button
                                    type="button"
                                    :disabled="!profile.enabled"
                                    class="min-w-0 flex-1 text-left disabled:opacity-40"
                                    @click="executeProfile(profile)"
                                >
                                    <p class="truncate text-sm font-semibold">{{ profile.name }}</p>
                                    <p class="mt-1 truncate text-xs text-slate-400">
                                        {{ testTypeLabel(profile.test_type) }} · {{ profile.dicom_node.name }}
                                    </p>
                                </button>
                                <div v-if="canManageProfiles" class="flex">
                                    <button
                                        type="button"
                                        class="rounded p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white"
                                        title="Bearbeiten"
                                        @click="editProfile(profile)"
                                    >
                                        <Pencil :size="14" /></button
                                    ><button
                                        type="button"
                                        class="rounded p-1.5 text-slate-400 hover:bg-slate-800 hover:text-red-300"
                                        title="Archivieren"
                                        @click="archiveProfile(profile)"
                                    >
                                        <Archive :size="14" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-3 text-xs text-slate-400">Noch keine Profile angelegt.</p>
                </div>
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

                    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
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
                            class="flex min-h-[220px] flex-col rounded-2xl border border-teal-200 bg-white p-5 shadow-sm"
                        >
                            <div class="self-start rounded-xl bg-teal-50 p-2.5 text-teal-700">
                                <TableProperties :size="20" />
                            </div>
                            <h3 class="mt-5 font-semibold text-slate-950">Capability-Matrix</h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                                SOP Classes und Transfer Syntaxes per DICOM Association aushandeln – ohne C-STORE.
                            </p>
                            <button
                                type="button"
                                :disabled="!canRunStorage || !selectedNode.supports_store || capabilityForm.processing"
                                class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                                @click="openCapabilityTest"
                            >
                                <TableProperties :size="16" />Matrix prüfen
                            </button>
                        </article>

                        <article
                            class="flex min-h-[220px] flex-col rounded-2xl border border-rose-200 bg-white p-5 shadow-sm"
                        >
                            <div class="self-start rounded-xl bg-rose-50 p-2.5 text-rose-700"><Send :size="20" /></div>
                            <h3 class="mt-5 font-semibold text-slate-950">DICOM Storage</h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                                Synthetisches Secondary-Capture-Testobjekt kontrolliert per C-STORE senden.
                            </p>
                            <button
                                type="button"
                                :disabled="!canRunStorage || !selectedNode.supports_store || storageForm.processing"
                                class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                                @click="openStorageTest"
                            >
                                <Send :size="16" />Testobjekt senden
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
                            class="flex min-h-[220px] flex-col rounded-2xl border border-violet-200 bg-white p-5 shadow-sm"
                        >
                            <div class="self-start rounded-xl bg-violet-50 p-2.5 text-violet-700">
                                <FlaskConical :size="20" />
                            </div>
                            <h3 class="mt-5 font-semibold text-slate-950">Modality Worklist</h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                                C-FIND-Abfrage mit Datum, Modalität, Station AE und Patientenkriterien.
                            </p>
                            <button
                                type="button"
                                :disabled="
                                    !canRunWorklist || !selectedNode.supports_worklist || worklistForm.processing
                                "
                                class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                                @click="openWorklistTest"
                            >
                                <FlaskConical :size="16" />Worklist abfragen
                            </button>
                        </article>

                        <article
                            class="flex min-h-[220px] flex-col rounded-2xl border border-amber-200 bg-white p-5 shadow-sm"
                        >
                            <div class="self-start rounded-xl bg-amber-50 p-2.5 text-amber-700">
                                <Database :size="20" />
                            </div>
                            <h3 class="mt-5 font-semibold text-slate-950">PACS Query</h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                                Study-Root-C-FIND nach Patient, Accession Number oder Study UID.
                            </p>
                            <button
                                type="button"
                                :disabled="!canRunPacsQuery || !selectedNode.supports_query || pacsForm.processing"
                                class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                                @click="openPacsQuery"
                            >
                                <Database :size="16" />PACS abfragen
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
                                    {{ testTypeLabel(displayedDiagnosticResult.testType) }} ·
                                    {{ displayedDiagnosticResult.durationMilliseconds }} ms
                                </p>
                            </div>

                            <div
                                v-if="capabilityMatrix.length"
                                class="overflow-x-auto rounded-xl border border-slate-200"
                            >
                                <table class="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-3 py-3 text-left font-semibold text-slate-700">SOP Class</th>
                                            <th
                                                v-for="(syntax, key) in capabilityTransferSyntaxes"
                                                :key="key"
                                                class="min-w-28 px-3 py-3 text-left font-semibold text-slate-700"
                                            >
                                                {{ syntax.label }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <tr v-for="(sop, sopKey) in capabilitySopClasses" :key="sopKey">
                                            <th class="px-3 py-3 text-left font-medium text-slate-800">
                                                {{ sop.label }}
                                            </th>
                                            <td
                                                v-for="(_, syntaxKey) in capabilityTransferSyntaxes"
                                                :key="syntaxKey"
                                                class="px-3 py-3"
                                            >
                                                <span
                                                    class="rounded-full px-2 py-1 font-semibold"
                                                    :class="
                                                        capabilityStatus(String(sopKey), String(syntaxKey)) ===
                                                        'accepted'
                                                            ? 'bg-emerald-50 text-emerald-700'
                                                            : capabilityStatus(String(sopKey), String(syntaxKey)) ===
                                                                'not_tested'
                                                              ? 'bg-slate-100 text-slate-500'
                                                              : 'bg-rose-50 text-rose-700'
                                                    "
                                                    >{{ capabilityStatus(String(sopKey), String(syntaxKey)) }}</span
                                                >
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="border-t border-slate-200 bg-teal-50 px-3 py-2 text-xs text-teal-800">
                                    Prüfart: ausschließlich Presentation-Context-Negotiation; kein Objekt wurde
                                    gespeichert.
                                </p>
                                <div class="border-t border-slate-200 p-3 text-right">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
                                        @click="exportCapabilityMatrix"
                                    >
                                        <Download :size="14" />Matrix als CSV
                                    </button>
                                </div>
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
                            <div
                                v-if="worklistResults.length"
                                class="overflow-x-auto rounded-xl border border-slate-200"
                            >
                                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                    <thead class="bg-slate-50 text-slate-500 uppercase">
                                        <tr>
                                            <th class="px-3 py-2">Patient</th>
                                            <th class="px-3 py-2">Patient-ID</th>
                                            <th class="px-3 py-2">Accession</th>
                                            <th class="px-3 py-2">Modalität</th>
                                            <th class="px-3 py-2">Station AE</th>
                                            <th class="px-3 py-2">Datum / Zeit</th>
                                            <th class="px-3 py-2">Beschreibung</th>
                                            <th class="px-3 py-2">Requested Procedure</th>
                                            <th class="px-3 py-2">SPS ID</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr
                                            v-for="(item, index) in worklistResults"
                                            :key="`${item.scheduledProcedureStepId}-${index}`"
                                        >
                                            <td class="px-3 py-2 font-semibold">{{ item.patientName || '—' }}</td>
                                            <td class="px-3 py-2">{{ item.patientId || '—' }}</td>
                                            <td class="px-3 py-2">{{ item.accessionNumber || '—' }}</td>
                                            <td class="px-3 py-2">{{ item.modality || '—' }}</td>
                                            <td class="px-3 py-2">{{ item.scheduledStationAeTitle || '—' }}</td>
                                            <td class="px-3 py-2">
                                                {{ item.scheduledDate || '—' }} {{ item.scheduledTime || '' }}
                                            </td>
                                            <td class="px-3 py-2">{{ item.scheduledDescription || '—' }}</td>
                                            <td class="px-3 py-2">{{ item.requestedProcedureId || '—' }}</td>
                                            <td class="px-3 py-2">{{ item.scheduledProcedureStepId || '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
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
                                    <td class="px-4 py-3">{{ testTypeLabel(run.test_type) }}</td>
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
                        :disabled="!canExport"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-40"
                        @click="exportRun(selectedHistoryRun, 'json')"
                    >
                        <Download :size="16" />JSON
                    </button>
                    <button
                        type="button"
                        :disabled="!canExport"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-40"
                        @click="exportRun(selectedHistoryRun, 'csv')"
                    >
                        <Download :size="16" />CSV
                    </button>
                </div>
            </aside>
        </div>

        <div v-if="worklistDialogOpen && selectedNode" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                class="absolute inset-0 bg-slate-950/40"
                aria-label="Worklist-Dialog schließen"
                @click="worklistDialogOpen = false"
            />
            <aside class="absolute inset-y-0 right-0 w-full max-w-2xl overflow-y-auto bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-violet-600 uppercase">Modality Worklist</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ selectedNode.name }}</h2>
                        <p class="mt-1 font-mono text-sm text-slate-500">
                            {{ selectedNode.ae_title }} · {{ selectedNode.host }}:{{ selectedNode.port }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                        @click="worklistDialogOpen = false"
                    >
                        <X :size="20" />
                    </button>
                </div>
                <form class="mt-6 space-y-5" @submit.prevent="runWorklistTest">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-sm font-medium text-slate-700"
                            >Calling AE Title<input
                                v-model="worklistForm.calling_ae_title"
                                maxlength="16"
                                required
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm" /></label
                        ><label class="text-sm font-medium text-slate-700"
                            >Called AE Title<input
                                v-model="worklistForm.called_ae_title"
                                maxlength="16"
                                required
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm" /></label
                        ><label class="text-sm font-medium text-slate-700"
                            >Station AE<input
                                v-model="worklistForm.scheduled_station_ae_title"
                                maxlength="16"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm" /></label
                        ><label class="text-sm font-medium text-slate-700"
                            >Modalität<input
                                v-model="worklistForm.modality"
                                maxlength="16"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label
                        ><label class="text-sm font-medium text-slate-700"
                            >Untersuchungsdatum<input
                                v-model="worklistForm.examination_date"
                                type="date"
                                required
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label
                        ><label class="text-sm font-medium text-slate-700"
                            >Bis einschließlich<input
                                v-model="worklistForm.examination_date_to"
                                type="date"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label
                        ><label class="text-sm font-medium text-slate-700"
                            >Patientenname<input
                                v-model="worklistForm.patient_name"
                                maxlength="128"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label
                        ><label class="text-sm font-medium text-slate-700"
                            >Patient-ID<input
                                v-model="worklistForm.patient_id"
                                maxlength="64"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label
                        ><label class="text-sm font-medium text-slate-700 sm:col-span-2"
                            >Accession Number<input
                                v-model="worklistForm.accession_number"
                                maxlength="64"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        /></label>
                    </div>
                    <div
                        v-if="Object.keys(worklistForm.errors).length"
                        class="rounded-xl bg-red-50 p-3 text-sm text-red-700"
                    >
                        <p v-for="message in worklistForm.errors" :key="message">{{ message }}</p>
                    </div>
                    <p class="text-xs leading-5 text-slate-500">
                        Die Abfrage wird ausschließlich im Backend gegen diesen registrierten Knoten ausgeführt.
                        Temporäre Filter verändern den Knoten nicht.
                    </p>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600"
                            @click="resetWorklistFilters"
                        >
                            Filter zurücksetzen</button
                        ><button
                            type="submit"
                            :disabled="worklistForm.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                        >
                            <LoaderCircle v-if="worklistForm.processing" :size="16" class="animate-spin" /><FlaskConical
                                v-else
                                :size="16"
                            />{{ worklistForm.processing ? 'Abfrage läuft' : 'C-FIND starten' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>

        <div v-if="pacsDialogOpen && selectedNode" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                class="absolute inset-0 bg-slate-950/40"
                aria-label="PACS-Dialog schließen"
                @click="pacsDialogOpen = false"
            />
            <aside class="absolute inset-y-0 right-0 w-full max-w-2xl overflow-y-auto bg-white p-6 shadow-2xl">
                <div class="flex justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-amber-600 uppercase">PACS Study Query</p>
                        <h2 class="mt-2 text-xl font-semibold">{{ selectedNode.name }}</h2>
                    </div>
                    <button type="button" @click="pacsDialogOpen = false"><X :size="20" /></button>
                </div>
                <form class="mt-6 space-y-5" @submit.prevent="runPacsQuery">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label
                            v-for="field in [
                                { key: 'calling_ae_title', label: 'Calling AE Title' },
                                { key: 'called_ae_title', label: 'Called AE Title' },
                                { key: 'patient_name', label: 'Patientenname' },
                                { key: 'patient_id', label: 'Patient-ID' },
                                { key: 'accession_number', label: 'Accession Number' },
                                { key: 'study_instance_uid', label: 'Study Instance UID' },
                                { key: 'modality', label: 'Modalität' },
                                { key: 'study_description', label: 'Study Description' },
                            ]"
                            :key="field.key"
                            class="text-sm font-medium text-slate-700"
                            >{{ field.label
                            }}<input
                                v-model="pacsForm[field.key as keyof typeof pacsForm]"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label
                        ><label class="text-sm font-medium"
                            >Study Date<input
                                v-model="pacsForm.study_date"
                                type="date"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label
                        ><label class="text-sm font-medium"
                            >Bis einschließlich<input
                                v-model="pacsForm.study_date_to"
                                type="date"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                        /></label>
                    </div>
                    <div
                        v-if="Object.keys(pacsForm.errors).length"
                        class="rounded-xl bg-red-50 p-3 text-sm text-red-700"
                    >
                        <p v-for="message in pacsForm.errors" :key="message">{{ message }}</p>
                    </div>
                    <p class="text-xs text-slate-500">
                        Nur Study-Root C-FIND. C-MOVE und C-GET werden nicht ausgeführt.
                    </p>
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="pacsForm.processing"
                            class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                        >
                            {{ pacsForm.processing ? 'Abfrage läuft' : 'C-FIND starten' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>

        <div v-if="profileDialogOpen" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                class="absolute inset-0 bg-slate-950/40"
                aria-label="Profil-Dialog schließen"
                @click="profileDialogOpen = false"
            />
            <aside class="absolute inset-y-0 right-0 w-full max-w-xl overflow-y-auto bg-white p-6 shadow-2xl">
                <div class="flex justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-blue-600 uppercase">Testprofil</p>
                        <h2 class="mt-2 text-xl font-semibold">
                            {{ editingProfileId ? 'Profil bearbeiten' : 'Profil anlegen' }}
                        </h2>
                    </div>
                    <button type="button" @click="profileDialogOpen = false"><X :size="20" /></button>
                </div>
                <form class="mt-6 space-y-4" @submit.prevent="saveProfile">
                    <label class="block text-sm font-medium"
                        >Name<input
                            v-model="profileForm.name"
                            required
                            maxlength="160"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label
                    ><label class="block text-sm font-medium"
                        >Beschreibung<textarea
                            v-model="profileForm.description"
                            rows="3"
                            maxlength="2000"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                        />
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-sm font-medium"
                            >Testtyp<select
                                v-model="profileForm.test_type"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                            >
                                <option value="network">Netzwerk</option>
                                <option value="dicom_echo">C-ECHO</option>
                                <option value="worklist">Worklist</option>
                                <option value="pacs_query">PACS Query</option>
                            </select></label
                        ><label class="text-sm font-medium"
                            >DICOM-Knoten<select
                                v-model="profileForm.dicom_node_public_id"
                                required
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                            >
                                <option v-for="node in nodes" :key="node.public_id" :value="node.public_id">
                                    {{ node.name }}
                                </option>
                            </select></label
                        ><label class="text-sm font-medium"
                            >Calling AE Title<input
                                v-model="profileForm.calling_ae_title"
                                maxlength="16"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono" /></label
                        ><label class="text-sm font-medium"
                            >Timeout (Sekunden)<input
                                v-model.number="profileForm.timeout_seconds"
                                type="number"
                                min="1"
                                max="60"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                        /></label>
                    </div>
                    <label class="flex items-center gap-2 text-sm"
                        ><input v-model="profileForm.enabled" type="checkbox" class="rounded border-slate-300" />Profil
                        aktiviert</label
                    >
                    <p class="text-xs leading-5 text-slate-500">
                        Bei neuen Worklist- und PACS-Profilen werden die aktuell im jeweiligen Testformular gesetzten
                        Filter übernommen.
                    </p>
                    <div
                        v-if="Object.keys(profileForm.errors).length"
                        class="rounded-xl bg-red-50 p-3 text-sm text-red-700"
                    >
                        <p v-for="message in profileForm.errors" :key="message">{{ message }}</p>
                    </div>
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                        >
                            {{ profileForm.processing ? 'Speichern …' : 'Profil speichern' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>

        <div v-if="fileAnalysisDialogOpen" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                type="button"
                class="absolute inset-0 bg-slate-950/40"
                aria-label="Dateianalyse schließen"
                @click="fileAnalysisDialogOpen = false"
            />
            <section class="absolute inset-y-0 right-0 w-full max-w-2xl overflow-y-auto bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-blue-600 uppercase">Serverseitige Analyse</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">DICOM-Datei analysieren</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            Maximal 20 MiB. Die Datei wird nicht versendet und nach der Analyse gelöscht.
                        </p>
                    </div>
                    <button type="button" @click="fileAnalysisDialogOpen = false"><X :size="20" /></button>
                </div>
                <form class="mt-6 space-y-4" @submit.prevent="analyzeDicomFile">
                    <label
                        class="block rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-600"
                        ><FileSearch :size="28" class="mx-auto mb-3 text-blue-600" /><input
                            type="file"
                            class="block w-full text-sm"
                            @change="selectDicomFile"
                    /></label>
                    <p v-if="fileAnalysisForm.errors.dicom_file" class="text-sm text-red-600">
                        {{ fileAnalysisForm.errors.dicom_file }}
                    </p>
                    <button
                        type="submit"
                        :disabled="fileAnalysisForm.processing || !fileAnalysisForm.dicom_file"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                    >
                        <LoaderCircle v-if="fileAnalysisForm.processing" :size="16" class="animate-spin" /><FileSearch
                            v-else
                            :size="16"
                        />Datei analysieren
                    </button>
                </form>
                <div v-if="fileAnalysisResult" ref="fileAnalysisResultElement" class="mt-8 scroll-mt-6 space-y-4">
                    <div
                        class="rounded-xl p-4"
                        :class="
                            fileAnalysisResult.successful
                                ? 'bg-emerald-50 text-emerald-800'
                                : 'bg-rose-50 text-rose-800'
                        "
                    >
                        <p class="font-semibold">
                            {{
                                fileAnalysisResult.successful
                                    ? 'DICOM-Datei erfolgreich analysiert'
                                    : 'Analyse fehlgeschlagen'
                            }}
                        </p>
                        <p v-for="error in fileAnalysisResult.errors" :key="error" class="mt-1 text-sm">
                            {{ error }}
                        </p>
                    </div>
                    <dl v-if="Object.keys(fileAnalysisResult.summary).length" class="grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="(value, key) in fileAnalysisResult.summary"
                            :key="key"
                            class="rounded-xl border border-slate-200 p-3"
                        >
                            <dt class="text-xs text-slate-500">{{ key }}</dt>
                            <dd class="mt-1 text-sm font-medium break-all text-slate-900">{{ value ?? '—' }}</dd>
                        </div>
                    </dl>
                    <div
                        v-if="fileAnalysisResult.warnings.length"
                        class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800"
                    >
                        <p v-for="warning in fileAnalysisResult.warnings" :key="warning">{{ warning }}</p>
                    </div>
                    <details v-if="fileAnalysisResult.dump" class="rounded-xl border border-slate-200 p-4">
                        <summary class="cursor-pointer text-sm font-semibold text-slate-700">
                            Vollständiger bereinigter DICOM-Dump
                        </summary>
                        <pre class="mt-3 max-h-96 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100">{{
                            fileAnalysisResult.dump
                        }}</pre>
                    </details>
                </div>
            </section>
        </div>

        <div v-if="capabilityDialogOpen && selectedNode" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                type="button"
                class="absolute inset-0 bg-slate-950/40"
                aria-label="Capability-Dialog schließen"
                @click="capabilityDialogOpen = false"
            />
            <section class="absolute inset-y-0 right-0 w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-teal-600 uppercase">DICOM Capability-Matrix</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">Presentation Contexts prüfen</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            Die Association wird nach der Aushandlung beendet. Es wird kein C-STORE ausgeführt.
                        </p>
                    </div>
                    <button type="button" @click="capabilityDialogOpen = false"><X :size="20" /></button>
                </div>
                <form class="mt-6 space-y-4" @submit.prevent="runCapabilityTest">
                    <label class="block text-sm font-medium text-slate-700"
                        >Calling AE Title<input
                            v-model="capabilityForm.calling_ae_title"
                            maxlength="16"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                    /></label>
                    <label class="block text-sm font-medium text-slate-700"
                        >Called AE Title<input
                            v-model="capabilityForm.called_ae_title"
                            maxlength="16"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                    /></label>
                    <button
                        type="submit"
                        :disabled="capabilityForm.processing"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                    >
                        <LoaderCircle
                            v-if="capabilityForm.processing"
                            :size="16"
                            class="animate-spin"
                        /><TableProperties v-else :size="16" />Capability-Matrix prüfen
                    </button>
                </form>
            </section>
        </div>

        <div v-if="storageDialogOpen && selectedNode" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                class="absolute inset-0 bg-slate-950/50"
                aria-label="Storage-Dialog schließen"
                @click="storageDialogOpen = false"
            />
            <aside class="absolute inset-y-0 right-0 w-full max-w-xl overflow-y-auto bg-white p-6 shadow-2xl">
                <div class="flex justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-rose-600 uppercase">Kontrollierter DICOM Storage-Test</p>
                        <h2 class="mt-2 text-xl font-semibold">{{ selectedNode.name }}</h2>
                    </div>
                    <button type="button" @click="storageDialogOpen = false"><X :size="20" /></button>
                </div>
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    <p class="font-semibold">Achtung: Im Zielsystem kann ein dauerhaftes Objekt entstehen.</p>
                    <p class="mt-1">
                        Es werden ausschließlich synthetische Daten verwendet: PatientName DICOMNODE^TEST und eine
                        temporäre TEST-Patient-ID.
                    </p>
                </div>
                <form class="mt-6 space-y-4" @submit.prevent="runStorageTest">
                    <label class="block text-sm font-medium"
                        >Calling AE Title<input
                            v-model="storageForm.calling_ae_title"
                            maxlength="16"
                            required
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono" /></label
                    ><label class="block text-sm font-medium"
                        >Called AE Title<input
                            v-model="storageForm.called_ae_title"
                            maxlength="16"
                            required
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono" /></label
                    ><label class="flex items-start gap-3 rounded-xl border border-rose-200 p-4 text-sm"
                        ><input
                            v-model="storageForm.confirmed"
                            type="checkbox"
                            class="mt-1 rounded border-slate-300"
                        /><span
                            >Ich bestätige ausdrücklich, dass ein gekennzeichnetes synthetisches Testobjekt im
                            ausgewählten Zielsystem gespeichert werden darf.</span
                        ></label
                    >
                    <div
                        v-if="Object.keys(storageForm.errors).length"
                        class="rounded-xl bg-red-50 p-3 text-sm text-red-700"
                    >
                        <p v-for="message in storageForm.errors" :key="message">{{ message }}</p>
                    </div>
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="!storageForm.confirmed || storageForm.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                        >
                            <LoaderCircle v-if="storageForm.processing" :size="16" class="animate-spin" /><Send
                                v-else
                                :size="16"
                            />{{ storageForm.processing ? 'C-STORE läuft' : 'Verbindlich senden' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </AppLayout>
</template>
