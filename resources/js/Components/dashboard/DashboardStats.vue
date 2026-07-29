<script setup lang="ts">
import { AlertTriangle, Boxes, Cable, Radio } from '@lucide/vue';

type Summary = {
    systems: number;
    dicomNodes: number;
    connections: number;
    failedDicomNodes: number;
    unverifiedDicomNodes: number;
};

defineProps<{ summary: Summary }>();

const cards = [
    { key: 'systems', label: 'Systeme', detail: 'Aktiv dokumentiert', icon: Boxes, href: '/systems' },
    { key: 'dicomNodes', label: 'DICOM-Knoten', detail: 'Aktive Application Entities', icon: Radio, href: '/systems' },
    {
        key: 'connections',
        label: 'Verbindungen',
        detail: 'Modellierte Kommunikationspfade',
        icon: Cable,
        href: '/systems',
    },
    {
        key: 'failedDicomNodes',
        label: 'Fehlgeschlagene Prüfungen',
        detail: 'Letzter C-ECHO nicht erfolgreich',
        icon: AlertTriangle,
        href: '/systems',
    },
] as const;
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <a
            v-for="card in cards"
            :key="card.key"
            :href="card.href"
            class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md"
        >
            <div class="flex items-start justify-between">
                <div
                    class="grid h-10 w-10 place-items-center rounded-xl"
                    :class="
                        card.key === 'failedDicomNodes' && summary.failedDicomNodes > 0
                            ? 'bg-red-50 text-red-600'
                            : 'bg-blue-50 text-blue-600'
                    "
                >
                    <component :is="card.icon" :size="20" />
                </div>

                <span
                    v-if="card.key === 'failedDicomNodes' && summary.unverifiedDicomNodes > 0"
                    class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700"
                >
                    {{ summary.unverifiedDicomNodes }} ungeprüft
                </span>
            </div>

            <p class="mt-5 text-3xl font-semibold text-slate-950">
                {{ summary[card.key] }}
            </p>
            <p class="mt-2 text-sm font-semibold text-slate-800">{{ card.label }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ card.detail }}</p>
        </a>
    </div>
</template>
