<script setup lang="ts">
import { ArrowUpRight } from '@lucide/vue';
import type { Component } from 'vue';
defineProps<{
    label: string;
    value: number | string;
    detail?: string;
    href?: string;
    icon: Component;
    disabled?: boolean;
}>();
</script>
<template>
    <component
        :is="href && !disabled ? 'a' : 'div'"
        :href="href && !disabled ? href : undefined"
        class="group block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition"
        :class="
            disabled ? 'cursor-not-allowed opacity-70' : 'hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md'
        "
    >
        <div class="mb-5 flex items-start justify-between">
            <div class="grid h-11 w-11 place-items-center rounded-xl bg-blue-50 text-blue-600">
                <component :is="icon" :size="21" />
            </div>
            <ArrowUpRight :size="17" class="text-slate-300" />
        </div>
        <p class="text-3xl font-semibold tracking-tight text-slate-950">{{ value }}</p>
        <p class="mt-2 text-sm font-medium text-slate-800">{{ label }}</p>
        <p v-if="detail" class="mt-1 text-xs text-slate-500">{{ detail }}</p>
    </component>
</template>
