<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Download, FilterX, LockKeyhole, ShieldCheck } from '@lucide/vue';
import { computed, reactive } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type Option = { public_id: string; name: string };
type Site = Option & { organization_id: number };
type Department = Option & { site_id: number };
type System = Option & { organization_id: number; site_id: number | null; department_id: number | null };
type Row = {
    public_id: string;
    name: string;
    source_system: string;
    source_node: string;
    source_host: string;
    source_ae_title: string;
    target_system: string;
    target_node: string;
    target_host: string;
    target_ae_title: string;
    port: number;
    service: string;
    tls_enabled: boolean;
    source_organization: string | null;
    source_site: string | null;
    source_department: string | null;
    target_organization: string | null;
    target_site: string | null;
    target_department: string | null;
};
type Filters = { organization?: string; site?: string; department?: string; system?: string; service?: string };
const props = defineProps<{
    rows: Row[];
    filters: Filters;
    organizations: Option[];
    sites: Site[];
    departments: Department[];
    systems: System[];
    services: string[];
}>();
const form = reactive<Filters>({
    organization: '',
    site: '',
    department: '',
    system: '',
    service: '',
    ...props.filters,
});
const query = computed(() => Object.fromEntries(Object.entries(form).filter(([, value]) => value)));
const apply = (): void => router.get('/reports/firewall-matrix', query.value, { preserveState: true, replace: true });
const reset = (): void => {
    Object.assign(form, { organization: '', site: '', department: '', system: '', service: '' });
    apply();
};
const exportUrl = (format: string): string =>
    `/reports/firewall-matrix/export/${format}?${new URLSearchParams(query.value as Record<string, string>)}`;
const labels: Record<string, string> = {
    echo: 'C-ECHO',
    store: 'C-STORE',
    worklist: 'Worklist',
    query: 'Query',
    move: 'C-MOVE',
    get: 'C-GET',
};
</script>

<template>
    <Head title="Firewall-Matrix" />
    <AppLayout
        ><div class="space-y-6">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold tracking-widest text-blue-600 uppercase">Reports</p>
                    <h1 class="mt-2 text-3xl font-semibold text-slate-950">Firewall- und Portmatrix</h1>
                    <p class="mt-2 text-sm text-slate-500">
                        Aktive Kommunikationspfade als Grundlage für Firewall-Freigaben und Betriebsdokumentation.
                    </p>
                </div>
                <div class="flex gap-2">
                    <a
                        :href="exportUrl('csv')"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold"
                        ><Download :size="16" />CSV</a
                    ><a
                        :href="exportUrl('pdf')"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
                        ><Download :size="16" />PDF</a
                    >
                </div>
            </header>
            <form
                class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2 xl:grid-cols-5"
                @submit.prevent="apply"
            >
                <select v-model="form.organization" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Organisationen</option>
                    <option v-for="item in organizations" :key="item.public_id" :value="item.public_id">
                        {{ item.name }}
                    </option>
                </select>
                <select v-model="form.site" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Standorte</option>
                    <option v-for="item in sites" :key="item.public_id" :value="item.public_id">{{ item.name }}</option>
                </select>
                <select v-model="form.department" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Abteilungen</option>
                    <option v-for="item in departments" :key="item.public_id" :value="item.public_id">
                        {{ item.name }}
                    </option>
                </select>
                <select v-model="form.system" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Systeme</option>
                    <option v-for="item in systems" :key="item.public_id" :value="item.public_id">
                        {{ item.name }}
                    </option>
                </select>
                <select v-model="form.service" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Alle Dienste</option>
                    <option v-for="item in services" :key="item" :value="item">{{ labels[item] }}</option>
                </select>
                <div class="flex gap-2 md:col-span-2 xl:col-span-5 xl:justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold"
                        @click="reset"
                    >
                        <FilterX :size="16" />Zurücksetzen</button
                    ><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">
                        Filter anwenden
                    </button>
                </div>
            </form>
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="flex items-center justify-between border-b border-slate-200 p-5">
                    <div>
                        <h2 class="font-semibold text-slate-950">Aktive Verbindungen</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ rows.length }} dokumentierte Pfade</p>
                    </div>
                    <ShieldCheck :size="22" class="text-blue-600" />
                </header>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3">Kontext</th>
                                <th class="px-4 py-3">Quelle</th>
                                <th class="px-4 py-3">Ziel</th>
                                <th class="px-4 py-3">Port</th>
                                <th class="px-4 py-3">Dienst</th>
                                <th class="px-4 py-3">TLS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="row in rows" :key="row.public_id">
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{
                                        [row.source_organization, row.source_site, row.source_department]
                                            .filter(Boolean)
                                            .join(' · ') || 'Nicht zugeordnet'
                                    }}
                                    <span class="mx-1 text-slate-400">→</span>
                                    {{
                                        [row.target_organization, row.target_site, row.target_department]
                                            .filter(Boolean)
                                            .join(' · ') || 'Nicht zugeordnet'
                                    }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ row.source_system }}</p>
                                    <p class="font-mono text-xs text-slate-500">
                                        {{ row.source_host }} · {{ row.source_ae_title }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ row.target_system }}</p>
                                    <p class="font-mono text-xs text-slate-500">
                                        {{ row.target_host }} · {{ row.target_ae_title }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 font-mono">{{ row.port }}</td>
                                <td class="px-4 py-3">{{ labels[row.service] }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="row.tls_enabled ? 'text-emerald-700' : 'text-amber-700'"
                                        class="inline-flex items-center gap-1 font-semibold"
                                        ><LockKeyhole :size="14" />{{ row.tls_enabled ? 'Ja' : 'Nein' }}</span
                                    >
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                                    Keine aktiven Verbindungen für diese Filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div></AppLayout
    >
</template>
