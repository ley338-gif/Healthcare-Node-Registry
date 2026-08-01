<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, ChevronDown, Download, Eye, FileText, Pencil, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import RegistryDocumentUploadSlideover from './RegistryDocumentUploadSlideover.vue';
import RegistryDocumentMetadataSlideover from './RegistryDocumentMetadataSlideover.vue';
import RegistryDocumentPreviewSlideover from './RegistryDocumentPreviewSlideover.vue';
import RegistryDocumentVersionSlideover from './RegistryDocumentVersionSlideover.vue';
import Pagination from '../Pagination.vue';

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
    valid_from: string | null;
    valid_until: string | null;
    contract_reference: string | null;
    tags: string[];
    validity_status: 'active' | 'expiring_soon' | 'expired' | 'undated' | 'archived';
    validity_status_label: string;
    updated_at: string;
    current_version: RegistryDocumentVersionItem | null;
    versions: RegistryDocumentVersionItem[];
    documentable_name?: string;
    documentable_type_key?: string;
    documentable_type_label?: string;
    documentable_url?: string;
};

export type RegistryDocumentPagination = {
    data: RegistryDocumentItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total: number;
};

const props = withDefaults(
    defineProps<{
        documents: RegistryDocumentPagination;
        documentableType: string;
        documentableId: string;
        categories: Array<{ value: string; label: string }>;
        canUpload: boolean;
        canManageVersions: boolean;
        canDownload: boolean;
        canPreview: boolean;
        canUpdate: boolean;
        canArchive: boolean;
        filters: Record<string, string | undefined>;
        uploaders: Array<{ public_id: string; name: string }>;
        showFilters?: boolean;
        showContext?: boolean;
        standalone?: boolean;
    }>(),
    { showFilters: false, showContext: false, standalone: false },
);

const uploadOpen = ref(false);
const versionDocument = ref<RegistryDocumentItem | null>(null);
const previewDocument = ref<RegistryDocumentItem | null>(null);
const previewVersion = ref<RegistryDocumentVersionItem | null>(null);
const metadataDocument = ref<RegistryDocumentItem | null>(null);
const expandedDocumentId = ref<string | null>(null);
const search = ref(props.filters.document_search ?? '');
const category = ref(props.filters.document_category ?? '');
const fileType = ref(props.filters.document_file_type ?? '');
const status = ref(props.filters.document_status ?? '');
const scanStatus = ref(props.filters.document_scan_status ?? '');
const validity = ref(props.filters.document_validity ?? '');
const uploader = ref(props.filters.document_uploader ?? '');
const from = ref(props.filters.document_from ?? '');
const to = ref(props.filters.document_to ?? '');
const entityType = ref(props.filters.document_entity_type ?? '');
const expandedDocument = computed(
    () => props.documents.data.find((item) => item.public_id === expandedDocumentId.value) ?? null,
);
const sortedVersions = computed(() =>
    [...(expandedDocument.value?.versions ?? [])].sort((left, right) => right.version_number - left.version_number),
);

const size = (value: number): string =>
    new Intl.NumberFormat('de-DE', { style: 'unit', unit: 'kilobyte', maximumFractionDigits: 1 }).format(value / 1024);
const dateTime = (value: string): string =>
    new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
const date = (value: string): string =>
    new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium', timeZone: 'UTC' }).format(new Date(value));
const validityClasses = (status: RegistryDocumentItem['validity_status']): string =>
    ({
        active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        expiring_soon: 'bg-amber-50 text-amber-700 ring-amber-200',
        expired: 'bg-red-50 text-red-700 ring-red-200',
        undated: 'bg-slate-100 text-slate-600 ring-slate-200',
        archived: 'bg-slate-200 text-slate-600 ring-slate-300',
    })[status];
const scanLabel = (status: string): string =>
    ({
        clean: 'Sauber',
        pending: 'Ausstehend',
        unavailable: 'Nicht verfügbar',
        failed: 'Fehlgeschlagen',
        infected: 'Infiziert',
    })[status] ?? status;
const scanClasses = (status: string): string =>
    ({
        clean: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        pending: 'bg-amber-50 text-amber-700 ring-amber-200',
        unavailable: 'bg-slate-100 text-slate-600 ring-slate-200',
        failed: 'bg-red-50 text-red-700 ring-red-200',
        infected: 'bg-red-50 text-red-700 ring-red-200',
    })[status] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
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
const toggleArchive = (document: RegistryDocumentItem): void => {
    const archived = document.validity_status === 'archived';
    if (!window.confirm(archived ? 'Dokument wiederherstellen?' : 'Dokument archivieren?')) return;
    router.post(
        `/registry-documents/${document.public_id}/${archived ? 'restore' : 'archive'}`,
        {},
        { preserveScroll: true },
    );
};
const applyFilters = (): void => {
    const current = Object.fromEntries(new URLSearchParams(window.location.search));
    router.get(
        window.location.pathname,
        {
            ...current,
            document_search: search.value || undefined,
            document_category: category.value || undefined,
            document_file_type: fileType.value || undefined,
            document_status: status.value || undefined,
            document_scan_status: scanStatus.value || undefined,
            document_validity: validity.value || undefined,
            document_uploader: uploader.value || undefined,
            document_from: from.value || undefined,
            document_to: to.value || undefined,
            document_entity_type: entityType.value || undefined,
            document_page: undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};
const resetFilters = (): void => {
    search.value = category.value = fileType.value = status.value = scanStatus.value = validity.value = '';
    uploader.value = from.value = to.value = entityType.value = '';
    applyFilters();
};
</script>

<template>
    <section :class="standalone ? '' : 'mt-6 border-t border-slate-200 pt-6'">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="font-semibold text-slate-950">
                    Dateien <span class="text-slate-400">{{ documents.total }}</span>
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                    {{
                        showContext
                            ? 'Versionierte Dateien aus der gesamten Registry.'
                            : 'Versionierte Dateien zu diesem Registry-Eintrag.'
                    }}
                </p>
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

        <div v-if="showFilters" class="mt-5 grid gap-3 rounded-xl bg-slate-50 p-3 sm:grid-cols-2 xl:grid-cols-5">
            <input
                v-model="search"
                aria-label="Dokumente durchsuchen"
                placeholder="Titel, Referenz, Tag oder Dateiname"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                @keyup.enter="applyFilters"
            />
            <select
                v-if="showContext"
                v-model="entityType"
                aria-label="Registry-Ebene filtern"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
            >
                <option value="">Alle Registry-Ebenen</option>
                <option value="organization">Organisationen</option>
                <option value="site">Standorte</option>
                <option value="department">Abteilungen</option>
                <option value="system">Systeme</option>
            </select>
            <select
                v-model="category"
                aria-label="Kategorie filtern"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
            >
                <option value="">Alle Kategorien</option>
                <option v-for="option in categories" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
            <select
                v-model="fileType"
                aria-label="Dateityp filtern"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
            >
                <option value="">Alle Dateitypen</option>
                <option
                    v-for="type in ['pdf', 'png', 'jpg', 'jpeg', 'docx', 'xlsx', 'txt', 'zip']"
                    :key="type"
                    :value="type"
                >
                    {{ type.toUpperCase() }}
                </option>
            </select>
            <select
                v-model="status"
                aria-label="Dokumentstatus filtern"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
            >
                <option value="">Alle Dokumentstatus</option>
                <option value="active">Aktiv</option>
                <option value="archived">Archiviert</option>
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
                <option value="active">Aktiv</option>
                <option value="expiring_soon">Läuft bald ab</option>
                <option value="expired">Abgelaufen</option>
                <option value="undated">Ohne Gültigkeitsdatum</option>
                <option value="archived">Archiviert</option>
            </select>
            <select
                v-model="uploader"
                aria-label="Uploader filtern"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
            >
                <option value="">Alle Uploader</option>
                <option v-for="item in uploaders" :key="item.public_id" :value="item.public_id">
                    {{ item.name }}
                </option>
            </select>
            <input
                v-model="from"
                type="date"
                aria-label="Upload von"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
            />
            <input
                v-model="to"
                type="date"
                aria-label="Upload bis"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
            />
            <div class="flex gap-2">
                <button
                    type="button"
                    class="flex-1 rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white"
                    @click="applyFilters"
                >
                    Filtern
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600"
                    @click="resetFilters"
                >
                    Zurücksetzen
                </button>
            </div>
        </div>
        <div v-if="!documents.data.length" class="mt-4 flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
            <FileText :size="19" class="text-slate-400" />
            <p class="text-sm text-slate-500">Keine Dokumente entsprechen der Auswahl.</p>
        </div>
        <div v-else class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-100/80 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">Titel</th>
                        <th v-if="showContext" class="px-4 py-3">Zuordnung</th>
                        <th class="px-4 py-3">Kategorie</th>
                        <th class="px-4 py-3">Version</th>
                        <th class="px-4 py-3">Datei</th>
                        <th class="px-4 py-3">Gültigkeit</th>
                        <th class="px-4 py-3">Scanstatus</th>
                        <th class="px-4 py-3"><span class="sr-only">Aktionen</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr
                        v-for="item in documents.data"
                        :key="item.public_id"
                        class="transition-colors hover:bg-blue-50/40"
                    >
                        <td class="px-4 py-4 font-medium text-slate-900">{{ item.title }}</td>
                        <td v-if="showContext" class="px-4 py-4">
                            <Link
                                v-if="item.documentable_url"
                                :href="item.documentable_url"
                                class="font-semibold text-blue-700 hover:text-blue-900"
                            >
                                {{ item.documentable_name }}
                            </Link>
                            <p class="mt-1 text-xs text-slate-500">{{ item.documentable_type_label }}</p>
                        </td>
                        <td class="px-4 py-4">{{ item.category_label }}</td>
                        <td class="px-4 py-4">
                            {{ item.current_version ? `v${item.current_version.version_number}` : '—' }}
                        </td>
                        <td class="px-4 py-4">
                            <template v-if="item.current_version"
                                >{{ item.current_version.original_filename }} ·
                                {{ size(item.current_version.size_bytes) }}</template
                            ><template v-else>—</template>
                        </td>
                        <td class="px-4 py-4">
                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                :class="validityClasses(item.validity_status)"
                            >
                                {{ item.validity_status_label }}
                            </span>
                            <p v-if="item.valid_until" class="mt-1 text-xs text-slate-500">
                                bis {{ date(item.valid_until) }}
                            </p>
                        </td>
                        <td class="px-4 py-4">
                            <span
                                v-if="item.current_version"
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                :class="scanClasses(item.current_version.malware_scan_status)"
                            >
                                {{ scanLabel(item.current_version.malware_scan_status) }}
                            </span>
                            <span v-else class="text-sm text-slate-500">{{ item.status }}</span>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-2">
                                <button
                                    v-if="canUpdate && item.validity_status !== 'archived'"
                                    type="button"
                                    class="inline-flex items-center gap-1 font-semibold text-slate-600 hover:text-blue-900"
                                    @click="metadataDocument = item"
                                >
                                    <Pencil :size="15" /> Bearbeiten
                                </button>
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
                                    v-if="canArchive"
                                    type="button"
                                    class="inline-flex items-center gap-1 font-semibold text-slate-600 hover:text-blue-900"
                                    @click="toggleArchive(item)"
                                >
                                    <ArchiveRestore v-if="item.validity_status === 'archived'" :size="15" />
                                    <Archive v-else :size="15" />
                                    {{ item.validity_status === 'archived' ? 'Wiederherstellen' : 'Archivieren' }}
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
        <Pagination v-if="documents.links.length > 3" class="mt-4" :links="documents.links" />

        <section v-if="expandedDocument" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h4 class="font-semibold text-slate-950">Versionshistorie · {{ expandedDocument.title }}</h4>
                    <p class="mt-1 text-xs text-slate-500">Frühere Versionen bleiben unverändert erhalten.</p>
                    <p v-if="expandedDocument.contract_reference" class="mt-1 text-xs text-slate-500">
                        Vertragsreferenz: {{ expandedDocument.contract_reference }}
                    </p>
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

        <RegistryDocumentUploadSlideover
            :open="uploadOpen"
            :documentable-type="documentableType"
            :documentable-id="documentableId"
            :categories="props.categories"
            @close="uploadOpen = false"
        />
        <RegistryDocumentMetadataSlideover
            :document="metadataDocument"
            :categories="categories"
            @close="metadataDocument = null"
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
