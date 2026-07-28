<script setup lang="ts">
import { CheckCircle2, Circle, ListChecks } from '@lucide/vue';

type Task = {
    label: string;
    completed: boolean;
    href: string;
};

defineProps<{ tasks: Task[] }>();
</script>

<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex items-center gap-3">
            <div class="grid h-10 w-10 place-items-center rounded-xl bg-violet-50 text-violet-600">
                <ListChecks :size="19" />
            </div>
            <div>
                <h2 class="font-semibold text-slate-950">Registry-Aufgaben</h2>
                <p class="text-sm text-slate-500">Nächste sinnvolle Schritte im Datenbestand.</p>
            </div>
        </div>

        <div class="space-y-2">
            <a
                v-for="task in tasks"
                :key="task.label"
                :href="task.href"
                class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-slate-50"
            >
                <CheckCircle2 v-if="task.completed" :size="19" class="shrink-0 text-emerald-600" />
                <Circle v-else :size="19" class="shrink-0 text-slate-300" />
                <span
                    class="text-sm"
                    :class="task.completed ? 'text-slate-500 line-through' : 'font-medium text-slate-800'"
                >
                    {{ task.label }}
                </span>
            </a>
        </div>
    </section>
</template>
