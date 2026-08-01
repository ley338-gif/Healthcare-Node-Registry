<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import type { DicomConnection, DicomNodeOption } from '../../../Components/registry/dicom/DicomConnectionManager.vue';
import type { DicomNode } from '../../../Components/registry/dicom/DicomNodeManager.vue';
import SystemEditSlideOver from '../../../Components/registry/systems/SystemEditSlideOver.vue';
import SystemWorkspaceDetail, {
    type DepartmentOption,
    type OrganizationOption,
    type SelectOption,
    type SiteOption,
    type SystemDetail,
} from '../../../Components/registry/systems/SystemWorkspaceDetail.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { ref } from 'vue';
import type { AuditEvent } from '../../../Components/audit/AuditHistoryPanel.vue';
import type { RegistryDocumentationItem } from '../../../Components/documentation/documentationTypes';
import type { RegistryDocumentItem } from '../../../Components/documents/RegistryDocumentList.vue';

defineProps<{
    system: SystemDetail;
    systemTypes: SelectOption[];
    statuses: SelectOption[];
    organizations: OrganizationOption[];
    sites: SiteOption[];
    departments: DepartmentOption[];
    dicomNodes: DicomNode[];
    dicomConnections: DicomConnection[];
    dicomNodeOptions: DicomNodeOption[];
    canManage: boolean;
    canManageDicomNodes: boolean;
    canManageDicomConnections: boolean;
    history: {
        data: AuditEvent[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    historyStats: { total: number; today: number; last7Days: number; last30Days: number };
    historyFilters: Record<string, string | undefined>;
    historyEventTypes: string[];
    historyUsers: Array<{ public_id: string; name: string }>;
    documentation: RegistryDocumentationItem[];
    documents: RegistryDocumentItem[];
    documentCategories: Array<{ value: string; label: string }>;
    canUploadDocuments: boolean;
    canManageDocumentVersions: boolean;
    canDownloadDocuments: boolean;
}>();

const editPanelOpen = ref(false);
</script>

<template>
    <Head :title="system.name" />

    <AppLayout>
        <Link
            :href="`/systems?selected=${system.public_id}`"
            class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-blue-700"
        >
            <ArrowLeft :size="17" />
            Im System-Workspace öffnen
        </Link>

        <SystemWorkspaceDetail
            :system="system"
            :system-types="systemTypes"
            :statuses="statuses"
            :dicom-nodes="dicomNodes"
            :dicom-connections="dicomConnections"
            :dicom-node-options="dicomNodeOptions"
            :can-manage="canManage"
            :can-manage-dicom-nodes="canManageDicomNodes"
            :can-manage-dicom-connections="canManageDicomConnections"
            :history="history"
            :history-stats="historyStats"
            :history-filters="historyFilters"
            :history-event-types="historyEventTypes"
            :history-users="historyUsers"
            :documentation="documentation"
            :documents="documents"
            :document-categories="documentCategories"
            :can-upload-documents="canUploadDocuments"
            :can-manage-document-versions="canManageDocumentVersions"
            :can-download-documents="canDownloadDocuments"
            @edit="editPanelOpen = true"
        />

        <SystemEditSlideOver
            :open="editPanelOpen"
            :system="system"
            :organizations="organizations"
            :sites="sites"
            :departments="departments"
            :system-types="systemTypes"
            :statuses="statuses"
            @close="editPanelOpen = false"
        />
    </AppLayout>
</template>
