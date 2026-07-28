<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Archive, ArrowRight, Pencil, Plus, Route, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

type SystemOption = { id: number; name: string };

export type DicomNodeOption = {
    id: number;
    public_id: string;
    system_id: number;
    name: string;
    ae_title: string;
    host: string;
    port: number;
    system: SystemOption;
};

export type DicomConnection = {
    public_id: string;
    source_dicom_node_id: number;
    target_dicom_node_id: number;
    destination_dicom_node_id: number | null;
    name: string;
    service: 'echo' | 'store' | 'worklist' | 'query' | 'move' | 'get';
    status: 'active' | 'planned' | 'maintenance' | 'inactive';
    calling_ae_title: string | null;
    called_ae_title: string | null;
    port_override: number | null;
    tls_enabled: boolean;
    test_enabled: boolean;
    description: string | null;
    notes: string | null;
    source_node: DicomNodeOption;
    target_node: DicomNodeOption;
    destination_node: DicomNodeOption | null;
};

const props = defineProps<{
    connections: DicomConnection[];
    nodeOptions: DicomNodeOption[];
    canManage: boolean;
}>();

const createOpen = ref(false);
const editOpen = ref(false);
const archiveOpen = ref(false);
const archiveProcessing = ref(false);
const selected = ref<DicomConnection | null>(null);

const serviceLabels: Record<DicomConnection['service'], string> = {
    echo: 'C-ECHO',
    store: 'C-STORE',
    worklist: 'Worklist',
    query: 'Query',
    move: 'C-MOVE',
    get: 'C-GET',
};

const statusLabels: Record<DicomConnection['status'], string> = {
    active: 'Aktiv',
    planned: 'Geplant',
    maintenance: 'Wartung',
    inactive: 'Inaktiv',
};

const emptyForm = () => ({
    source_dicom_node_id: null as number | null,
    target_dicom_node_id: null as number | null,
    destination_dicom_node_id: null as number | null,
    name: '',
    service: 'echo' as DicomConnection['service'],
    status: 'active' as DicomConnection['status'],
    calling_ae_title: '',
    called_ae_title: '',
    port_override: null as number | null,
    tls_enabled: false,
    test_enabled: true,
    description: '',
    notes: '',
});

const createForm = useForm(emptyForm());
const editForm = useForm(emptyForm());

const nodeLabel = (node: DicomNodeOption): string => `${node.system.name} · ${node.name} · ${node.ae_title}`;

const createSourceOptions = computed(() =>
    props.nodeOptions.filter((node) => node.id !== createForm.target_dicom_node_id),
);
const createTargetOptions = computed(() =>
    props.nodeOptions.filter((node) => node.id !== createForm.source_dicom_node_id),
);
const editSourceOptions = computed(() => props.nodeOptions.filter((node) => node.id !== editForm.target_dicom_node_id));
const editTargetOptions = computed(() => props.nodeOptions.filter((node) => node.id !== editForm.source_dicom_node_id));

watch(
    () => createForm.service,
    (value) => {
        if (value !== 'move') createForm.destination_dicom_node_id = null;
    },
);
watch(
    () => editForm.service,
    (value) => {
        if (value !== 'move') editForm.destination_dicom_node_id = null;
    },
);

const closeCreate = (): void => {
    if (createForm.processing) return;
    createOpen.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreate = (): void => {
    createForm.post('/dicom-connections', {
        preserveScroll: true,
        onSuccess: closeCreate,
    });
};

const openEdit = (connection: DicomConnection): void => {
    selected.value = connection;
    editForm.source_dicom_node_id = connection.source_dicom_node_id;
    editForm.target_dicom_node_id = connection.target_dicom_node_id;
    editForm.destination_dicom_node_id = connection.destination_dicom_node_id;
    editForm.name = connection.name;
    editForm.service = connection.service;
    editForm.status = connection.status;
    editForm.calling_ae_title = connection.calling_ae_title ?? '';
    editForm.called_ae_title = connection.called_ae_title ?? '';
    editForm.port_override = connection.port_override;
    editForm.tls_enabled = connection.tls_enabled;
    editForm.test_enabled = connection.test_enabled;
    editForm.description = connection.description ?? '';
    editForm.notes = connection.notes ?? '';
    editForm.clearErrors();
    editOpen.value = true;
};

const closeEdit = (): void => {
    if (editForm.processing) return;
    editOpen.value = false;
    selected.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = (): void => {
    if (!selected.value) return;
    editForm.put(`/dicom-connections/${selected.value.public_id}`, {
        preserveScroll: true,
        onSuccess: closeEdit,
    });
};

const openArchive = (connection: DicomConnection): void => {
    selected.value = connection;
    archiveOpen.value = true;
};

const closeArchive = (): void => {
    if (archiveProcessing.value) return;
    archiveOpen.value = false;
    selected.value = null;
};

const confirmArchive = (): void => {
    if (!selected.value) return;
    archiveProcessing.value = true;
    router.post(
        `/dicom-connections/${selected.value.public_id}/archive`,
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

const effectiveCallingAe = (connection: DicomConnection): string =>
    connection.calling_ae_title ?? connection.source_node.ae_title;
const effectiveCalledAe = (connection: DicomConnection): string =>
    connection.called_ae_title ?? connection.target_node.ae_title;
const effectivePort = (connection: DicomConnection): number => connection.port_override ?? connection.target_node.port;
</script>

<template>
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header
            class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="font-semibold text-slate-950">DICOM-Verbindungen</h2>
                <p class="mt-1 text-sm text-slate-500">Kommunikationspfade für die spätere Network Map.</p>
            </div>
            <button
                v-if="canManage"
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                @click="createOpen = true"
            >
                <Plus :size="17" /> Verbindung
            </button>
        </header>

        <div v-if="connections.length === 0" class="px-5 py-12 text-center">
            <Route :size="34" class="mx-auto text-slate-300" />
            <p class="mt-4 font-medium text-slate-900">Noch keine DICOM-Verbindungen</p>
            <p class="mt-1 text-sm text-slate-500">Lege den ersten Kommunikationspfad an.</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Verbindung</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pfad</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Dienst</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">
                            Konfiguration
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th
                            v-if="canManage"
                            class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase"
                        >
                            Aktionen
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr
                        v-for="connection in connections"
                        :key="connection.public_id"
                        class="align-top hover:bg-slate-50"
                    >
                        <td class="px-5 py-4">
                            <p class="font-semibold text-slate-900">{{ connection.name }}</p>
                            <p v-if="connection.description" class="mt-1 max-w-xs text-xs text-slate-500">
                                {{ connection.description }}
                            </p>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex min-w-72 items-center gap-2">
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ connection.source_node.name }}</p>
                                    <p class="font-mono text-xs text-slate-500">
                                        {{ connection.source_node.ae_title }}
                                    </p>
                                </div>
                                <ArrowRight :size="18" class="shrink-0 text-slate-400" />
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ connection.target_node.name }}</p>
                                    <p class="font-mono text-xs text-slate-500">
                                        {{ connection.target_node.ae_title }}
                                    </p>
                                </div>
                            </div>
                            <p
                                v-if="connection.destination_node"
                                class="mt-2 rounded-lg bg-violet-50 px-3 py-2 text-xs text-violet-700"
                            >
                                C-MOVE-Ziel: {{ connection.destination_node.name }} ·
                                {{ connection.destination_node.ae_title }}
                            </p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">{{
                                serviceLabels[connection.service]
                            }}</span>
                        </td>
                        <td class="px-5 py-4 text-xs text-slate-600">
                            <p>
                                Calling: <span class="font-mono">{{ effectiveCallingAe(connection) }}</span>
                            </p>
                            <p class="mt-1">
                                Called: <span class="font-mono">{{ effectiveCalledAe(connection) }}</span>
                            </p>
                            <p class="mt-1">
                                Ziel:
                                <span class="font-mono"
                                    >{{ connection.target_node.host }}:{{ effectivePort(connection) }}</span
                                >
                            </p>
                            <p v-if="connection.tls_enabled" class="mt-1 font-medium text-emerald-700">TLS</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{
                                statusLabels[connection.status]
                            }}</span>
                            <p
                                class="mt-2 text-xs"
                                :class="connection.test_enabled ? 'text-emerald-700' : 'text-slate-400'"
                            >
                                {{ connection.test_enabled ? 'Tests aktiviert' : 'Tests deaktiviert' }}
                            </p>
                        </td>
                        <td v-if="canManage" class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    :aria-label="`${connection.name} bearbeiten`"
                                    class="rounded-lg p-2 text-slate-500 hover:bg-blue-50 hover:text-blue-700"
                                    @click="openEdit(connection)"
                                >
                                    <Pencil :size="17" />
                                </button>
                                <button
                                    type="button"
                                    :aria-label="`${connection.name} archivieren`"
                                    class="rounded-lg p-2 text-slate-500 hover:bg-amber-50 hover:text-amber-700"
                                    @click="openArchive(connection)"
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
                        <p class="text-xs font-semibold text-blue-600 uppercase">DICOM Network</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-950">Verbindung anlegen</h2>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="closeCreate">
                        <X :size="20" />
                    </button>
                </header>
                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submitCreate">
                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Name *</span
                            ><input
                                v-model="createForm.name"
                                type="text"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            /><span v-if="createForm.errors.name" class="mt-1 block text-xs text-red-600">{{
                                createForm.errors.name
                            }}</span></label
                        >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label
                                ><span class="text-sm font-medium text-slate-700">Quellknoten *</span
                                ><select
                                    v-model="createForm.source_dicom_node_id"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option :value="null">Bitte auswählen</option>
                                    <option v-for="node in createSourceOptions" :key="node.id" :value="node.id">
                                        {{ nodeLabel(node) }}
                                    </option></select
                                ><span
                                    v-if="createForm.errors.source_dicom_node_id"
                                    class="mt-1 block text-xs text-red-600"
                                    >{{ createForm.errors.source_dicom_node_id }}</span
                                ></label
                            >
                            <label
                                ><span class="text-sm font-medium text-slate-700">Zielknoten *</span
                                ><select
                                    v-model="createForm.target_dicom_node_id"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option :value="null">Bitte auswählen</option>
                                    <option v-for="node in createTargetOptions" :key="node.id" :value="node.id">
                                        {{ nodeLabel(node) }}
                                    </option></select
                                ><span
                                    v-if="createForm.errors.target_dicom_node_id"
                                    class="mt-1 block text-xs text-red-600"
                                    >{{ createForm.errors.target_dicom_node_id }}</span
                                ></label
                            >
                            <label
                                ><span class="text-sm font-medium text-slate-700">Dienst *</span
                                ><select
                                    v-model="createForm.service"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option value="echo">C-ECHO</option>
                                    <option value="store">C-STORE</option>
                                    <option value="worklist">Worklist</option>
                                    <option value="query">Query</option>
                                    <option value="move">C-MOVE</option>
                                    <option value="get">C-GET</option>
                                </select></label
                            >
                            <label
                                ><span class="text-sm font-medium text-slate-700">Status *</span
                                ><select
                                    v-model="createForm.status"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option value="active">Aktiv</option>
                                    <option value="planned">Geplant</option>
                                    <option value="maintenance">Wartung</option>
                                    <option value="inactive">Inaktiv</option>
                                </select></label
                            >
                        </div>
                        <label v-if="createForm.service === 'move'" class="block"
                            ><span class="text-sm font-medium text-slate-700">C-MOVE-Ziel *</span
                            ><select
                                v-model="createForm.destination_dicom_node_id"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            >
                                <option :value="null">Bitte auswählen</option>
                                <option v-for="node in nodeOptions" :key="node.id" :value="node.id">
                                    {{ nodeLabel(node) }}
                                </option></select
                            ><span
                                v-if="createForm.errors.destination_dicom_node_id"
                                class="mt-1 block text-xs text-red-600"
                                >{{ createForm.errors.destination_dicom_node_id }}</span
                            ></label
                        >
                        <div class="grid gap-4 sm:grid-cols-3">
                            <label
                                ><span class="text-sm font-medium text-slate-700">Calling AE</span
                                ><input
                                    v-model="createForm.calling_ae_title"
                                    maxlength="16"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm uppercase"
                            /></label>
                            <label
                                ><span class="text-sm font-medium text-slate-700">Called AE</span
                                ><input
                                    v-model="createForm.called_ae_title"
                                    maxlength="16"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm uppercase"
                            /></label>
                            <label
                                ><span class="text-sm font-medium text-slate-700">Port</span
                                ><input
                                    v-model.number="createForm.port_override"
                                    type="number"
                                    min="1"
                                    max="65535"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            /></label>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                                ><input v-model="createForm.tls_enabled" type="checkbox" /><span class="text-sm"
                                    >TLS aktiviert</span
                                ></label
                            >
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                                ><input v-model="createForm.test_enabled" type="checkbox" /><span class="text-sm"
                                    >Verbindungstests aktiviert</span
                                ></label
                            >
                        </div>
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Beschreibung</span
                            ><textarea
                                v-model="createForm.description"
                                rows="3"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            />
                        </label>
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Interne Notizen</span
                            ><textarea
                                v-model="createForm.notes"
                                rows="3"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            />
                        </label>
                    </div>
                    <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm"
                            @click="closeCreate"
                        >
                            Abbrechen</button
                        ><button
                            type="submit"
                            :disabled="createForm.processing"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                        >
                            {{ createForm.processing ? 'Wird gespeichert …' : 'Verbindung anlegen' }}
                        </button>
                    </footer>
                </form>
            </aside>
        </div>

        <div v-if="editOpen && selected" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                type="button"
                aria-label="Formular schließen"
                class="absolute inset-0 bg-slate-950/40"
                @click="closeEdit"
            />
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold text-blue-600 uppercase">DICOM Network</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-950">Verbindung bearbeiten</h2>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="closeEdit">
                        <X :size="20" />
                    </button>
                </header>
                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submitEdit">
                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Name *</span
                            ><input
                                v-model="editForm.name"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        /></label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label
                                ><span class="text-sm font-medium text-slate-700">Quellknoten *</span
                                ><select
                                    v-model="editForm.source_dicom_node_id"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option v-for="node in editSourceOptions" :key="node.id" :value="node.id">
                                        {{ nodeLabel(node) }}
                                    </option>
                                </select></label
                            >
                            <label
                                ><span class="text-sm font-medium text-slate-700">Zielknoten *</span
                                ><select
                                    v-model="editForm.target_dicom_node_id"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option v-for="node in editTargetOptions" :key="node.id" :value="node.id">
                                        {{ nodeLabel(node) }}
                                    </option>
                                </select></label
                            >
                            <label
                                ><span class="text-sm font-medium text-slate-700">Dienst *</span
                                ><select
                                    v-model="editForm.service"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option value="echo">C-ECHO</option>
                                    <option value="store">C-STORE</option>
                                    <option value="worklist">Worklist</option>
                                    <option value="query">Query</option>
                                    <option value="move">C-MOVE</option>
                                    <option value="get">C-GET</option>
                                </select></label
                            >
                            <label
                                ><span class="text-sm font-medium text-slate-700">Status *</span
                                ><select
                                    v-model="editForm.status"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                    <option value="active">Aktiv</option>
                                    <option value="planned">Geplant</option>
                                    <option value="maintenance">Wartung</option>
                                    <option value="inactive">Inaktiv</option>
                                </select></label
                            >
                        </div>
                        <label v-if="editForm.service === 'move'" class="block"
                            ><span class="text-sm font-medium text-slate-700">C-MOVE-Ziel *</span
                            ><select
                                v-model="editForm.destination_dicom_node_id"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            >
                                <option :value="null">Bitte auswählen</option>
                                <option v-for="node in nodeOptions" :key="node.id" :value="node.id">
                                    {{ nodeLabel(node) }}
                                </option>
                            </select></label
                        >
                        <div class="grid gap-4 sm:grid-cols-3">
                            <label
                                ><span class="text-sm font-medium text-slate-700">Calling AE</span
                                ><input
                                    v-model="editForm.calling_ae_title"
                                    maxlength="16"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm uppercase"
                            /></label>
                            <label
                                ><span class="text-sm font-medium text-slate-700">Called AE</span
                                ><input
                                    v-model="editForm.called_ae_title"
                                    maxlength="16"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm uppercase"
                            /></label>
                            <label
                                ><span class="text-sm font-medium text-slate-700">Port</span
                                ><input
                                    v-model.number="editForm.port_override"
                                    type="number"
                                    min="1"
                                    max="65535"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            /></label>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                                ><input v-model="editForm.tls_enabled" type="checkbox" /><span class="text-sm"
                                    >TLS aktiviert</span
                                ></label
                            >
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                                ><input v-model="editForm.test_enabled" type="checkbox" /><span class="text-sm"
                                    >Verbindungstests aktiviert</span
                                ></label
                            >
                        </div>
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Beschreibung</span
                            ><textarea
                                v-model="editForm.description"
                                rows="3"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            />
                        </label>
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Interne Notizen</span
                            ><textarea
                                v-model="editForm.notes"
                                rows="3"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            />
                        </label>
                    </div>
                    <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm"
                            @click="closeEdit"
                        >
                            Abbrechen</button
                        ><button
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
            v-if="archiveOpen && selected"
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
                    <h2 class="font-semibold text-slate-950">DICOM-Verbindung archivieren</h2>
                    <p class="mt-1 text-sm text-slate-500">Der Pfad bleibt für Audit und Historie erhalten.</p>
                </header>
                <div class="px-6 py-5 text-sm text-slate-700">
                    Soll <strong>{{ selected.name }}</strong> wirklich archiviert werden?
                </div>
                <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button
                        type="button"
                        :disabled="archiveProcessing"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm"
                        @click="closeArchive"
                    >
                        Abbrechen</button
                    ><button
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
