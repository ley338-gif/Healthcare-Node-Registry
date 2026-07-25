<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}>();

const displayLabel = (label: string): string => {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace('Previous', 'Zurück')
        .replace('Next', 'Weiter');
};
</script>

<template>
    <nav class="flex flex-wrap gap-1" aria-label="Seitennavigation">
        <template v-for="link in links" :key="`${link.label}-${link.url}`">
            <Link
                v-if="link.url"
                :href="link.url"
                class="rounded-md border px-3 py-1.5 text-sm"
                :class="
                    link.active
                        ? 'border-blue-600 bg-blue-600 text-white'
                        : 'border-slate-300 bg-white hover:bg-slate-50'
                "
            >
                {{ displayLabel(link.label) }}
            </Link>

            <span
                v-else
                class="cursor-not-allowed rounded-md border border-slate-200 px-3 py-1.5 text-sm text-slate-400"
            >
                {{ displayLabel(link.label) }}
            </span>
        </template>
    </nav>
</template>
