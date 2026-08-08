<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Network, Pencil, Plus, Star, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';

export type SystemNetworkInterface = {
    public_id: string;
    interface_label: string;
    hostname: string | null;
    fqdn: string | null;
    ip_address: string | null;
    is_primary: boolean;
};

const props = defineProps<{
    systemPublicId: string;
    interfaces: SystemNetworkInterface[];
    canManage: boolean;
}>();

const open = ref(false);
const selected = ref<SystemNetworkInterface | null>(null);
const form = useForm({
    interface_label: '',
    hostname: '',
    fqdn: '',
    ip_address: '',
    is_primary: false,
});

const create = (): void => {
    selected.value = null;
    form.reset();
    form.is_primary = props.interfaces.length === 0;
    form.clearErrors();
    open.value = true;
};

const edit = (networkInterface: SystemNetworkInterface): void => {
    selected.value = networkInterface;
    form.interface_label = networkInterface.interface_label;
    form.hostname = networkInterface.hostname ?? '';
    form.fqdn = networkInterface.fqdn ?? '';
    form.ip_address = networkInterface.ip_address ?? '';
    form.is_primary = networkInterface.is_primary;
    form.clearErrors();
    open.value = true;
};

const close = (): void => {
    if (form.processing) return;
    open.value = false;
    selected.value = null;
    form.reset();
    form.clearErrors();
};

const submit = (): void => {
    const options = { preserveScroll: true, onSuccess: close };
    if (selected.value === null) {
        form.post(`/systems/${props.systemPublicId}/network-interfaces`, options);
        return;
    }
    form.put(`/system-network-interfaces/${selected.value.public_id}`, options);
};

const remove = (networkInterface: SystemNetworkInterface): void => {
    if (!window.confirm(`Netzwerkinterface „${networkInterface.interface_label}“ löschen?`)) return;
    router.delete(`/system-network-interfaces/${networkInterface.public_id}`, { preserveScroll: true });
};
</script>

<template>
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div>
                <h3 class="font-semibold text-slate-950">Netzwerkinterfaces</h3>
                <p class="mt-1 text-sm text-slate-500">Hosts, VIPs und Management-Schnittstellen dieses Systems.</p>
            </div>
            <button
                v-if="canManage"
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
                @click="create"
            >
                <Plus :size="16" />Interface anlegen
            </button>
        </header>

        <div v-if="interfaces.length" class="grid gap-4 p-5 lg:grid-cols-2">
            <article
                v-for="networkInterface in interfaces"
                :key="networkInterface.public_id"
                class="rounded-xl border border-slate-200 p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="rounded-lg bg-blue-50 p-2 text-blue-700"><Network :size="18" /></span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-semibold text-slate-900">{{ networkInterface.interface_label }}</h4>
                                <span
                                    v-if="networkInterface.is_primary"
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700"
                                    ><Star :size="12" />Primär</span
                                >
                            </div>
                            <dl class="mt-3 grid gap-x-5 gap-y-2 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs text-slate-500">Hostname</dt>
                                    <dd class="font-mono">{{ networkInterface.hostname || '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">FQDN</dt>
                                    <dd class="font-mono break-all">{{ networkInterface.fqdn || '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">IP-Adresse</dt>
                                    <dd class="font-mono">{{ networkInterface.ip_address || '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    <div v-if="canManage" class="flex shrink-0 gap-1">
                        <button
                            type="button"
                            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                            title="Bearbeiten"
                            @click="edit(networkInterface)"
                        >
                            <Pencil :size="16" />
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-red-600 hover:bg-red-50"
                            title="Löschen"
                            @click="remove(networkInterface)"
                        >
                            <Trash2 :size="16" />
                        </button>
                    </div>
                </div>
            </article>
        </div>
        <div v-else class="p-8 text-center text-sm text-slate-500">Noch keine Netzwerkinterfaces hinterlegt.</div>
    </section>

    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                type="button"
                class="absolute inset-0 bg-slate-950/40"
                aria-label="Dialog schließen"
                @click="close"
            />
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">Netzwerk</p>
                        <h2 class="mt-1 text-xl font-semibold">
                            {{ selected ? 'Interface bearbeiten' : 'Interface anlegen' }}
                        </h2>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="close">
                        <X :size="20" />
                    </button>
                </header>
                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
                    <div class="flex-1 space-y-4 overflow-y-auto px-6 py-6">
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Bezeichnung *</span
                            ><input
                                v-model="form.interface_label"
                                maxlength="160"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                placeholder="z. B. Cluster-VIP"
                            /><span v-if="form.errors.interface_label" class="mt-1 block text-xs text-red-600">{{
                                form.errors.interface_label
                            }}</span></label
                        >
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Hostname</span
                            ><input
                                v-model="form.hostname"
                                maxlength="255"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                            /><span v-if="form.errors.hostname" class="mt-1 block text-xs text-red-600">{{
                                form.errors.hostname
                            }}</span></label
                        >
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">FQDN</span
                            ><input
                                v-model="form.fqdn"
                                maxlength="255"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                            /><span v-if="form.errors.fqdn" class="mt-1 block text-xs text-red-600">{{
                                form.errors.fqdn
                            }}</span></label
                        >
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">IP-Adresse</span
                            ><input
                                v-model="form.ip_address"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                                placeholder="IPv4 oder IPv6"
                            /><span v-if="form.errors.ip_address" class="mt-1 block text-xs text-red-600">{{
                                form.errors.ip_address
                            }}</span></label
                        >
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4"
                            ><input v-model="form.is_primary" type="checkbox" class="mt-1" /><span
                                ><span class="block text-sm font-medium text-slate-800">Primäres Interface</span
                                ><span class="mt-1 block text-xs text-slate-500"
                                    >Wird für bestehende Ansichten und Integrationen als Standardendpunkt
                                    gespiegelt.</span
                                ></span
                            ></label
                        >
                        <p class="text-xs text-slate-500">
                            Mindestens Hostname, FQDN oder IP-Adresse muss angegeben werden.
                        </p>
                    </div>
                    <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold"
                            @click="close"
                        >
                            Abbrechen</button
                        ><button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            Speichern
                        </button>
                    </footer>
                </form>
            </aside>
        </div>
    </Teleport>
</template>
