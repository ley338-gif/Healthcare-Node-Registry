<script setup lang="ts">
import { Building2, Hospital, UsersRound } from '@lucide/vue';

type Summary = {
    organizations: number;
    sites: number;
    departments: number;
};

defineProps<{ summary: Summary }>();
</script>

<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div>
            <h2 class="font-semibold text-slate-950">Organisationskontext</h2>
            <p class="mt-1 text-sm text-slate-500">Grundlage für Filter, Berechtigungen und Topologiegruppen.</p>
        </div>

        <div class="mt-5 space-y-3">
            <a
                href="/structure"
                class="flex items-center justify-between rounded-xl border border-slate-200 p-4 transition hover:border-blue-300"
            >
                <div class="flex items-center gap-3">
                    <Building2 :size="19" class="text-blue-600" />
                    <span class="text-sm font-medium text-slate-700">Organisationen</span>
                </div>
                <strong class="text-lg text-slate-950">{{ summary.organizations }}</strong>
            </a>

            <a
                href="/structure"
                class="flex items-center justify-between rounded-xl border border-slate-200 p-4 transition hover:border-blue-300"
            >
                <div class="flex items-center gap-3">
                    <Hospital :size="19" class="text-blue-600" />
                    <span class="text-sm font-medium text-slate-700">Standorte</span>
                </div>
                <strong class="text-lg text-slate-950">{{ summary.sites }}</strong>
            </a>

            <a
                href="/structure"
                class="flex items-center justify-between rounded-xl border border-slate-200 p-4 transition hover:border-blue-300"
            >
                <div class="flex items-center gap-3">
                    <UsersRound :size="19" class="text-blue-600" />
                    <span class="text-sm font-medium text-slate-700">Abteilungen</span>
                </div>
                <strong class="text-lg text-slate-950">{{ summary.departments }}</strong>
            </a>
        </div>
    </section>
</template>
