<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Boxes,
    Building2,
    Cable,
    FileText,
    FlaskConical,
    LayoutDashboard,
    Map,
    Radar,
    TableProperties,
    Settings,
    ShieldCheck,
} from '@lucide/vue';
import type { PageProps } from '../../types';
const page = usePage<PageProps>();
const canViewAudit = ((page.props.auth as { permissions?: string[] })?.permissions ?? []).includes('audit.view');
const canViewConnections = page.props.auth.permissions.some((permission) =>
    ['registry.view', 'registry.manage'].includes(permission),
);
const canViewDiscovery = page.props.auth.permissions.some((permission) =>
    ['discovery.view', 'discovery.run', 'discovery.manage'].includes(permission),
);
const canViewSettings = page.props.auth.permissions.some((permission) =>
    ['users.manage', 'roles.manage', 'settings.manage'].includes(permission),
);
const isActive = (paths: string[]) =>
    paths.some((path) => (path === '/' ? page.url === '/' : page.url.startsWith(path)));
const groups = [
    {
        label: 'Registry',
        items: [
            {
                label: 'Organisationsstruktur',
                href: '/structure',
                paths: ['/structure', '/organizations', '/sites', '/departments'],
                icon: Building2,
                enabled: true,
            },
            {
                label: 'Systeme',
                href: '/systems',
                paths: ['/systems'],
                icon: Boxes,
                enabled: true,
            },
            {
                label: 'Dokumente',
                href: '/documents',
                paths: ['/documents'],
                icon: FileText,
                enabled: true,
            },
        ],
    },
    {
        label: 'Erkennung',
        items: [
            {
                label: 'Discovery',
                href: '/discovery',
                paths: ['/discovery'],
                icon: Radar,
                enabled: canViewDiscovery,
            },
        ],
    },
    {
        label: 'Kommunikation',
        items: [
            {
                label: 'Topologie',
                href: '/network',
                paths: ['/network'],
                icon: Map,
                enabled: true,
            },
            {
                label: 'Verbindungen',
                href: '/connections',
                paths: ['/connections'],
                icon: Cable,
                enabled: canViewConnections,
            },
            {
                label: 'Firewall-Matrix',
                href: '/reports/firewall-matrix',
                paths: ['/reports/firewall-matrix'],
                icon: TableProperties,
                enabled: canViewConnections,
            },
            {
                label: 'Tests',
                href: '/tests',
                paths: ['/tests'],
                icon: FlaskConical,
                enabled: true,
            },
        ],
    },
    {
        label: 'Administration',
        items: [
            {
                label: 'Audit',
                href: '/audit',
                paths: ['/audit'],
                icon: ShieldCheck,
                enabled: canViewAudit,
            },
            {
                label: 'Einstellungen',
                href: '/settings',
                paths: ['/settings'],
                icon: Settings,
                enabled: canViewSettings,
            },
        ],
    },
];
</script>
<template>
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 border-r border-slate-800 bg-slate-950 lg:block">
        <div class="flex h-20 items-center gap-3 border-b border-slate-800 px-5 text-white">
            <div class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-sm font-semibold">HN</div>
            <div>
                <p class="text-sm font-semibold">Healthcare Node Registry</p>

                <p class="text-xs text-slate-400">Healthcare Infrastructure Registry</p>
            </div>
        </div>
        <nav class="space-y-6 p-4">
            <Link
                href="/"
                class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium"
                :class="isActive(['/']) ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-900'"
                ><LayoutDashboard :size="18" />Dashboard</Link
            >
            <section v-for="group in groups" :key="group.label">
                <p class="mb-1 px-3 py-2 text-[11px] font-semibold tracking-[0.14em] text-slate-500 uppercase">
                    {{ group.label }}
                </p>
                <div class="space-y-1">
                    <template v-for="item in group.items" :key="item.label"
                        ><Link
                            v-if="item.enabled"
                            :href="item.href"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium"
                            :class="
                                isActive(item.paths) ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900'
                            "
                            ><component :is="item.icon" :size="18" />{{ item.label }}</Link
                        >
                        <div
                            v-else
                            class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-500"
                        >
                            <component :is="item.icon" :size="18" />{{ item.label
                            }}<span v-if="'badge' in item" class="ml-auto text-[9px] tracking-wide uppercase">
                                {{ item.badge }}
                            </span>
                        </div></template
                    >
                </div>
            </section>
        </nav>
        <div class="absolute inset-x-4 bottom-4">
            <div class="rounded-xl border border-slate-800 p-3 text-xs text-slate-400">
                <div class="mb-2 flex items-center gap-2">
                    <Settings :size="15" />
                    Version 0.3.0-dev
                </div>

                <p>Healthcare Node Registry</p>

                <p class="mt-1 leading-5 text-slate-500">Registry · DICOM · Dokumente · Tests · Audit</p>
            </div>
        </div>
    </aside>
</template>
