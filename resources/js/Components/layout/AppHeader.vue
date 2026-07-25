<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CircleHelp, LogOut, Search } from '@lucide/vue';
import type { PageProps } from '../../types';
const page = usePage<PageProps>();
</script>
<template>
    <header
        class="sticky top-0 z-20 flex min-h-20 items-center gap-5 border-b border-slate-200 bg-white/95 px-5 backdrop-blur lg:px-8"
    >
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-slate-900">Zentrale Systemdokumentation</p>
            <p class="text-xs text-slate-500">On-Premise · revisionsorientiert</p>
        </div>
        <div class="hidden max-w-xl flex-1 md:block">
            <div class="relative">
                <Search class="absolute top-3 left-3 text-slate-400" :size="18" /><input
                    disabled
                    type="search"
                    placeholder="Globale Suche folgt mit der System Registry"
                    class="w-full cursor-not-allowed rounded-xl border border-slate-300 bg-slate-50 py-2.5 pr-3 pl-10 text-sm text-slate-500"
                />
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button disabled class="hidden rounded-lg border border-slate-300 p-2.5 text-slate-400 sm:block">
                <CircleHelp :size="18" />
            </button>
            <div class="hidden text-right xl:block">
                <p class="text-sm font-medium text-slate-900">{{ page.props.auth.user?.name }}</p>
                <p class="text-xs text-slate-500">{{ page.props.auth.user?.email }}</p>
            </div>
            <Link
                href="/logout"
                method="post"
                as="button"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-medium hover:bg-slate-50"
                ><LogOut :size="16" /><span class="hidden sm:inline">Abmelden</span></Link
            >
        </div>
    </header>
</template>
