<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, CircleMinus, CirclePlus, GitCompare, Radar } from '@lucide/vue';

export type DiscoveryChange = {
    id: string;
    title: string;
    scheduleName: string;
    currentRunName: string;
    newHostsCount: number;
    missingHostsCount: number;
    createdAt: string | null;
    unread: boolean;
    href: string;
};

defineProps<{
    unread: number;
    items: DiscoveryChange[];
}>();

const formatDate = (value: string | null): string =>
    value
        ? new Intl.DateTimeFormat('de-DE', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value))
        : 'Zeitpunkt unbekannt';
</script>

<template>
    <section class="mt-6 rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-700">
                    <Radar :size="19" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-semibold text-slate-950">Discovery-Änderungen</h2>
                        <span
                            v-if="unread"
                            class="rounded-full bg-blue-600 px-2 py-0.5 text-xs font-semibold text-white"
                        >
                            {{ unread }} neu
                        </span>
                    </div>
                    <p class="text-sm text-slate-500">Neue oder verschwundene Hosts in wiederkehrenden Scans</p>
                </div>
            </div>

            <Link
                href="/discovery"
                class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-800"
            >
                Discovery öffnen
                <ArrowRight :size="15" />
            </Link>
        </div>

        <div v-if="items.length" class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200">
            <Link
                v-for="item in items"
                :key="item.id"
                :href="item.href"
                class="flex items-start gap-3 px-4 py-3.5 transition hover:bg-slate-50"
            >
                <div class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700">
                    <GitCompare :size="17" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span v-if="item.unread" class="h-2 w-2 shrink-0 rounded-full bg-blue-600" title="Neu" />
                        <p class="truncate text-sm font-semibold text-slate-900">
                            {{ item.scheduleName || item.title }}
                        </p>
                    </div>
                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ item.currentRunName }}</p>
                    <div class="mt-2 flex flex-wrap gap-3 text-xs font-medium">
                        <span class="inline-flex items-center gap-1 text-emerald-700">
                            <CirclePlus :size="14" /> {{ item.newHostsCount }} neu
                        </span>
                        <span class="inline-flex items-center gap-1 text-red-700">
                            <CircleMinus :size="14" /> {{ item.missingHostsCount }} verschwunden
                        </span>
                    </div>
                </div>

                <p class="shrink-0 text-xs text-slate-400">{{ formatDate(item.createdAt) }}</p>
            </Link>
        </div>

        <div v-else class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-6 text-center">
            <p class="text-sm font-semibold text-emerald-800">Keine Discovery-Änderungen gemeldet.</p>
        </div>
    </section>
</template>
