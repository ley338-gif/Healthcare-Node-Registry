<script setup lang="ts">
defineProps<{ eyebrow?: string; title: string; description?: string }>();
</script>
<template>
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p v-if="eyebrow" class="mb-2 text-xs font-semibold tracking-wider text-blue-600 uppercase">
                {{ eyebrow }}
            </p>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-950">{{ title }}</h1>
            <p v-if="description" class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2"><slot name="actions" /></div>
    </div>
</template>
