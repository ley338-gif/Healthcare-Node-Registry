<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search, X } from '@lucide/vue';
import { ref } from 'vue';
import Pagination from '../Pagination.vue';

export type AuditEvent = {
    event_id: string;
    event_type: string;
    subject_type: string;
    subject_public_id: string | null;
    actor_name: string;
    metadata: Record<string, unknown>;
    occurred_at: string;
};

type Link = { url: string | null; label: string; active: boolean };
type Filters = Record<string, string | undefined>;

const props = defineProps<{
    events: { data: AuditEvent[]; links: Link[]; total: number };
    stats: { total: number; today: number; last7Days: number; last30Days: number };
    filters: Filters;
    eventTypes: string[];
    users: Array<{ public_id: string; name: string }>;
    queryContext?: Record<string, string>;
    allowScopeSelection?: boolean;
}>();

const from = ref(props.filters.history_from ?? '');
const to = ref(props.filters.history_to ?? '');
const type = ref(props.filters.history_type ?? '');
const user = ref(props.filters.history_user ?? '');
const status = ref(props.filters.history_status ?? '');
const search = ref(props.filters.history_search ?? '');
const scope = ref(props.filters.history_scope ?? 'descendants');
const selected = ref<AuditEvent | null>(null);

const apply = (): void => {
    router.get(
        window.location.pathname,
        {
            ...props.queryContext,
            history_from: from.value || undefined,
            history_to: to.value || undefined,
            history_type: type.value || undefined,
            history_user: user.value || undefined,
            history_status: status.value || undefined,
            history_search: search.value || undefined,
            history_scope: props.allowScopeSelection ? scope.value : undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const reset = (): void => {
    from.value = '';
    to.value = '';
    type.value = '';
    user.value = '';
    status.value = '';
    search.value = '';
    scope.value = 'descendants';
    apply();
};

const label = (eventType: string): string => eventType.split('.').slice(1).join(' · ').replaceAll('_', ' ');
const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
const record = (value: unknown): Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value) ? (value as Record<string, unknown>) : {};
const changes = (event: AuditEvent): Array<{ field: string; before: unknown; after: unknown }> => {
    const before = record(event.metadata.before);
    const after = record(event.metadata.after);
    return [...new Set([...Object.keys(before), ...Object.keys(after)])]
        .filter((field) => JSON.stringify(before[field]) !== JSON.stringify(after[field]))
        .map((field) => ({ field, before: before[field], after: after[field] }));
};
const display = (value: unknown): string =>
    value === null || value === undefined || value === ''
        ? '—'
        : typeof value === 'object'
          ? JSON.stringify(value)
          : String(value);
</script>

<template>
    <div class="space-y-5">
        <div class="grid gap-3 sm:grid-cols-4">
            <div
                v-for="item in [
                    ['Gesamt', stats.total],
                    ['Heute', stats.today],
                    ['7 Tage', stats.last7Days],
                    ['30 Tage', stats.last30Days],
                ]"
                :key="String(item[0])"
                class="rounded-xl border border-slate-200 bg-white p-4"
            >
                <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">{{ item[0] }}</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ item[1] }}</p>
            </div>
        </div>

        <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-3 xl:grid-cols-6">
            <select
                v-if="allowScopeSelection"
                v-model="scope"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
            >
                <option value="direct">Nur direkte Änderungen</option>
                <option value="descendants">Direkte und untergeordnete Änderungen</option>
            </select>
            <input
                v-model="from"
                type="date"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                aria-label="Von"
            />
            <input
                v-model="to"
                type="date"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                aria-label="Bis"
            />
            <select v-model="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Alle Ereignisse</option>
                <option v-for="item in eventTypes" :key="item" :value="item">{{ label(item) }}</option>
            </select>
            <select v-model="user" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Alle Benutzer</option>
                <option v-for="item in users" :key="item.public_id" :value="item.public_id">{{ item.name }}</option>
            </select>
            <select v-model="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Alle Status</option>
                <option value="success">Erfolgreich</option>
                <option value="failed">Fehlgeschlagen</option>
                <option value="warning">Warnung</option>
            </select>
            <div class="flex gap-2">
                <div class="relative min-w-0 flex-1">
                    <Search :size="15" class="absolute top-3 left-3 text-slate-400" /><input
                        v-model="search"
                        class="w-full rounded-lg border border-slate-300 py-2 pr-2 pl-9 text-sm"
                        placeholder="Suchen"
                        @keyup.enter="apply"
                    />
                </div>
                <button class="rounded-lg bg-blue-600 px-3 text-sm font-medium text-white" @click="apply">
                    Filtern
                </button>
            </div>
            <button class="text-left text-sm font-medium text-slate-600 hover:text-blue-700" @click="reset">
                Filter zurücksetzen
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs tracking-wide text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Zeitpunkt</th>
                            <th class="px-4 py-3">Ereignis</th>
                            <th class="px-4 py-3">Objekt</th>
                            <th class="px-4 py-3">Benutzer</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="event in events.data" :key="event.event_id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                {{ formatDate(event.occurred_at) }}
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ label(event.event_type) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ event.subject_type }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ event.actor_name }}</td>
                            <td class="px-4 py-3 text-right">
                                <button class="font-medium text-blue-700 hover:text-blue-900" @click="selected = event">
                                    Details
                                </button>
                            </td>
                        </tr>
                        <tr v-if="events.data.length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                                Keine Ereignisse für diese Auswahl.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="events.links.length > 3" class="border-t border-slate-200 p-4">
                <Pagination :links="events.links" />
            </div>
        </div>

        <Teleport to="body"
            ><div
                v-if="selected"
                class="fixed inset-0 z-50 flex justify-end bg-slate-950/30"
                @click.self="selected = null"
            >
                <aside class="h-full w-full max-w-xl overflow-y-auto bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Audit-Details</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ label(selected.event_type) }}</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ formatDate(selected.occurred_at) }} · {{ selected.actor_name }}
                            </p>
                        </div>
                        <button class="rounded-lg p-2 hover:bg-slate-100" @click="selected = null">
                            <X :size="20" />
                        </button>
                    </div>
                    <div v-if="changes(selected).length" class="mt-6 space-y-3">
                        <h3 class="font-semibold text-slate-900">Geänderte Felder</h3>
                        <div
                            v-for="change in changes(selected)"
                            :key="change.field"
                            class="rounded-lg border border-slate-200 p-3"
                        >
                            <p class="text-sm font-medium text-slate-800">{{ change.field }}</p>
                            <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-xs text-slate-500">Vorher</p>
                                    <p class="break-words">{{ display(change.before) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Nachher</p>
                                    <p class="break-words">{{ display(change.after) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="
                            selected.event_type.includes('diagnostic') || selected.event_type.includes('verification')
                        "
                        class="mt-6"
                    >
                        <h3 class="font-semibold text-slate-900">Testergebnis</h3>
                        <dl class="mt-2 grid grid-cols-2 gap-2 text-sm">
                            <template v-for="(value, key) in selected.metadata" :key="key"
                                ><dt class="text-slate-500">{{ key }}</dt>
                                <dd class="break-words text-slate-900">{{ display(value) }}</dd></template
                            >
                        </dl>
                    </div>
                    <details class="mt-6 rounded-lg border border-slate-200 p-3">
                        <summary class="cursor-pointer text-sm font-medium text-slate-700">
                            Technische JSON-Daten
                        </summary>
                        <pre class="mt-3 overflow-x-auto text-xs whitespace-pre-wrap text-slate-600">{{
                            JSON.stringify(selected.metadata, null, 2)
                        }}</pre>
                    </details>
                </aside>
            </div></Teleport
        >
    </div>
</template>
