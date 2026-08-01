<script setup lang="ts">
import { computed } from 'vue';
import type { DocumentationStatus } from './documentationTypes';

const props = defineProps<{ status: DocumentationStatus }>();
const presentation = computed(
    () =>
        ({
            empty: { label: 'Nicht gepflegt', classes: 'bg-slate-100 text-slate-600 ring-slate-500/20' },
            partial: { label: 'Teilweise gepflegt', classes: 'bg-amber-50 text-amber-800 ring-amber-600/20' },
            complete: { label: 'Vollständig', classes: 'bg-emerald-50 text-emerald-800 ring-emerald-600/20' },
        })[props.status],
);
</script>

<template>
    <span
        class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
        :class="presentation.classes"
    >
        {{ presentation.label }}
    </span>
</template>
