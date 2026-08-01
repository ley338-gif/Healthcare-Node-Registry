<script setup lang="ts">
defineProps<{
    label: string;
    requiredFilled: number;
    requiredTotal: number;
    percentage: number | null;
}>();
</script>

<template>
    <section class="rounded-2xl border border-blue-200 bg-blue-50/60 p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Dokumentationsstand</p>
                <p class="mt-1 text-2xl font-semibold text-blue-950">{{ label }}</p>
                <p v-if="percentage !== null" class="mt-1 text-sm text-blue-800">
                    {{ requiredFilled }} von {{ requiredTotal }} definierten Pflichtfeldern · {{ percentage }} %
                </p>
                <p v-else class="mt-1 text-sm text-blue-800">Für diese Ansicht sind keine Pflichtfelder definiert.</p>
            </div>
            <div v-if="percentage !== null" class="h-2.5 w-full max-w-sm overflow-hidden rounded-full bg-blue-100">
                <div class="h-full rounded-full bg-blue-600 transition-all" :style="{ width: `${percentage}%` }" />
            </div>
        </div>
    </section>
</template>
