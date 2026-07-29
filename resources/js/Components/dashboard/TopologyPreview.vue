<script setup lang="ts">
import { ArrowRight, Network } from '@lucide/vue';

type Summary = {
    systems: number;
    dicomNodes: number;
    connections: number;
};

defineProps<{ summary: Summary }>();
</script>

<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold text-slate-950">Kommunikationsübersicht</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Die bestehenden Verbindungsdaten bilden die Grundlage der kommenden Network Map.
                </p>
            </div>
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600">
                <Network :size="20" />
            </div>
        </div>

        <div
            v-if="summary.connections === 0"
            class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center"
        >
            <p class="font-medium text-slate-800">Noch keine DICOM-Verbindungen vorhanden</p>
            <p class="mt-1 text-sm text-slate-500">Lege zuerst Kommunikationspfade zwischen den Knoten an.</p>
            <a href="/systems" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-600">
                Zu den Systemen
                <ArrowRight :size="16" />
            </a>
        </div>

        <div v-else class="mt-5">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-2xl font-semibold text-slate-950">{{ summary.systems }}</p>
                    <p class="mt-1 text-xs text-slate-500">Systeme</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-2xl font-semibold text-slate-950">{{ summary.dicomNodes }}</p>
                    <p class="mt-1 text-xs text-slate-500">DICOM-Knoten</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-2xl font-semibold text-slate-950">{{ summary.connections }}</p>
                    <p class="mt-1 text-xs text-slate-500">Verbindungen</p>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
                <p class="text-sm text-blue-800">Die Datenbasis für die Network Map ist vorhanden.</p>
                <ArrowRight :size="17" class="text-blue-600" />
            </div>
        </div>
    </section>
</template>
