<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useRegistryNavigation } from './useRegistryNavigation';
const { primarySections, activeGroup, isActive } = useRegistryNavigation();
</script>
<template>
    <nav
        aria-label="Hauptnavigation mobil"
        class="flex flex-wrap gap-2 border-b border-slate-200 bg-white px-5 py-3 lg:hidden"
    >
        <Link
            href="/"
            class="shrink-0 rounded-lg px-3 py-2 text-sm"
            :class="isActive(['/']) ? 'bg-blue-50 text-blue-700' : 'text-slate-600'"
            >Übersicht</Link
        >
        <template v-for="section in primarySections" :key="section.label">
            <Link
                v-for="item in section.items"
                :key="item.label"
                :href="item.href"
                :aria-current="isActive(item.paths) ? 'page' : undefined"
                class="shrink-0 rounded-lg px-3 py-2 text-sm"
                :class="isActive(item.paths) ? 'bg-blue-50 text-blue-700' : 'text-slate-600'"
                >{{ item.label }}</Link
            >
        </template>
    </nav>
    <nav
        v-if="activeGroup"
        :aria-label="activeGroup.label"
        class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-white px-5 py-3 lg:px-8"
    >
        <span class="mr-3 text-sm font-semibold text-slate-900">{{ activeGroup.label }}</span>
        <Link
            v-for="item in activeGroup.items"
            :key="item.href"
            :href="item.href"
            :aria-current="isActive(item.paths) ? 'page' : undefined"
            class="rounded-lg px-3 py-2 text-sm font-medium"
            :class="isActive(item.paths) ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100'"
            >{{ item.label }}</Link
        >
    </nav>
</template>
