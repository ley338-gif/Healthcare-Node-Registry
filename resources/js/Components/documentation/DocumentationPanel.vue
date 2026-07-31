<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileText, Pencil, X } from '@lucide/vue';
import { computed, ref } from 'vue';

export type DocumentationField = {
    key: string;
    label: string;
    type?: 'text' | 'textarea' | 'url' | 'boolean';
    required?: boolean;
};

export type DocumentationSection = {
    key: string;
    title: string;
    description: string;
    fields: DocumentationField[];
};

export type RegistryDocumentationItem = {
    public_id: string;
    documentation_type: string;
    section: string;
    title: string;
    content: string | null;
    structured_data: Record<string, unknown>;
    visibility: 'internal' | 'restricted';
    updated_at: string;
    updated_by_user?: { public_id: string; name: string };
};

const props = defineProps<{
    documentableType: 'organizations' | 'sites' | 'departments' | 'systems';
    documentableId: string;
    sections: DocumentationSection[];
    documentation: RegistryDocumentationItem[];
    canManage: boolean;
    masterData?: Array<{ label: string; value: string | null }>;
}>();

const selectedSection = ref<DocumentationSection | null>(null);
const selectedDocument = computed(() =>
    selectedSection.value
        ? (props.documentation.find((item) => item.section === selectedSection.value?.key) ?? null)
        : null,
);
const form = useForm({
    documentation_type: 'operations',
    section: '',
    title: '',
    content: null as string | null,
    structured_data: {} as Record<string, string>,
    visibility: 'internal' as 'internal' | 'restricted',
});

const requiredFields = computed(() =>
    props.sections.flatMap((section) => section.fields.filter((field) => field.required)),
);
const completedRequiredFields = computed(() =>
    props.sections.reduce((total, section) => {
        const document = props.documentation.find((item) => item.section === section.key);
        return (
            total +
            section.fields.filter((field) => field.required && hasValue(document?.structured_data[field.key])).length
        );
    }, 0),
);
const completion = computed(() =>
    requiredFields.value.length === 0
        ? 0
        : Math.round((completedRequiredFields.value / requiredFields.value.length) * 100),
);
const completionLabel = computed(() => {
    if (completion.value === 0) return 'Nicht begonnen';
    if (completion.value < 60) return 'Unvollständig';
    if (completion.value < 100) return 'Weitgehend vollständig';
    return 'Vollständig';
});

function hasValue(value: unknown): boolean {
    return typeof value === 'boolean' || (typeof value === 'string' && value.trim() !== '');
}

const sectionDocument = (section: DocumentationSection): RegistryDocumentationItem | undefined =>
    props.documentation.find((item) => item.section === section.key);
const sectionFilled = (section: DocumentationSection): number => {
    const document = sectionDocument(section);
    return section.fields.filter((field) => hasValue(document?.structured_data[field.key])).length;
};
const display = (value: unknown): string => {
    if (value === true) return 'Ja';
    if (value === false) return 'Nein';
    if (value === 'yes') return 'Ja';
    if (value === 'no') return 'Nein';
    return typeof value === 'string' && value.trim() !== '' ? value : 'Nicht hinterlegt';
};
const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));

const edit = (section: DocumentationSection): void => {
    selectedSection.value = section;
    const document = sectionDocument(section);
    form.documentation_type = 'operations';
    form.section = section.key;
    form.title = section.title;
    form.content = document?.content ?? null;
    form.visibility = document?.visibility ?? 'internal';
    form.structured_data = Object.fromEntries(
        section.fields.map((field) => [
            field.key,
            field.type === 'boolean'
                ? document?.structured_data[field.key] === true || document?.structured_data[field.key] === 'yes'
                    ? 'yes'
                    : 'no'
                : String(document?.structured_data[field.key] ?? ''),
        ]),
    ) as Record<string, string>;
    form.clearErrors();
};

const close = (): void => {
    selectedSection.value = null;
    form.reset();
};

const save = (): void => {
    const document = selectedDocument.value;
    const options = { preserveScroll: true, onSuccess: close };
    if (document) {
        form.put(`/registry-documentation/${document.public_id}`, options);
        return;
    }
    form.post(`/registry-documentation/${props.documentableType}/${props.documentableId}`, options);
};
</script>

<template>
    <div class="space-y-5">
        <section class="rounded-2xl border border-blue-200 bg-blue-50/60 p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Dokumentationsstand</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-950">{{ completionLabel }}</p>
                    <p class="mt-1 text-sm text-blue-800">
                        {{ completedRequiredFields }} von {{ requiredFields.length }} definierten Pflichtfeldern ·
                        {{ completion }} %
                    </p>
                </div>
                <div class="h-2.5 w-full max-w-sm overflow-hidden rounded-full bg-blue-100">
                    <div class="h-full rounded-full bg-blue-600 transition-all" :style="{ width: `${completion}%` }" />
                </div>
            </div>
        </section>

        <section v-if="masterData?.length" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="font-semibold text-slate-950">Technische Stammdaten</h3>
            <p class="mt-1 text-xs text-slate-500">Diese Werte werden aus dem System gelesen und nicht dupliziert.</p>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="item in masterData" :key="item.label" class="rounded-xl bg-white p-3 ring-1 ring-slate-200">
                    <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
                    <dd class="mt-1 text-sm font-semibold break-words text-slate-900">
                        {{ item.value || 'Nicht hinterlegt' }}
                    </dd>
                </div>
            </dl>
        </section>

        <div class="grid gap-4 xl:grid-cols-2">
            <article
                v-for="section in sections"
                :key="section.key"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="rounded-xl bg-slate-100 p-2.5 text-slate-600"><FileText :size="18" /></div>
                        <div>
                            <h3 class="font-semibold text-slate-950">{{ section.title }}</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500">{{ section.description }}</p>
                        </div>
                    </div>
                    <button
                        v-if="canManage"
                        type="button"
                        class="rounded-lg p-2 text-blue-700 hover:bg-blue-50"
                        :aria-label="`${section.title} bearbeiten`"
                        @click="edit(section)"
                    >
                        <Pencil :size="16" />
                    </button>
                </div>
                <div
                    v-if="sectionFilled(section) === 0"
                    class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500"
                >
                    Noch keine Angaben hinterlegt.
                </div>
                <dl v-else class="mt-5 space-y-3">
                    <template v-for="field in section.fields" :key="field.key"
                        ><div v-if="hasValue(sectionDocument(section)?.structured_data[field.key])">
                            <dt class="text-xs font-medium text-slate-500">{{ field.label }}</dt>
                            <dd class="mt-1 text-sm whitespace-pre-wrap text-slate-800">
                                {{ display(sectionDocument(section)?.structured_data[field.key]) }}
                            </dd>
                        </div></template
                    >
                </dl>
                <div
                    class="mt-5 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500"
                >
                    <span>{{ sectionFilled(section) }} von {{ section.fields.length }} Feldern</span
                    ><span v-if="sectionDocument(section)"
                        >Aktualisiert {{ formatDate(sectionDocument(section)!.updated_at) }}</span
                    >
                </div>
            </article>
        </div>

        <Teleport to="body"
            ><div
                v-if="selectedSection"
                class="fixed inset-0 z-50 flex justify-end bg-slate-950/30"
                @click.self="close"
            >
                <aside class="h-full w-full max-w-2xl overflow-y-auto bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">
                                Dokumentation bearbeiten
                            </p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-950">{{ selectedSection.title }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ selectedSection.description }}</p>
                        </div>
                        <button class="rounded-lg p-2 hover:bg-slate-100" @click="close"><X :size="20" /></button>
                    </div>
                    <form class="mt-6 space-y-5" @submit.prevent="save">
                        <div v-for="field in selectedSection.fields" :key="field.key">
                            <label class="mb-1.5 block text-sm font-medium text-slate-700"
                                >{{ field.label }} <span v-if="field.required" class="text-red-600">*</span></label
                            ><select
                                v-if="field.type === 'boolean'"
                                v-model="form.structured_data[field.key]"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            >
                                <option value="no">Nein</option>
                                <option value="yes">Ja</option></select
                            ><textarea
                                v-else-if="field.type === 'textarea'"
                                v-model="form.structured_data[field.key]"
                                rows="4"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            /><input
                                v-else
                                v-model="form.structured_data[field.key]"
                                :type="field.type === 'url' ? 'url' : 'text'"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Sichtbarkeit</label
                            ><select
                                v-model="form.visibility"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                            >
                                <option value="internal">Intern</option>
                                <option value="restricted">Eingeschränkt</option>
                            </select>
                        </div>
                        <div
                            v-if="Object.keys(form.errors).length"
                            class="rounded-xl bg-red-50 p-3 text-sm text-red-700"
                        >
                            Bitte prüfe die markierten Eingaben.
                        </div>
                        <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                            <button
                                type="button"
                                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
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
                        </div>
                    </form>
                </aside>
            </div></Teleport
        >
    </div>
</template>
