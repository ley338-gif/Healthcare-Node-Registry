<script setup lang="ts">
import { Boxes, CircleAlert, CircleCheck, CircleHelp } from '@lucide/vue';
import type { NodeProps } from '@vue-flow/core';

export type SystemGroupData = {
    publicId: string;
    name: string;
    systemType: string;
    status: string;
    organization: string | null;
    site: string | null;
    department: string | null;
    nodeCount: number;
    failedCount: number;
    unverifiedCount: number;
};

defineProps<NodeProps<SystemGroupData>>();
</script>

<template>
    <section
        class="h-full w-full overflow-hidden rounded-3xl border-2 border-slate-300 bg-white/65 shadow-sm backdrop-blur-sm"
    >
        <header class="flex items-start justify-between gap-4 border-b border-slate-200 bg-white/90 px-5 py-4">
            <div class="flex min-w-0 items-start gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-900 text-white">
                    <Boxes :size="19" />
                </div>

                <div class="min-w-0">
                    <p class="text-[11px] font-semibold tracking-wide text-blue-600 uppercase">
                        {{ data.systemType }}
                    </p>
                    <h2 class="truncate font-semibold text-slate-950">
                        {{ data.name }}
                    </h2>
                    <p class="mt-1 truncate text-xs text-slate-500">
                        {{
                            [data.organization, data.site, data.department].filter(Boolean).join(' · ') ||
                            'Keine Zuordnung'
                        }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                    {{ data.nodeCount }} Knoten
                </span>

                <CircleAlert v-if="data.failedCount > 0" :size="18" class="text-red-600" />
                <CircleHelp v-else-if="data.unverifiedCount > 0" :size="18" class="text-amber-600" />
                <CircleCheck v-else :size="18" class="text-emerald-600" />
            </div>
        </header>
    </section>
</template>
