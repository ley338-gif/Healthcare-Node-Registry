<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileText, LoaderCircle, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

type Option = { value: string; label: string };
const props = defineProps<{ open: boolean; documentableType: string; documentableId: string; categories: Option[] }>();
const emit = defineEmits<{ close: [] }>();
const fileInput = ref<HTMLInputElement | null>(null);
const tags = ref('');
const form = useForm({
    title: '',
    description: '',
    category: '',
    file: null as File | null,
    visibility: 'internal',
    valid_from: '',
    valid_until: '',
    contract_reference: '',
    tags: [] as string[],
});
const progress = computed(() => form.progress?.percentage ?? 0);
const selectedFile = computed(() => form.file);
const fileSize = computed(() =>
    selectedFile.value
        ? new Intl.NumberFormat('de-DE', { style: 'unit', unit: 'megabyte', maximumFractionDigits: 2 }).format(
              selectedFile.value.size / 1024 / 1024,
          )
        : '',
);
watch(
    () => props.open,
    (open) => {
        if (open) form.clearErrors();
    },
);
const chooseFile = (event: Event): void => {
    const target = event.target;
    form.file = target instanceof HTMLInputElement ? (target.files?.[0] ?? null) : null;
};
const close = (): void => {
    if (form.processing) return;
    form.reset();
    form.clearErrors();
    tags.value = '';
    if (fileInput.value) fileInput.value.value = '';
    emit('close');
};
const submit = (): void => {
    if (form.processing) return;
    form.transform((data) => ({
        ...data,
        tags: tags.value
            .split(',')
            .map((tag) => tag.trim())
            .filter(Boolean),
    })).post(`/registry-documents/${props.documentableType}/${props.documentableId}`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: close,
    });
};
</script>
<template>
    <Teleport to="body"
        ><div
            v-if="open"
            class="fixed inset-0 z-50"
            role="dialog"
            aria-modal="true"
            aria-labelledby="document-upload-title"
        >
            <button
                type="button"
                aria-label="Upload schließen"
                class="absolute inset-0 bg-slate-950/40"
                @click="close"
            />
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Dokumentenablage</p>
                        <h2 id="document-upload-title" class="mt-1 text-xl font-semibold text-slate-950">
                            Dokument hochladen
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">Datei und fachliche Metadaten sicher hinterlegen.</p>
                    </div>
                    <button
                        type="button"
                        aria-label="Upload schließen"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                        @click="close"
                    >
                        <X :size="20" />
                    </button>
                </header>
                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
                    <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
                        <section>
                            <h3 class="text-sm font-semibold text-slate-950">Dokument</h3>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <label class="block sm:col-span-2"
                                    ><span class="text-sm font-medium text-slate-700">Titel *</span
                                    ><input
                                        v-model="form.title"
                                        class="mt-1.5 w-full rounded-xl border px-3 py-2.5 text-sm"
                                        :class="form.errors.title ? 'border-red-400' : 'border-slate-300'"
                                    /><span v-if="form.errors.title" class="mt-1 block text-xs text-red-600">{{
                                        form.errors.title
                                    }}</span></label
                                ><label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Kategorie *</span
                                    ><select
                                        v-model="form.category"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    >
                                        <option value="">Bitte auswählen</option>
                                        <option v-for="option in categories" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option></select
                                    ><span v-if="form.errors.category" class="mt-1 block text-xs text-red-600">{{
                                        form.errors.category
                                    }}</span></label
                                ><label class="block"
                                    ><span class="text-sm font-medium text-slate-700">Sichtbarkeit</span
                                    ><select
                                        v-model="form.visibility"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    >
                                        <option value="internal">Intern</option>
                                        <option value="restricted">Eingeschränkt</option>
                                    </select></label
                                ><label class="block sm:col-span-2"
                                    ><span class="text-sm font-medium text-slate-700">Beschreibung</span
                                    ><textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    />
                                </label>
                            </div>
                        </section>
                        <section class="border-t border-slate-200 pt-5">
                            <h3 class="text-sm font-semibold text-slate-950">Datei *</h3>
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".pdf,.png,.jpg,.jpeg,.docx,.xlsx,.txt,.zip"
                                class="mt-4 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-700"
                                @change="chooseFile"
                            />
                            <div v-if="selectedFile" class="mt-3 flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                                <FileText :size="20" class="text-blue-600" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ selectedFile.name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ selectedFile.type || 'Typ wird serverseitig geprüft' }} · {{ fileSize }}
                                    </p>
                                </div>
                            </div>
                            <span v-if="form.errors.file" class="mt-2 block text-xs text-red-600">{{
                                form.errors.file
                            }}</span>
                            <div v-if="form.processing" class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3">
                                <div class="flex items-center gap-2 text-sm font-medium text-blue-800">
                                    <LoaderCircle :size="17" class="animate-spin" />Upload und Sicherheitsprüfung
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-blue-100">
                                    <div class="h-full bg-blue-600" :style="{ width: `${progress}%` }" />
                                </div>
                                <p class="mt-2 text-xs text-blue-700">
                                    MIME, Dateisignatur, Hash und Malware-Status werden serverseitig geprüft.
                                </p>
                            </div>
                        </section>
                        <section class="grid gap-4 border-t border-slate-200 pt-5 sm:grid-cols-2">
                            <label
                                ><span class="text-sm font-medium text-slate-700">Gültig ab</span
                                ><input
                                    v-model="form.valid_from"
                                    type="date"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label
                            ><label
                                ><span class="text-sm font-medium text-slate-700">Gültig bis</span
                                ><input
                                    v-model="form.valid_until"
                                    type="date"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                /><span v-if="form.errors.valid_until" class="mt-1 block text-xs text-red-600">{{
                                    form.errors.valid_until
                                }}</span></label
                            ><label
                                ><span class="text-sm font-medium text-slate-700">Vertragsreferenz</span
                                ><input
                                    v-model="form.contract_reference"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label
                            ><label
                                ><span class="text-sm font-medium text-slate-700">Schlagwörter</span
                                ><input
                                    v-model="tags"
                                    placeholder="Kommagetrennt"
                                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            /></label>
                        </section>
                    </div>
                    <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            :disabled="form.processing"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                            @click="close"
                        >
                            Abbrechen</button
                        ><button
                            type="submit"
                            :disabled="form.processing || !form.file"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            Hochladen
                        </button>
                    </footer>
                </form>
            </aside>
        </div></Teleport
    >
</template>
