<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    description?: string;
    icon: Component;
}>();
</script>

<template>
    <div class="px-5 py-12 text-center">
        <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400">
            <component :is="icon" :size="23" />
        </div>

        <p class="mt-4 font-medium text-slate-900">
            {{ title }}
        </p>

        <p v-if="description" class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">
            {{ description }}
        </p>

        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
