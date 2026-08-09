<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { KeyRound, ShieldCheck } from '@lucide/vue';

const form = useForm({ code: '' });
const submit = () => form.post('/mfa/challenge', { onFinish: () => form.reset('code') });
</script>

<template>
    <Head title="MFA bestätigen" />
    <main class="grid min-h-screen place-items-center bg-slate-950 px-4">
        <section class="w-full max-w-md rounded-2xl border border-slate-800 bg-white p-8 shadow-2xl">
            <div class="mb-7 flex items-center gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-white">
                    <ShieldCheck :size="24" />
                </div>
                <div>
                    <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Zweiter Faktor</p>
                    <h1 class="text-xl font-semibold">Anmeldung bestätigen</h1>
                </div>
            </div>
            <p class="mb-5 text-sm text-slate-600">
                Geben Sie den sechsstelligen Code Ihrer Authenticator-App oder einen unbenutzten Wiederherstellungscode
                ein.
            </p>
            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label for="code" class="mb-1.5 block text-sm font-medium">MFA-Code</label>
                    <input
                        id="code"
                        v-model="form.code"
                        type="text"
                        inputmode="text"
                        autocomplete="one-time-code"
                        maxlength="64"
                        required
                        autofocus
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 font-mono tracking-wider ring-blue-500 outline-none focus:ring-2"
                    />
                    <p v-if="form.errors.code" class="mt-1 text-sm text-red-700">{{ form.errors.code }}</p>
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 font-medium text-white hover:bg-blue-700 disabled:opacity-60"
                >
                    <KeyRound :size="18" />
                    {{ form.processing ? 'Code wird geprüft …' : 'Anmeldung abschließen' }}
                </button>
            </form>
            <a href="/login" class="mt-5 block text-center text-sm text-slate-500 hover:text-slate-700"
                >Zurück zur Anmeldung</a
            >
        </section>
    </main>
</template>
