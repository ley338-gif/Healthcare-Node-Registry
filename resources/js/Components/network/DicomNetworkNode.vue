<script setup lang="ts">
import { Handle, Position, type NodeProps } from '@vue-flow/core';
import { CircleAlert, CircleCheck, CircleHelp, LockKeyhole, Server } from '@lucide/vue';

export type DicomMapNodeData = {
    publicId: string;
    name: string;
    aeTitle: string;
    host: string;
    port: number;
    role: string;
    tlsEnabled: boolean;
    verificationStatus: string | null;
    verificationDurationMs: number | null;
    systemPublicId: string;
    systemName: string;
    systemType: string;
};

const props = defineProps<NodeProps<DicomMapNodeData>>();

const statusClass = (): string => {
    if (props.data.verificationStatus === 'success') {
        return 'border-emerald-300 bg-emerald-50/40';
    }

    if (props.data.verificationStatus !== null) {
        return 'border-red-300 bg-red-50/40';
    }

    return 'border-slate-300 bg-white';
};

const statusText = (): string => {
    if (props.data.verificationStatus === 'success') {
        return props.data.verificationDurationMs !== null
            ? `Erreichbar · ${props.data.verificationDurationMs} ms`
            : 'Erreichbar';
    }

    if (props.data.verificationStatus !== null) {
        return props.data.verificationStatus;
    }

    return 'Noch nicht geprüft';
};
</script>

<template>
    <article class="w-64 rounded-2xl border-2 p-4 shadow-sm transition" :class="statusClass()">
        <Handle type="target" :position="Position.Left" class="!h-3 !w-3 !border-2 !border-white !bg-blue-600" />

        <div class="flex items-start gap-3">
            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-100 text-blue-700">
                <Server :size="18" />
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-semibold text-blue-700">
                    {{ data.systemName }}
                </p>
                <h3 class="mt-0.5 truncate font-semibold text-slate-950">
                    {{ data.name }}
                </h3>
                <p class="mt-1 font-mono text-xs text-slate-600">
                    {{ data.aeTitle }}
                </p>
            </div>
        </div>

        <div class="mt-3 rounded-xl border border-slate-200/80 bg-white/80 px-3 py-2">
            <p class="font-mono text-xs text-slate-700">{{ data.host }}:{{ data.port }}</p>
            <div class="mt-2 flex items-center justify-between gap-2">
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 uppercase">
                    {{ data.role }}
                </span>

                <span
                    v-if="data.tlsEnabled"
                    class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700"
                >
                    <LockKeyhole :size="12" />
                    TLS
                </span>
            </div>
        </div>

        <div class="mt-3 flex items-center gap-2 text-xs font-medium">
            <CircleCheck v-if="data.verificationStatus === 'success'" :size="15" class="text-emerald-600" />
            <CircleAlert v-else-if="data.verificationStatus !== null" :size="15" class="text-red-600" />
            <CircleHelp v-else :size="15" class="text-slate-400" />

            <span
                :class="
                    data.verificationStatus === 'success'
                        ? 'text-emerald-700'
                        : data.verificationStatus !== null
                          ? 'text-red-700'
                          : 'text-slate-500'
                "
            >
                {{ statusText() }}
            </span>
        </div>

        <Handle type="source" :position="Position.Right" class="!h-3 !w-3 !border-2 !border-white !bg-blue-600" />
    </article>
</template>
