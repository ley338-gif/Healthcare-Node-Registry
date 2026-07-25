<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Boxes,
    Building2,
    Cable,
    ChevronDown,
    CircleHelp,
    FileText,
    LayoutDashboard,
    LogOut,
    Network,
    Search,
    Settings,
    ShieldCheck,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import type { PageProps } from '../types';

const page = usePage<PageProps>();
const registryOpen = ref(true);

const active = (href: string) => (href === '/' ? page.url === '/' : page.url.startsWith(href));

const structureActive = computed(() =>
    ['/structure', '/organizations', '/sites', '/departments'].some((path) => page.url.startsWith(path)),
);

const registryItems = [
    { label: 'Organisationsstruktur', href: '/structure', icon: Building2, enabled: true },
    { label: 'Systeme', href: '#', icon: Boxes, enabled: false, badge: 'nächster Sprint' },
    { label: 'Verbindungen', href: '#', icon: Cable, enabled: false, badge: 'geplant' },
    { label: 'Topologie', href: '#', icon: Network, enabled: false, badge: 'geplant' },
];

const secondaryItems = [
    { label: 'Dokumentation', icon: FileText },
    { label: 'Monitoring', icon: Activity },
    { label: 'Audit', icon: ShieldCheck },
];
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <aside class="fixed inset-y-0 left-0 hidden w-72 border-r border-slate-800 bg-slate-950 lg:block">
            <div class="flex h-20 items-center gap-3 border-b border-slate-800 px-5 text-white">
                <div class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 font-semibold">HN</div>
                <div>
                    <p class="text-sm font-semibold">Healthcare Node Registry</p>
                    <p class="text-xs text-slate-400">Registry Core</p>
                </div>
            </div>

            <nav class="space-y-5 p-4">
                <Link
                    href="/"
                    class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium"
                    :class="active('/') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-900'"
                >
                    <LayoutDashboard :size="18" />
                    Dashboard
                </Link>

                <section>
                    <button
                        type="button"
                        class="mb-1 flex w-full items-center justify-between px-3 py-2 text-xs font-semibold tracking-wider text-slate-500 uppercase"
                        @click="registryOpen = !registryOpen"
                    >
                        Registry
                        <ChevronDown :size="15" :class="{ 'rotate-180': !registryOpen }" />
                    </button>

                    <div v-show="registryOpen" class="space-y-1">
                        <template v-for="item in registryItems" :key="item.label">
                            <Link
                                v-if="item.enabled"
                                :href="item.href"
                                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium"
                                :class="
                                    structureActive ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900'
                                "
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
                    </div>
                </section>

                <section>
                    <p class="px-3 py-2 text-xs font-semibold tracking-wider text-slate-500 uppercase">Betrieb</p>
                    <div class="space-y-1">
                        <div
                            v-for="item in secondaryItems"
                            :key="item.label"
                            class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-500"
                        >
                            <component :is="item.icon" :size="18" />
                            {{ item.label }}
                            <span class="ml-auto text-[9px] tracking-wide uppercase">geplant</span>
                        </div>
                    </div>
                </section>
            </nav>

            <div class="absolute inset-x-4 bottom-4">
                <div class="rounded-xl border border-slate-800 p-3 text-xs text-slate-400">
                    <div class="mb-2 flex items-center gap-2">
                        <Settings :size="15" />
                        Version 0.2.0-dev
                    </div>
                    Dokumentation, keine Live-Messung
                </div>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header
                class="sticky top-0 z-20 flex min-h-20 items-center gap-5 border-b border-slate-200 bg-white/95 px-5 backdrop-blur lg:px-8"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">Zentrale Systemdokumentation</p>
                    <p class="text-xs text-slate-500">On-Premise · revisionsorientiert</p>
                </div>

                <div class="hidden max-w-xl flex-1 md:block">
                    <div class="relative">
                        <Search class="absolute top-3 left-3 text-slate-400" :size="18" />
                        <input
                            disabled
                            type="search"
                            placeholder="Globale Suche nach Systemen, AE Titles und IP-Adressen folgt"
                            class="w-full cursor-not-allowed rounded-xl border border-slate-300 bg-slate-50 py-2.5 pr-3 pl-10 text-sm text-slate-500"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button disabled class="hidden rounded-lg border border-slate-300 p-2.5 text-slate-500 sm:block">
                        <CircleHelp :size="18" />
                    </button>
                    <div class="hidden text-right xl:block">
                        <p class="text-sm font-medium">{{ page.props.auth.user?.name }}</p>
                        <p class="text-xs text-slate-500">{{ page.props.auth.user?.email }}</p>
                    </div>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-medium hover:bg-slate-50"
                    >
                        <LogOut :size="16" />
                        <span class="hidden sm:inline">Abmelden</span>
                    </Link>
                </div>
            </header>

            <main class="p-5 lg:p-8">
                <div
                    v-if="page.props.flash.success"
                    class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
                >
                    {{ page.props.flash.success }}
                </div>
                <div
                    v-if="page.props.flash.error"
                    class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
                >
                    {{ page.props.flash.error }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
