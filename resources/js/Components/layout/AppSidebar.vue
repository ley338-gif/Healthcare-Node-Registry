<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutDashboard } from '@lucide/vue';
import { useRegistryNavigation } from './useRegistryNavigation';
const { primarySections, isActive } = useRegistryNavigation();
</script>
<template>
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 flex-col border-r border-slate-800 bg-slate-950 lg:flex">
        <div class="flex h-20 shrink-0 items-center gap-3 border-b border-slate-800 px-5 text-white">
            <div class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-sm font-semibold">HN</div>
            <div>
                <p class="text-sm font-semibold">Healthcare Node Registry</p>
                <p class="text-xs text-slate-400">Systeme · DICOM · Dokumentation</p>
            </div>
        </div>
        <nav aria-label="Hauptnavigation" class="flex-1 space-y-2 overflow-y-auto p-4">
            <Link
                href="/"
                :aria-current="isActive(['/']) ? 'page' : undefined"
                class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium"
                :class="isActive(['/']) ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-900'"
                ><LayoutDashboard :size="18" />Übersicht</Link
            >
            <section v-for="section in primarySections" :key="section.label" class="pt-4">
                <p
                    v-if="section.label"
                    class="mb-2 px-3 text-[11px] font-semibold tracking-wider text-slate-500 uppercase"
                >
                    {{ section.label }}
                </p>
                <div class="space-y-1">
                    <Link
                        v-for="item in section.items"
                        :key="item.label"
                        :href="item.href"
                        :aria-current="isActive(item.paths) ? 'page' : undefined"
                        class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium"
                        :class="isActive(item.paths) ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900'"
                        ><component :is="item.icon" :size="18" />{{ item.label }}</Link
                    >
                </div>
            </section>
        </nav>
        <p class="border-t border-slate-800 p-5 text-xs leading-5 text-slate-500">
            Zentrale Registry für Healthcare-IT<br />PACS, RIS, Modalitäten und ihre Kommunikation
        </p>
    </aside>
</template>
