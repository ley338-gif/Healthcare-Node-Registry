<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, ArrowRight, Boxes, Building2, Cable, CircleDot, Hospital, Network, UsersRound } from '@lucide/vue';
import AppLayout from '../Layouts/AppLayout.vue';

defineProps<{
    summary: { organizations: number; sites: number; departments: number; systems: number; connections: number };
    recentChanges: Array<{
        event_type: string;
        subject_type: string;
        subject_public_id: string | null;
        occurred_at: string | null;
    }>;
    moduleStatus: Array<{ label: string; status: string }>;
}>();

const cards = [
    { key: 'organizations', label: 'Organisationen', icon: Building2, href: '/structure', detail: 'Aktive Einträge' },
    { key: 'sites', label: 'Standorte', icon: Hospital, href: '/structure', detail: 'Aktive Einträge' },
    { key: 'departments', label: 'Abteilungen', icon: UsersRound, href: '/structure', detail: 'Aktive Einträge' },
    { key: 'systems', label: 'Systeme', icon: Boxes, href: '#', detail: 'Nächster Sprint' },
    { key: 'connections', label: 'Verbindungen', icon: Cable, href: '#', detail: 'Geplant' },
] as const;

const eventLabel = (eventType: string): string =>
    ({
        'registry.organization.created': 'Organisation angelegt',
        'registry.organization.updated': 'Organisation geändert',
        'registry.organization.archived': 'Organisation archiviert',
        'registry.site.created': 'Standort angelegt',
        'registry.site.updated': 'Standort geändert',
        'registry.site.archived': 'Standort archiviert',
        'registry.department.created': 'Abteilung angelegt',
        'registry.department.updated': 'Abteilung geändert',
        'registry.department.archived': 'Abteilung archiviert',
    })[eventType] ?? eventType;
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="mb-7">
            <p class="mb-2 text-xs font-semibold tracking-wider text-blue-600 uppercase">Übersicht</p>
            <h1 class="text-2xl font-semibold">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Aktueller Registry-Stand ohne erfundene Monitoringwerte.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <Link
                v-for="card in cards"
                :key="card.key"
                :href="card.href"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300"
            >
                <div class="mb-5 flex items-center justify-between">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-600">
                        <component :is="card.icon" :size="20" />
                    </div>
                    <ArrowRight :size="16" class="text-slate-300" />
                </div>
                <p class="text-3xl font-semibold">{{ summary[card.key] }}</p>
                <p class="mt-2 text-sm font-medium">{{ card.label }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ card.detail }}</p>
            </Link>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold">Registry-Struktur</h2>
                        <p class="mt-1 text-sm text-slate-500">Aktueller fachlicher Datenbestand.</p>
                    </div>
                    <Link href="/structure" class="text-sm font-medium text-blue-600">Öffnen</Link>
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <Link href="/organizations" class="rounded-xl border border-slate-200 p-4 hover:border-blue-300">
                        <Building2 :size="19" class="text-blue-600" />
                        <p class="mt-4 text-2xl font-semibold">{{ summary.organizations }}</p>
                        <p class="mt-1 text-sm text-slate-600">Organisationen</p>
                    </Link>
                    <Link href="/sites" class="rounded-xl border border-slate-200 p-4 hover:border-blue-300">
                        <Hospital :size="19" class="text-blue-600" />
                        <p class="mt-4 text-2xl font-semibold">{{ summary.sites }}</p>
                        <p class="mt-1 text-sm text-slate-600">Standorte</p>
                    </Link>
                    <Link href="/departments" class="rounded-xl border border-slate-200 p-4 hover:border-blue-300">
                        <UsersRound :size="19" class="text-blue-600" />
                        <p class="mt-4 text-2xl font-semibold">{{ summary.departments }}</p>
                        <p class="mt-1 text-sm text-slate-600">Abteilungen</p>
                    </Link>
                </div>

                <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5">
                    <div class="flex items-start gap-3">
                        <Network :size="21" class="mt-0.5 text-slate-400" />
                        <div>
                            <p class="font-medium">Topologie folgt auf Basis echter System- und Verbindungsdaten</p>
                            <p class="mt-1 text-sm text-slate-500">
                                Die Visualisierung wird erst aktiviert, wenn Systeme und Beziehungen modelliert sind.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center gap-2">
                    <Activity :size="19" class="text-blue-600" />
                    <div>
                        <h2 class="font-semibold">Letzte Änderungen</h2>
                        <p class="text-sm text-slate-500">Audit-Ereignisse der Registry.</p>
                    </div>
                </div>

                <div v-if="recentChanges.length === 0" class="py-10 text-center text-sm text-slate-500">
                    Noch keine Änderungen vorhanden.
                </div>
                <div v-else class="divide-y divide-slate-100">
                    <div
                        v-for="change in recentChanges"
                        :key="`${change.event_type}-${change.occurred_at}`"
                        class="flex gap-3 py-3"
                    >
                        <CircleDot :size="15" class="mt-1 shrink-0 text-blue-600" />
                        <div>
                            <p class="text-sm font-medium">{{ eventLabel(change.event_type) }}</p>
                            <p class="text-xs text-slate-500">{{ change.subject_type }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Modulstatus</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-5">
                <div v-for="item in moduleStatus" :key="item.label" class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-medium">{{ item.label }}</p>
                    <span
                        class="mt-3 inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="
                            item.status === 'bereit'
                                ? 'bg-emerald-50 text-emerald-800'
                                : item.status === 'nächster Sprint'
                                  ? 'bg-blue-50 text-blue-700'
                                  : 'bg-slate-100 text-slate-600'
                        "
                    >
                        {{ item.status }}
                    </span>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
