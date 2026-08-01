<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { documentationCompleteness, documentForSection, hasDocumentationValue } from './documentationCompleteness';
import DocumentationCompleteness from './DocumentationCompleteness.vue';
import DocumentationEditorSlideover from './DocumentationEditorSlideover.vue';
import DocumentationSectionCard from './DocumentationSectionCard.vue';
import type { DocumentationSection, RegistryDocumentationItem } from './documentationTypes';

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
    selectedSection.value ? documentForSection(selectedSection.value, props.documentation) : undefined,
);
const completeness = computed(() => documentationCompleteness(props.sections, props.documentation));
const form = useForm({
    documentation_type: 'operations',
    section: '',
    title: '',
    content: null as string | null,
    structured_data: {} as Record<string, string>,
    visibility: 'internal' as 'internal' | 'restricted',
});

const edit = (section: DocumentationSection): void => {
    selectedSection.value = section;
    const document = documentForSection(section, props.documentation);
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
    if (form.processing) return;
    selectedSection.value = null;
    form.reset();
    form.clearErrors();
};

const updateField = (key: string, value: string): void => {
    form.structured_data[key] = value;
    form.clearErrors(`structured_data.${key}` as keyof typeof form.data);
};

const save = (): void => {
    if (selectedSection.value === null) return;
    form.clearErrors();
    for (const field of selectedSection.value.fields) {
        if (field.required && !hasDocumentationValue(form.structured_data[field.key])) {
            form.setError(`structured_data.${field.key}` as keyof typeof form.data, `${field.label} ist erforderlich.`);
        }
    }
    if (form.hasErrors) return;

    const options = { preserveScroll: true, onSuccess: close };
    if (selectedDocument.value) {
        form.put(`/registry-documentation/${selectedDocument.value.public_id}`, options);
        return;
    }
    form.post(`/registry-documentation/${props.documentableType}/${props.documentableId}`, options);
};
</script>

<template>
    <div class="space-y-5">
        <DocumentationCompleteness
            :label="completeness.label"
            :required-filled="completeness.requiredFilled"
            :required-total="completeness.requiredTotal"
            :percentage="completeness.percentage"
        />

        <section v-if="masterData?.length" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="font-semibold text-slate-950">Technische Stammdaten</h3>
            <p class="mt-1 text-xs text-slate-500">Diese Werte werden aus der Registry gelesen und nicht dupliziert.</p>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="item in masterData"
                    :key="item.label"
                    class="rounded-xl bg-white px-3 py-2.5 ring-1 ring-slate-200"
                >
                    <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
                    <dd class="mt-1 text-sm font-semibold break-words text-slate-900">
                        {{ item.value || 'Nicht hinterlegt' }}
                    </dd>
                </div>
            </dl>
        </section>

        <div class="columns-1 gap-4 md:columns-2 2xl:columns-3">
            <DocumentationSectionCard
                v-for="section in sections"
                :key="section.key"
                class="mb-4 break-inside-avoid"
                :section="section"
                :document="documentForSection(section, documentation)"
                :can-manage="canManage"
                @edit="edit"
            />
        </div>

        <DocumentationEditorSlideover
            :section="selectedSection"
            :form="form"
            @close="close"
            @save="save"
            @field-change="updateField"
            @visibility-change="(value) => (form.visibility = value)"
        />
    </div>
</template>
