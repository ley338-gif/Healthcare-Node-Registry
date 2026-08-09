<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Radar, ShieldAlert, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
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
type PortConfig = {
    port: number;
    protocol: 'tcp';
    label: string;
    is_dicom_candidate: boolean;
    enabled: boolean;
};
type PortProfile = {
    public_id: string;
    name: string;
    description: string | null;
    ports: PortConfig[];
    active: boolean;
    is_default: boolean;
    creator: string | null;
};

defineProps<{ networks: Network[]; portProfiles: PortProfile[] }>();

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

const editingProfileId = ref<string | null>(null);
const profileForm = useForm({
    name: '',
    description: '',
    active: true,
    is_default: false,
    ports: [{ port: 11112, protocol: 'tcp', label: 'DICOM', is_dicom_candidate: true, enabled: true }] as PortConfig[],
});
const resetProfile = (): void => {
    editingProfileId.value = null;
    profileForm.reset();
    profileForm.clearErrors();
};
const editProfile = (profile: PortProfile): void => {
    editingProfileId.value = profile.public_id;
    profileForm.name = profile.name;
    profileForm.description = profile.description ?? '';
    profileForm.active = profile.active;
    profileForm.is_default = profile.is_default;
    profileForm.ports = profile.ports.map((port) => ({ ...port }));
};
const addProfilePort = (): void => {
    profileForm.ports.push({ port: 0, protocol: 'tcp', label: '', is_dicom_candidate: false, enabled: true });
};
const removeProfilePort = (index: number): void => {
    profileForm.ports.splice(index, 1);
};
const submitProfile = (): void => {
    const options = { preserveScroll: true, onSuccess: resetProfile };
    if (editingProfileId.value) {
        profileForm.put(`/settings/discovery/port-profiles/${editingProfileId.value}`, options);
    } else {
        profileForm.post('/settings/discovery/port-profiles', options);
    }
};
const toggleProfile = (profile: PortProfile): void => {
    router.put(
        `/settings/discovery/port-profiles/${profile.public_id}`,
        { ...profile, active: !profile.active },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Discovery-Einstellungen" />
    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                eyebrow="Administration"
                title="Discovery-Einstellungen"
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

            <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <h2 class="font-semibold text-slate-950">Portprofile</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Profile dienen als Vorlage. Beim Start eines Laufs wird eine eigene Portkopie gespeichert.
                    </p>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <article
                        v-for="profile in portProfiles"
                        :key="profile.public_id"
                        class="rounded-xl border border-slate-200 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-slate-900">{{ profile.name }}</h3>
                                    <span
                                        v-if="profile.is_default"
                                        class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"
                                        >Standard</span
                                    >
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            profile.active
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-slate-100 text-slate-500'
                                        "
                                        >{{ profile.active ? 'Aktiv' : 'Deaktiviert' }}</span
                                    >
                                </div>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ profile.description ?? 'Keine Beschreibung' }}
                                </p>
                                <p class="mt-2 text-xs text-slate-400">
                                    {{ profile.ports.length }} Ports · {{ profile.creator ?? 'System' }}
                                </p>
                            </div>
                            <div class="flex gap-1">
                                <button
                                    type="button"
                                    class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                    @click="editProfile(profile)"
                                >
                                    Bearbeiten
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                    @click="toggleProfile(profile)"
                                >
                                    {{ profile.active ? 'Deaktivieren' : 'Aktivieren' }}
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
                <form
                    class="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4"
                    @submit.prevent="submitProfile"
                >
                    <h3 class="font-semibold text-slate-900">
                        {{ editingProfileId ? 'Portprofil bearbeiten' : 'Portprofil anlegen' }}
                    </h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Name *</span
                            ><input
                                v-model="profileForm.name"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        /></label>
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Beschreibung</span
                            ><input
                                v-model="profileForm.description"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        /></label>
                    </div>
                    <p v-if="profileForm.errors.name" class="text-xs text-red-600">{{ profileForm.errors.name }}</p>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left">Aktiv</th>
                                    <th class="px-3 py-2 text-left">Port</th>
                                    <th class="px-3 py-2 text-left">Beschreibung</th>
                                    <th class="px-3 py-2 text-left">DICOM</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(port, index) in profileForm.ports" :key="index">
                                    <td class="px-3 py-2"><input v-model="port.enabled" type="checkbox" /></td>
                                    <td class="px-3 py-2">
                                        <input
                                            v-model.number="port.port"
                                            type="number"
                                            min="1"
                                            max="65535"
                                            class="w-24 rounded-lg border border-slate-300 px-2 py-1 font-mono"
                                        />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input
                                            v-model="port.label"
                                            class="w-56 rounded-lg border border-slate-300 px-2 py-1"
                                        />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input v-model="port.is_dicom_candidate" type="checkbox" />
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <button
                                            type="button"
                                            class="text-slate-400 hover:text-red-600"
                                            @click="removeProfilePort(index)"
                                        >
                                            <Trash2 :size="15" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="profileForm.errors.ports" class="text-xs text-red-600">{{ profileForm.errors.ports }}</p>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700"
                            @click="addProfilePort"
                        >
                            <Plus :size="15" /> Port hinzufügen
                        </button>
                        <div class="flex items-center gap-3">
                            <label v-if="editingProfileId" class="flex items-center gap-2 text-sm"
                                ><input v-model="profileForm.is_default" type="checkbox" /> Als Standard
                                verwenden</label
                            >
                            <button
                                v-if="editingProfileId"
                                type="button"
                                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold"
                                @click="resetProfile"
                            >
                                Abbrechen
                            </button>
                            <button
                                type="submit"
                                :disabled="profileForm.processing"
                                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                            >
                                {{ editingProfileId ? 'Speichern' : 'Anlegen' }}
                            </button>
                        </div>
                    </div>
                </form>
            </section>

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
