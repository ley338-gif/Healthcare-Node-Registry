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
