<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    Archive,
    CircleCheck,
    CircleX,
    LoaderCircle,
    LockKeyhole,
    Pencil,
    Plus,
    Radio,
    RefreshCw,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

export type DicomNode = {
    public_id: string;
    name: string;
    ae_title: string;
    host: string;
    port: number;
    role: 'scu' | 'scp' | 'both';
    status: 'active' | 'planned' | 'maintenance' | 'inactive';
    tls_enabled: boolean;
    supports_echo: boolean;
    supports_store: boolean;
    supports_query: boolean;
    supports_retrieve: boolean;
    supports_storage_commitment: boolean;
    supports_mpps: boolean;
    supports_worklist: boolean;
    description: string | null;
    notes: string | null;
    last_verified_at: string | null;
    last_verification_status: string | null;
    last_verification_duration_ms: number | null;
    last_verification_message: string | null;
};

const props = defineProps<{
    systemPublicId: string;
    nodes: DicomNode[];
    canManage: boolean;
}>();

const createOpen = ref(false);
const editOpen = ref(false);
const archiveOpen = ref(false);
const archiveProcessing = ref(false);
const verifyProcessingId = ref<string | null>(null);
const selectedNode = ref<DicomNode | null>(null);

const roleLabels: Record<DicomNode['role'], string> = {
    scu: 'SCU',
    scp: 'SCP',
    both: 'SCU & SCP',
};

const statusLabels: Record<DicomNode['status'], string> = {
    active: 'Aktiv',
    planned: 'Geplant',
    maintenance: 'Wartung',
    inactive: 'Inaktiv',
};

const serviceDefinitions: Array<{
    key:
        | 'supports_echo'
        | 'supports_store'
        | 'supports_query'
        | 'supports_retrieve'
        | 'supports_storage_commitment'
        | 'supports_mpps'
        | 'supports_worklist';
    label: string;
}> = [
    { key: 'supports_echo', label: 'C-ECHO' },
    { key: 'supports_store', label: 'C-STORE' },
    { key: 'supports_query', label: 'Query' },
    { key: 'supports_retrieve', label: 'Retrieve' },
    {
        key: 'supports_storage_commitment',
        label: 'Storage Commitment',
    },
    { key: 'supports_mpps', label: 'MPPS' },
    { key: 'supports_worklist', label: 'Worklist' },
];

const activeServices = (node: DicomNode): string[] =>
    serviceDefinitions.filter((service) => node[service.key]).map((service) => service.label);

const emptyForm = () => ({
    name: '',
    ae_title: '',
    host: '',
    port: 11112,
    role: 'both' as DicomNode['role'],
    status: 'active' as DicomNode['status'],
    tls_enabled: false,
    supports_echo: true,
    supports_store: false,
    supports_query: false,
    supports_retrieve: false,
    supports_storage_commitment: false,
    supports_mpps: false,
    supports_worklist: false,
    description: '',
    notes: '',
});

const createForm = useForm(emptyForm());
const editForm = useForm(emptyForm());

const hasNodes = computed(() => props.nodes.length > 0);

const closeCreate = (): void => {
    if (createForm.processing) {
        return;
    }

    createOpen.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreate = (): void => {
    createForm.post(`/systems/${props.systemPublicId}/dicom-nodes`, {
        preserveScroll: true,
        onSuccess: closeCreate,
    });
};

const openEdit = (node: DicomNode): void => {
    selectedNode.value = node;
    editOpen.value = true;
};

watch(
    () => [editOpen.value, selectedNode.value] as const,
    ([open, node]) => {
        if (!open || node === null) {
            return;
        }

        editForm.name = node.name;
        editForm.ae_title = node.ae_title;
        editForm.host = node.host;
        editForm.port = node.port;
        editForm.role = node.role;
        editForm.status = node.status;
        editForm.tls_enabled = node.tls_enabled;
        editForm.supports_echo = node.supports_echo;
        editForm.supports_store = node.supports_store;
        editForm.supports_query = node.supports_query;
        editForm.supports_retrieve = node.supports_retrieve;
        editForm.supports_storage_commitment = node.supports_storage_commitment;
        editForm.supports_mpps = node.supports_mpps;
        editForm.supports_worklist = node.supports_worklist;
        editForm.description = node.description ?? '';
        editForm.notes = node.notes ?? '';
        editForm.clearErrors();
    },
);

const closeEdit = (): void => {
    if (editForm.processing) {
        return;
    }

    editOpen.value = false;
    selectedNode.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = (): void => {
    if (selectedNode.value === null) {
        return;
    }

    editForm.put(`/dicom-nodes/${selectedNode.value.public_id}`, {
        preserveScroll: true,
        onSuccess: closeEdit,
    });
};

const openArchive = (node: DicomNode): void => {
    selectedNode.value = node;
    archiveOpen.value = true;
};

const closeArchive = (): void => {
    if (archiveProcessing.value) {
        return;
    }

    archiveOpen.value = false;
    selectedNode.value = null;
};

const confirmArchive = (): void => {
    if (selectedNode.value === null) {
        return;
    }

    archiveProcessing.value = true;

    router.post(
        `/dicom-nodes/${selectedNode.value.public_id}/archive`,
        {},
        {
            preserveScroll: true,
            onSuccess: closeArchive,
            onFinish: () => {
                archiveProcessing.value = false;
            },
        },
    );
};

const verifyNode = (node: DicomNode): void => {
    if (!node.supports_echo || verifyProcessingId.value !== null) {
        return;
    }

    verifyProcessingId.value = node.public_id;

    router.post(
        `/dicom-nodes/${node.public_id}/verify`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                verifyProcessingId.value = null;
            },
        },
    );
};

const formatVerificationDate = (value: string | null): string => {
    if (value === null) {
        return 'Noch nicht geprüft';
    }

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
};

const verificationLabel = (status: string | null): string => {
    const labels: Record<string, string> = {
        success: 'Erfolgreich',
        timeout: 'Timeout',
        unreachable: 'Nicht erreichbar',
        failed: 'Fehlgeschlagen',
        error: 'Fehler',
    };

    return status === null ? 'Ungeprüft' : (labels[status] ?? status);
};

const verificationSuccessful = (node: DicomNode): boolean => node.last_verification_status === 'success';

const verificationStatusClass = (node: DicomNode): string => {
    if (node.last_verification_status === 'success') {
        return 'bg-emerald-500';
    }

    if (node.last_verification_status === null) {
        return 'bg-slate-300';
    }

    return 'bg-red-500';
};
</script>

<template>
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header
            class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="font-semibold text-slate-950">DICOM-Knoten</h2>
                <p class="mt-1 text-sm text-slate-500">AE Titles, Endpunkte, Rollen und unterstützte Dienste.</p>
            </div>

            <button
                v-if="canManage"
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                @click="createOpen = true"
            >
                <Plus :size="17" />
                DICOM-Knoten
            </button>
        </header>

        <div v-if="!hasNodes" class="px-5 py-12 text-center">
            <Radio :size="34" class="mx-auto text-slate-300" />
            <p class="mt-4 font-medium text-slate-900">Noch keine DICOM-Knoten</p>
            <p class="mt-1 text-sm text-slate-500">Erfasse den ersten DICOM-Endpunkt für dieses System.</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Knoten</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Endpunkt</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Rolle</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Dienste</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Connectivity</th>
                        <th
                            v-if="canManage"
                            class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase"
                        >
                            Aktionen
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <tr v-for="node in nodes" :key="node.public_id" class="align-top transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    :class="verificationStatusClass(node)"
                                    aria-hidden="true"
                                />
                                <p class="font-semibold text-slate-900">
                                    {{ node.name }}
                                </p>
                            </div>
                            <p class="mt-1 font-mono text-xs text-slate-500">
                                {{ node.ae_title }}
                            </p>
                        </td>

                        <td class="px-5 py-4">
                            <p class="font-mono text-sm text-slate-800">{{ node.host }}:{{ node.port }}</p>
                            <p
                                v-if="node.tls_enabled"
                                class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-emerald-700"
                            >
                                <LockKeyhole :size="13" />
                                TLS
                            </p>
                        </td>

                        <td class="px-5 py-4 text-sm text-slate-700">
                            {{ roleLabels[node.role] }}
                        </td>

                        <td class="max-w-md px-5 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="service in activeServices(node)"
                                    :key="service"
                                    class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700"
                                >
                                    {{ service }}
                                </span>

                                <span v-if="activeServices(node).length === 0" class="text-xs text-slate-400">
                                    Keine Dienste
                                </span>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <span
                                class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
                            >
                                {{ statusLabels[node.status] }}
                            </span>
                        </td>

                        <td class="px-5 py-4">
                            <div v-if="node.last_verified_at" class="flex min-w-48 items-start gap-2">
                                <CircleCheck
                                    v-if="verificationSuccessful(node)"
                                    :size="17"
                                    class="mt-0.5 shrink-0 text-emerald-600"
                                />

                                <CircleX v-else :size="17" class="mt-0.5 shrink-0 text-red-600" />

                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-medium"
                                        :class="verificationSuccessful(node) ? 'text-emerald-700' : 'text-red-700'"
                                    >
                                        {{ verificationLabel(node.last_verification_status) }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ formatVerificationDate(node.last_verified_at) }}
                                        <template v-if="node.last_verification_duration_ms !== null">
                                            ·
                                            {{ node.last_verification_duration_ms }}
                                            ms
                                        </template>
                                    </p>

                                    <p
                                        v-if="node.last_verification_message"
                                        class="mt-1 max-w-64 truncate text-xs text-slate-400"
                                        :title="node.last_verification_message"
                                    >
                                        {{ node.last_verification_message }}
                                    </p>
                                </div>
                            </div>

                            <span v-else class="text-xs text-slate-400"> Noch nicht geprüft </span>
                        </td>

                        <td v-if="canManage" class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    :disabled="!node.supports_echo || verifyProcessingId !== null"
                                    :aria-label="`${node.name} per C-ECHO prüfen`"
                                    :title="
                                        node.supports_echo
                                            ? 'C-ECHO testen'
                                            : 'C-ECHO ist für diesen Knoten deaktiviert'
                                    "
                                    class="rounded-lg p-2 text-slate-500 transition hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-35"
                                    @click="verifyNode(node)"
                                >
                                    <LoaderCircle
                                        v-if="verifyProcessingId === node.public_id"
                                        :size="17"
                                        class="animate-spin"
                                    />
                                    <RefreshCw v-else :size="17" />
                                </button>

                                <button
                                    type="button"
                                    :aria-label="`${node.name} bearbeiten`"
                                    class="rounded-lg p-2 text-slate-500 transition hover:bg-blue-50 hover:text-blue-700"
                                    @click="openEdit(node)"
                                >
                                    <Pencil :size="17" />
                                </button>

                                <button
                                    type="button"
                                    :aria-label="`${node.name} archivieren`"
                                    class="rounded-lg p-2 text-slate-500 transition hover:bg-amber-50 hover:text-amber-700"
                                    @click="openArchive(node)"
                                >
                                    <Archive :size="17" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <Teleport to="body">
        <div v-if="createOpen" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                type="button"
                aria-label="Formular schließen"
                class="absolute inset-0 bg-slate-950/40"
                @click="closeCreate"
            />

            <aside class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">DICOM</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-950">DICOM-Knoten anlegen</h2>
                    </div>

                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="closeCreate">
                        <X :size="20" />
                    </button>
                </header>

                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submitCreate">
                    <div class="flex-1 space-y-7 overflow-y-auto px-6 py-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="sm:col-span-2">
                                <span class="text-sm font-medium text-slate-700"> Name * </span>
                                <input
                                    v-model="createForm.name"
                                    type="text"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                />
                                <span v-if="createForm.errors.name" class="mt-1 block text-xs text-red-600">
                                    {{ createForm.errors.name }}
                                </span>
                            </label>

                            <label>
                                <span class="text-sm font-medium text-slate-700"> AE Title * </span>
                                <input
                                    v-model="createForm.ae_title"
                                    type="text"
                                    maxlength="16"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm uppercase"
                                />
                                <span v-if="createForm.errors.ae_title" class="mt-1 block text-xs text-red-600">
                                    {{ createForm.errors.ae_title }}
                                </span>
                            </label>

                            <label>
                                <span class="text-sm font-medium text-slate-700"> Host/IP * </span>
                                <input
                                    v-model="createForm.host"
                                    type="text"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                                />
                                <span v-if="createForm.errors.host" class="mt-1 block text-xs text-red-600">
                                    {{ createForm.errors.host }}
                                </span>
                            </label>

                            <label>
                                <span class="text-sm font-medium text-slate-700"> Port * </span>
                                <input
                                    v-model.number="createForm.port"
                                    type="number"
                                    min="1"
                                    max="65535"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                />
                                <span v-if="createForm.errors.port" class="mt-1 block text-xs text-red-600">
                                    {{ createForm.errors.port }}
                                </span>
                            </label>

                            <label>
                                <span class="text-sm font-medium text-slate-700"> Rolle * </span>
                                <select
                                    v-model="createForm.role"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option value="scu">SCU</option>
                                    <option value="scp">SCP</option>
                                    <option value="both">SCU & SCP</option>
                                </select>
                            </label>

                            <label>
                                <span class="text-sm font-medium text-slate-700"> Status * </span>
                                <select
                                    v-model="createForm.status"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option value="active">Aktiv</option>
                                    <option value="planned">Geplant</option>
                                    <option value="maintenance">Wartung</option>
                                    <option value="inactive">Inaktiv</option>
                                </select>
                            </label>

                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 sm:self-end">
                                <input v-model="createForm.tls_enabled" type="checkbox" class="h-4 w-4" />
                                <span class="text-sm font-medium text-slate-700"> TLS aktiviert </span>
                            </label>
                        </div>

                        <section>
                            <h3 class="text-sm font-semibold text-slate-950">Unterstützte Dienste</h3>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <label
                                    v-for="service in serviceDefinitions"
                                    :key="service.key"
                                    class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                                >
                                    <input v-model="createForm[service.key]" type="checkbox" class="h-4 w-4" />
                                    <span class="text-sm text-slate-700">
                                        {{ service.label }}
                                    </span>
                                </label>
                            </div>
                        </section>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700"> Beschreibung </span>
                            <textarea
                                v-model="createForm.description"
                                rows="3"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            />
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700"> Interne Notizen </span>
                            <textarea
                                v-model="createForm.notes"
                                rows="3"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            />
                        </label>
                    </div>

                    <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium"
                            @click="closeCreate"
                        >
                            Abbrechen
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                        >
                            {{ createForm.processing ? 'Wird gespeichert …' : 'Knoten anlegen' }}
                        </button>
                    </footer>
                </form>
            </aside>
        </div>

        <div v-if="editOpen && selectedNode" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                type="button"
                aria-label="Formular schließen"
                class="absolute inset-0 bg-slate-950/40"
                @click="closeEdit"
            />

            <aside class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">DICOM</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-950">DICOM-Knoten bearbeiten</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ selectedNode.name }}
                        </p>
                    </div>

                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="closeEdit">
                        <X :size="20" />
                    </button>
                </header>

                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submitEdit">
                    <div class="flex-1 space-y-7 overflow-y-auto px-6 py-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="sm:col-span-2">
                                <span class="text-sm font-medium text-slate-700"> Name * </span>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                />
                            </label>

                            <label>
                                <span class="text-sm font-medium text-slate-700"> AE Title * </span>
                                <input
                                    v-model="editForm.ae_title"
                                    type="text"
                                    maxlength="16"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm uppercase"
                                />
                                <span v-if="editForm.errors.ae_title" class="mt-1 block text-xs text-red-600">
                                    {{ editForm.errors.ae_title }}
                                </span>
                            </label>

                            <label>
                                <span class="text-sm font-medium text-slate-700"> Host/IP * </span>
                                <input
                                    v-model="editForm.host"
                                    type="text"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                                />
                            </label>

                            <label>
                                <span class="text-sm font-medium text-slate-700"> Port * </span>
                                <input
                                    v-model.number="editForm.port"
                                    type="number"
                                    min="1"
                                    max="65535"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                />
                            </label>

                            <label>
                                <span class="text-sm font-medium text-slate-700"> Rolle * </span>
                                <select
                                    v-model="editForm.role"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option value="scu">SCU</option>
                                    <option value="scp">SCP</option>
                                    <option value="both">SCU & SCP</option>
                                </select>
                            </label>

                            <label>
                                <span class="text-sm font-medium text-slate-700"> Status * </span>
                                <select
                                    v-model="editForm.status"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option value="active">Aktiv</option>
                                    <option value="planned">Geplant</option>
                                    <option value="maintenance">Wartung</option>
                                    <option value="inactive">Inaktiv</option>
                                </select>
                            </label>

                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 sm:self-end">
                                <input v-model="editForm.tls_enabled" type="checkbox" class="h-4 w-4" />
                                <span class="text-sm font-medium text-slate-700"> TLS aktiviert </span>
                            </label>
                        </div>

                        <section>
                            <h3 class="text-sm font-semibold text-slate-950">Unterstützte Dienste</h3>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <label
                                    v-for="service in serviceDefinitions"
                                    :key="service.key"
                                    class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                                >
                                    <input v-model="editForm[service.key]" type="checkbox" class="h-4 w-4" />
                                    <span class="text-sm text-slate-700">
                                        {{ service.label }}
                                    </span>
                                </label>
                            </div>
                        </section>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700"> Beschreibung </span>
                            <textarea
                                v-model="editForm.description"
                                rows="3"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            />
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700"> Interne Notizen </span>
                            <textarea
                                v-model="editForm.notes"
                                rows="3"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            />
                        </label>
                    </div>

                    <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium"
                            @click="closeEdit"
                        >
                            Abbrechen
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                        >
                            {{ editForm.processing ? 'Wird gespeichert …' : 'Änderungen speichern' }}
                        </button>
                    </footer>
                </form>
            </aside>
        </div>

        <div
            v-if="archiveOpen && selectedNode"
            class="fixed inset-0 z-[60] grid place-items-center px-4"
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                aria-label="Dialog schließen"
                class="absolute inset-0 bg-slate-950/45"
                @click="closeArchive"
            />

            <section class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <header class="border-b border-slate-200 px-6 py-5">
                    <h2 class="font-semibold text-slate-950">DICOM-Knoten archivieren</h2>
                    <p class="mt-1 text-sm text-slate-500">Der Datensatz bleibt für Audit und Historie erhalten.</p>
                </header>

                <div class="px-6 py-5 text-sm text-slate-700">
                    Soll
                    <strong class="font-semibold text-slate-950">
                        {{ selectedNode.name }}
                    </strong>
                    wirklich archiviert werden?
                </div>

                <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button
                        type="button"
                        :disabled="archiveProcessing"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium"
                        @click="closeArchive"
                    >
                        Abbrechen
                    </button>
                    <button
                        type="button"
                        :disabled="archiveProcessing"
                        class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                        @click="confirmArchive"
                    >
                        {{ archiveProcessing ? 'Wird archiviert …' : 'Archivieren' }}
                    </button>
                </footer>
            </section>
        </div>
    </Teleport>
</template>
