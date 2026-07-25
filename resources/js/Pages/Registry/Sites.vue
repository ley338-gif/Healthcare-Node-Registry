<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Archive, Pencil, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';
interface Org {
    id: number;
    public_id: string;
    name: string;
}
interface Item {
    public_id: string;
    organization_id: number;
    organization: Org;
    name: string;
    code: string | null;
    street: string | null;
    postal_code: string | null;
    city: string | null;
    country_code: string;
    timezone: string;
    description: string | null;
    departments_count: number;
    archived_at: string | null;
}
const props = defineProps<{
    items: { data: Item[]; links: Array<{ url: string | null; label: string; active: boolean }> };
    organizations: Org[];
    filters: { search: string; archived: boolean };
    canManage: boolean;
}>();
const editing = ref<Item | null>(null),
    search = ref(props.filters.search),
    archived = ref(props.filters.archived);
const form = useForm({
    organization_id: props.organizations[0]?.id ?? 0,
    name: '',
    code: '',
    street: '',
    postal_code: '',
    city: '',
    country_code: 'DE',
    timezone: 'Europe/Berlin',
    description: '',
});
const filter = () =>
    router.get(
        '/sites',
        { search: search.value, archived: archived.value || undefined },
        { preserveState: true, replace: true },
    );
const reset = () => {
    editing.value = null;
    form.reset();
    form.organization_id = props.organizations[0]?.id ?? 0;
};
const edit = (i: Item) => {
    editing.value = i;
    Object.assign(form, {
        organization_id: i.organization_id,
        name: i.name,
        code: i.code ?? '',
        street: i.street ?? '',
        postal_code: i.postal_code ?? '',
        city: i.city ?? '',
        country_code: i.country_code,
        timezone: i.timezone,
        description: i.description ?? '',
    });
};
const submit = () =>
    editing.value
        ? form.put(`/sites/${editing.value.public_id}`, { preserveScroll: true, onSuccess: reset })
        : form.post('/sites', { preserveScroll: true, onSuccess: reset });
const archive = (i: Item) =>
    confirm(`„${i.name}“ archivieren?`) && router.post(`/sites/${i.public_id}/archive`, {}, { preserveScroll: true });
</script>
<template>
    <Head title="Standorte" /><AppLayout
        ><div class="mb-6 flex justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Standorte</h1>
                <p class="text-sm text-slate-500">Betriebsstätten der Organisationen.</p>
            </div>
            <button
                v-if="canManage"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm text-white"
                @click="reset"
            >
                <Plus :size="17" />Neu
            </button>
        </div>
        <div class="mb-5 flex gap-3 rounded-xl border bg-white p-4">
            <label class="relative flex-1"
                ><Search class="absolute top-2.5 left-3 text-slate-400" :size="18" /><input
                    v-model="search"
                    class="w-full rounded-lg border py-2 pr-3 pl-10"
                    placeholder="Name, Code oder Ort"
                    @keyup.enter="filter" /></label
            ><label class="flex items-center gap-2 text-sm"
                ><input v-model="archived" type="checkbox" @change="filter" />Archivierte</label
            ><button class="rounded-lg border px-4" @click="filter">Filtern</button>
        </div>
        <div class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
            <section class="overflow-hidden rounded-xl border bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-5 py-3">Standort</th>
                            <th class="px-5 py-3">Organisation</th>
                            <th class="px-5 py-3">Abteilungen</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="i in items.data" :key="i.public_id">
                            <td class="px-5 py-4">
                                <p class="font-medium">{{ i.name }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ [i.postal_code, i.city].filter(Boolean).join(' ') || '–' }}
                                </p>
                            </td>
                            <td class="px-5 py-4">{{ i.organization?.name }}</td>
                            <td class="px-5 py-4">{{ i.departments_count }}</td>
                            <td class="px-5 py-4">
                                <div v-if="canManage && !i.archived_at" class="flex justify-end gap-2">
                                    <button class="rounded border p-2" @click="edit(i)"><Pencil :size="15" /></button
                                    ><button class="rounded border p-2 text-red-700" @click="archive(i)">
                                        <Archive :size="15" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items.data.length">
                            <td colspan="4" class="p-10 text-center text-slate-500">Keine Standorte gefunden.</td>
                        </tr>
                    </tbody>
                </table>
                <div class="border-t p-4"><Pagination :links="items.links" /></div>
            </section>
            <aside v-if="canManage" class="rounded-xl border bg-white p-5">
                <h2 class="mb-4 font-semibold">{{ editing ? 'Bearbeiten' : 'Neuer Standort' }}</h2>
                <form class="space-y-3" @submit.prevent="submit">
                    <select v-model="form.organization_id" required class="w-full rounded-lg border px-3 py-2">
                        <option v-for="o in organizations" :key="o.id" :value="o.id">{{ o.name }}</option></select
                    ><input
                        v-model="form.name"
                        required
                        placeholder="Name *"
                        class="w-full rounded-lg border px-3 py-2"
                    />
                    <p class="text-sm text-red-700">{{ form.errors.name }}</p>
                    <input v-model="form.code" placeholder="Code" class="w-full rounded-lg border px-3 py-2" /><input
                        v-model="form.street"
                        placeholder="Straße"
                        class="w-full rounded-lg border px-3 py-2"
                    />
                    <div class="grid grid-cols-2 gap-2">
                        <input v-model="form.postal_code" placeholder="PLZ" class="rounded-lg border px-3 py-2" /><input
                            v-model="form.city"
                            placeholder="Ort"
                            class="rounded-lg border px-3 py-2"
                        />
                    </div>
                    <div class="grid grid-cols-[90px_1fr] gap-2">
                        <input
                            v-model="form.country_code"
                            maxlength="2"
                            class="rounded-lg border px-3 py-2 uppercase"
                        /><input v-model="form.timezone" class="rounded-lg border px-3 py-2" />
                    </div>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Beschreibung"
                        class="w-full rounded-lg border px-3 py-2"
                    /><button
                        class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-white"
                        :disabled="form.processing || !organizations.length"
                    >
                        {{ editing ? 'Speichern' : 'Anlegen' }}
                    </button>
                </form>
            </aside>
        </div></AppLayout
    >
</template>
