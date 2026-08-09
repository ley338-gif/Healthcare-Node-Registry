<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Copy, ExternalLink, LogOut, ShieldCheck } from '@lucide/vue';
import { ref } from 'vue';

const props = defineProps<{ secret: string; provisioningUri: string; required: boolean }>();
const form = useForm({ code: '' });
const logoutForm = useForm({});
const copied = ref(false);
const copySecret = async () => {
    await navigator.clipboard.writeText(props.secret);
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 2000);
};
</script>

<template>
    <Head title="MFA einrichten" />
    <main class="min-h-screen bg-slate-950 px-4 py-10">
        <section class="mx-auto w-full max-w-2xl rounded-2xl border border-slate-800 bg-white p-8 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-white">
                        <ShieldCheck :size="24" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Kontosicherheit</p>
                        <h1 class="text-xl font-semibold">Authenticator-App verbinden</h1>
                    </div>
                </div>
                <button
                    class="inline-flex items-center gap-2 text-sm text-slate-500"
                    @click="logoutForm.post('/logout')"
                >
                    <LogOut :size="16" /> Abmelden
                </button>
            </div>

            <div v-if="required" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                Dieses Konto besitzt privilegierte Berechtigungen. Die Einrichtung ist vor dem Zugriff auf die Registry
                verpflichtend.
            </div>

            <ol class="mt-6 space-y-5 text-sm text-slate-700">
                <li>
                    <p class="font-semibold">1. Konto in einer TOTP-kompatiblen Authenticator-App hinzufügen</p>
                    <a
                        :href="provisioningUri"
                        class="mt-2 inline-flex items-center gap-2 text-blue-700 hover:underline"
                    >
                        <ExternalLink :size="16" /> In Authenticator-App öffnen
                    </a>
                </li>
                <li>
                    <p class="font-semibold">2. Alternativ den Einrichtungsschlüssel manuell eingeben</p>
                    <div class="mt-2 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <code class="min-w-0 flex-1 text-sm break-all">{{ secret }}</code>
                        <button type="button" class="rounded-lg p-2 hover:bg-slate-200" @click="copySecret">
                            <Copy :size="17" />
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Typ: zeitbasiert (TOTP), 6 Stellen, 30 Sekunden.</p>
                    <p v-if="copied" class="mt-1 text-xs text-emerald-700">Schlüssel kopiert.</p>
                </li>
                <li>
                    <p class="font-semibold">3. Angezeigten Code bestätigen</p>
                    <form class="mt-2 flex flex-col gap-2 sm:flex-row" @submit.prevent="form.post('/mfa/setup')">
                        <input
                            v-model="form.code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            placeholder="123456"
                            required
                            class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2.5 font-mono tracking-widest"
                        />
                        <button
                            :disabled="form.processing"
                            class="rounded-xl bg-blue-600 px-5 py-2.5 font-semibold text-white disabled:opacity-60"
                        >
                            MFA aktivieren
                        </button>
                    </form>
                    <p v-if="form.errors.code" class="mt-1 text-sm text-red-700">{{ form.errors.code }}</p>
                </li>
            </ol>
        </section>
    </main>
</template>
