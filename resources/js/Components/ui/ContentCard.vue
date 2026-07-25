<script setup lang="ts">
withDefaults(defineProps<{ title?: string; description?: string; padded?: boolean }>(), { padded: true });
</script>
<template>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header
            v-if="title || description || $slots.actions"
            class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4"
        >
            <div>
                <h2 v-if="title" class="font-semibold text-slate-950">{{ title }}</h2>
                <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
            </div>
            <div v-if="$slots.actions"><slot name="actions" /></div>
        </header>
        <div :class="padded ? 'p-5' : ''"><slot /></div>
    </section>
</template>
