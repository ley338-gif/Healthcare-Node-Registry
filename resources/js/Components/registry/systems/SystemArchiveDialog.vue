<script setup lang="ts">
import { Archive, X } from '@lucide/vue';

defineProps<{
    open: boolean;
    systemName: string;
    processing: boolean;
}>();

const emit = defineEmits<{
    close: [];
    confirm: [];
}>();
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[60] grid place-items-center px-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="archive-system-title"
        >
            <button
                type="button"
                aria-label="Dialog schließen"
                class="absolute inset-0 bg-slate-950/45"
                @click="emit('close')"
            />

            <section class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div class="flex gap-3">
                        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-700">
                            <Archive :size="20" />
                        </div>

                        <div>
                            <h2 id="archive-system-title" class="font-semibold text-slate-950">System archivieren</h2>

                            <p class="mt-1 text-sm text-slate-500">Das System verschwindet aus der aktiven Registry.</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        aria-label="Dialog schließen"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                        @click="emit('close')"
                    >
                        <X :size="18" />
                    </button>
                </header>

                <div class="px-6 py-5">
                    <p class="text-sm leading-6 text-slate-700">
                        Soll
                        <strong class="font-semibold text-slate-950">
                            {{ systemName }}
                        </strong>
                        wirklich archiviert werden?
                    </p>

                    <p class="mt-3 text-sm text-slate-500">
                        Der Datensatz wird nicht gelöscht und bleibt für Audit und Historie erhalten.
                    </p>
                </div>

                <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button
                        type="button"
                        :disabled="processing"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                        @click="emit('close')"
                    >
                        Abbrechen
                    </button>

                    <button
                        type="button"
                        :disabled="processing"
                        class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700 disabled:cursor-wait disabled:opacity-60"
                        @click="emit('confirm')"
                    >
                        {{ processing ? 'Wird archiviert …' : 'System archivieren' }}
                    </button>
                </footer>
            </section>
        </div>
    </Teleport>
</template>
