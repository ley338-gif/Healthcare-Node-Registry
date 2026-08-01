<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileText, LoaderCircle, X } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    open: boolean;
    document: { public_id: string; title: string } | null;
}>();

const emit = defineEmits<{ close: [] }>();
const input = ref<HTMLInputElement | null>(null);
const form = useForm({ file: null as File | null, change_note: '' });
const progress = computed(() => form.progress?.percentage ?? 0);

const choose = (event: Event): void => {
    const target = event.target;
    form.file = target instanceof HTMLInputElement ? (target.files?.[0] ?? null) : null;
};

const close = (): void => {
    if (form.processing) return;
    form.reset();
    form.clearErrors();
    if (input.value) input.value.value = '';
    emit('close');
};

const submit = (): void => {
    if (!props.document || form.processing) return;
    form.post(`/registry-documents/${props.document.public_id}/versions`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: close,
    });
};
</script>

<template>
    <Teleport to="body">
        <div v-if="open && document" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button type="button" class="absolute inset-0 bg-slate-950/40" aria-label="Schließen" @click="close" />
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Dokumentversion</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-950">Neue Version hochladen</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ document.title }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 hover:bg-slate-100"
                        aria-label="Schließen"
                        @click="close"
                    >
                        <X :size="20" />
                    </button>
                </header>
                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Datei *</span>
                            <input
                                ref="input"
                                type="file"
                                accept=".pdf,.png,.jpg,.jpeg,.docx,.xlsx,.txt,.zip"
                                class="mt-2 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-700"
                                @change="choose"
                            />
                            <span v-if="form.errors.file" class="mt-1 block text-xs text-red-600">{{
                                form.errors.file
                            }}</span>
                        </label>
                        <div v-if="form.file" class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                            <FileText :size="20" class="text-blue-600" />
                            <p class="truncate text-sm font-medium">{{ form.file.name }}</p>
                        </div>
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Änderungsnotiz *</span>
                            <textarea
                                v-model="form.change_note"
                                rows="4"
                                placeholder="Was wurde geändert?"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            />
                            <span v-if="form.errors.change_note" class="mt-1 block text-xs text-red-600">
                                {{ form.errors.change_note }}
                            </span>
                        </label>
                        <div v-if="form.processing" class="rounded-xl bg-blue-50 p-3">
                            <p class="flex items-center gap-2 text-sm text-blue-800">
                                <LoaderCircle :size="17" class="animate-spin" />Upload und Sicherheitsprüfung
                            </p>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-blue-100">
                                <div class="h-full bg-blue-600" :style="{ width: `${progress}%` }" />
                            </div>
                        </div>
                    </div>
                    <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            :disabled="form.processing"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold"
                            @click="close"
                        >
                            Abbrechen
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.file"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            Version hochladen
                        </button>
                    </footer>
                </form>
            </aside>
        </div>
    </Teleport>
</template>
