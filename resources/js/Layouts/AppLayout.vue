<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Activity, Boxes, Cable, FileText, LayoutDashboard, LogOut, Network, Settings } from '@lucide/vue';
import type { PageProps } from '../types';

const page = usePage<PageProps>();

const navigation = [
    { label: 'Dashboard', href: '/', icon: LayoutDashboard, enabled: true },
    { label: 'Systeme', href: '#', icon: Boxes, enabled: false },
    { label: 'Endpunkte', href: '#', icon: Cable, enabled: false },
    { label: 'Topologie', href: '#', icon: Network, enabled: false },
    { label: 'Dokumente', href: '#', icon: FileText, enabled: false },
    { label: 'Audit', href: '#', icon: Activity, enabled: false },
];
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-slate-200 bg-slate-950 lg:block">
            <div class="flex h-16 items-center gap-3 border-b border-slate-800 px-5 text-white">
                <div class="grid h-9 w-9 place-items-center rounded-lg bg-blue-600 font-semibold">HN</div>
                <div>
                    <p class="text-sm font-semibold">Healthcare Registry</p>
                    <p class="text-xs text-slate-400">Technical Foundation</p>
                </div>
            </div>

            <nav class="space-y-1 p-3">
                <template v-for="item in navigation" :key="item.label">
                    <Link
                        v-if="item.enabled"
                        :href="item.href"
                        class="flex items-center gap-3 rounded-lg bg-slate-800 px-3 py-2.5 text-sm font-medium text-white"
                    >
                        <component :is="item.icon" :size="18" />
                        {{ item.label }}
                    </Link>
                    <div
                        v-else
                        class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-500"
                        :title="`${item.label} folgt in einem späteren Release`"
                    >
                        <component :is="item.icon" :size="18" />
                        {{ item.label }}
                        <span class="ml-auto text-[10px] uppercase tracking-wide">geplant</span>
                    </div>
                </template>
            </nav>

            <div class="absolute inset-x-3 bottom-3">
                <div class="rounded-lg border border-slate-800 p-3 text-xs text-slate-400">
                    <div class="mb-2 flex items-center gap-2">
                        <Settings :size="15" />
                        Version 0.1.0
                    </div>
                    Keine Live-Monitoringdaten
                </div>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-5 backdrop-blur">
                <div>
                    <p class="text-sm font-medium text-slate-900">Zentrale Systemdokumentation</p>
                    <p class="text-xs text-slate-500">On-Premise · ohne verpflichtende Telemetrie</p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium">{{ page.props.auth.user?.name }}</p>
                        <p class="text-xs text-slate-500">{{ page.props.auth.user?.email }}</p>
                    </div>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50"
                    >
                        <LogOut :size="16" />
                        Abmelden
                    </Link>
                </div>
            </header>

            <main class="p-5 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
