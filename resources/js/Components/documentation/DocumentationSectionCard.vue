<script setup lang="ts">
import { FileText, Pencil } from '@lucide/vue';
import { computed } from 'vue';
import { hasDocumentationValue, sectionCounts, sectionStatus } from './documentationCompleteness';
import DocumentationStatusBadge from './DocumentationStatusBadge.vue';
import type { DocumentationSection, RegistryDocumentationItem } from './documentationTypes';

const props = defineProps<{
    section: DocumentationSection;
    document?: RegistryDocumentationItem;
    canManage: boolean;
}>();
const emit = defineEmits<{ edit: [section: DocumentationSection] }>();

const counts = computed(() => sectionCounts(props.section, props.document));
const status = computed(() => sectionStatus(props.section, props.document));
const previewFields = computed(() => {
    const populated = props.section.fields.filter((field) =>
        hasDocumentationValue(props.document?.structured_data[field.key]),
    );
    const missingRequired = props.section.fields.filter(
        (field) => field.required && !hasDocumentationValue(props.document?.structured_data[field.key]),
    );

    return [...populated.slice(0, 3), ...missingRequired]
        .filter((field, index, fields) => fields.findIndex((candidate) => candidate.key === field.key) === index)
        .slice(0, 4);
});

const display = (value: unknown): string => {
    if (value === true || value === 'yes') return 'Ja';
    if (value === false || value === 'no') return 'Nein';
    return typeof value === 'string' && value.trim() !== '' ? value : 'Nicht hinterlegt';
};
const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
</script>

<template>
    <article class="self-start rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
                <div class="rounded-xl bg-slate-100 p-2.5 text-slate-600"><FileText :size="18" /></div>
                <div class="min-w-0">
                    <h3 class="font-semibold text-slate-950">{{ section.title }}</h3>
                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ section.description }}</p>
                </div>
            </div>
            <DocumentationStatusBadge :status="status" />
        </div>

        <div
            v-if="status === 'empty'"
            class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5"
        >
            <p class="text-sm text-slate-500">Noch keine Angaben hinterlegt.</p>
            <button
                v-if="canManage"
                type="button"
                class="shrink-0 text-sm font-semibold text-blue-700 hover:text-blue-900"
                @click="emit('edit', section)"
            >
                Erfassen
            </button>
        </div>

        <dl v-else class="mt-4 grid gap-x-4 gap-y-3 sm:grid-cols-2">
            <div v-for="field in previewFields" :key="field.key" class="min-w-0">
                <dt class="flex items-center gap-1 text-xs font-medium text-slate-500">
                    {{ field.label }} <span v-if="field.required" class="text-red-600" title="Pflichtfeld">*</span>
                </dt>
                <dd
                    class="mt-1 line-clamp-3 text-sm whitespace-pre-wrap"
                    :class="
                        hasDocumentationValue(document?.structured_data[field.key])
                            ? 'text-slate-800'
                            : 'text-slate-400'
                    "
                >
                    {{ display(document?.structured_data[field.key]) }}
                </dd>
            </div>
        </dl>

        <footer
            class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500"
        >
            <span>
                {{ counts.filled }} von {{ counts.total }} Feldern
                <template v-if="counts.requiredTotal"
                    >· {{ counts.requiredFilled }}/{{ counts.requiredTotal }} Pflichtfelder</template
                >
            </span>
            <div class="flex items-center gap-3">
                <span v-if="document">Aktualisiert {{ formatDate(document.updated_at) }}</span>
                <button
                    v-if="canManage"
                    type="button"
                    class="inline-flex items-center gap-1 font-semibold text-blue-700 hover:text-blue-900"
                    @click="emit('edit', section)"
                >
                    <Pencil :size="14" /> Bearbeiten
                </button>
            </div>
        </footer>
    </article>
</template>
