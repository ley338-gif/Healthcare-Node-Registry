<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, Upload } from '@lucide/vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/ui/PageHeader.vue';

type PreviewRow = { line: number; source: Record<string, string>; errors: string[]; valid: boolean };
type Preview = {
    kind: 'systems' | 'dicom_nodes';
    headers: string[];
    rows: PreviewRow[];
    valid: number;
    invalid: number;
};

const props = defineProps<{ preview: Preview | null; token: string | null }>();
const form = useForm<{ kind: 'systems' | 'dicom_nodes'; csv_file: File | null }>({ kind: 'systems', csv_file: null });
const confirmForm = useForm({});
const selectFile = (event: Event): void => {
    form.csv_file = (event.target as HTMLInputElement).files?.[0] ?? null;
};
const submitPreview = (): void => form.post('/systems/import/preview', { forceFormData: true });
const confirm = (): void => {
    if (props.token) confirmForm.post(`/systems/import/${props.token}`);
};
</script>

<template>
    <Head title="CSV-Import" />
    <AppLayout>
        <PageHeader
            eyebrow="Registry"
            title="CSV-Import"
            description="Systeme und DICOM-Knoten zunächst validieren und anschließend kontrolliert importieren."
        >
            <template #actions
                ><Link
                    href="/systems"
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                    >Zurück zu Systeme</Link
                ></template
            >
        </PageHeader>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-slate-950">Datei prüfen</h2>
            <p class="mt-1 text-sm text-slate-500">
                Die Vorschau verändert keine Registry-Daten. Maximal 2 MiB und 1.000 Datenzeilen.
            </p>
            <form class="mt-5 grid gap-4 md:grid-cols-[240px_1fr_auto]" @submit.prevent="submitPreview">
                <select v-model="form.kind" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="systems">Systeme</option>
                    <option value="dicom_nodes">DICOM-Knoten</option>
                </select>
                <input
                    type="file"
                    accept=".csv,text/csv"
                    required
                    class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    @change="selectFile"
                />
                <button
                    type="submit"
                    :disabled="form.processing || !form.csv_file"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                >
                    <Upload :size="16" />Vorschau erstellen
                </button>
            </form>
            <p v-if="form.errors.csv_file" class="mt-3 text-sm text-red-600">{{ form.errors.csv_file }}</p>
        </section>

        <section v-if="preview" class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 p-5">
                <div>
                    <h2 class="font-semibold text-slate-950">Importvorschau</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ preview.valid }} gültig · {{ preview.invalid }} fehlerhaft
                    </p>
                </div>
                <button
                    type="button"
                    :disabled="preview.valid === 0 || confirmForm.processing"
                    class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                    @click="confirm"
                >
                    {{ preview.valid }} gültige Zeilen importieren
                </button>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Zeile</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Datensatz</th>
                            <th class="px-4 py-3">Hinweise</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="row in preview.rows" :key="row.line">
                            <td class="px-4 py-3">{{ row.line }}</td>
                            <td class="px-4 py-3">
                                <span v-if="row.valid" class="inline-flex items-center gap-1 text-emerald-700"
                                    ><CheckCircle2 :size="15" />Gültig</span
                                ><span v-else class="inline-flex items-center gap-1 text-red-700"
                                    ><AlertTriangle :size="15" />Fehler</span
                                >
                            </td>
                            <td class="max-w-xl px-4 py-3 font-mono text-xs">
                                {{ Object.values(row.source).filter(Boolean).join(' · ') }}
                            </td>
                            <td class="px-4 py-3 text-red-700">
                                <div v-for="error in row.errors" :key="error">{{ error }}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppLayout>
</template>
