<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Copy, ShieldCheck } from '@lucide/vue';
import { ref } from 'vue';

const props = defineProps<{ codes: string[] }>();
const copied = ref(false);
const copyCodes = async () => {
    await navigator.clipboard.writeText(props.codes.join('\n'));
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 2000);
};
</script>

<template>
    <Head title="MFA-Wiederherstellungscodes" />
    <main class="grid min-h-screen place-items-center bg-slate-950 px-4 py-10">
        <section class="w-full max-w-xl rounded-2xl border border-slate-800 bg-white p-8 shadow-2xl">
            <div class="flex items-center gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-600 text-white">
                    <ShieldCheck :size="24" />
                </div>
                <div>
                    <p class="text-xs font-semibold tracking-wide text-emerald-700 uppercase">MFA aktiviert</p>
                    <h1 class="text-xl font-semibold">Wiederherstellungscodes sichern</h1>
                </div>
            </div>
            <p class="mt-5 text-sm text-slate-600">
                Jeder Code kann genau einmal verwendet werden. Speichern Sie diese Codes jetzt getrennt vom Gerät. Sie
                werden später nicht erneut angezeigt.
            </p>
            <div class="mt-5 grid grid-cols-1 gap-2 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                <code v-for="code in codes" :key="code" class="text-sm">{{ code }}</code>
            </div>
            <button
                type="button"
                class="mt-4 inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold"
                @click="copyCodes"
            >
                <Copy :size="17" /> {{ copied ? 'Kopiert' : 'Codes kopieren' }}
            </button>
            <Link href="/" class="mt-6 block rounded-xl bg-blue-600 px-4 py-2.5 text-center font-semibold text-white"
                >Ich habe die Codes sicher gespeichert</Link
            >
        </section>
    </main>
</template>
