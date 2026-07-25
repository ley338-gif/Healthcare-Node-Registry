<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Archive, Pencil, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';
interface Item {
    public_id: string;
    name: string;
    short_name: string | null;
    description: string | null;
    sites_count: number;
    archived_at: string | null;
}
const props = defineProps<{
    items: { data: Item[]; links: Array<{ url: string | null; label: string; active: boolean }> };
    filters: { search: string; archived: boolean };
    canManage: boolean;
}>();
const editing = ref<Item | null>(null),
    search = ref(props.filters.search),
    archived = ref(props.filters.archived);
const form = useForm({ name: '', short_name: '', description: '' });
const filter = () =>
    router.get(
        '/organizations',
        { search: search.value, archived: archived.value || undefined },
        { preserveState: true, replace: true },
    );
const edit = (i: Item) => {
    editing.value = i;
    form.name = i.name;
    form.short_name = i.short_name ?? '';
    form.description = i.description ?? '';
};
const reset = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
};
const submit = () =>
    editing.value
        ? form.put(`/organizations/${editing.value.public_id}`, { preserveScroll: true, onSuccess: reset })
        : form.post('/organizations', { preserveScroll: true, onSuccess: reset });
const archive = (i: Item) =>
    confirm(`„${i.name}“ archivieren?`) &&
    router.post(`/organizations/${i.public_id}/archive`, {}, { preserveScroll: true });
</script>
<template>
    <Head title="Organisationen" /><AppLayout
        ><div class="mb-6 flex justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Organisationen</h1>
                <p class="text-sm text-slate-500">Oberste Ebene der Registry.</p>
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
                    placeholder="Suchen"
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
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Standorte</th>
                            <th class="px-5 py-3">Status</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="i in items.data" :key="i.public_id">
                            <td class="px-5 py-4">
                                <p class="font-medium">{{ i.name }}</p>
                                <p class="text-xs text-slate-500">{{ i.short_name || '–' }}</p>
                            </td>
                            <td class="px-5 py-4">{{ i.sites_count }}</td>
                            <td class="px-5 py-4">{{ i.archived_at ? 'Archiviert' : 'Aktiv' }}</td>
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
                            <td colspan="4" class="p-10 text-center text-slate-500">Keine Organisationen gefunden.</td>
                        </tr>
                    </tbody>
                </table>
                <div class="border-t p-4"><Pagination :links="items.links" /></div>
            </section>
            <aside v-if="canManage" class="rounded-xl border bg-white p-5">
                <h2 class="mb-4 font-semibold">{{ editing ? 'Bearbeiten' : 'Neue Organisation' }}</h2>
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="text-sm font-medium">Name *</label
                        ><input v-model="form.name" required class="mt-1 w-full rounded-lg border px-3 py-2" />
                        <p class="text-sm text-red-700">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Kurzname</label
                        ><input v-model="form.short_name" class="mt-1 w-full rounded-lg border px-3 py-2" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Beschreibung</label
                        ><textarea
                            v-model="form.description"
                            rows="5"
                            class="mt-1 w-full rounded-lg border px-3 py-2"
                        />
                    </div>
                    <button class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-white" :disabled="form.processing">
                        {{ editing ? 'Speichern' : 'Anlegen' }}
                    </button>
                </form>
            </aside>
        </div></AppLayout
    >
</template>
