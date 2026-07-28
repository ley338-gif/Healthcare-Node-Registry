<script setup lang="ts">
import { Activity, CircleDot } from '@lucide/vue';

type Change = {
    event_type: string;
    label: string;
    subject_type: string;
    subject_public_id: string | null;
    subject_label: string | null;
    occurred_at: string | null;
};

defineProps<{ changes: Change[] }>();

const formatDate = (value: string | null): string => {
    if (value === null) return 'Zeitpunkt unbekannt';

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
};
</script>

<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex items-center gap-3">
            <div class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-600">
                <Activity :size="19" />
            </div>
            <div>
                <h2 class="font-semibold text-slate-950">Letzte Änderungen</h2>
                <p class="text-sm text-slate-500">Verständlich aufbereitete Audit-Ereignisse.</p>
            </div>
        </div>

        <div v-if="changes.length === 0" class="py-10 text-center text-sm text-slate-500">
            Noch keine Änderungen vorhanden.
        </div>

        <div v-else class="divide-y divide-slate-100">
            <article
                v-for="change in changes"
                :key="`${change.event_type}-${change.occurred_at}-${change.subject_public_id}`"
                class="flex gap-3 py-3.5"
            >
                <CircleDot :size="16" class="mt-1 shrink-0 text-blue-600" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">{{ change.label }}</p>
                    <p v-if="change.subject_label" class="mt-0.5 truncate text-sm text-slate-600">
                        {{ change.subject_label }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">{{ formatDate(change.occurred_at) }}</p>
                </div>
            </article>
        </div>
    </section>
</template>
