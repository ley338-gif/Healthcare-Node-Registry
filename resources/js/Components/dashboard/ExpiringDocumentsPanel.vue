<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, CalendarClock, CircleAlert, FileClock } from '@lucide/vue';

export type ExpiringDocument = {
    publicId: string;
    title: string;
    categoryLabel: string;
    contextName: string;
    validUntil: string;
    daysRemaining: number;
    status: 'expired' | 'expiring_soon';
    unread: boolean;
    href: string;
};

defineProps<{
    total: number;
    expired: number;
    expiringSoon: number;
    warningDays: number;
    items: ExpiringDocument[];
}>();

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`));

const deadlineLabel = (item: ExpiringDocument): string => {
    if (item.daysRemaining < 0) {
        const days = Math.abs(item.daysRemaining);

        return `Seit ${days} ${days === 1 ? 'Tag' : 'Tagen'} abgelaufen`;
    }
    if (item.daysRemaining === 0) {
        return 'Läuft heute ab';
    }

    return `Noch ${item.daysRemaining} ${item.daysRemaining === 1 ? 'Tag' : 'Tage'}`;
};
</script>

<template>
    <section class="mt-6 rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-700">
                    <CalendarClock :size="19" />
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Ablaufende Dokumente</h2>
                    <p class="text-sm text-slate-500">Fristen innerhalb der nächsten {{ warningDays }} Tage</p>
                </div>
            </div>

            <Link
                href="/documents?document_validity=expiring_soon"
                class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-800"
            >
                Dokumente öffnen
                <ArrowRight :size="15" />
            </Link>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 px-4 py-3">
                <p class="text-xs text-slate-500">Hinweise gesamt</p>
                <p class="mt-1 text-xl font-semibold text-slate-950">{{ total }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-xs text-amber-700">Läuft bald ab</p>
                <p class="mt-1 text-xl font-semibold text-amber-800">{{ expiringSoon }}</p>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                <p class="text-xs text-rose-700">Bereits abgelaufen</p>
                <p class="mt-1 text-xl font-semibold text-rose-800">{{ expired }}</p>
            </div>
        </div>

        <div v-if="items.length > 0" class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200">
            <Link
                v-for="item in items"
                :key="item.publicId"
                :href="item.href"
                class="flex items-start gap-3 px-4 py-3.5 transition hover:bg-slate-50"
            >
                <div
                    class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl"
                    :class="item.status === 'expired' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700'"
                >
                    <CircleAlert v-if="item.status === 'expired'" :size="17" />
                    <FileClock v-else :size="17" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span
                            v-if="item.unread"
                            class="h-2 w-2 shrink-0 rounded-full bg-blue-600"
                            title="Neuer Hinweis"
                        />
                        <p class="truncate text-sm font-semibold text-slate-900">{{ item.title }}</p>
                    </div>
                    <p class="mt-0.5 truncate text-xs text-slate-500">
                        {{ item.categoryLabel }}<template v-if="item.contextName"> · {{ item.contextName }}</template>
                    </p>
                </div>

                <div class="shrink-0 text-right">
                    <p
                        class="text-xs font-semibold"
                        :class="item.status === 'expired' ? 'text-rose-700' : 'text-amber-700'"
                    >
                        {{ deadlineLabel(item) }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">{{ formatDate(item.validUntil) }}</p>
                </div>
            </Link>
        </div>

        <div v-else class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-6 text-center">
            <p class="text-sm font-semibold text-emerald-800">Keine Dokumentfristen im Warnzeitraum.</p>
        </div>
    </section>
</template>
