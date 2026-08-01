<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, CirclePlus, Download, FileText, History, Pencil, RotateCcw, Search, TestTube2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import Pagination from '../../Components/Pagination.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type Option = { public_id: string; name: string };
type Event = {
    event_id: string;
    event_type: string;
    subject_type: string;
    subject_public_id: string | null;
    actor_name: string;
    occurred_at: string;
    metadata: Record<string, unknown>;
    entity: { label: string; url: string | null };
};
type Page<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    from: number | null;
    to: number | null;
};
const props = defineProps<{
    events: Page<Event>;
    filters: Record<string, string>;
    stats: Record<string, number>;
    eventTypes: string[];
    users: Option[];
    organizations: Option[];
    sites: Option[];
    departments: Option[];
    systems: Option[];
}>();
const form = ref({
    history_search: props.filters.history_search ?? '',
    history_from: props.filters.history_from ?? '',
    history_to: props.filters.history_to ?? '',
    history_type: props.filters.history_type ?? '',
    history_user: props.filters.history_user ?? '',
    object_type: props.filters.object_type ?? '',
    organization: props.filters.organization ?? '',
    site: props.filters.site ?? '',
    department: props.filters.department ?? '',
    system: props.filters.system ?? '',
    only_errors: props.filters.only_errors === '1',
    only_tests: props.filters.only_tests === '1',
    sort: props.filters.sort ?? 'desc',
});
const selected = ref<Event | null>(null);
const objectTypes = [
    'Organization',
    'Site',
    'Department',
    'System',
    'DicomNode',
    'DicomConnection',
    'RegistryDocument',
    'RegistryDocumentVersion',
    'DiagnosticTestRun',
    'User',
];
const cards = computed(() => [
    { label: 'Gesamtaktivitäten', value: props.stats.total, detail: 'Systemweit erfasst', icon: History },
    { label: 'Änderungen', value: props.stats.changes, detail: 'Bearbeitete Objekte', icon: Pencil },
    { label: 'Erstellungen', value: props.stats.created, detail: 'Neue Objekte', icon: CirclePlus },
    { label: 'Archivierungen', value: props.stats.archived, detail: 'Nachvollziehbar erhalten', icon: Archive },
    { label: 'Dokumente', value: props.stats.documents, detail: 'Dokumentereignisse', icon: FileText },
    { label: 'Testausführungen', value: props.stats.tests, detail: 'Diagnoseereignisse', icon: TestTube2 },
]);
const params = () =>
    Object.fromEntries(
        Object.entries(form.value)
            .filter(([, value]) => value !== '' && value !== false)
            .map(([key, value]) => [key, typeof value === 'boolean' ? '1' : value]),
    );
const apply = () => router.get('/audit', params(), { preserveState: true, replace: true });
const reset = () => {
    Object.assign(form.value, {
        history_search: '',
        history_from: '',
        history_to: '',
        history_type: '',
        history_user: '',
        object_type: '',
        organization: '',
        site: '',
        department: '',
        system: '',
        only_errors: false,
        only_tests: false,
        sort: 'desc',
    });
    apply();
};
const action = (value: string) =>
    ({
        created: 'Erstellt',
        updated: 'Geändert',
        archived: 'Archiviert',
        restored: 'Wiederhergestellt',
        deleted: 'Gelöscht',
        completed: 'Test',
        uploaded: 'Dokument',
        login: 'Login',
        logout: 'Logout',
        imported: 'Import',
        exported: 'Export',
    })[value.split('.').at(-1) ?? ''] ?? value.replaceAll('.', ' · ');
const badge = (value: string) =>
    value.includes('failed')
        ? 'bg-red-50 text-red-700'
        : value.includes('created') || value.includes('success')
          ? 'bg-emerald-50 text-emerald-700'
          : value.includes('archived') || value.includes('deleted')
            ? 'bg-amber-50 text-amber-700'
            : value.includes('diagnostic')
              ? 'bg-violet-50 text-violet-700'
              : 'bg-blue-50 text-blue-700';
const date = (value: string) =>
    new Intl.DateTimeFormat('de-DE', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));
const detail = (event: Event) =>
    String(
        event.metadata.details ??
            (Array.isArray(event.metadata.changed_fields) ? event.metadata.changed_fields.join(', ') : '—'),
    );
const exportUrl = computed(
    () => `/audit/export/csv?${new URLSearchParams(params() as Record<string, string>).toString()}`,
);
</script>

<template>
    <Head title="Audit" />
    <AppLayout>
        <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">Administration</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-950">Audit</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Systemweite Historie sämtlicher Änderungen, Tests und administrativer Aktivitäten.
                </p>
            </div>
            <div class="relative w-full lg:w-80">
                <Search :size="17" class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" /><input
                    v-model="form.history_search"
                    type="search"
                    placeholder="Audit durchsuchen"
                    class="w-full rounded-xl border border-slate-300 py-2.5 pr-3 pl-10 text-sm focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                    @keyup.enter="apply"
                />
            </div>
        </header>

        <section class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <article
                v-for="card in cards"
                :key="card.label"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-blue-700">
                        <component :is="card.icon" :size="18" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">{{ card.label }}</p>
                        <p class="text-xl font-semibold text-slate-950">{{ card.value }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ card.detail }}</p>
            </article>
        </section>

        <section class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-5">
                <input
                    v-model="form.history_from"
                    type="date"
                    aria-label="Zeitraum von"
                    class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                /><input
                    v-model="form.history_to"
                    type="date"
                    aria-label="Zeitraum bis"
                    class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                />
                <select v-model="form.object_type" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Objekttypen</option>
                    <option v-for="item in objectTypes" :key="item">{{ item }}</option>
                </select>
                <select v-model="form.history_type" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Aktionen</option>
                    <option v-for="item in eventTypes" :key="item" :value="item">{{ action(item) }}</option>
                </select>
                <select v-model="form.history_user" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Benutzer</option>
                    <option v-for="item in users" :key="item.public_id" :value="item.public_id">{{ item.name }}</option>
                </select>
                <select v-model="form.organization" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Organisationen</option>
                    <option v-for="item in organizations" :key="item.public_id" :value="item.public_id">
                        {{ item.name }}
                    </option>
                </select>
                <select v-model="form.site" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Standorte</option>
                    <option v-for="item in sites" :key="item.public_id" :value="item.public_id">{{ item.name }}</option>
                </select>
                <select v-model="form.department" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Abteilungen</option>
                    <option v-for="item in departments" :key="item.public_id" :value="item.public_id">
                        {{ item.name }}
                    </option>
                </select>
                <select v-model="form.system" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Systeme</option>
                    <option v-for="item in systems" :key="item.public_id" :value="item.public_id">
                        {{ item.name }}
                    </option>
                </select>
                <div class="flex gap-2">
                    <button
                        class="flex-1 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
                        @click="apply"
                    >
                        Filtern</button
                    ><button
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-700"
                        @click="reset"
                    >
                        <RotateCcw :size="16" /> Zurücksetzen
                    </button>
                </div>
            </div>
            <div class="flex flex-wrap gap-5 border-t border-slate-100 px-4 py-3 text-sm text-slate-700">
                <label class="flex items-center gap-2"
                    ><input v-model="form.only_errors" type="checkbox" /> Nur Fehler</label
                ><label class="flex items-center gap-2"
                    ><input v-model="form.only_tests" type="checkbox" /> Nur Testereignisse</label
                ><select
                    v-model="form.sort"
                    class="ml-auto rounded-lg border border-slate-300 px-2 py-1 text-xs"
                    @change="apply"
                >
                    <option value="desc">Neueste zuerst</option>
                    <option value="asc">Älteste zuerst</option>
                </select>
            </div>
        </section>

        <section class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm text-slate-500">{{ events.total }} Aktivitäten</p>
                <a
                    :href="exportUrl"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700"
                    ><Download :size="16" /> Exportieren (CSV)</a
                >
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs tracking-wide text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Zeitpunkt</th>
                            <th class="px-4 py-3">Benutzer</th>
                            <th class="px-4 py-3">Aktion</th>
                            <th class="px-4 py-3">Objekttyp</th>
                            <th class="px-4 py-3">Objekt</th>
                            <th class="hidden px-4 py-3 lg:table-cell">Details</th>
                            <th class="hidden px-4 py-3 xl:table-cell">Standort</th>
                            <th class="hidden px-4 py-3 2xl:table-cell">IP-Adresse</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="event in events.data"
                            :key="event.event_id"
                            class="cursor-pointer hover:bg-slate-50"
                            @click="selected = event"
                        >
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ date(event.occurred_at) }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ event.actor_name }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="badge(event.event_type)"
                                    >{{ action(event.event_type) }}</span
                                >
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ event.entity.label }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ event.metadata.name ?? event.subject_public_id ?? '—' }}
                            </td>
                            <td class="hidden max-w-xs truncate px-4 py-3 text-slate-600 lg:table-cell">
                                {{ detail(event) }}
                            </td>
                            <td class="hidden px-4 py-3 text-slate-600 xl:table-cell">
                                {{ event.metadata.site_name ?? event.metadata.department_name ?? '—' }}
                            </td>
                            <td class="hidden px-4 py-3 font-mono text-xs text-slate-600 2xl:table-cell">
                                {{ event.metadata.ip_address ?? '—' }}
                            </td>
                        </tr>
                        <tr v-if="events.data.length === 0">
                            <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                                Keine Audit-Einträge für diese Auswahl.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="events.links.length > 3" class="border-t border-slate-200 p-4">
                <Pagination :links="events.links" />
            </div>
        </section>

        <Teleport to="body"
            ><div
                v-if="selected"
                class="fixed inset-0 z-50 flex justify-end bg-slate-950/30"
                @click.self="selected = null"
            >
                <aside class="h-full w-full max-w-xl overflow-y-auto bg-white p-6 shadow-2xl">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Audit-Details</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ action(selected.event_type) }}</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ date(selected.occurred_at) }} · {{ selected.actor_name }}
                            </p>
                        </div>
                        <button class="rounded-lg p-2 hover:bg-slate-100" @click="selected = null">
                            <X :size="20" />
                        </button>
                    </div>
                    <dl class="mt-6 grid grid-cols-[140px_1fr] gap-3 text-sm">
                        <dt class="text-slate-500">Objekt</dt>
                        <dd>
                            {{ selected.entity.label }} · {{ selected.metadata.name ?? selected.subject_public_id }}
                        </dd>
                        <dt class="text-slate-500">Änderungsdetails</dt>
                        <dd>{{ detail(selected) }}</dd>
                        <dt class="text-slate-500">IP-Adresse</dt>
                        <dd>{{ selected.metadata.ip_address ?? '—' }}</dd>
                        <dt class="text-slate-500">Browser / User-Agent</dt>
                        <dd class="break-words">{{ selected.metadata.user_agent ?? '—' }}</dd>
                    </dl>
                    <div
                        v-if="selected.metadata.before || selected.metadata.after"
                        class="mt-6 grid gap-3 sm:grid-cols-2"
                    >
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs font-semibold text-slate-500 uppercase">Vorher</p>
                            <pre class="mt-2 text-xs whitespace-pre-wrap">{{
                                JSON.stringify(selected.metadata.before, null, 2)
                            }}</pre>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs font-semibold text-slate-500 uppercase">Nachher</p>
                            <pre class="mt-2 text-xs whitespace-pre-wrap">{{
                                JSON.stringify(selected.metadata.after, null, 2)
                            }}</pre>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <Link
                            v-if="selected.entity.url"
                            :href="selected.entity.url"
                            class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white"
                            >Objekt öffnen</Link
                        >
                    </div>
                    <details class="mt-6 rounded-xl border border-slate-200 p-4">
                        <summary class="cursor-pointer text-sm font-medium">Technische Daten</summary>
                        <pre class="mt-3 overflow-x-auto text-xs whitespace-pre-wrap">{{
                            JSON.stringify(selected.metadata, null, 2)
                        }}</pre>
                    </details>
                </aside>
            </div></Teleport
        >
    </AppLayout>
</template>
