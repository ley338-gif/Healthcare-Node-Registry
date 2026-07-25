<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Boxes, Cable, FileText, GitBranch, Info } from '@lucide/vue';
import AppLayout from '../Layouts/AppLayout.vue';

defineProps<{
    summary: {
        systems: number;
        endpoints: number;
        connections: number;
        documents: number;
    };
    foundationStatus: Array<{ label: string; status: string }>;
}>();

const cards = [
    { key: 'systems', label: 'Systeme', icon: Boxes },
    { key: 'endpoints', label: 'Endpunkte', icon: Cable },
    { key: 'connections', label: 'Verbindungen', icon: GitBranch },
    { key: 'documents', label: 'Dokumente', icon: FileText },
] as const;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="mb-7">
            <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">
                Technisches Grundgerüst – Registry-Fachmodule werden in den nächsten Releases ergänzt.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="card in cards"
                :key="card.key"
                class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="mb-5 flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-600">{{ card.label }}</p>
                    <component :is="card.icon" :size="20" class="text-blue-600" />
                </div>
                <p class="text-3xl font-semibold">{{ summary[card.key] }}</p>
                <p class="mt-1 text-xs text-slate-500">Noch keine Fachdaten erfasst</p>
            </article>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-center gap-2">
                    <Info :size="19" class="text-blue-600" />
                    <h2 class="font-semibold">Foundation-Status</h2>
                </div>

                <div class="divide-y divide-slate-100">
                    <div
                        v-for="item in foundationStatus"
                        :key="item.label"
                        class="flex items-center justify-between py-3"
                    >
                        <span class="text-sm">{{ item.label }}</span>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="
                                item.status === 'bereit'
                                    ? 'bg-emerald-50 text-emerald-800'
                                    : 'bg-slate-100 text-slate-600'
                            "
                        >
                            {{ item.status }}
                        </span>
                    </div>
                </div>
            </section>

            <aside class="rounded-xl border border-blue-200 bg-blue-50 p-6">
                <h2 class="font-semibold text-blue-950">Wichtiger Hinweis</h2>
                <p class="mt-2 text-sm leading-6 text-blue-900">
                    Angezeigte Verbindungen und Statuswerte werden später dokumentierte Informationen darstellen. Ohne
                    echte Messdaten werden keine Begriffe wie „online“, „healthy“ oder „erreichbar“ verwendet.
                </p>
            </aside>
        </div>
    </AppLayout>
</template>
