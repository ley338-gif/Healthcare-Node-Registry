<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Activity, Boxes, Cable, CircleDot, FileText, Network, RadioTower, Server, Upload, WifiOff } from '@lucide/vue';
import ContentCard from '../Components/ui/ContentCard.vue';
import EmptyState from '../Components/ui/EmptyState.vue';
import PageHeader from '../Components/ui/PageHeader.vue';
import StatCard from '../Components/ui/StatCard.vue';
import AppLayout from '../Layouts/AppLayout.vue';

defineProps<{
    summary: {
        systems: number;
        connections: number;
        online: number;
        offline: number;
        documents: number;
    };
    recentChanges: Array<{
        event_type: string;
        subject_type: string;
        subject_public_id: string | null;
        occurred_at: string | null;
    }>;
}>();

const cards = [
    { key: 'systems', label: 'Systeme', icon: Server, detail: 'Noch nicht implementiert' },
    { key: 'connections', label: 'Verbindungen', icon: Cable, detail: 'Noch nicht implementiert' },
    { key: 'online', label: 'Online', icon: RadioTower, detail: 'Monitoring noch nicht aktiv' },
    { key: 'offline', label: 'Offline', icon: WifiOff, detail: 'Monitoring noch nicht aktiv' },
    { key: 'documents', label: 'Dokumente', icon: FileText, detail: 'Dokumentenmodul geplant' },
] as const;

const eventLabels: Record<string, string> = {
    'registry.organization.created': 'Organisation angelegt',
    'registry.organization.updated': 'Organisation geändert',
    'registry.organization.archived': 'Organisation archiviert',
    'registry.site.created': 'Standort angelegt',
    'registry.site.updated': 'Standort geändert',
    'registry.site.archived': 'Standort archiviert',
    'registry.department.created': 'Abteilung angelegt',
    'registry.department.updated': 'Abteilung geändert',
    'registry.department.archived': 'Abteilung archiviert',
};

const quickActions = [
    { label: 'Neues System', icon: Server },
    { label: 'Neue Verbindung', icon: Cable },
    { label: 'C-ECHO-Test', icon: Activity },
    { label: 'Dokument hochladen', icon: Upload },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <PageHeader
            title="Dashboard"
            description="Übersicht der Systemlandschaft, Kommunikationsbeziehungen und Betriebszustände."
        />

        <div class="mt-7 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <StatCard
                v-for="card in cards"
                :key="card.key"
                :label="card.label"
                :value="summary[card.key]"
                :detail="card.detail"
                :icon="card.icon"
                disabled
            />
        </div>

        <div class="mt-6 grid gap-6 2xl:grid-cols-[minmax(0,1.8fr)_380px]">
            <div class="space-y-6">
                <ContentCard
                    title="Netzwerk-Topologie"
                    description="Visualisierung der dokumentierten Kommunikationsbeziehungen."
                >
                    <EmptyState
                        title="Topologie noch nicht verfügbar"
                        description="Sobald Systeme und Verbindungen erfasst werden können, erscheint hier die interaktive Infrastrukturansicht."
                        :icon="Network"
                    >
                        <template #action>
                            <div
                                class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-500"
                            >
                                <Boxes :size="15" />
                                System Registry ist der nächste Sprint
                            </div>
                        </template>
                    </EmptyState>
                </ContentCard>

                <div class="grid gap-6 xl:grid-cols-2">
                    <ContentCard
                        title="Verbindungsübersicht"
                        description="Quelle, Ziel, Protokoll, Service und Status."
                    >
                        <EmptyState
                            title="Keine Verbindungen vorhanden"
                            description="Verbindungen werden nach Einführung der System Registry modelliert."
                            :icon="Cable"
                        />
                    </ContentCard>

                    <ContentCard
                        title="Monitoring-Überblick"
                        description="Technische Erreichbarkeit und fachliche Dienste."
                    >
                        <EmptyState
                            title="Monitoring noch nicht aktiv"
                            description="C-ECHO, TCP, MWL, Store und weitere Prüfungen folgen in einem späteren Modul."
                            :icon="Activity"
                        />
                    </ContentCard>
                </div>
            </div>

            <aside class="space-y-6">
                <ContentCard title="Systemstatus" description="Aktueller Betriebszustand der registrierten Systeme.">
                    <div class="flex min-h-48 items-center justify-center">
                        <div class="text-center">
                            <div
                                class="mx-auto grid h-20 w-20 place-items-center rounded-full border-8 border-slate-100 text-slate-400"
                            >
                                <CircleDot :size="28" />
                            </div>
                            <p class="mt-4 text-sm font-medium text-slate-800">Noch keine Statusdaten</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Die Anzeige wird mit dem Monitoring-Modul aktiviert.
                            </p>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard title="Letzte Änderungen" description="Audit-Ereignisse der Registry.">
                    <EmptyState
                        v-if="recentChanges.length === 0"
                        title="Noch keine Änderungen"
                        description="Neue Registry-Ereignisse erscheinen automatisch an dieser Stelle."
                        :icon="Activity"
                    />

                    <div v-else class="divide-y divide-slate-100">
                        <div
                            v-for="change in recentChanges"
                            :key="`${change.event_type}-${change.occurred_at}`"
                            class="flex gap-3 py-3"
                        >
                            <CircleDot :size="15" class="mt-1 shrink-0 text-blue-600" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900">
                                    {{ eventLabels[change.event_type] ?? change.event_type }}
                                </p>
                                <p class="text-xs text-slate-500">{{ change.subject_type }}</p>
                            </div>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard title="Schnellaktionen" description="Häufig benötigte administrative Vorgänge.">
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            v-for="action in quickActions"
                            :key="action.label"
                            type="button"
                            disabled
                            class="flex min-h-24 cursor-not-allowed flex-col items-start justify-between rounded-xl border border-slate-200 bg-slate-50 p-4 text-left opacity-70"
                        >
                            <component :is="action.icon" :size="20" class="text-blue-600" />
                            <span class="text-sm font-medium text-slate-700">{{ action.label }}</span>
                        </button>
                    </div>
                </ContentCard>
            </aside>
        </div>
    </AppLayout>
</template>
