<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    Boxes,
    Building2,
    Cable,
    CircleAlert,
    CircleCheck,
    CircleHelp,
    Clock3,
    FlaskConical,
    Map,
    Radio,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';

type Summary = {
    organizations: number;
    sites: number;
    departments: number;
    systems: number;
    dicomNodes: number;
    connections: number;
    failedDicomNodes: number;
    unverifiedDicomNodes: number;
};

type RecentChange = {
    event_type: string;
    label: string;
    subject_type: string;
    subject_public_id: string | null;
    subject_label: string | null;
    occurred_at: string | null;
};

type Task = {
    label: string;
    completed: boolean;
    href: string;
};

type Diagnostics = {
    failedTests: number;
    averageDurationMilliseconds: number;
    lastSuccessfulEchoAt: string | null;
    recentTests: Array<{
        publicId: string;
        testType: string;
        status: string;
        durationMilliseconds: number;
        startedAt: string;
        dicomNode: { publicId: string; name: string };
    }>;
};

const props = defineProps<{
    summary: Summary;
    recentChanges: RecentChange[];
    tasks: Task[];
    diagnostics: Diagnostics | null;
}>();

const testTypeLabel = (type: string): string =>
    ({
        network: 'Netzwerk',
        dicom_echo: 'C-ECHO',
        worklist: 'Worklist',
        pacs_query: 'PACS Query',
        dicom_storage: 'DICOM Storage',
        dicom_capability_matrix: 'Capability-Matrix',
    })[type] ?? type;

const warningCount = computed(() => props.summary.failedDicomNodes + props.summary.unverifiedDicomNodes);

const formatDate = (value: string | null): string => {
    if (value === null) {
        return 'Zeitpunkt unbekannt';
    }

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
};

const changeIcon = (eventType: string) => {
    if (eventType.includes('dicom_connection')) {
        return Cable;
    }

    if (eventType.includes('dicom_node')) {
        return Radio;
    }

    if (eventType.includes('system')) {
        return Boxes;
    }

    return Building2;
};

const changeIconClass = (eventType: string): string => {
    if (eventType.endsWith('.archived')) {
        return 'bg-amber-50 text-amber-700';
    }

    if (eventType.endsWith('.created')) {
        return 'bg-emerald-50 text-emerald-700';
    }

    if (eventType.endsWith('.verified')) {
        return 'bg-blue-50 text-blue-700';
    }

    return 'bg-slate-100 text-slate-600';
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">Betriebsübersicht</p>

                <h1 class="mt-2 text-2xl font-semibold text-slate-950">Dashboard</h1>

                <p class="mt-1 text-sm text-slate-500">Aktueller Zustand der System- und DICOM-Registry.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    href="/systems"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700"
                >
                    <Boxes :size="17" />
                    Systeme
                </Link>

                <Link
                    href="/network"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                >
                    <Map :size="17" />
                    Topologie
                </Link>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                href="/systems"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md"
            >
                <div class="flex items-start justify-between">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-600">
                        <Boxes :size="20" />
                    </div>
                    <ArrowRight :size="16" class="text-slate-300" />
                </div>

                <p class="mt-5 text-3xl font-semibold text-slate-950">
                    {{ summary.systems }}
                </p>
                <p class="mt-2 text-sm font-semibold text-slate-800">Systeme</p>
                <p class="mt-1 text-xs text-slate-500">Aktiv dokumentierte Systeme</p>
            </Link>

            <Link
                href="/systems"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md"
            >
                <div class="flex items-start justify-between">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-violet-50 text-violet-600">
                        <Radio :size="20" />
                    </div>
                    <ArrowRight :size="16" class="text-slate-300" />
                </div>

                <p class="mt-5 text-3xl font-semibold text-slate-950">
                    {{ summary.dicomNodes }}
                </p>
                <p class="mt-2 text-sm font-semibold text-slate-800">DICOM-Knoten</p>
                <p class="mt-1 text-xs text-slate-500">Erfasste Application Entities</p>
            </Link>

            <Link
                href="/network"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md"
            >
                <div class="flex items-start justify-between">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-50 text-cyan-700">
                        <Cable :size="20" />
                    </div>
                    <ArrowRight :size="16" class="text-slate-300" />
                </div>

                <p class="mt-5 text-3xl font-semibold text-slate-950">
                    {{ summary.connections }}
                </p>
                <p class="mt-2 text-sm font-semibold text-slate-800">Verbindungen</p>
                <p class="mt-1 text-xs text-slate-500">Modellierte Kommunikationspfade</p>
            </Link>

            <Link
                href="/systems"
                class="rounded-2xl border bg-white p-5 shadow-sm transition hover:shadow-md"
                :class="
                    warningCount > 0
                        ? 'border-amber-300 hover:border-amber-400'
                        : 'border-slate-200 hover:border-emerald-300'
                "
            >
                <div class="flex items-start justify-between">
                    <div
                        class="grid h-10 w-10 place-items-center rounded-xl"
                        :class="warningCount > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                    >
                        <CircleAlert v-if="warningCount > 0" :size="20" />
                        <CircleCheck v-else :size="20" />
                    </div>
                    <ArrowRight :size="16" class="text-slate-300" />
                </div>

                <p
                    class="mt-5 text-3xl font-semibold"
                    :class="warningCount > 0 ? 'text-amber-700' : 'text-emerald-700'"
                >
                    {{ warningCount }}
                </p>
                <p class="mt-2 text-sm font-semibold text-slate-800">Auffälligkeiten</p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ summary.failedDicomNodes }}
                    fehlgeschlagen ·
                    {{ summary.unverifiedDicomNodes }}
                    ungeprüft
                </p>
            </Link>
        </div>

        <section v-if="diagnostics" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-teal-50 text-teal-700">
                        <FlaskConical :size="19" />
                    </div>
                    <div>
                        <h2 class="font-semibold text-slate-950">Diagnosestatus</h2>
                        <p class="text-sm text-slate-500">Aktuelle Ergebnisse des Test-Workspace</p>
                    </div>
                </div>
                <Link
                    href="/tests"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white"
                    ><FlaskConical :size="16" />Tests öffnen</Link
                >
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                    <p class="text-xs font-medium text-rose-700">Fehlgeschlagene Testläufe</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-800">{{ diagnostics.failedTests }}</p>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <p class="text-xs font-medium text-blue-700">Durchschnittliche Testdauer</p>
                    <p class="mt-2 text-2xl font-semibold text-blue-800">
                        {{ diagnostics.averageDurationMilliseconds }} ms
                    </p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-xs font-medium text-emerald-700">Letzter erfolgreicher C-ECHO</p>
                    <p class="mt-2 text-sm font-semibold text-emerald-800">
                        {{
                            diagnostics.lastSuccessfulEchoAt
                                ? formatDate(diagnostics.lastSuccessfulEchoAt)
                                : 'Noch nicht erfolgreich'
                        }}
                    </p>
                </div>
            </div>
            <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Zeitpunkt</th>
                            <th class="px-4 py-3">Knoten</th>
                            <th class="px-4 py-3">Test</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Dauer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="test in diagnostics.recentTests" :key="test.publicId">
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(test.startedAt) }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ test.dicomNode.name }}</td>
                            <td class="px-4 py-3">{{ testTypeLabel(test.testType) }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-semibold"
                                    :class="
                                        test.status === 'success'
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-rose-50 text-rose-700'
                                    "
                                    >{{ test.status }}</span
                                >
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1"
                                    ><Clock3 :size="14" />{{ test.durationMilliseconds }} ms</span
                                >
                            </td>
                        </tr>
                        <tr v-if="diagnostics.recentTests.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                Noch keine Diagnose-Testläufe vorhanden.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_0.9fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-600">
                            <Activity :size="19" />
                        </div>

                        <div>
                            <h2 class="font-semibold text-slate-950">Letzte Änderungen</h2>
                            <p class="text-sm text-slate-500">Aktuelle Ereignisse der Registry</p>
                        </div>
                    </div>
                </div>

                <div v-if="recentChanges.length === 0" class="py-12 text-center">
                    <Activity :size="30" class="mx-auto text-slate-300" />
                    <p class="mt-3 text-sm font-medium text-slate-700">Noch keine Änderungen vorhanden</p>
                </div>

                <div v-else class="mt-4 divide-y divide-slate-100">
                    <article
                        v-for="change in recentChanges"
                        :key="`${change.event_type}-${change.occurred_at}-${change.subject_public_id}`"
                        class="flex gap-3 py-3.5"
                    >
                        <div
                            class="grid h-9 w-9 shrink-0 place-items-center rounded-xl"
                            :class="changeIconClass(change.event_type)"
                        >
                            <component :is="changeIcon(change.event_type)" :size="17" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ change.label }}
                            </p>
                            <p v-if="change.subject_label" class="mt-0.5 truncate text-sm text-slate-600">
                                {{ change.subject_label }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ formatDate(change.occurred_at) }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-violet-50 text-violet-600">
                        <CircleHelp :size="19" />
                    </div>

                    <div>
                        <h2 class="font-semibold text-slate-950">Registry-Status</h2>
                        <p class="text-sm text-slate-500">Prüfzustand der DICOM-Knoten</p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3">
                        <div class="flex items-center gap-3">
                            <CircleCheck :size="18" class="text-emerald-600" />
                            <span class="text-sm text-slate-700"> Ohne bekannten Fehler </span>
                        </div>

                        <strong class="text-slate-950">
                            {{
                                Math.max(
                                    summary.dicomNodes - summary.failedDicomNodes - summary.unverifiedDicomNodes,
                                    0,
                                )
                            }}
                        </strong>
                    </div>

                    <div class="flex items-center justify-between rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                        <div class="flex items-center gap-3">
                            <CircleAlert :size="18" class="text-red-600" />
                            <span class="text-sm text-red-800"> Prüfung fehlgeschlagen </span>
                        </div>

                        <strong class="text-red-800">
                            {{ summary.failedDicomNodes }}
                        </strong>
                    </div>

                    <div
                        class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <CircleHelp :size="18" class="text-amber-600" />
                            <span class="text-sm text-amber-800"> Noch nicht geprüft </span>
                        </div>

                        <strong class="text-amber-800">
                            {{ summary.unverifiedDicomNodes }}
                        </strong>
                    </div>
                </div>

                <Link
                    href="/network"
                    class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    <Map :size="17" />
                    Topologie öffnen
                </Link>
            </section>
        </div>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <h2 class="font-semibold text-slate-950">Organisationskontext</h2>
                <p class="mt-1 text-sm text-slate-500">Kompakte Übersicht der Registry-Struktur</p>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <Link
                    href="/structure"
                    class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-slate-50"
                >
                    <div class="flex items-center gap-3">
                        <Building2 :size="18" class="text-blue-600" />
                        <span class="text-sm font-medium text-slate-700"> Organisationen </span>
                    </div>
                    <strong>{{ summary.organizations }}</strong>
                </Link>

                <Link
                    href="/structure"
                    class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-slate-50"
                >
                    <span class="text-sm font-medium text-slate-700"> Standorte </span>
                    <strong>{{ summary.sites }}</strong>
                </Link>

                <Link
                    href="/structure"
                    class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-slate-50"
                >
                    <span class="text-sm font-medium text-slate-700"> Abteilungen </span>
                    <strong>{{ summary.departments }}</strong>
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
