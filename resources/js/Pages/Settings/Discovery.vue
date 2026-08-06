<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Radar, ShieldAlert, Trash2 } from '@lucide/vue';
import PageHeader from '../../Components/ui/PageHeader.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type Network = {
    public_id: string;
    cidr: string;
    description: string | null;
    active: boolean;
    creator: string | null;
    created_at: string | null;
};

defineProps<{ networks: Network[] }>();

const form = useForm({ cidr: '', description: '' });

const submit = (): void => {
    form.post('/settings/discovery', { preserveScroll: true, onSuccess: () => form.reset() });
};

const toggleActive = (network: Network): void => {
    router.put(
        `/settings/discovery/${network.public_id}`,
        { active: !network.active, description: network.description },
        { preserveScroll: true },
    );
};

const remove = (network: Network): void => {
    router.delete(`/settings/discovery/${network.public_id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Discovery-Einstellungen" />
    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                eyebrow="Administration"
                title="Discovery: Freigegebene Netzbereiche"
                description="Nur innerhalb dieser Bereiche können Discovery-Läufe gestartet werden. Standardmäßig sind ausschließlich private (RFC1918) Netze freigegeben."
            />

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="flex items-center gap-2 font-semibold">
                    <ShieldAlert :size="16" /> Sicherheitsrelevante Einstellung
                </p>
                <p class="mt-1">
                    Geben Sie ausschließlich Netzbereiche frei, für deren Scan Sie eine ausdrückliche Berechtigung
                    besitzen. Jede Änderung wird im Audit protokolliert.
                </p>
            </div>

            <form
                class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                @submit.prevent="submit"
            >
                <label class="block"
                    ><span class="text-sm font-medium text-slate-700">CIDR-Netz *</span
                    ><input
                        v-model="form.cidr"
                        placeholder="192.168.0.0/16"
                        class="mt-1.5 w-56 rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                    /><span v-if="form.errors.cidr" class="mt-1 block text-xs text-red-600">{{
                        form.errors.cidr
                    }}</span></label
                >
                <label class="block flex-1"
                    ><span class="text-sm font-medium text-slate-700">Beschreibung</span
                    ><input
                        v-model="form.description"
                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                /></label>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                >
                    <Plus :size="16" /> Freigeben
                </button>
            </form>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div v-if="networks.length === 0" class="px-5 py-12 text-center">
                    <Radar :size="30" class="mx-auto text-slate-300" />
                    <p class="mt-3 font-medium text-slate-900">Keine Netzbereiche freigegeben</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Ohne Freigabe können keine Discovery-Läufe gestartet werden.
                    </p>
                </div>
                <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">CIDR</th>
                            <th class="px-4 py-3 text-left">Beschreibung</th>
                            <th class="px-4 py-3 text-left">Angelegt von</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="network in networks" :key="network.public_id">
                            <td class="px-4 py-3 font-mono">{{ network.cidr }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ network.description ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ network.creator ?? 'System' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        network.active
                                            ? 'bg-emerald-50 text-emerald-800'
                                            : 'bg-slate-100 text-slate-500'
                                    "
                                >
                                    {{ network.active ? 'Aktiv' : 'Deaktiviert' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <button
                                        type="button"
                                        class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                        @click="toggleActive(network)"
                                    >
                                        {{ network.active ? 'Deaktivieren' : 'Aktivieren' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600"
                                        @click="remove(network)"
                                    >
                                        <Trash2 :size="15" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </AppLayout>
</template>
