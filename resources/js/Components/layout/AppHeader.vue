<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, CircleHelp, FileSearch, LoaderCircle, LogOut, Search, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import type { PageProps } from '../../types';
const page = usePage<PageProps>();
type SearchResult = { group: string; type: string; title: string; subtitle: string; url: string };
const query = ref('');
const results = ref<SearchResult[]>([]);
const loading = ref(false);
const open = ref(false);
const activeIndex = ref(-1);
let timer: number | undefined;
let request: AbortController | undefined;
const groupedResults = computed(() =>
    results.value.reduce<Record<string, SearchResult[]>>((groups, result) => {
        (groups[result.group] ??= []).push(result);
        return groups;
    }, {}),
);
const runSearch = (): void => {
    window.clearTimeout(timer);
    request?.abort();
    const term = query.value.trim();
    activeIndex.value = -1;
    if (term.length < 2) {
        results.value = [];
        loading.value = false;
        return;
    }
    loading.value = true;
    timer = window.setTimeout(async () => {
        request = new AbortController();
        try {
            const response = await fetch(`/search?q=${encodeURIComponent(term)}`, {
                headers: { Accept: 'application/json' },
                signal: request.signal,
            });
            if (!response.ok) throw new Error('Search request failed');
            const payload = (await response.json()) as { results: SearchResult[] };
            results.value = payload.results;
        } catch (error) {
            if (!(error instanceof DOMException && error.name === 'AbortError')) results.value = [];
        } finally {
            loading.value = false;
        }
    }, 250);
};
const flatResults = computed(() => Object.values(groupedResults.value).flat());
const moveSelection = (direction: number): void => {
    if (flatResults.value.length === 0) return;
    activeIndex.value = (activeIndex.value + direction + flatResults.value.length) % flatResults.value.length;
};
const openSelection = (): void => {
    const result = flatResults.value[activeIndex.value];
    if (result) window.location.assign(result.url);
};
const close = (): void => {
    open.value = false;
    activeIndex.value = -1;
};
const clear = (): void => {
    query.value = '';
    results.value = [];
    close();
};
onBeforeUnmount(() => {
    window.clearTimeout(timer);
    request?.abort();
});
</script>
<template>
    <header
        class="sticky top-0 z-20 flex min-h-20 items-center gap-5 border-b border-slate-200 bg-white/95 px-5 backdrop-blur lg:px-8"
    >
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-slate-900">Zentrale Systemdokumentation</p>
            <p class="text-xs text-slate-500">On-Premise · revisionsorientiert</p>
        </div>
        <div class="relative z-30 hidden max-w-xl flex-1 md:block" @focusin="open = true">
            <div class="relative">
                <Search class="absolute top-3 left-3 text-slate-400" :size="18" />
                <input
                    v-model="query"
                    type="search"
                    autocomplete="off"
                    placeholder="Registry, DICOM, Dokumente und Tests durchsuchen"
                    class="w-full rounded-xl border border-slate-300 bg-slate-50 py-2.5 pr-10 pl-10 text-sm text-slate-800 transition focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-50 focus:outline-none"
                    @input="runSearch"
                    @keydown.down.prevent="moveSelection(1)"
                    @keydown.up.prevent="moveSelection(-1)"
                    @keydown.enter.prevent="openSelection"
                    @keydown.esc="close"
                />
                <LoaderCircle v-if="loading" class="absolute top-3 right-3 animate-spin text-blue-500" :size="18" />
                <button
                    v-else-if="query"
                    type="button"
                    class="absolute top-2 right-2 rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Suche leeren"
                    @click="clear"
                >
                    <X :size="18" />
                </button>
            </div>
            <div
                v-if="open && query.trim().length >= 2"
                class="absolute top-full right-0 left-0 z-50 mt-2 max-h-[70vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl"
            >
                <template v-if="results.length">
                    <section v-for="(items, group) in groupedResults" :key="group" class="mb-2 last:mb-0">
                        <p class="px-3 py-2 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">
                            {{ group }}
                        </p>
                        <Link
                            v-for="item in items"
                            :key="`${item.type}-${item.url}`"
                            :href="item.url"
                            class="group flex items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-blue-50"
                            :class="flatResults[activeIndex]?.url === item.url ? 'bg-blue-50' : ''"
                            @click="clear"
                        >
                            <span
                                class="grid size-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-blue-600"
                            >
                                <FileSearch :size="17" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="truncate text-sm font-semibold text-slate-900">{{ item.title }}</span>
                                    <span
                                        class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500"
                                        >{{ item.type }}</span
                                    >
                                </span>
                                <span class="block truncate text-xs text-slate-500">{{ item.subtitle }}</span>
                            </span>
                            <ArrowRight :size="16" class="shrink-0 text-slate-300 group-hover:text-blue-600" />
                        </Link>
                    </section>
                </template>
                <div v-else-if="!loading" class="px-4 py-8 text-center">
                    <FileSearch :size="24" class="mx-auto text-slate-300" />
                    <p class="mt-2 text-sm font-medium text-slate-700">Keine Treffer</p>
                    <p class="mt-1 text-xs text-slate-400">
                        Prüfe den Suchbegriff oder verwende mindestens zwei Zeichen.
                    </p>
                </div>
            </div>
            <button
                v-if="open"
                type="button"
                class="fixed inset-0 -z-10 cursor-default"
                aria-label="Suche schließen"
                @click="close"
            />
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
