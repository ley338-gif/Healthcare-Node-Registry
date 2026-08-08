<script setup lang="ts">
import { Download, Upload, X } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import type { RegistryDocumentItem, RegistryDocumentVersionItem } from './RegistryDocumentList.vue';

defineProps<{
    document: RegistryDocumentItem | null;
    version: RegistryDocumentVersionItem | null;
    canDownload: boolean;
    canManageVersions: boolean;
    canApprove: boolean;
}>();

const emit = defineEmits<{ close: []; newVersion: [document: RegistryDocumentItem] }>();

const size = (value: number): string =>
    new Intl.NumberFormat('de-DE', { style: 'unit', unit: 'kilobyte', maximumFractionDigits: 1 }).format(value / 1024);
const dateTime = (value: string): string =>
    new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
const submitReview = (publicId: string): void =>
    router.post(`/registry-document-versions/${publicId}/submit-review`, {}, { preserveScroll: true });
const approve = (publicId: string): void =>
    router.post(`/registry-document-versions/${publicId}/approve`, {}, { preserveScroll: true });
const reject = (publicId: string): void => {
    const reviewNote = window.prompt('Begründung der Ablehnung');
    if (reviewNote?.trim())
        router.post(
            `/registry-document-versions/${publicId}/reject`,
            { review_note: reviewNote },
            { preserveScroll: true },
        );
};
</script>

<template>
    <Teleport to="body">
        <div v-if="document && version" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <button
                type="button"
                class="absolute inset-0 bg-slate-950/50"
                aria-label="Schließen"
                @click="emit('close')"
            />
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-6xl flex-col bg-white shadow-2xl">
                <header class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Dokumentversion</p>
                        <h2 class="mt-1 truncate text-xl font-semibold text-slate-950">{{ document.title }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ document.category_label }} · Version {{ version.version_number }} ·
                            {{ size(version.size_bytes) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="canManageVersions"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            @click="emit('newVersion', document)"
                        >
                            <Upload :size="16" /> Neue Version
                        </button>
                        <a
                            v-if="
                                canDownload &&
                                version.approval_status === 'approved' &&
                                version.malware_scan_status === 'clean'
                            "
                            :href="`/registry-document-versions/${version.public_id}/download`"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            <Download :size="16" /> Download
                        </a>
                        <button
                            type="button"
                            class="rounded-lg p-2 hover:bg-slate-100"
                            aria-label="Schließen"
                            @click="emit('close')"
                        >
                            <X :size="20" />
                        </button>
                    </div>
                </header>

                <div class="grid min-h-0 flex-1 bg-slate-100 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <iframe
                        v-if="
                            version.approval_status === 'approved' &&
                            version.malware_scan_status === 'clean' &&
                            version.mime_type === 'application/pdf'
                        "
                        :src="`/registry-document-versions/${version.public_id}/preview`"
                        :title="`PDF-Vorschau ${document.title}, Version ${version.version_number}`"
                        class="h-full min-h-[70vh] w-full bg-white"
                    />
                    <div v-else class="grid min-h-[70vh] place-items-center p-8 text-center">
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ version.approval_status_label }}</h3>
                            <p class="mt-2 max-w-lg text-sm text-slate-600">
                                {{
                                    version.approval_status === 'pending_review'
                                        ? 'Wartet auf Freigabe durch eine zweite Person.'
                                        : 'Eine Vorschau ist erst nach Freigabe und erfolgreicher Dateiprüfung verfügbar.'
                                }}
                            </p>
                            <div class="mt-5 flex flex-wrap justify-center gap-2">
                                <button
                                    v-if="canManageVersions && ['draft', 'rejected'].includes(version.approval_status)"
                                    type="button"
                                    class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white"
                                    @click="submitReview(version.public_id)"
                                >
                                    Zur Prüfung einreichen
                                </button>
                                <button
                                    v-if="canApprove && version.approval_status === 'pending_review'"
                                    type="button"
                                    class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white"
                                    @click="approve(version.public_id)"
                                >
                                    Freigeben
                                </button>
                                <button
                                    v-if="canApprove && version.approval_status === 'pending_review'"
                                    type="button"
                                    class="rounded-lg border border-red-300 px-3 py-2 text-sm font-semibold text-red-700"
                                    @click="reject(version.public_id)"
                                >
                                    Ablehnen
                                </button>
                            </div>
                        </div>
                    </div>
                    <aside class="overflow-y-auto border-l border-slate-200 bg-white p-5">
                        <h3 class="text-sm font-semibold text-slate-950">Dateiinformationen</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500">Dateiname</dt>
                                <dd class="mt-1 font-medium break-words text-slate-800">
                                    {{ version.original_filename }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Hochgeladen</dt>
                                <dd class="mt-1 text-slate-800">{{ dateTime(version.uploaded_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Uploader</dt>
                                <dd class="mt-1 text-slate-800">{{ version.uploaded_by_user?.name ?? 'Unbekannt' }}</dd>
                            </div>
                            <div v-if="version.change_note">
                                <dt class="text-xs text-slate-500">Änderungsnotiz</dt>
                                <dd class="mt-1 text-slate-800">{{ version.change_note }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Freigabestatus</dt>
                                <dd class="mt-1 font-medium text-slate-800">{{ version.approval_status_label }}</dd>
                            </div>
                            <div v-if="version.reviewed_by_user">
                                <dt class="text-xs text-slate-500">Geprüft durch</dt>
                                <dd class="mt-1 text-slate-800">{{ version.reviewed_by_user.name }}</dd>
                            </div>
                            <div v-if="version.review_note">
                                <dt class="text-xs text-slate-500">Prüfnotiz</dt>
                                <dd class="mt-1 text-slate-800">{{ version.review_note }}</dd>
                            </div>
                        </dl>
                        <h3 class="mt-6 text-sm font-semibold text-slate-950">Versionen</h3>
                        <ul class="mt-3 space-y-2">
                            <li
                                v-for="item in [...document.versions].sort(
                                    (a, b) => b.version_number - a.version_number,
                                )"
                                :key="item.public_id"
                                class="rounded-lg border border-slate-200 px-3 py-2 text-xs"
                            >
                                <div class="flex items-center justify-between">
                                    <strong>Version {{ item.version_number }}</strong
                                    ><span
                                        v-if="document.current_version?.public_id === item.public_id"
                                        class="font-semibold text-blue-700"
                                        >Aktuell</span
                                    >
                                </div>
                                <p class="mt-1 text-slate-500">{{ dateTime(item.uploaded_at) }}</p>
                            </li>
                        </ul>
                    </aside>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
