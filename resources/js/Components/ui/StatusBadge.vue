<script setup lang="ts">
import { computed } from 'vue';
const props = defineProps<{ status: 'ready' | 'next' | 'planned' | 'later' | 'active' | 'archived'; label: string }>();
const classes = computed(
    () =>
        ({
            ready: 'bg-emerald-50 text-emerald-800 ring-emerald-600/20',
            active: 'bg-emerald-50 text-emerald-800 ring-emerald-600/20',
            next: 'bg-blue-50 text-blue-700 ring-blue-600/20',
            planned: 'bg-slate-100 text-slate-700 ring-slate-500/20',
            later: 'bg-amber-50 text-amber-800 ring-amber-600/20',
            archived: 'bg-slate-100 text-slate-500 ring-slate-500/20',
        })[props.status],
);
</script>
<template>
    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset" :class="classes">{{
        label
    }}</span>
</template>
