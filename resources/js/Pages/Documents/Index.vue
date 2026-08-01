<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import RegistryDocumentList, {
    type RegistryDocumentPagination,
} from '../../Components/documents/RegistryDocumentList.vue';
import PageHeader from '../../Components/ui/PageHeader.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps<{
    documents: RegistryDocumentPagination;
    documentFilters: Record<string, string | undefined>;
    documentUploaders: Array<{ public_id: string; name: string }>;
    documentCategories: Array<{ value: string; label: string }>;
    canManageDocumentVersions: boolean;
    canDownloadDocuments: boolean;
    canViewDocuments: boolean;
    canUpdateDocuments: boolean;
    canArchiveDocuments: boolean;
}>();
</script>

<template>
    <Head title="Dokumente" />
    <AppLayout>
        <div class="space-y-5">
            <PageHeader
                eyebrow="Registry"
                title="Dokumente"
                description="Dokumente aller Organisationen, Standorte, Abteilungen und Systeme zentral durchsuchen und verwalten."
            />

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
                />
            </div>
        </div>
    </AppLayout>
</template>
