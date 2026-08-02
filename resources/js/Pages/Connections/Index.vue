<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FilterX, Search } from '@lucide/vue';
import { reactive } from 'vue';
import DicomConnectionManager, {
    type DicomConnection,
    type DicomNodeOption,
} from '../../Components/registry/dicom/DicomConnectionManager.vue';
import Pagination from '../../Components/Pagination.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type Option = { public_id: string; name: string };
type SystemOption = Option & { site: Option | null; department: Option | null };
type Filters = {
    search?: string;
    source_system?: string;
    target_system?: string;
    service?: string;
    site?: string;
    department?: string;
    status?: string;
    port?: number | string;
    ae_title?: string;
    sort?: string;
    direction?: string;
};
const props = defineProps<{
    connections: {
        data: DicomConnection[];
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    nodes: DicomNodeOption[];
    systems: SystemOption[];
    filters: Filters;
    services: string[];
    canManage: boolean;
}>();
const form = reactive<Filters>({ ...props.filters });
const sites = Array.from(
    new Map(props.systems.filter((item) => item.site).map((item) => [item.site!.public_id, item.site!])).values(),
);
const departments = Array.from(
    new Map(
        props.systems.filter((item) => item.department).map((item) => [item.department!.public_id, item.department!]),
    ).values(),
);
const applyFilters = (): void => router.get('/connections', form, { preserveState: true, replace: true });
const resetFilters = (): void => {
    Object.keys(form).forEach((key) => delete form[key as keyof Filters]);
    applyFilters();
};
const serviceLabels: Record<string, string> = {
    echo: 'C-ECHO',
    store: 'C-STORE',
    worklist: 'Worklist',
    query: 'Query',
    move: 'C-MOVE',
    get: 'C-GET',
};
const hasFilters = Object.entries(props.filters).some(
    ([key, value]) => !['sort', 'direction'].includes(key) && value !== undefined && value !== null && value !== '',
);
</script>

<template>
    <Head title="Verbindungen" />
    <AppLayout>
        <div class="space-y-6">
            <header>
                <p class="text-xs font-semibold tracking-widest text-blue-600 uppercase">Kommunikation</p>
                <h1 class="mt-2 text-3xl font-semibold text-slate-950">Verbindungen</h1>
                <p class="mt-2 text-sm text-slate-500">
                    DICOM-Kommunikationspfade zwischen Systemen, Knoten und Diensten zentral dokumentieren und
                    durchsuchen.
                </p>
            </header>
            <form class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="applyFilters">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <label class="relative xl:col-span-2"
                        ><Search :size="17" class="absolute top-3 left-3 text-slate-400" /><input
                            v-model="form.search"
                            class="w-full rounded-xl border border-slate-300 py-2.5 pr-3 pl-10 text-sm"
                            placeholder="Name, System, AE Title, Host oder Port"
                    /></label>
                    <select v-model="form.source_system" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Alle Quellsysteme</option>
                        <option v-for="item in systems" :key="item.public_id" :value="item.public_id">
                            {{ item.name }}
                        </option>
                    </select>
                    <select v-model="form.target_system" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Alle Zielsysteme</option>
                        <option v-for="item in systems" :key="item.public_id" :value="item.public_id">
                            {{ item.name }}
                        </option>
                    </select>
                    <select v-model="form.service" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Alle Dienste</option>
                        <option v-for="item in services" :key="item" :value="item">{{ serviceLabels[item] }}</option>
                    </select>
                    <select v-model="form.site" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Alle Standorte</option>
                        <option v-for="item in sites" :key="item.public_id" :value="item.public_id">
                            {{ item.name }}
                        </option>
                    </select>
                    <select v-model="form.department" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Alle Abteilungen</option>
                        <option v-for="item in departments" :key="item.public_id" :value="item.public_id">
                            {{ item.name }}
                        </option>
                    </select>
                    <select v-model="form.status" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Alle Status</option>
                        <option value="active">Aktiv</option>
                        <option value="planned">Geplant</option>
                        <option value="maintenance">Wartung</option>
                        <option value="inactive">Inaktiv</option>
                        <option value="archived">Archiviert</option>
                    </select>
                    <input
                        v-model="form.port"
                        type="number"
                        min="1"
                        max="65535"
                        class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        placeholder="Zielport"
                    />
                    <input
                        v-model="form.ae_title"
                        maxlength="16"
                        class="rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                        placeholder="AE Title"
                    />
                    <select v-model="form.sort" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="name">Sortierung: Name</option>
                        <option value="source_system">Quellsystem</option>
                        <option value="target_system">Zielsystem</option>
                        <option value="service">Dienst</option>
                        <option value="port">Zielport</option>
                        <option value="status">Status</option>
                        <option value="last_test">Letzter Test</option>
                    </select>
                    <select v-model="form.direction" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="asc">Aufsteigend</option>
                        <option value="desc">Absteigend</option>
                    </select>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold"
                        @click="resetFilters"
                    >
                        <FilterX :size="16" />Zurücksetzen</button
                    ><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">
                        Filter anwenden
                    </button>
                </div>
            </form>
            <DicomConnectionManager
                :connections="connections.data"
                :node-options="nodes"
                :can-manage="canManage"
                :empty-title="hasFilters ? 'Keine Verbindungen entsprechen den aktuellen Filtern.' : undefined"
                :empty-text="hasFilters ? 'Passe die Filter an oder setze sie vollständig zurück.' : undefined"
            />
            <div v-if="connections.total > 0" class="flex items-center justify-between">
                <p class="text-sm text-slate-500">{{ connections.total }} Verbindungen</p>
                <Pagination :links="connections.links" />
            </div>
        </div>
    </AppLayout>
</template>
