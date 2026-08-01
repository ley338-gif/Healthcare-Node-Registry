<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import RegistryDocumentList, {
    type RegistryDocumentPagination,
} from '../../Components/documents/RegistryDocumentList.vue';
import RegistryDocumentUploadSlideover, {
    type RegistryDocumentTargets,
} from '../../Components/documents/RegistryDocumentUploadSlideover.vue';
import PageHeader from '../../Components/ui/PageHeader.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps<{
    documents: RegistryDocumentPagination;
    documentFilters: Record<string, string | undefined>;
    documentUploaders: Array<{ public_id: string; name: string }>;
    documentCategories: Array<{ value: string; label: string }>;
    documentTargets: RegistryDocumentTargets;
    canUploadDocuments: boolean;
    canManageDocumentVersions: boolean;
    canDownloadDocuments: boolean;
    canViewDocuments: boolean;
    canUpdateDocuments: boolean;
    canArchiveDocuments: boolean;
}>();

const uploadOpen = ref(false);
</script>

<template>
    <Head title="Dokumente" />
    <AppLayout>
        <div class="space-y-5">
            <PageHeader
                eyebrow="Registry"
                title="Dokumente"
                description="Dokumente aller Organisationen, Standorte, Abteilungen und Systeme zentral durchsuchen und verwalten."
            >
                <template #actions>
                    <button
                        v-if="canUploadDocuments"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                        @click="uploadOpen = true"
                    >
                        <Plus :size="17" />
                        Neues Dokument
                    </button>
                </template>
            </PageHeader>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-7">
                <RegistryDocumentList
                    :documents="documents"
                    documentable-type=""
                    documentable-id=""
                    :categories="documentCategories"
                    :can-upload="false"
                    :can-manage-versions="canManageDocumentVersions"
                    :can-download="canDownloadDocuments"
                    :can-preview="canViewDocuments"
                    :can-update="canUpdateDocuments"
                    :can-archive="canArchiveDocuments"
                    :filters="documentFilters"
                    :uploaders="documentUploaders"
                    show-filters
                    show-context
                    standalone
                />
            </div>
        </div>

        <RegistryDocumentUploadSlideover
            :open="uploadOpen"
            documentable-type=""
            documentable-id=""
            :categories="documentCategories"
            :targets="documentTargets"
            @close="uploadOpen = false"
        />
    </AppLayout>
</template>
