<script setup lang="ts">
import { FileText } from '@lucide/vue';
import { computed, ref } from 'vue';
import RegistryDocumentUploadSlideover from './RegistryDocumentUploadSlideover.vue';
export type RegistryDocumentItem = {
    public_id: string;
    title: string;
    description: string | null;
    category: { value: string } | string;
    category_label: string;
    visibility: string;
    status: string;
    valid_until: string | null;
    updated_at: string;
    current_version: {
        version_number: number;
        original_filename: string;
        mime_type: string;
        size_bytes: number;
        malware_scan_status: string;
        uploaded_at: string;
        uploaded_by_user?: { name: string };
    } | null;
};
const props = defineProps<{
    documents: RegistryDocumentItem[];
    documentableType: string;
    documentableId: string;
    categories: Array<{ value: string; label: string }>;
    canUpload: boolean;
}>();
const uploadOpen = ref(false);
const search = ref('');
const category = ref('');
const scanStatus = ref('');
const validity = ref('');
const filterCategories = computed(() => [
    ...new Map(props.documents.map((item) => [categoryValue(item), item.category_label])).entries(),
]);
const filtered = computed(() =>
    props.documents.filter((item) => {
        const term = search.value.trim().toLocaleLowerCase('de');
        const matchesSearch =
            term === '' ||
            [item.title, item.description, item.current_version?.original_filename].some((value) =>
                value?.toLocaleLowerCase('de').includes(term),
            );
        const expired = item.valid_until !== null && new Date(item.valid_until).getTime() < Date.now();
        return (
            matchesSearch &&
            (!category.value || categoryValue(item) === category.value) &&
            (!scanStatus.value || item.current_version?.malware_scan_status === scanStatus.value) &&
            (!validity.value || (validity.value === 'expired' ? expired : item.valid_until === null))
        );
    }),
);
const categoryValue = (item: RegistryDocumentItem): string =>
    typeof item.category === 'string' ? item.category : item.category.value;
const size = (v: number) =>
    new Intl.NumberFormat('de-DE', { style: 'unit', unit: 'kilobyte', maximumFractionDigits: 1 }).format(v / 1024);
const date = (v: string) => new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium' }).format(new Date(v));
</script>
<template>
    <section class="mt-6 border-t border-slate-200 pt-6">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="font-semibold text-slate-950">
                    Dateien <span class="text-slate-400">{{ documents.length }}</span>
                </h3>
                <p class="mt-1 text-sm text-slate-500">Versionierte Dateien zu diesem Registry-Eintrag.</p>
            </div>
            <button
                v-if="canUpload"
                type="button"
                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                @click="uploadOpen = true"
            >
                Dokument hochladen
            </button>
        </div>
        <div v-if="!documents.length" class="mt-4 flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
            <FileText :size="19" class="text-slate-400" />
            <p class="text-sm text-slate-500">Noch keine Dateien hinterlegt.</p>
        </div>
        <template v-else>
            <div class="mt-4 grid gap-2 rounded-xl bg-slate-50 p-3 sm:grid-cols-2 xl:grid-cols-4">
                <input
                    v-model="search"
                    aria-label="Dokumente durchsuchen"
                    placeholder="Titel oder Dateiname"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                />
                <select
                    v-model="category"
                    aria-label="Kategorie filtern"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                >
                    <option value="">Alle Kategorien</option>
                    <option v-for="option in filterCategories" :key="option[0]" :value="option[0]">
                        {{ option[1] }}
                    </option>
                </select>
                <select
                    v-model="scanStatus"
                    aria-label="Scanstatus filtern"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                >
                    <option value="">Alle Scanstatus</option>
                    <option value="clean">Sauber</option>
                    <option value="pending">Ausstehend</option>
                    <option value="unavailable">Nicht verfügbar</option>
                    <option value="failed">Fehlgeschlagen</option>
                    <option value="infected">Infiziert</option>
                </select>
                <select
                    v-model="validity"
                    aria-label="Gültigkeit filtern"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                >
                    <option value="">Alle Gültigkeiten</option>
                    <option value="expired">Abgelaufen</option>
                    <option value="undated">Ohne Gültigkeitsdatum</option>
                </select>
            </div>
            <p v-if="!filtered.length" class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">
                Keine Dokumente entsprechen den Filtern.
            </p>
            <div v-else class="mt-4 hidden overflow-x-auto rounded-xl border border-slate-200 md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Titel</th>
                            <th class="px-4 py-3">Kategorie</th>
                            <th class="px-4 py-3">Version</th>
                            <th class="px-4 py-3">Datei</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Geändert</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in filtered" :key="item.public_id">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ item.title }}</td>
                            <td class="px-4 py-3">{{ item.category_label }}</td>
                            <td class="px-4 py-3">
                                {{ item.current_version ? `v${item.current_version.version_number}` : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <template v-if="item.current_version"
                                    >{{ item.current_version.original_filename }} ·
                                    {{ size(item.current_version.size_bytes) }}</template
                                ><template v-else>—</template>
                            </td>
                            <td class="px-4 py-3">{{ item.current_version?.malware_scan_status ?? item.status }}</td>
                            <td class="px-4 py-3">{{ date(item.updated_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 grid gap-3 md:hidden">
                <article v-for="item in filtered" :key="item.public_id" class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-start gap-3">
                        <FileText :size="18" class="mt-0.5 text-blue-600" />
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ item.title }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ item.category_label }} ·
                                {{ item.current_version ? `v${item.current_version.version_number}` : 'Keine Version' }}
                            </p>
                            <p v-if="item.current_version" class="mt-2 truncate text-sm text-slate-700">
                                {{ item.current_version.original_filename }} ·
                                {{ size(item.current_version.size_bytes) }}
                            </p>
                            <p class="mt-2 text-xs text-slate-500">Geändert {{ date(item.updated_at) }}</p>
                        </div>
                    </div>
                </article>
            </div>
        </template>
        <RegistryDocumentUploadSlideover
            :open="uploadOpen"
            :documentable-type="documentableType"
            :documentable-id="documentableId"
            :categories="props.categories"
            @close="uploadOpen = false"
        />
    </section>
</template>
