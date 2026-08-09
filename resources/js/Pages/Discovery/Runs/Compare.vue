<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CircleEqual, CircleMinus, CirclePlus, GitCompare, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

type Run = {
    public_id: string;
    name: string;
    ip_range: string;
    finished_at: string | null;
    found_hosts_count: number;
    dicom_candidates_count: number;
};
type Snapshot = {
    public_id: string;
    ip_address: string;
    hostname: string | null;
    reverse_dns: string | null;
    is_reachable: boolean;
    open_ports: number[];
    dicom_endpoints: string[];
    suggested_system_type: string | null;
    confidence_score: string;
    confidence_percentage: number;
};
type Change = { field: string; before: unknown; after: unknown };
type Result = {
    change_type: 'new' | 'missing' | 'changed' | 'unchanged';
    ip_address: string;
    baseline: Snapshot | null;
    current: Snapshot | null;
    changes: Change[];
};

const props = defineProps<{
    currentRun: Run;
    baselineRun: Run;
    summary: { new: number; missing: number; changed: number; unchanged: number; total: number };
    results: Result[];
}>();

const typeFilter = ref<'all' | Result['change_type']>('all');
const search = ref('');
const filteredResults = computed(() => {
    const needle = search.value.trim().toLocaleLowerCase('de-DE');

    return props.results.filter((result) => {
        if (typeFilter.value !== 'all' && result.change_type !== typeFilter.value) return false;
        if (!needle) return true;
        const snapshot = result.current ?? result.baseline;

        return [result.ip_address, snapshot?.hostname, snapshot?.reverse_dns, ...(snapshot?.dicom_endpoints ?? [])]
            .filter(Boolean)
            .some((value) => String(value).toLocaleLowerCase('de-DE').includes(needle));
    });
});

const typeLabels: Record<Result['change_type'], string> = {
    new: 'Neu',
    missing: 'Verschwunden',
    changed: 'Verändert',
    unchanged: 'Unverändert',
};
const typeClasses: Record<Result['change_type'], string> = {
    new: 'bg-emerald-50 text-emerald-700',
    missing: 'bg-red-50 text-red-700',
    changed: 'bg-amber-50 text-amber-800',
    unchanged: 'bg-slate-100 text-slate-600',
};
const fieldLabels: Record<string, string> = {
    hostname: 'Hostname',
    reverse_dns: 'Reverse DNS',
    is_reachable: 'Erreichbarkeit',
    open_ports: 'Offene Ports',
    dicom_endpoints: 'DICOM-Endpunkte',
    suggested_system_type: 'Vorgeschlagener Systemtyp',
    confidence_score: 'Confidence-Stufe',
    confidence_percentage: 'Confidence-Wert',
};
const confidenceLabels: Record<string, string> = {
    unknown: 'Unbekannt',
    very_low: 'Sehr niedrig',
    low: 'Niedrig',
    medium: 'Mittel',
    high: 'Hoch',
    very_high: 'Sehr hoch',
};
const formatValue = (value: unknown, field?: string): string => {
    if (Array.isArray(value)) return value.length ? value.join(', ') : '—';
    if (typeof value === 'boolean') return value ? 'Erreichbar' : 'Nicht erreichbar';
    if (value === null || value === undefined || value === '') return '—';
    if (field === 'confidence_score') return confidenceLabels[String(value)] ?? String(value);
    if (field === 'confidence_percentage') return `${value} %`;

    return String(value);
};
const snapshotLabel = (snapshot: Snapshot | null): string =>
    snapshot?.hostname ?? snapshot?.reverse_dns ?? 'Kein Hostname';
const summaryCards = computed(() => [
    {
        key: 'new' as const,
        label: 'Neue Hosts',
        value: props.summary.new,
        icon: CirclePlus,
        classes: 'text-emerald-700 bg-emerald-50',
    },
    {
        key: 'missing' as const,
        label: 'Verschwundene Hosts',
        value: props.summary.missing,
        icon: CircleMinus,
        classes: 'text-red-700 bg-red-50',
    },
    {
        key: 'changed' as const,
        label: 'Veränderte Hosts',
        value: props.summary.changed,
        icon: GitCompare,
        classes: 'text-amber-800 bg-amber-50',
    },
    {
        key: 'unchanged' as const,
        label: 'Unveränderte Hosts',
        value: props.summary.unchanged,
        icon: CircleEqual,
        classes: 'text-slate-700 bg-slate-100',
    },
]);
</script>

<template>
    <Head :title="`Scanvergleich: ${currentRun.name}`" />
    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                eyebrow="Discovery"
                title="Scanstände vergleichen"
                :description="`${baselineRun.name} → ${currentRun.name}`"
            >
                <template #actions>
                    <Link
                        :href="`/discovery/runs/${currentRun.public_id}`"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <ArrowLeft :size="16" /> Zum aktuellen Lauf
                    </Link>
                </template>
            </PageHeader>

            <section class="rounded-2xl border border-blue-100 bg-blue-50/50 p-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Ausgangsstand</p>
                        <Link
                            :href="`/discovery/runs/${baselineRun.public_id}`"
                            class="mt-1 block font-semibold text-blue-700"
                        >
                            {{ baselineRun.name }}
                        </Link>
                        <p class="mt-1 text-sm text-slate-600">
                            {{
                                baselineRun.finished_at
                                    ? new Date(baselineRun.finished_at).toLocaleString('de-DE')
                                    : '—'
                            }}
                            · {{ baselineRun.found_hosts_count }} Hosts
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Aktueller Stand</p>
                        <Link
                            :href="`/discovery/runs/${currentRun.public_id}`"
                            class="mt-1 block font-semibold text-blue-700"
                        >
                            {{ currentRun.name }}
                        </Link>
                        <p class="mt-1 text-sm text-slate-600">
                            {{
                                currentRun.finished_at ? new Date(currentRun.finished_at).toLocaleString('de-DE') : '—'
                            }}
                            · {{ currentRun.found_hosts_count }} Hosts
                        </p>
                    </div>
                </div>
                <p class="mt-4 text-xs text-slate-500">
                    Zielbereich: <span class="font-mono">{{ currentRun.ip_range }}</span
                    >. Latenz und manueller Review-Status werden nicht als technische Änderung gewertet. Abweichende
                    Scanoptionen können das Ergebnis beeinflussen.
                </p>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <button
                    v-for="card in summaryCards"
                    :key="card.key"
                    type="button"
                    class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-blue-300"
                    @click="typeFilter = typeFilter === card.key ? 'all' : card.key"
                >
                    <span class="inline-flex rounded-xl p-2" :class="card.classes"
                        ><component :is="card.icon" :size="18"
                    /></span>
                    <p class="mt-3 text-2xl font-bold text-slate-950">{{ card.value }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ card.label }}</p>
                </button>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
                    <div>
                        <h2 class="font-semibold text-slate-950">Hostvergleich</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ filteredResults.length }} von {{ summary.total }} Hosts
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <select v-model="typeFilter" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="all">Alle Änderungen</option>
                            <option value="new">Neu</option>
                            <option value="missing">Verschwunden</option>
                            <option value="changed">Verändert</option>
                            <option value="unchanged">Unverändert</option>
                        </select>
                        <label class="relative">
                            <Search :size="16" class="absolute top-2.5 left-3 text-slate-400" />
                            <input
                                v-model="search"
                                placeholder="IP, Hostname oder AE-Titel"
                                class="w-72 rounded-xl border border-slate-300 py-2 pr-3 pl-9 text-sm"
                            />
                        </label>
                    </div>
                </header>

                <div v-if="filteredResults.length === 0" class="p-10 text-center text-sm text-slate-500">
                    Keine passenden Vergleichsergebnisse gefunden.
                </div>
                <div v-else class="divide-y divide-slate-100">
                    <article v-for="result in filteredResults" :key="result.ip_address" class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-semibold text-slate-950">{{ result.ip_address }}</span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="typeClasses[result.change_type]"
                                    >
                                        {{ typeLabels[result.change_type] }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ snapshotLabel(result.current ?? result.baseline) }}
                                </p>
                            </div>
                            <Link
                                v-if="result.current"
                                :href="`/discovery/runs/${currentRun.public_id}`"
                                class="text-xs font-semibold text-blue-700"
                            >
                                Aktuellen Lauf ansehen
                            </Link>
                        </div>

                        <div
                            v-if="result.change_type === 'new' || result.change_type === 'missing'"
                            class="mt-4 grid gap-3 text-sm sm:grid-cols-3"
                        >
                            <div>
                                <span class="text-xs text-slate-500">Offene Ports</span>
                                <p class="mt-1">{{ formatValue((result.current ?? result.baseline)?.open_ports) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500">DICOM-Endpunkte</span>
                                <p class="mt-1">
                                    {{ formatValue((result.current ?? result.baseline)?.dicom_endpoints) }}
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500">Confidence</span>
                                <p class="mt-1">
                                    {{
                                        formatValue(
                                            (result.current ?? result.baseline)?.confidence_score,
                                            'confidence_score',
                                        )
                                    }}
                                    · {{ (result.current ?? result.baseline)?.confidence_percentage }} %
                                </p>
                            </div>
                        </div>

                        <div v-else-if="result.changes.length" class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[680px] text-left text-sm">
                                <thead class="text-xs tracking-wide text-slate-500 uppercase">
                                    <tr>
                                        <th class="pr-4 pb-2">Merkmal</th>
                                        <th class="pr-4 pb-2">Vorher</th>
                                        <th class="pb-2">Aktuell</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="change in result.changes" :key="change.field">
                                        <th class="py-2 pr-4 font-medium text-slate-700">
                                            {{ fieldLabels[change.field] ?? change.field }}
                                        </th>
                                        <td class="py-2 pr-4 text-slate-500">
                                            {{ formatValue(change.before, change.field) }}
                                        </td>
                                        <td class="py-2 text-slate-950">
                                            {{ formatValue(change.after, change.field) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
