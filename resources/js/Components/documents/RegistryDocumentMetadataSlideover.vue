<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { ref, watch } from 'vue';
import type { RegistryDocumentItem } from './RegistryDocumentList.vue';

const props = defineProps<{
    document: RegistryDocumentItem | null;
    categories: Array<{ value: string; label: string }>;
}>();
const emit = defineEmits<{ close: [] }>();
const tags = ref('');
const form = useForm({
    title: '',
    description: '',
    category: '',
    visibility: 'internal',
    valid_from: '',
    valid_until: '',
    contract_reference: '',
    tags: [] as string[],
});

watch(
    () => props.document,
    (document) => {
        if (!document) return;
        form.title = document.title;
        form.description = document.description ?? '';
        form.category = typeof document.category === 'string' ? document.category : document.category.value;
        form.visibility = document.visibility;
        form.valid_from = document.valid_from ?? '';
        form.valid_until = document.valid_until ?? '';
        form.contract_reference = document.contract_reference ?? '';
        form.tags = document.tags;
        tags.value = document.tags.join(', ');
        form.clearErrors();
    },
);

const close = (): void => {
    if (form.processing) return;
    form.reset();
    form.clearErrors();
    emit('close');
};
const submit = (): void => {
    if (!props.document || form.processing) return;
    form.transform((data) => ({
        ...data,
        tags: tags.value
            .split(',')
            .map((tag) => tag.trim())
            .filter(Boolean),
    })).put(`/registry-documents/${props.document.public_id}`, { preserveScroll: true, onSuccess: close });
};
</script>

<template>
    <Teleport to="body">
        <div v-if="document" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button type="button" class="absolute inset-0 bg-slate-950/40" aria-label="Schließen" @click="close" />
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Dokumentenablage</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-950">Metadaten bearbeiten</h2>
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
                    <div class="grid flex-1 gap-4 overflow-y-auto px-6 py-6 sm:grid-cols-2">
                        <label class="sm:col-span-2"
                            ><span class="text-sm font-medium text-slate-700">Titel *</span
                            ><input
                                v-model="form.title"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            /><span v-if="form.errors.title" class="mt-1 block text-xs text-red-600">{{
                                form.errors.title
                            }}</span></label
                        >
                        <label
                            ><span class="text-sm font-medium text-slate-700">Kategorie *</span
                            ><select
                                v-model="form.category"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            >
                                <option v-for="option in categories" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select></label
                        >
                        <label
                            ><span class="text-sm font-medium text-slate-700">Sichtbarkeit</span
                            ><select
                                v-model="form.visibility"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            >
                                <option value="internal">Intern</option>
                                <option value="restricted">Eingeschränkt</option>
                            </select></label
                        >
                        <label class="sm:col-span-2"
                            ><span class="text-sm font-medium text-slate-700">Beschreibung</span
                            ><textarea
                                v-model="form.description"
                                rows="4"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            />
                        </label>
                        <label
                            ><span class="text-sm font-medium text-slate-700">Gültig ab</span
                            ><input
                                v-model="form.valid_from"
                                type="date"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        /></label>
                        <label
                            ><span class="text-sm font-medium text-slate-700">Gültig bis</span
                            ><input
                                v-model="form.valid_until"
                                type="date"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            /><span v-if="form.errors.valid_until" class="mt-1 block text-xs text-red-600">{{
                                form.errors.valid_until
                            }}</span></label
                        >
                        <label
                            ><span class="text-sm font-medium text-slate-700">Vertragsreferenz</span
                            ><input
                                v-model="form.contract_reference"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        /></label>
                        <label
                            ><span class="text-sm font-medium text-slate-700">Schlagwörter</span
                            ><input
                                v-model="tags"
                                placeholder="Kommagetrennt"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        /></label>
                    </div>
                    <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            :disabled="form.processing"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold"
                            @click="close"
                        >
                            Abbrechen</button
                        ><button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            Speichern
                        </button>
                    </footer>
                </form>
            </aside>
        </div>
    </Teleport>
</template>
