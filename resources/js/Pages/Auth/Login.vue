<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { LockKeyhole, ShieldCheck } from '@lucide/vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Anmelden" />

    <main class="grid min-h-screen place-items-center bg-slate-950 px-4">
        <section class="w-full max-w-md rounded-2xl border border-slate-800 bg-white p-8 shadow-2xl">
            <div class="mb-7 flex items-center gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-white">
                    <ShieldCheck :size="24" />
                </div>
                <div>
                    <h1 class="text-xl font-semibold">Healthcare Node Registry</h1>
                    <p class="text-sm text-slate-500">Sicherer Administrationszugang</p>
                </div>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium">E-Mail-Adresse</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="username"
                        required
                        autofocus
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 ring-blue-500 outline-none focus:ring-2"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-700">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium">Passwort</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 ring-blue-500 outline-none focus:ring-2"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-700">{{ form.errors.password }}</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input v-model="form.remember" type="checkbox" class="rounded border-slate-300" />
                    Sitzung merken
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 font-medium text-white hover:bg-blue-700 disabled:opacity-60"
                >
                    <LockKeyhole :size="18" />
                    {{ form.processing ? 'Anmeldung läuft …' : 'Anmelden' }}
                </button>
            </form>

            <p class="mt-6 text-center text-xs text-slate-500">
                Keine verpflichtende Cloud · keine verpflichtende Telemetrie
            </p>
        </section>
    </main>
</template>
