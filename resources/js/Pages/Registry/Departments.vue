<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Archive,
    CircleAlert,
    CircleCheck,
    Clock3,
    FilterX,
    Hospital,
    Pencil,
    Plus,
    Search,
    Server,
    UsersRound,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Pagination from '../../Components/Pagination.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type Site = {
    id: number;
    public_id: string;
    organization_id: number;
    name: string;
    organization: { name: string } | null;
};
type DepartmentItem = {
    id: number;
    public_id: string;
    site_id: number;
    site: Site;
    name: string;
    code: string | null;
    specialty: string | null;
    description: string | null;
    systems_count: number;
    archived_at: string | null;
};
type SystemItem = {
    id: number;
    public_id: string;
    name: string;
    system_type: string;
    status: string;
    hostname: string | null;
    ip_address: string | null;
    dicom_nodes_count: number;
    failed_dicom_nodes_count: number;
};
type SelectedDepartment = DepartmentItem & {
    created_at: string;
    updated_at: string;
    dicom_nodes_count: number;
    failed_dicom_nodes_count: number;
    systems: SystemItem[];
};
type TabId = 'overview' | 'general' | 'systems' | 'history';

const props = defineProps<{
    items: {
        data: DepartmentItem[];
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    selected: SelectedDepartment | null;
    sites: Site[];
    filters: { search: string; archived: boolean };
    canManage: boolean;
    canUpdateSelected: boolean;
    canArchiveSelected: boolean;
}>();

const search = ref(props.filters.search);
const archived = ref(props.filters.archived);
const activeTab = ref<TabId>('overview');
const panelOpen = ref(false);
const archiveOpen = ref(false);
const editing = ref(false);
const archiveProcessing = ref(false);

const form = useForm({
    site_id: props.sites[0]?.id ?? 0,
    name: '',
    code: '',
    specialty: '',
    description: '',
});

const tabs: Array<{ id: TabId; label: string }> = [
    { id: 'overview', label: 'Übersicht' },
    { id: 'general', label: 'Allgemein' },
    { id: 'systems', label: 'Systeme' },
    { id: 'history', label: 'Historie' },
];

const hasItems = computed(() => props.items.data.length > 0);
const activeSystems = computed(
    () => props.selected?.systems.filter((system) => system.status === 'active').length ?? 0,
);
const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
const systemTypeLabel = (value: string): string =>
    ({
        pacs: 'PACS',
        ris: 'RIS',
        kis: 'KIS',
        modality: 'Modalität',
        viewer: 'Viewer',
        integration_engine: 'Integrationsserver',
        server: 'Server',
        database: 'Datenbank',
        storage: 'Storage',
        network: 'Netzwerkgerät',
        other: 'Sonstiges',
    })[value] ?? value;
const systemStatusLabel = (value: string): string =>
    ({ active: 'Aktiv', planned: 'Geplant', maintenance: 'Wartung', inactive: 'Inaktiv', retired: 'Außer Betrieb' })[
        value
    ] ?? value;
const systemStatusClass = (value: string): string =>
    ({
        active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        planned: 'bg-blue-50 text-blue-700 ring-blue-200',
        maintenance: 'bg-amber-50 text-amber-700 ring-amber-200',
        inactive: 'bg-slate-100 text-slate-600 ring-slate-200',
        retired: 'bg-slate-200 text-slate-700 ring-slate-300',
    })[value] ?? 'bg-slate-100 text-slate-600 ring-slate-200';

watch(
    () => props.selected?.public_id,
    () => {
        activeTab.value = 'overview';
    },
);

const navigate = (selected?: string): void => {
    router.get(
        '/departments',
        {
            search: search.value || undefined,
            archived: archived.value || undefined,
            selected,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};
const applyFilters = (): void => navigate(props.selected?.public_id);
const resetFilters = (): void => {
    search.value = '';
    archived.value = false;
    router.get('/departments', {}, { preserveState: true, replace: true });
};
const openCreate = (): void => {
    editing.value = false;
    form.reset();
    form.site_id = props.sites[0]?.id ?? 0;
    form.clearErrors();
    panelOpen.value = true;
};
const openEdit = (): void => {
    if (props.selected === null) return;
    editing.value = true;
    Object.assign(form, {
        site_id: props.selected.site_id,
        name: props.selected.name,
        code: props.selected.code ?? '',
        specialty: props.selected.specialty ?? '',
        description: props.selected.description ?? '',
    });
    form.clearErrors();
    panelOpen.value = true;
};
const closePanel = (): void => {
    if (form.processing) return;
    panelOpen.value = false;
    editing.value = false;
    form.reset();
    form.clearErrors();
};
const submit = (): void => {
    if (editing.value && props.selected !== null) {
        form.put(`/departments/${props.selected.public_id}`, { preserveScroll: true, onSuccess: closePanel });
        return;
    }
    form.post('/departments', { preserveScroll: true, onSuccess: closePanel });
};
const archiveSelected = (): void => {
    if (props.selected === null) return;
    archiveProcessing.value = true;
    router.post(
        `/departments/${props.selected.public_id}/archive`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                archiveOpen.value = false;
            },
            onFinish: () => {
                archiveProcessing.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Abteilungen" />
    <AppLayout>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">Registry</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-950">Abteilungen</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Fachliche Einheiten und zugeordnete Systeme im gemeinsamen Workspace verwalten.
                </p>
            </div>
            <button
                v-if="canManage"
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                @click="openCreate"
            >
                <Plus :size="17" /> Neue Abteilung
            </button>
        </div>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <form class="flex flex-col gap-3 lg:flex-row" @submit.prevent="applyFilters">
                <div class="relative min-w-0 flex-1">
                    <Search :size="18" class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Name, Code oder Fachbereich durchsuchen"
                        class="w-full rounded-xl border border-slate-300 py-2.5 pr-3 pl-10 text-sm"
                    />
                </div>
                <label
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700"
                >
                    <input v-model="archived" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600" />
                    Archivierte anzeigen
                </label>
                <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">
                    Filtern
                </button>
                <button
                    type="button"
                    :disabled="search === '' && !archived"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-40"
                    @click="resetFilters"
                >
                    <FilterX :size="16" /> Zurücksetzen
                </button>
            </form>
        </section>

        <div v-if="hasItems" class="mt-6 grid min-h-[720px] gap-5 xl:grid-cols-[330px_minmax(0,1fr)]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 shadow-sm">
                <header class="border-b border-slate-200 bg-white px-4 py-4">
                    <h2 class="font-semibold text-slate-950">Abteilungen</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ items.total }} Einträge gefunden</p>
                </header>
                <div class="max-h-[650px] space-y-2 overflow-y-auto p-3">
                    <button
                        v-for="department in items.data"
                        :key="department.public_id"
                        type="button"
                        :class="[
                            'w-full rounded-xl border px-3 py-3 text-left transition',
                            selected?.public_id === department.public_id
                                ? 'border-blue-200 bg-blue-50 ring-1 ring-blue-100'
                                : 'border-transparent bg-white hover:border-slate-200',
                        ]"
                        @click="navigate(department.public_id)"
                    >
                        <div class="flex items-start gap-3">
                            <div class="grid h-9 w-9 place-items-center rounded-xl bg-violet-50 text-violet-700">
                                <UsersRound :size="17" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="truncate text-sm font-semibold text-slate-950">{{ department.name }}</p>
                                    <span
                                        class="mt-1 h-2 w-2 rounded-full"
                                        :class="department.archived_at ? 'bg-slate-400' : 'bg-emerald-500'"
                                    />
                                </div>
                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                    {{ department.site.organization?.name }} · {{ department.site.name }}
                                </p>
                                <div class="mt-2 flex gap-3 text-[11px] text-slate-500">
                                    <span>{{ department.specialty || department.code || 'Keine Zusatzangabe' }}</span
                                    ><span>{{ department.systems_count }} Systeme</span>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
                <div class="border-t border-slate-200 bg-white p-3"><Pagination :links="items.links" /></div>
            </section>

            <section v-if="selected" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700"
                                    >Abteilung</span
                                >
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"
                                    >{{ selected.site.organization?.name }} · {{ selected.site.name }}</span
                                >
                            </div>
                            <h2 class="mt-3 text-2xl font-semibold text-slate-950">{{ selected.name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ selected.specialty || selected.code || 'Keine Zusatzangabe' }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                v-if="canUpdateSelected && !selected.archived_at"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                                @click="openEdit"
                            >
                                <Pencil :size="16" /> Bearbeiten
                            </button>
                            <button
                                v-if="canArchiveSelected && !selected.archived_at"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700"
                                @click="archiveOpen = true"
                            >
                                <Archive :size="16" /> Archivieren
                            </button>
                        </div>
                    </div>
                </header>

                <div class="grid gap-3 border-b border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <p class="text-xs text-slate-500">Systeme</p>
                        <p class="mt-1 text-xl font-semibold">{{ selected.systems_count }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <p class="text-xs text-slate-500">DICOM-Knoten</p>
                        <p class="mt-1 text-xl font-semibold">{{ selected.dicom_nodes_count }}</p>
                    </div>
                    <div
                        class="rounded-xl border px-4 py-3"
                        :class="
                            selected.failed_dicom_nodes_count > 0
                                ? 'border-red-200 bg-red-50'
                                : 'border-emerald-200 bg-emerald-50'
                        "
                    >
                        <p class="text-xs">Auffällige Knoten</p>
                        <p class="mt-1 text-xl font-semibold">{{ selected.failed_dicom_nodes_count }}</p>
                    </div>
                </div>

                <nav class="flex gap-7 overflow-x-auto border-b border-slate-200 px-6">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        :class="[
                            'shrink-0 border-b-2 py-4 text-sm font-semibold',
                            activeTab === tab.id
                                ? 'border-blue-600 text-blue-700'
                                : 'border-transparent text-slate-500',
                        ]"
                        @click="activeTab = tab.id"
                    >
                        {{ tab.label }}
                    </button>
                </nav>

                <div v-if="activeTab === 'overview'" class="grid gap-5 p-5 lg:p-7 xl:grid-cols-[1.2fr_0.8fr]">
                    <article class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-semibold">Abteilungsprofil</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            {{ selected.description || 'Noch keine Beschreibung hinterlegt.' }}
                        </p>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="flex gap-3">
                                <div class="rounded-xl bg-cyan-50 p-2 text-cyan-700"><Server :size="17" /></div>
                                <div>
                                    <p class="text-xs text-slate-500">Systeme</p>
                                    <p class="text-sm font-semibold">{{ activeSystems }} aktiv</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="rounded-xl bg-blue-50 p-2 text-blue-700"><Hospital :size="17" /></div>
                                <div>
                                    <p class="text-xs text-slate-500">Standort</p>
                                    <p class="text-sm font-semibold">{{ selected.site.name }}</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div
                                    class="rounded-xl p-2"
                                    :class="
                                        selected.failed_dicom_nodes_count
                                            ? 'bg-red-50 text-red-700'
                                            : 'bg-emerald-50 text-emerald-700'
                                    "
                                >
                                    <CircleAlert v-if="selected.failed_dicom_nodes_count" :size="17" /><CircleCheck
                                        v-else
                                        :size="17"
                                    />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">DICOM-Zustand</p>
                                    <p class="text-sm font-semibold">
                                        {{
                                            selected.failed_dicom_nodes_count
                                                ? `${selected.failed_dicom_nodes_count} auffällig`
                                                : 'Keine Auffälligkeiten'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </article>
                    <article class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-semibold">Metadaten</h3>
                        <dl class="mt-4 space-y-4 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500">Public ID</dt>
                                <dd class="mt-1 font-mono text-xs break-all">{{ selected.public_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Erstellt</dt>
                                <dd class="mt-1">{{ formatDate(selected.created_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Geändert</dt>
                                <dd class="mt-1">{{ formatDate(selected.updated_at) }}</dd>
                            </div>
                        </dl>
                    </article>
                </div>

                <div v-else-if="activeTab === 'general'" class="p-5 lg:p-7">
                    <article class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-semibold">Allgemeine Informationen</h3>
                        <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold text-slate-500 uppercase">Organisation</dt>
                                <dd class="mt-1 text-sm">{{ selected.site.organization?.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-slate-500 uppercase">Standort</dt>
                                <dd class="mt-1 text-sm">{{ selected.site.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-slate-500 uppercase">Code</dt>
                                <dd class="mt-1 text-sm">{{ selected.code || 'Nicht hinterlegt' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-slate-500 uppercase">Fachbereich</dt>
                                <dd class="mt-1 text-sm">{{ selected.specialty || 'Nicht hinterlegt' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-semibold text-slate-500 uppercase">Beschreibung</dt>
                                <dd class="mt-2 text-sm whitespace-pre-line">
                                    {{ selected.description || 'Nicht hinterlegt' }}
                                </dd>
                            </div>
                        </dl>
                    </article>
                </div>

                <div v-else-if="activeTab === 'systems'" class="space-y-4 p-5 lg:p-7">
                    <div class="flex items-end justify-between">
                        <div>
                            <h3 class="font-semibold">Systeme</h3>
                            <p class="mt-1 text-sm text-slate-500">Systeme dieser Abteilung.</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{
                            selected.systems.length
                        }}</span>
                    </div>
                    <div
                        v-if="selected.systems.length === 0"
                        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center"
                    >
                        <Server :size="30" class="mx-auto text-slate-400" />
                        <p class="mt-4 font-semibold">Keine Systeme vorhanden</p>
                    </div>
                    <div v-else class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
                        <article
                            v-for="system in selected.systems"
                            :key="system.public_id"
                            class="rounded-2xl border border-slate-200 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold">{{ system.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ systemTypeLabel(system.system_type) }}</p>
                                </div>
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                                        systemStatusClass(system.status),
                                    ]"
                                    >{{ systemStatusLabel(system.status) }}</span
                                >
                            </div>
                            <p class="mt-4 font-mono text-xs text-slate-500">
                                {{ system.ip_address || system.hostname || 'Kein Endpunkt' }}
                            </p>
                            <div class="mt-3 text-xs text-slate-500">{{ system.dicom_nodes_count }} DICOM-Knoten</div>
                            <Link
                                :href="`/systems?selected=${system.public_id}`"
                                class="mt-4 inline-flex text-sm font-semibold text-blue-700"
                                >Öffnen</Link
                            >
                        </article>
                    </div>
                </div>

                <div v-else class="p-5 lg:p-7">
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
                        <Clock3 :size="30" class="mx-auto text-slate-400" />
                        <p class="mt-4 font-semibold">Historie wird vorbereitet</p>
                    </div>
                </div>
            </section>
        </div>

        <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <UsersRound :size="34" class="mx-auto text-slate-300" />
            <p class="mt-4 font-semibold">Keine Abteilungen gefunden</p>
        </div>

        <div v-if="panelOpen" class="fixed inset-0 z-50">
            <button type="button" class="absolute inset-0 bg-slate-950/30" @click="closePanel" />
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">Abteilung</p>
                        <h2 class="mt-2 text-xl font-semibold">
                            {{ editing ? 'Abteilung bearbeiten' : 'Neue Abteilung' }}
                        </h2>
                    </div>
                    <button type="button" class="rounded-xl p-2 hover:bg-slate-100" @click="closePanel">
                        <X :size="20" />
                    </button>
                </header>
                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                        <div>
                            <label class="text-sm font-semibold">Standort *</label
                            ><select
                                v-model="form.site_id"
                                required
                                class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                            >
                                <option v-for="site in sites" :key="site.id" :value="site.id">
                                    {{ site.organization?.name }} · {{ site.name }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold">Name *</label
                                ><input
                                    v-model="form.name"
                                    required
                                    class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                                />
                            </div>
                            <div>
                                <label class="text-sm font-semibold">Code</label
                                ><input
                                    v-model="form.code"
                                    class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-semibold">Fachbereich</label
                            ><input
                                v-model="form.specialty"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                            />
                        </div>
                        <div>
                            <label class="text-sm font-semibold">Beschreibung</label
                            ><textarea
                                v-model="form.description"
                                rows="6"
                                class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                            />
                        </div>
                        <p v-if="Object.keys(form.errors).length" class="text-sm text-red-700">
                            Bitte prüfe die markierten Eingaben.
                        </p>
                    </div>
                    <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="rounded-xl border px-4 py-2.5 text-sm font-semibold"
                            @click="closePanel"
                        >
                            Abbrechen</button
                        ><button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            {{ editing ? 'Änderungen speichern' : 'Abteilung anlegen' }}
                        </button>
                    </footer>
                </form>
            </aside>
        </div>

        <div v-if="archiveOpen && selected" class="fixed inset-0 z-50 grid place-items-center p-4">
            <button type="button" class="absolute inset-0 bg-slate-950/35" @click="archiveOpen = false" />
            <section class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="font-semibold">Abteilung archivieren?</h2>
                <p class="mt-2 text-sm text-slate-600">„{{ selected.name }}“ wird archiviert.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-xl border px-4 py-2.5 text-sm font-semibold"
                        @click="archiveOpen = false"
                    >
                        Abbrechen</button
                    ><button
                        type="button"
                        :disabled="archiveProcessing"
                        class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                        @click="archiveSelected"
                    >
                        Archivieren
                    </button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
