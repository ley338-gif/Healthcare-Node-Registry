<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, Network, Radar, ScanSearch, Search, Server } from '@lucide/vue';
import ContentCard from '../../Components/ui/ContentCard.vue';
import EmptyState from '../../Components/ui/EmptyState.vue';
import PageHeader from '../../Components/ui/PageHeader.vue';
import StatCard from '../../Components/ui/StatCard.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type LastRun = {
    public_id: string;
    name: string;
    status: string;
    progress_percentage: number;
    started_at: string | null;
    finished_at: string | null;
    ip_range: string;
    found_hosts_count: number;
    dicom_candidates_count: number;
    confirmed_systems_count: number;
    open_reviews_count: number;
} | null;

defineProps<{
    stats: {
        runs_count: number;
        hosts_found_count: number;
        dicom_candidates_count: number;
        confirmed_systems_count: number;
        unreviewed_count: number;
        failed_checks_count: number;
    };
    lastRun: LastRun;
    canRun: boolean;
}>();

const statusLabels: Record<string, string> = {
    draft: 'Entwurf',
    pending: 'Geplant',
    running: 'Läuft',
    cancelling: 'Wird abgebrochen',
    completed: 'Abgeschlossen',
    partially_failed: 'Teilweise fehlgeschlagen',
    cancelled: 'Abgebrochen',
    failed: 'Fehlgeschlagen',
};

const statusClasses: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-600',
    pending: 'bg-blue-50 text-blue-700',
    running: 'bg-blue-50 text-blue-700',
    cancelling: 'bg-amber-50 text-amber-800',
    completed: 'bg-emerald-50 text-emerald-800',
    partially_failed: 'bg-amber-50 text-amber-800',
    cancelled: 'bg-slate-100 text-slate-600',
    failed: 'bg-red-50 text-red-700',
};

const duration = (run: NonNullable<LastRun>): string => {
    if (!run.started_at) return '—';
    const end = run.finished_at ? new Date(run.finished_at) : new Date();
    const seconds = Math.max(0, Math.round((end.getTime() - new Date(run.started_at).getTime()) / 1000));
    if (seconds < 60) return `${seconds}s`;
    return `${Math.floor(seconds / 60)}m ${seconds % 60}s`;
};
</script>

<template>
    <Head title="Discovery" />
    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                eyebrow="Erkennung"
                title="Discovery Dashboard"
                description="Automatisierte Erkennung und Dokumentation von DICOM-Systemen im Netzwerk. Alle Ergebnisse sind Vorschläge und müssen manuell geprüft werden."
            >
                <template #actions>
                    <Link
                        v-if="canRun"
                        href="/discovery/runs/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        <ScanSearch :size="17" /> Neuer Discovery-Lauf
                    </Link>
                </template>
            </PageHeader>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard label="Discovery-Läufe" :value="stats.runs_count" :icon="Radar" />
                <StatCard label="Gefundene Hosts" :value="stats.hosts_found_count" :icon="Server" />
                <StatCard label="Mögliche DICOM-Endpunkte" :value="stats.dicom_candidates_count" :icon="Network" />
                <StatCard label="Bestätigte Systeme" :value="stats.confirmed_systems_count" :icon="CheckCircle2" />
                <StatCard label="Ungeprüfte Treffer" :value="stats.unreviewed_count" :icon="Search" />
                <StatCard label="Fehlgeschlagene Prüfungen" :value="stats.failed_checks_count" :icon="AlertTriangle" />
            </section>

            <ContentCard
                title="Letzter Discovery-Lauf"
                description="Status und Fortschritt des zuletzt angelegten Laufs."
            >
                <EmptyState
                    v-if="!lastRun"
                    :icon="Radar"
                    title="Noch kein Discovery-Lauf durchgeführt"
                    description="Starten Sie einen geführten Wizard, um einen IP-Bereich auf DICOM-Systeme zu prüfen."
                >
                    <template v-if="canRun" #action>
                        <Link
                            href="/discovery/runs/create"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
                        >
                            Discovery-Lauf starten
                        </Link>
                    </template>
                </EmptyState>
                <div v-else class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <Link
                                :href="`/discovery/runs/${lastRun.public_id}`"
                                class="font-semibold text-slate-950 hover:text-blue-700"
                            >
                                {{ lastRun.name }}
                            </Link>
                            <p class="mt-1 font-mono text-xs text-slate-500">{{ lastRun.ip_range }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusClasses[lastRun.status]">
                            {{ statusLabels[lastRun.status] ?? lastRun.status }}
                        </span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-blue-600 transition-all"
                            :style="{ width: `${lastRun.progress_percentage}%` }"
                        />
                    </div>
                    <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                        <div>
                            <dt class="text-xs text-slate-500">Startzeit</dt>
                            <dd class="mt-1 font-medium text-slate-900">
                                {{ lastRun.started_at ? new Date(lastRun.started_at).toLocaleString('de-DE') : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Dauer</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ duration(lastRun) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Gefundene Hosts</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ lastRun.found_hosts_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">DICOM-Kandidaten</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ lastRun.dicom_candidates_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Übernommene Systeme</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ lastRun.confirmed_systems_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Offene Prüfungen</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ lastRun.open_reviews_count }}</dd>
                        </div>
                    </dl>
                    <Link
                        :href="`/discovery/runs/${lastRun.public_id}`"
                        class="inline-block text-sm font-semibold text-blue-700 hover:text-blue-800"
                    >
                        Details und Review-Queue ansehen →
                    </Link>
                </div>
            </ContentCard>
        </div>
    </AppLayout>
</template>
