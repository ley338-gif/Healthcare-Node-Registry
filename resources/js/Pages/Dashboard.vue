<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashboardStats from '../Components/dashboard/DashboardStats.vue';
import RecentChanges from '../Components/dashboard/RecentChanges.vue';
import RegistryOverview from '../Components/dashboard/RegistryOverview.vue';
import TaskPanel from '../Components/dashboard/TaskPanel.vue';
import TopologyPreview from '../Components/dashboard/TopologyPreview.vue';
import AppLayout from '../Layouts/AppLayout.vue';

type Summary = {
    organizations: number;
    sites: number;
    departments: number;
    systems: number;
    dicomNodes: number;
    connections: number;
    failedDicomNodes: number;
    unverifiedDicomNodes: number;
};

type RecentChange = {
    event_type: string;
    label: string;
    subject_type: string;
    subject_public_id: string | null;
    subject_label: string | null;
    occurred_at: string | null;
};

type Task = {
    label: string;
    completed: boolean;
    href: string;
};

defineProps<{
    summary: Summary;
    recentChanges: RecentChange[];
    tasks: Task[];
}>();
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="mb-7">
            <p class="mb-2 text-xs font-semibold tracking-wider text-blue-600 uppercase">Betriebsübersicht</p>
            <h1 class="text-2xl font-semibold text-slate-950">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">
                Systeme, DICOM-Kommunikation und relevante Änderungen auf einen Blick.
            </p>
        </div>

        <DashboardStats :summary="summary" />

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_1fr]">
            <RecentChanges :changes="recentChanges" />
            <TaskPanel :tasks="tasks" />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1.4fr]">
            <RegistryOverview :summary="summary" />
            <TopologyPreview :summary="summary" />
        </div>
    </AppLayout>
</template>
