<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import type { DocumentationSection } from './documentationTypes';

type DocumentationForm = {
    documentation_type: string;
    section: string;
    title: string;
    content: string | null;
    structured_data: Record<string, string>;
    visibility: 'internal' | 'restricted';
};

defineProps<{
    section: DocumentationSection | null;
    form: InertiaForm<DocumentationForm>;
}>();
const emit = defineEmits<{
    close: [];
    save: [];
    fieldChange: [key: string, value: string];
    visibilityChange: [value: 'internal' | 'restricted'];
}>();

const errorFor = (form: InertiaForm<DocumentationForm>, key: string): string | undefined =>
    form.errors[`structured_data.${key}` as keyof DocumentationForm];
const fieldChanged = (key: string, event: Event): void => {
    const target = event.target;
    if (
        target instanceof HTMLInputElement ||
        target instanceof HTMLTextAreaElement ||
        target instanceof HTMLSelectElement
    ) {
        emit('fieldChange', key, target.value);
    }
};
const visibilityChanged = (event: Event): void => {
    const target = event.target;
    if (target instanceof HTMLSelectElement && (target.value === 'internal' || target.value === 'restricted')) {
        emit('visibilityChange', target.value);
    }
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="section"
            class="fixed inset-0 z-50"
            role="dialog"
            aria-modal="true"
            aria-labelledby="documentation-editor-title"
        >
            <button
                type="button"
                aria-label="Formular schließen"
                class="absolute inset-0 bg-slate-950/40"
                @click="emit('close')"
            />
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">
                            Dokumentation bearbeiten
                        </p>
                        <h2 id="documentation-editor-title" class="mt-1 text-xl font-semibold text-slate-950">
                            {{ section.title }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">{{ section.description }}</p>
                    </div>
                    <button
                        type="button"
                        aria-label="Formular schließen"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                        @click="emit('close')"
                    >
                        <X :size="20" />
                    </button>
                </header>

                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="emit('save')">
                    <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
                        <section>
                            <h3 class="text-sm font-semibold text-slate-950">Angaben</h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Pflichtfelder sind mit einem Stern gekennzeichnet.
                            </p>
                            <div class="mt-4 space-y-4">
                                <label v-for="field in section.fields" :key="field.key" class="block">
                                    <span class="text-sm font-medium text-slate-700">
                                        {{ field.label }} <span v-if="field.required" class="text-red-600">*</span>
                                    </span>
                                    <select
                                        v-if="field.type === 'boolean'"
                                        :value="form.structured_data[field.key]"
                                        class="mt-1.5 w-full rounded-xl border px-3 py-2.5 text-sm"
                                        :class="errorFor(form, field.key) ? 'border-red-400' : 'border-slate-300'"
                                        @change="fieldChanged(field.key, $event)"
                                    >
                                        <option value="no">Nein</option>
                                        <option value="yes">Ja</option>
                                    </select>
                                    <textarea
                                        v-else-if="field.type === 'textarea'"
                                        :value="form.structured_data[field.key]"
                                        rows="4"
                                        class="mt-1.5 w-full rounded-xl border px-3 py-2.5 text-sm"
                                        :class="errorFor(form, field.key) ? 'border-red-400' : 'border-slate-300'"
                                        :aria-invalid="Boolean(errorFor(form, field.key))"
                                        @input="fieldChanged(field.key, $event)"
                                    />
                                    <input
                                        v-else
                                        :value="form.structured_data[field.key]"
                                        :type="field.type === 'url' ? 'url' : 'text'"
                                        class="mt-1.5 w-full rounded-xl border px-3 py-2.5 text-sm"
                                        :class="errorFor(form, field.key) ? 'border-red-400' : 'border-slate-300'"
                                        :aria-invalid="Boolean(errorFor(form, field.key))"
                                        @input="fieldChanged(field.key, $event)"
                                    />
                                    <span v-if="errorFor(form, field.key)" class="mt-1 block text-xs text-red-600">{{
                                        errorFor(form, field.key)
                                    }}</span>
                                </label>
                            </div>
                        </section>

                        <section class="border-t border-slate-200 pt-5">
                            <h3 class="text-sm font-semibold text-slate-950">Zugriff</h3>
                            <label class="mt-4 block">
                                <span class="text-sm font-medium text-slate-700">Sichtbarkeit</span>
                                <select
                                    :value="form.visibility"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    @change="visibilityChanged"
                                >
                                    <option value="internal">Intern</option>
                                    <option value="restricted">Eingeschränkt</option>
                                </select>
                            </label>
                        </section>
                    </div>
                    <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                            @click="emit('close')"
                        >
                            Abbrechen
                        </button>
                        <button
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
