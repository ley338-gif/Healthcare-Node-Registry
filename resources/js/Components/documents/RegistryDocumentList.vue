<script setup lang="ts">
import { ChevronDown, Download, Eye, FileText, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import RegistryDocumentUploadSlideover from './RegistryDocumentUploadSlideover.vue';
import RegistryDocumentPreviewSlideover from './RegistryDocumentPreviewSlideover.vue';
import RegistryDocumentVersionSlideover from './RegistryDocumentVersionSlideover.vue';

export type RegistryDocumentVersionItem = {
    public_id: string;
    version_number: number;
    original_filename: string;
    mime_type: string;
    size_bytes: number;
    sha256: string;
    malware_scan_status: string;
    malware_scan_message: string | null;
    change_note: string | null;
    uploaded_at: string;
    uploaded_by_user?: { name: string };
};

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
    current_version: RegistryDocumentVersionItem | null;
    versions: RegistryDocumentVersionItem[];
};

const props = defineProps<{
    documents: RegistryDocumentItem[];
    documentableType: string;
    documentableId: string;
    categories: Array<{ value: string; label: string }>;
    canUpload: boolean;
    canManageVersions: boolean;
    canDownload: boolean;
    canPreview: boolean;
}>();

const uploadOpen = ref(false);
const versionDocument = ref<RegistryDocumentItem | null>(null);
const previewDocument = ref<RegistryDocumentItem | null>(null);
const previewVersion = ref<RegistryDocumentVersionItem | null>(null);
const expandedDocumentId = ref<string | null>(null);
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
const expandedDocument = computed(
    () => props.documents.find((item) => item.public_id === expandedDocumentId.value) ?? null,
);
const sortedVersions = computed(() =>
    [...(expandedDocument.value?.versions ?? [])].sort((left, right) => right.version_number - left.version_number),
);

const categoryValue = (item: RegistryDocumentItem): string =>
    typeof item.category === 'string' ? item.category : item.category.value;
const size = (value: number): string =>
    new Intl.NumberFormat('de-DE', { style: 'unit', unit: 'kilobyte', maximumFractionDigits: 1 }).format(value / 1024);
const dateTime = (value: string): string =>
    new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
const scanLabel = (status: string): string =>
    ({
        clean: 'Sauber',
        pending: 'Ausstehend',
        unavailable: 'Nicht verfügbar',
        failed: 'Fehlgeschlagen',
        infected: 'Infiziert',
    })[status] ?? status;
const toggleVersions = (document: RegistryDocumentItem): void => {
    expandedDocumentId.value = expandedDocumentId.value === document.public_id ? null : document.public_id;
};
const openPreview = (document: RegistryDocumentItem, version: RegistryDocumentVersionItem): void => {
    previewDocument.value = document;
    previewVersion.value = version;
};
const closePreview = (): void => {
    previewDocument.value = null;
    previewVersion.value = null;
};
const uploadVersionFromPreview = (document: RegistryDocumentItem): void => {
    closePreview();
    versionDocument.value = document;
};
</script>

<template>
    <section class="mt-6 border-t border-slate-200 pt-6">
        <div class="flex items-start justify-between gap-4">
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
            <div v-else class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Titel</th>
                            <th class="px-4 py-3">Kategorie</th>
                            <th class="px-4 py-3">Version</th>
                            <th class="px-4 py-3">Datei</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"><span class="sr-only">Aktionen</span></th>
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
                            <td class="px-4 py-3">
                                {{
                                    item.current_version
                                        ? scanLabel(item.current_version.malware_scan_status)
                                        : item.status
                                }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3">
                                    <button
                                        v-if="
                                            canPreview &&
                                            item.current_version?.mime_type === 'application/pdf' &&
                                            item.current_version.malware_scan_status === 'clean'
                                        "
                                        type="button"
                                        class="inline-flex items-center gap-1 font-semibold text-blue-700 hover:text-blue-900"
                                        @click="openPreview(item, item.current_version)"
                                    >
                                        <Eye :size="15" /> Vorschau
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 font-semibold text-blue-700 hover:text-blue-900"
                                        @click="toggleVersions(item)"
                                    >
                                        Versionen
                                        <ChevronDown
                                            :size="15"
                                            :class="{ 'rotate-180': expandedDocumentId === item.public_id }"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <section v-if="expandedDocument" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h4 class="font-semibold text-slate-950">Versionshistorie · {{ expandedDocument.title }}</h4>
                        <p class="mt-1 text-xs text-slate-500">Frühere Versionen bleiben unverändert erhalten.</p>
                    </div>
                    <button
                        v-if="canManageVersions"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-blue-700"
                        @click="versionDocument = expandedDocument"
                    >
                        <Upload :size="16" /> Neue Version
                    </button>
                </div>
                <div class="mt-4 space-y-3">
                    <article
                        v-for="version in sortedVersions"
                        :key="version.public_id"
                        class="rounded-xl border border-slate-200 bg-white p-4"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <strong class="text-sm text-slate-950">v{{ version.version_number }}</strong
                                    ><span
                                        v-if="expandedDocument.current_version?.public_id === version.public_id"
                                        class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700"
                                        >Aktuell</span
                                    ><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{
                                        scanLabel(version.malware_scan_status)
                                    }}</span>
                                </div>
                                <p class="mt-2 truncate text-sm font-medium text-slate-800">
                                    {{ version.original_filename }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ size(version.size_bytes) }} · {{ dateTime(version.uploaded_at) }} ·
                                    {{ version.uploaded_by_user?.name ?? 'Unbekannt' }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    v-if="
                                        canPreview &&
                                        version.mime_type === 'application/pdf' &&
                                        version.malware_scan_status === 'clean'
                                    "
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    @click="openPreview(expandedDocument, version)"
                                >
                                    <Eye :size="15" /> Vorschau
                                </button>
                                <a
                                    v-if="canDownload && version.malware_scan_status === 'clean'"
                                    :href="`/registry-document-versions/${version.public_id}/download`"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    ><Download :size="15" /> Herunterladen</a
                                >
                            </div>
                        </div>
                        <p v-if="version.change_note" class="mt-3 text-sm text-slate-700">{{ version.change_note }}</p>
                        <p class="mt-3 font-mono text-[11px] break-all text-slate-400">SHA-256 {{ version.sha256 }}</p>
                    </article>
                </div>
            </section>
        </template>

        <RegistryDocumentUploadSlideover
            :open="uploadOpen"
            :documentable-type="documentableType"
            :documentable-id="documentableId"
            :categories="props.categories"
            @close="uploadOpen = false"
        />
        <RegistryDocumentVersionSlideover
            :open="versionDocument !== null"
            :document="versionDocument"
            @close="versionDocument = null"
        />
        <RegistryDocumentPreviewSlideover
            :document="previewDocument"
            :version="previewVersion"
            :can-download="canDownload"
            :can-manage-versions="canManageVersions"
            @close="closePreview"
            @new-version="uploadVersionFromPreview"
        />
    </section>
</template>
