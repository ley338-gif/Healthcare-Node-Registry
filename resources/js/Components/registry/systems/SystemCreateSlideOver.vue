<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { computed } from 'vue';

type SelectOption = {
    value: string;
    label: string;
};

type OrganizationOption = {
    id: number;
    name: string;
};

type SiteOption = {
    id: number;
    organization_id: number;
    name: string;
};

type DepartmentOption = {
    id: number;
    site_id: number;
    name: string;
};

const props = defineProps<{
    open: boolean;
    organizations: OrganizationOption[];
    sites: SiteOption[];
    departments: DepartmentOption[];
    systemTypes: SelectOption[];
    statuses: SelectOption[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const form = useForm({
    organization_id: null as number | null,
    site_id: null as number | null,
    department_id: null as number | null,
    name: '',
    system_type: '',
    status: 'active',
    hostname: '',
    fqdn: '',
    ip_address: '',
});

const availableSites = computed(() => props.sites.filter((site) => site.organization_id === form.organization_id));

const availableDepartments = computed(() =>
    props.departments.filter((department) => department.site_id === form.site_id),
);

const close = (): void => {
    if (form.processing) {
        return;
    }

    form.reset();
    form.clearErrors();
    emit('close');
};

const organizationChanged = (): void => {
    form.site_id = null;
    form.department_id = null;
};

const siteChanged = (): void => {
    form.department_id = null;
};

const submit = (): void => {
    form.post('/systems', {
        preserveScroll: true,
        onSuccess: close,
    });
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50"
            role="dialog"
            aria-modal="true"
            aria-labelledby="create-system-title"
        >
            <button
                type="button"
                aria-label="Formular schließen"
                class="absolute inset-0 bg-slate-950/40"
                @click="close"
            />

            <aside class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold tracking-wider text-blue-600 uppercase">System Registry</p>

                        <h2 id="create-system-title" class="mt-1 text-xl font-semibold text-slate-950">Neues System</h2>

                        <p class="mt-1 text-sm text-slate-500">Ein technisches oder fachliches System erfassen.</p>
                    </div>

                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="close">
                        <X :size="20" />
                    </button>
                </header>

                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
                    <div class="flex-1 space-y-7 overflow-y-auto px-6 py-6">
                        <section>
                            <h3 class="text-sm font-semibold text-slate-950">Zuordnung</h3>

                            <div class="mt-4 space-y-4">
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700"> Organisation * </span>

                                    <select
                                        v-model="form.organization_id"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                        @change="organizationChanged"
                                    >
                                        <option :value="null">Bitte auswählen</option>

                                        <option
                                            v-for="organization in organizations"
                                            :key="organization.id"
                                            :value="organization.id"
                                        >
                                            {{ organization.name }}
                                        </option>
                                    </select>

                                    <span v-if="form.errors.organization_id" class="mt-1 block text-xs text-red-600">
                                        {{ form.errors.organization_id }}
                                    </span>
                                </label>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label>
                                        <span class="text-sm font-medium text-slate-700"> Standort </span>

                                        <select
                                            v-model="form.site_id"
                                            :disabled="form.organization_id === null"
                                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm disabled:bg-slate-100"
                                            @change="siteChanged"
                                        >
                                            <option :value="null">Keine Zuordnung</option>

                                            <option v-for="site in availableSites" :key="site.id" :value="site.id">
                                                {{ site.name }}
                                            </option>
                                        </select>
                                    </label>

                                    <label>
                                        <span class="text-sm font-medium text-slate-700"> Abteilung </span>

                                        <select
                                            v-model="form.department_id"
                                            :disabled="form.site_id === null"
                                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm disabled:bg-slate-100"
                                        >
                                            <option :value="null">Keine Zuordnung</option>

                                            <option
                                                v-for="department in availableDepartments"
                                                :key="department.id"
                                                :value="department.id"
                                            >
                                                {{ department.name }}
                                            </option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-sm font-semibold text-slate-950">System</h3>

                            <div class="mt-4 space-y-4">
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700"> Name * </span>

                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    />

                                    <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600">
                                        {{ form.errors.name }}
                                    </span>
                                </label>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label>
                                        <span class="text-sm font-medium text-slate-700"> Systemtyp * </span>

                                        <select
                                            v-model="form.system_type"
                                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                        >
                                            <option value="">Bitte auswählen</option>

                                            <option
                                                v-for="option in systemTypes"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </label>

                                    <label>
                                        <span class="text-sm font-medium text-slate-700"> Status * </span>

                                        <select
                                            v-model="form.status"
                                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                        >
                                            <option
                                                v-for="option in statuses"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-sm font-semibold text-slate-950">Netzwerk</h3>

                            <div class="mt-4 space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label>
                                        <span class="text-sm font-medium text-slate-700"> Hostname </span>

                                        <input
                                            v-model="form.hostname"
                                            type="text"
                                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                        />
                                    </label>

                                    <label>
                                        <span class="text-sm font-medium text-slate-700"> IP-Adresse </span>

                                        <input
                                            v-model="form.ip_address"
                                            type="text"
                                            placeholder="10.10.10.20"
                                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                        />

                                        <span v-if="form.errors.ip_address" class="mt-1 block text-xs text-red-600">
                                            {{ form.errors.ip_address }}
                                        </span>
                                    </label>
                                </div>

                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700"> FQDN </span>

                                    <input
                                        v-model="form.fqdn"
                                        type="text"
                                        placeholder="pacs01.example.local"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                    />
                                </label>
                            </div>
                        </section>
                    </div>

                    <footer class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            @click="close"
                        >
                            Abbrechen
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Wird gespeichert …' : 'System anlegen' }}
                        </button>
                    </footer>
                </form>
            </aside>
        </div>
    </Teleport>
</template>
