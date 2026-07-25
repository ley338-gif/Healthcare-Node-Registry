<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, Hospital, Plus, UsersRound } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps<{
    summary: { organizations: number; sites: number; departments: number };
    recentOrganizations: Array<{ public_id: string; name: string; short_name: string | null }>;
    recentSites: Array<{ public_id: string; name: string; city: string | null; organization: { name: string } | null }>;
    recentDepartments: Array<{
        public_id: string;
        name: string;
        specialty: string | null;
        site: { name: string } | null;
    }>;
}>();

const sections = [
    {
        label: 'Organisationen',
        description: 'Träger, Klinikverbünde und Mandantenkontext.',
        href: '/organizations',
        icon: Building2,
        key: 'organizations',
    },
    {
        label: 'Standorte',
        description: 'Betriebsstätten und physische Standorte.',
        href: '/sites',
        icon: Hospital,
        key: 'sites',
    },
    {
        label: 'Abteilungen',
        description: 'Fachliche Zuordnung innerhalb eines Standorts.',
        href: '/departments',
        icon: UsersRound,
        key: 'departments',
    },
] as const;
</script>

<template>
    <Head title="Organisationsstruktur" />
    <AppLayout>
        <div class="mb-7">
            <p class="mb-2 text-xs font-semibold tracking-wider text-blue-600 uppercase">Registry</p>
            <h1 class="text-2xl font-semibold">Organisationsstruktur</h1>
            <p class="mt-1 text-sm text-slate-500">
                Gemeinsamer Einstieg für Organisationen, Standorte und Abteilungen.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Link
                v-for="section in sections"
                :key="section.key"
                :href="section.href"
                class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300 hover:shadow-md"
            >
                <div class="mb-6 flex items-start justify-between">
                    <div class="grid h-11 w-11 place-items-center rounded-xl bg-blue-50 text-blue-600">
                        <component :is="section.icon" :size="21" />
                    </div>
                    <ArrowRight :size="18" class="text-slate-300 group-hover:text-blue-600" />
                </div>
                <p class="text-3xl font-semibold">{{ summary[section.key] }}</p>
                <h2 class="mt-3 font-semibold">{{ section.label }}</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ section.description }}</p>
            </Link>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold">Organisationen</h2>
                    <Link href="/organizations" class="text-sm font-medium text-blue-600">Alle</Link>
                </div>
                <div v-if="recentOrganizations.length === 0" class="py-8 text-center text-sm text-slate-500">
                    Noch keine Einträge.
                </div>
                <div v-else class="divide-y divide-slate-100">
                    <div v-for="item in recentOrganizations" :key="item.public_id" class="py-3">
                        <p class="text-sm font-medium">{{ item.name }}</p>
                        <p class="text-xs text-slate-500">{{ item.short_name || 'Kein Kurzname' }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold">Standorte</h2>
                    <Link href="/sites" class="text-sm font-medium text-blue-600">Alle</Link>
                </div>
                <div v-if="recentSites.length === 0" class="py-8 text-center text-sm text-slate-500">
                    Noch keine Einträge.
                </div>
                <div v-else class="divide-y divide-slate-100">
                    <div v-for="item in recentSites" :key="item.public_id" class="py-3">
                        <p class="text-sm font-medium">{{ item.name }}</p>
                        <p class="text-xs text-slate-500">
                            {{ item.organization?.name }}<span v-if="item.city"> · {{ item.city }}</span>
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold">Abteilungen</h2>
                    <Link href="/departments" class="text-sm font-medium text-blue-600">Alle</Link>
                </div>
                <div v-if="recentDepartments.length === 0" class="py-8 text-center text-sm text-slate-500">
                    Noch keine Einträge.
                </div>
                <div v-else class="divide-y divide-slate-100">
                    <div v-for="item in recentDepartments" :key="item.public_id" class="py-3">
                        <p class="text-sm font-medium">{{ item.name }}</p>
                        <p class="text-xs text-slate-500">
                            {{ item.site?.name }}<span v-if="item.specialty"> · {{ item.specialty }}</span>
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <div class="mt-6 rounded-2xl border border-dashed border-blue-300 bg-blue-50/70 p-5">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h2 class="font-semibold text-blue-950">Nächster fachlicher Schritt: Systeme</h2>
                    <p class="mt-1 text-sm text-blue-800">
                        Systeme werden das zentrale Registry-Objekt und der Organisationsstruktur zugeordnet.
                    </p>
                </div>
                <button
                    disabled
                    class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg bg-blue-200 px-4 py-2.5 text-sm font-medium text-blue-700"
                >
                    <Plus :size="17" />
                    System anlegen
                </button>
            </div>
        </div>
    </AppLayout>
</template>
