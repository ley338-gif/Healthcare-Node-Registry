<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Boxes,
    Building2,
    Cable,
    FileText,
    LayoutDashboard,
    Network,
    Search,
    Settings,
    ShieldCheck,
} from '@lucide/vue';
import type { PageProps } from '../../types';

const page = usePage<PageProps>();

const isActive = (paths: string[]) =>
    paths.some((path) => (path === '/' ? page.url === '/' : page.url.startsWith(path)));

const items = [
    { label: 'Dashboard', href: '/', paths: ['/'], icon: LayoutDashboard, enabled: true },
    {
        label: 'Organisationsstruktur',
        href: '/structure',
        paths: ['/structure', '/organizations', '/sites', '/departments'],
        icon: Building2,
        enabled: true,
    },
    { label: 'Systeme', href: '#', paths: [], icon: Boxes, enabled: false, badge: 'nächster Sprint' },
    { label: 'Verbindungen', href: '#', paths: [], icon: Cable, enabled: false, badge: 'geplant' },
    { label: 'Topologie', href: '#', paths: [], icon: Network, enabled: false, badge: 'geplant' },
    { label: 'Dokumentation', href: '#', paths: [], icon: FileText, enabled: false, badge: 'geplant' },
    { label: 'Monitoring', href: '#', paths: [], icon: Activity, enabled: false, badge: 'geplant' },
    { label: 'Suche', href: '#', paths: [], icon: Search, enabled: false, badge: 'geplant' },
    { label: 'Audit', href: '#', paths: [], icon: ShieldCheck, enabled: false, badge: 'geplant' },
    { label: 'Einstellungen', href: '#', paths: [], icon: Settings, enabled: false, badge: 'geplant' },
];
</script>

<template>
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 border-r border-slate-800 bg-slate-950 lg:block">
        <div class="flex h-20 items-center gap-3 border-b border-slate-800 px-5 text-white">
            <div class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-sm font-semibold">HN</div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold">Healthcare Node Registry</p>
                <p class="text-xs text-slate-400">Infrastructure Control Center</p>
            </div>
        </div>

        <nav class="space-y-1 p-4">
            <template v-for="item in items" :key="item.label">
                <Link
                    v-if="item.enabled"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium"
                    :class="isActive(item.paths) ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-900'"
                >
                    <component :is="item.icon" :size="18" />
                    {{ item.label }}
                </Link>
                <div
                    v-else
                    class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-500"
                >
                    <component :is="item.icon" :size="18" />
                    {{ item.label }}
                    <span class="ml-auto text-[9px] tracking-wide uppercase">{{ item.badge }}</span>
                </div>
            </template>
        </nav>

        <div class="absolute inset-x-4 bottom-4">
            <div class="rounded-xl border border-slate-800 p-3 text-xs text-slate-400">
                <p class="font-medium text-slate-300">Version 0.2.0</p>
                <p class="mt-1">Dokumentation, keine aktive Systemsteuerung</p>
            </div>
        </div>
    </aside>
</template>
