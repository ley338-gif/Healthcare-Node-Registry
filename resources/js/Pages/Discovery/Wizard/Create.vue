<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, ArrowRight, Plus, Radar, ShieldAlert, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '../../../Components/ui/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

type PortConfig = {
    port: number;
    protocol: 'tcp';
    label: string;
    is_dicom_candidate: boolean;
    enabled: boolean;
};
type AeCandidate = {
    ae_title: string;
    role: 'calling' | 'called';
    source: 'manual' | 'imported' | 'registry' | 'hostname_derived' | 'default';
};

const props = defineProps<{
    defaultDicomPorts: PortConfig[];
    optionalPorts: PortConfig[];
    registryAeTitles: string[];
    defaultCallingAe: string;
    maxRangeSize: number;
    largeRangeWarningThreshold: number;
    maxParallelHostsLimit: number;
    maxAeAttemptsPerPort: number;
    allowedNetworks: { cidr: string; description: string | null }[];
}>();

const step = ref(1);
const totalSteps = 5;
const targetMode = ref<'cidr' | 'range'>('cidr');

const form = useForm({
    name: '',
    location: '',
    department: '',
    ip_range: '',
    exclude_ips: [] as string[],
    description: '',
    scan_options: {
        ping_enabled: true,
        reverse_dns_enabled: true,
        tcp_scan_enabled: true,
        dicom_check_enabled: true,
        scan_unresponsive_hosts: false,
        max_parallel_hosts: 8,
        timeout_seconds: 2,
        retries: 1,
        profile: 'standard' as 'careful' | 'standard' | 'fast' | 'custom',
    },
    ports: [...props.defaultDicomPorts, ...props.optionalPorts].map((port) => ({ ...port })) as PortConfig[],
    ae_candidates: [{ ae_title: props.defaultCallingAe, role: 'calling', source: 'default' }] as AeCandidate[],
    confirmed: false,
});

const cidrInput = ref('');
const startIpInput = ref('');
const endIpInput = ref('');
const excludeIpsText = ref('');
const newCalledAe = ref('');

const profiles = {
    careful: { max_parallel_hosts: 4, timeout_seconds: 3, retries: 2, scan_unresponsive_hosts: false },
    standard: { max_parallel_hosts: 8, timeout_seconds: 2, retries: 1, scan_unresponsive_hosts: false },
    fast: { max_parallel_hosts: 16, timeout_seconds: 1, retries: 0, scan_unresponsive_hosts: false },
} as const;

const applyProfile = (profile: 'careful' | 'standard' | 'fast' | 'custom'): void => {
    form.scan_options.profile = profile;
    if (profile !== 'custom') {
        Object.assign(form.scan_options, profiles[profile]);
    }
};

// Nur für die Live-Vorschau im Wizard - die maßgebliche Prüfung erfolgt serverseitig.
const ipToLong = (ip: string): number | null => {
    const parts = ip.trim().split('.');
    if (parts.length !== 4) return null;
    const nums = parts.map((part) => Number(part));
    if (nums.some((n) => !Number.isInteger(n) || n < 0 || n > 255)) return null;
    return ((nums[0] << 24) | (nums[1] << 16) | (nums[2] << 8) | nums[3]) >>> 0;
};

const rangeBounds = computed<{ start: number; end: number; count: number } | null>(() => {
    const value = form.ip_range.trim();
    if (!value) return null;
    if (value.includes('/')) {
        const [address, prefixRaw] = value.split('/');
        const prefix = Number(prefixRaw);
        const base = ipToLong(address);
        if (base === null || !Number.isInteger(prefix) || prefix < 0 || prefix > 32) return null;
        const hostBits = 32 - prefix;
        const mask = hostBits === 32 ? 0 : (~0 << hostBits) >>> 0;
        const network = (base & mask) >>> 0;
        const broadcast = (network | (~mask >>> 0)) >>> 0;
        return { start: network, end: broadcast, count: broadcast - network + 1 };
    }
    if (value.includes('-')) {
        const [startRaw, endRaw] = value.split('-');
        const start = ipToLong(startRaw);
        const end = ipToLong(endRaw);
        if (start === null || end === null) return null;
        return { start, end, count: Math.max(0, end - start + 1) };
    }
    const single = ipToLong(value);
    return single === null ? null : { start: single, end: single, count: 1 };
});

const addressCount = computed(() => rangeBounds.value?.count ?? 0);
const isRangeTooLarge = computed(() => addressCount.value > props.maxRangeSize);
const isRangeLarge = computed(() => addressCount.value >= props.largeRangeWarningThreshold);

const syncTargetInput = (): void => {
    if (targetMode.value === 'cidr') {
        form.ip_range = cidrInput.value.trim();
    } else {
        form.ip_range =
            startIpInput.value.trim() && endIpInput.value.trim()
                ? `${startIpInput.value.trim()}-${endIpInput.value.trim()}`
                : '';
    }
};

const syncExcludeIps = (): void => {
    form.exclude_ips = excludeIpsText.value
        .split(/[\s,;]+/)
        .map((ip) => ip.trim())
        .filter((ip) => ip.length > 0);
};

const enabledPorts = computed(() => form.ports.filter((port) => port.enabled));
const dicomCandidatePorts = computed(() => enabledPorts.value.filter((port) => port.is_dicom_candidate));
const calledAeCandidates = computed(() => form.ae_candidates.filter((candidate) => candidate.role === 'called'));
const callingAeCandidates = computed(() => form.ae_candidates.filter((candidate) => candidate.role === 'calling'));

const addCustomPort = (): void => {
    form.ports.push({ port: 0, protocol: 'tcp', label: '', is_dicom_candidate: false, enabled: true });
};
const removePort = (index: number): void => {
    form.ports.splice(index, 1);
};

const addCalledAe = (source: AeCandidate['source'] = 'manual'): void => {
    const title = newCalledAe.value.trim().toUpperCase();
    if (!title || calledAeCandidates.value.some((c) => c.ae_title === title)) return;
    form.ae_candidates.push({ ae_title: title, role: 'called', source });
    newCalledAe.value = '';
};
const addRegistryAe = (title: string): void => {
    if (calledAeCandidates.value.some((c) => c.ae_title === title)) return;
    form.ae_candidates.push({ ae_title: title, role: 'called', source: 'registry' });
};
const addCallingAe = (): void => {
    form.ae_candidates.push({ ae_title: '', role: 'calling', source: 'manual' });
};
const removeAeCandidate = (candidate: AeCandidate): void => {
    form.ae_candidates = form.ae_candidates.filter((c) => c !== candidate);
};

const estimatedTechnicalChecks = computed(() => {
    const hosts = addressCount.value;
    const portChecks = hosts * enabledPorts.value.length;
    const echoChecks =
        hosts *
        Math.min(
            dicomCandidatePorts.value.length * calledAeCandidates.value.length,
            dicomCandidatePorts.value.length * props.maxAeAttemptsPerPort,
        );
    return portChecks + Math.max(0, echoChecks);
});

const stepValid = computed<boolean>(() => {
    switch (step.value) {
        case 1:
            return form.name.trim() !== '' && !!rangeBounds.value && !isRangeTooLarge.value;
        case 2:
            return true;
        case 3:
            return enabledPorts.value.length > 0;
        case 4:
            return callingAeCandidates.value.length > 0;
        case 5:
            return form.confirmed;
        default:
            return false;
    }
});

const next = (): void => {
    if (step.value === 1) syncTargetInput();
    if (step.value < totalSteps) step.value += 1;
};
const back = (): void => {
    if (step.value > 1) step.value -= 1;
};

const submit = (): void => {
    syncTargetInput();
    syncExcludeIps();
    form.post('/discovery/runs');
};
</script>

<template>
    <Head title="Discovery-Lauf anlegen" />
    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <PageHeader
                eyebrow="Erkennung"
                title="Discovery-Lauf anlegen"
                description="Geführter Wizard zur Konfiguration eines begrenzten Netzwerk-Scans."
            />

            <ol class="flex items-center gap-2 text-xs font-medium text-slate-500">
                <li v-for="n in totalSteps" :key="n" class="flex items-center gap-2">
                    <span
                        class="grid h-7 w-7 place-items-center rounded-full"
                        :class="
                            n === step
                                ? 'bg-blue-600 text-white'
                                : n < step
                                  ? 'bg-emerald-100 text-emerald-700'
                                  : 'bg-slate-100 text-slate-500'
                        "
                        >{{ n }}</span
                    >
                    <span v-if="n < totalSteps" class="h-px w-6 bg-slate-200" />
                </li>
            </ol>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <!-- Schritt 1: Zielbereich -->
                <section v-if="step === 1" class="space-y-5">
                    <h2 class="text-lg font-semibold text-slate-950">1. Zielbereich</h2>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p class="flex items-center gap-2 font-semibold">
                            <ShieldAlert :size="16" /> Wichtiger Hinweis
                        </p>
                        <p class="mt-1">
                            Scannen Sie ausschließlich Netzbereiche, für die Sie eine ausdrückliche Berechtigung
                            besitzen.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Name des Discovery-Laufs *</span
                            ><input
                                v-model="form.name"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                placeholder="z. B. Radiologie Hauptklinik – Q1"
                        /></label>
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Standort</span
                            ><input
                                v-model="form.location"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        /></label>
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Abteilung</span
                            ><input
                                v-model="form.department"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        /></label>
                    </div>

                    <div class="flex gap-2 text-sm">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5 font-medium"
                            :class="targetMode === 'cidr' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'"
                            @click="targetMode = 'cidr'"
                        >
                            CIDR-Netz
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5 font-medium"
                            :class="targetMode === 'range' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'"
                            @click="targetMode = 'range'"
                        >
                            Start-/End-IP
                        </button>
                    </div>

                    <div v-if="targetMode === 'cidr'">
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">CIDR-Netz *</span
                            ><input
                                v-model="cidrInput"
                                placeholder="192.168.20.0/24"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                                @input="syncTargetInput"
                        /></label>
                    </div>
                    <div v-else class="grid gap-4 sm:grid-cols-2">
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Start-IP *</span
                            ><input
                                v-model="startIpInput"
                                placeholder="192.168.20.10"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                                @input="syncTargetInput"
                        /></label>
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">End-IP *</span
                            ><input
                                v-model="endIpInput"
                                placeholder="192.168.20.50"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                                @input="syncTargetInput"
                        /></label>
                    </div>

                    <p
                        v-if="rangeBounds"
                        class="text-sm"
                        :class="isRangeTooLarge ? 'text-red-600' : isRangeLarge ? 'text-amber-700' : 'text-slate-500'"
                    >
                        {{ addressCount }} IPv4-Adressen im Zielbereich.
                        <span v-if="isRangeTooLarge"
                            >Das konfigurierte Limit von {{ maxRangeSize }} Adressen wird überschritten.</span
                        >
                        <span v-else-if="isRangeLarge"
                            >Großer Bereich – die Prüfung kann entsprechend lange dauern.</span
                        >
                    </p>
                    <p v-if="form.errors.ip_range" class="text-sm text-red-600">{{ form.errors.ip_range }}</p>

                    <details class="rounded-xl border border-slate-200 p-3 text-sm text-slate-600">
                        <summary class="cursor-pointer font-medium text-slate-700">
                            Freigegebene Netzbereiche anzeigen
                        </summary>
                        <ul class="mt-2 space-y-1 font-mono text-xs">
                            <li v-for="net in allowedNetworks" :key="net.cidr">
                                {{ net.cidr }} <span class="font-sans text-slate-400">– {{ net.description }}</span>
                            </li>
                            <li v-if="allowedNetworks.length === 0" class="text-red-600">
                                Keine Netzbereiche freigegeben – ein Administrator muss zunächst unter Einstellungen
                                &gt; Discovery einen Bereich freigeben.
                            </li>
                        </ul>
                    </details>

                    <label class="block"
                        ><span class="text-sm font-medium text-slate-700"
                            >Ausschlussadressen (eine pro Zeile oder durch Komma getrennt)</span
                        ><textarea
                            v-model="excludeIpsText"
                            rows="2"
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm"
                            placeholder="192.168.20.1, 192.168.20.254"
                            @blur="syncExcludeIps"
                        />
                    </label>

                    <label class="block"
                        ><span class="text-sm font-medium text-slate-700">Beschreibung</span
                        ><textarea
                            v-model="form.description"
                            rows="2"
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        />
                    </label>
                </section>

                <!-- Schritt 2: Scanoptionen -->
                <section v-else-if="step === 2" class="space-y-5">
                    <h2 class="text-lg font-semibold text-slate-950">2. Scanoptionen</h2>

                    <div class="grid gap-2 sm:grid-cols-4">
                        <button
                            v-for="profile in ['careful', 'standard', 'fast', 'custom'] as const"
                            :key="profile"
                            type="button"
                            class="rounded-xl border px-3 py-2.5 text-sm font-semibold"
                            :class="
                                form.scan_options.profile === profile
                                    ? 'border-blue-600 bg-blue-50 text-blue-700'
                                    : 'border-slate-200 text-slate-600'
                            "
                            @click="applyProfile(profile)"
                        >
                            {{
                                {
                                    careful: 'Vorsichtig',
                                    standard: 'Standard',
                                    fast: 'Schnell',
                                    custom: 'Benutzerdefiniert',
                                }[profile]
                            }}
                        </button>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                            ><input
                                v-model="form.scan_options.ping_enabled"
                                type="checkbox"
                                @change="form.scan_options.profile = 'custom'"
                            /><span class="text-sm">Ping aktivieren</span></label
                        >
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                            ><input
                                v-model="form.scan_options.reverse_dns_enabled"
                                type="checkbox"
                                @change="form.scan_options.profile = 'custom'"
                            /><span class="text-sm">Reverse DNS aktivieren</span></label
                        >
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                            ><input
                                v-model="form.scan_options.tcp_scan_enabled"
                                type="checkbox"
                                @change="form.scan_options.profile = 'custom'"
                            /><span class="text-sm">TCP-Portprüfung aktivieren</span></label
                        >
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                            ><input
                                v-model="form.scan_options.dicom_check_enabled"
                                type="checkbox"
                                @change="form.scan_options.profile = 'custom'"
                            /><span class="text-sm">DICOM-Prüfung aktivieren</span></label
                        >
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 sm:col-span-2"
                            ><input
                                v-model="form.scan_options.scan_unresponsive_hosts"
                                type="checkbox"
                                @change="form.scan_options.profile = 'custom'"
                            /><span class="text-sm"
                                >Auch auf Ping nicht antwortende Hosts auf den konfigurierten Ports prüfen</span
                            ></label
                        >
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Maximale parallele Hosts</span
                            ><input
                                v-model.number="form.scan_options.max_parallel_hosts"
                                type="number"
                                min="1"
                                :max="maxParallelHostsLimit"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                @input="form.scan_options.profile = 'custom'"
                        /></label>
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Timeout pro Prüfung (Sekunden)</span
                            ><input
                                v-model.number="form.scan_options.timeout_seconds"
                                type="number"
                                min="1"
                                max="30"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                @input="form.scan_options.profile = 'custom'"
                        /></label>
                        <label class="block"
                            ><span class="text-sm font-medium text-slate-700">Wiederholungsversuche</span
                            ><input
                                v-model.number="form.scan_options.retries"
                                type="number"
                                min="0"
                                max="3"
                                class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                @input="form.scan_options.profile = 'custom'"
                        /></label>
                    </div>
                    <p class="text-xs text-slate-400">
                        Das Standardprofil ("Standard") ist bewusst konservativ konfiguriert.
                    </p>
                </section>

                <!-- Schritt 3: Portliste -->
                <section v-else-if="step === 3" class="space-y-4">
                    <h2 class="text-lg font-semibold text-slate-950">3. Portliste</h2>
                    <p class="text-sm text-slate-500">
                        Ein offener Port allein beweist keinen DICOM-Dienst. Als "DICOM-Kandidat" markierte, offene
                        Ports werden in Phase C per C-ECHO getestet.
                    </p>
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left">Aktiv</th>
                                    <th class="px-3 py-2 text-left">Port</th>
                                    <th class="px-3 py-2 text-left">Beschreibung</th>
                                    <th class="px-3 py-2 text-left">DICOM-Kandidat</th>
                                    <th class="px-3 py-2" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(port, index) in form.ports" :key="index">
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
                                            @click="removePort(index)"
                                        >
                                            <Trash2 :size="15" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700"
                        @click="addCustomPort"
                    >
                        <Plus :size="15" /> Weiteren Port hinzufügen
                    </button>
                    <p v-if="enabledPorts.length === 0" class="text-sm text-red-600">
                        Mindestens ein Port muss aktiviert sein.
                    </p>
                </section>

                <!-- Schritt 4: AE-Titel-Kandidaten -->
                <section v-else-if="step === 4" class="space-y-5">
                    <h2 class="text-lg font-semibold text-slate-950">4. AE-Titel-Kandidaten</h2>
                    <p class="text-sm text-slate-500">
                        AE-Titel können nicht zuverlässig automatisch ausgelesen werden. Es werden ausschließlich die
                        hier konfigurierten Kandidaten getestet – begrenzt auf {{ maxAeAttemptsPerPort }} Versuche je
                        Host und Port.
                    </p>

                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Calling AE Titles</h3>
                        <div class="mt-2 space-y-2">
                            <div
                                v-for="candidate in callingAeCandidates"
                                :key="candidate.ae_title + '-calling'"
                                class="flex items-center gap-2"
                            >
                                <input
                                    v-model="candidate.ae_title"
                                    maxlength="16"
                                    class="w-48 rounded-lg border border-slate-300 px-2 py-1.5 font-mono text-sm uppercase"
                                />
                                <button
                                    type="button"
                                    class="text-slate-400 hover:text-red-600"
                                    @click="removeAeCandidate(candidate)"
                                >
                                    <Trash2 :size="15" />
                                </button>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-blue-700"
                            @click="addCallingAe"
                        >
                            <Plus :size="15" /> Weiteren Calling AE hinzufügen
                        </button>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Called AE Titles (Kandidaten)</h3>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="candidate in calledAeCandidates"
                                :key="candidate.ae_title"
                                class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 font-mono text-xs text-slate-700"
                            >
                                {{ candidate.ae_title }} <span class="text-slate-400">({{ candidate.source }})</span>
                                <button type="button" @click="removeAeCandidate(candidate)">
                                    <Trash2 :size="12" />
                                </button>
                            </span>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <input
                                v-model="newCalledAe"
                                maxlength="16"
                                placeholder="z. B. PACS01"
                                class="w-48 rounded-lg border border-slate-300 px-2 py-1.5 font-mono text-sm uppercase"
                                @keydown.enter.prevent="addCalledAe('manual')"
                            />
                            <button
                                type="button"
                                class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white"
                                @click="addCalledAe('manual')"
                            >
                                Hinzufügen
                            </button>
                        </div>
                        <div v-if="registryAeTitles.length > 0" class="mt-3">
                            <p class="text-xs text-slate-500">Aus der Registry übernehmen:</p>
                            <div class="mt-1 flex flex-wrap gap-2">
                                <button
                                    v-for="title in registryAeTitles"
                                    :key="title"
                                    type="button"
                                    class="rounded-full border border-slate-200 px-2.5 py-1 font-mono text-xs text-slate-600 hover:border-blue-300 hover:text-blue-700"
                                    @click="addRegistryAe(title)"
                                >
                                    + {{ title }}
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Schritt 5: Zusammenfassung -->
                <section v-else class="space-y-5">
                    <h2 class="text-lg font-semibold text-slate-950">5. Zusammenfassung</h2>
                    <dl class="grid grid-cols-2 gap-4 rounded-xl border border-slate-200 p-4 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="text-slate-500">Zielnetz</dt>
                            <dd class="font-mono font-medium">{{ form.ip_range }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Mögliche IP-Adressen</dt>
                            <dd class="font-medium">{{ addressCount }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Ausgeschlossene Adressen</dt>
                            <dd class="font-medium">{{ form.exclude_ips.length }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Aktive Ports</dt>
                            <dd class="font-medium">{{ enabledPorts.length }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">AE-Titel-Kandidaten</dt>
                            <dd class="font-medium">{{ form.ae_candidates.length }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Parallelität</dt>
                            <dd class="font-medium">{{ form.scan_options.max_parallel_hosts }} Hosts</dd>
                        </div>
                        <div class="col-span-2 sm:col-span-3">
                            <dt class="text-slate-500">Geschätzte Anzahl technischer Prüfungen</dt>
                            <dd class="font-medium">
                                ≈ {{ estimatedTechnicalChecks.toLocaleString('de-DE') }} (Ping/Port-/DICOM-Prüfungen
                                zusammen, grobe Schätzung)
                            </dd>
                        </div>
                    </dl>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p class="flex items-center gap-2 font-semibold">
                            <AlertTriangle :size="16" /> Sicherheitswarnung
                        </p>
                        <p class="mt-1">
                            Der Scan erzeugt Netzwerkverkehr zu allen Adressen im Zielbereich. Scannen Sie
                            ausschließlich Netzbereiche, für die Sie eine ausdrückliche Berechtigung besitzen. Alle
                            Ergebnisse sind Vorschläge und müssen manuell geprüft werden.
                        </p>
                    </div>

                    <label class="flex items-start gap-3 rounded-xl border border-slate-300 p-4 text-sm">
                        <input v-model="form.confirmed" type="checkbox" class="mt-1 rounded border-slate-300" />
                        <span
                            ><strong
                                >Ich bin berechtigt, den oben genannten Zielbereich zu scannen, und bestätige den Start
                                des Discovery-Laufs.</strong
                            ><br />Diese Bestätigung wird im Audit protokolliert.</span
                        >
                    </label>

                    <div
                        v-if="Object.keys(form.errors).length > 0"
                        class="rounded-xl bg-red-50 p-3 text-sm text-red-700"
                    >
                        <p v-for="(message, key) in form.errors" :key="key">{{ message }}</p>
                    </div>
                </section>
            </div>

            <div class="flex justify-between">
                <button
                    type="button"
                    :disabled="step === 1"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium disabled:opacity-40"
                    @click="back"
                >
                    <ArrowLeft :size="16" /> Zurück
                </button>
                <button
                    v-if="step < totalSteps"
                    type="button"
                    :disabled="!stepValid"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                    @click="next"
                >
                    Weiter <ArrowRight :size="16" />
                </button>
                <button
                    v-else
                    type="button"
                    :disabled="!stepValid || form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                    @click="submit"
                >
                    <Radar :size="16" /> {{ form.processing ? 'Wird gestartet …' : 'Discovery-Lauf starten' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
