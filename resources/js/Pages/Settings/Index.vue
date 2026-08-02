<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { KeyRound, Pencil, Plus, RotateCcw, Search, ShieldCheck, Trash2, Users, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import Pagination from '../../Components/Pagination.vue';
import PageHeader from '../../Components/ui/PageHeader.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type Role = { id: number; name: string; display_name: string; users_count: number; permissions: Permission[] };
type Permission = { id: number; name: string; display_name: string; group?: string };
type User = {
    public_id: string;
    name: string;
    email: string;
    is_active: boolean;
    roles: Role[];
    last_activity: number | null;
    diagnostic_test_runs_count: number;
};
type PaginatedUsers = {
    data: User[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total: number;
};
const props = defineProps<{
    users: PaginatedUsers | null;
    roles: Role[];
    permissions: Record<string, Permission[]>;
    filters: { search: string; status: string; role: number | null };
    canManageUsers: boolean;
    canManageRoles: boolean;
    currentUserId: string;
}>();
const tab = ref<'users' | 'roles'>(props.canManageUsers ? 'users' : 'roles');
const search = ref(props.filters.search);
const status = ref(props.filters.status);
const roleFilter = ref(props.filters.role ? String(props.filters.role) : '');
const userOpen = ref(false);
const roleOpen = ref(false);
const passwordOpen = ref(false);
const editingUser = ref<User | null>(null);
const editingRole = ref<Role | null>(null);
const selectedUser = ref<User | null>(null);
const userForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_active: true,
    role_ids: [] as number[],
});
const roleForm = useForm({ name: '', display_name: '', permission_ids: [] as number[] });
const passwordForm = useForm({ password: '', password_confirmation: '' });
const activeUsers = computed(() => props.users?.data.filter((user) => user.is_active).length ?? 0);
const applyFilters = () =>
    router.get(
        '/settings',
        { search: search.value || undefined, status: status.value || undefined, role: roleFilter.value || undefined },
        { preserveState: true, replace: true },
    );
const resetFilters = () => {
    search.value = status.value = roleFilter.value = '';
    applyFilters();
};
const openCreateUser = () => {
    editingUser.value = null;
    userForm.reset();
    userForm.clearErrors();
    userForm.is_active = true;
    userOpen.value = true;
};
const openEditUser = (user: User) => {
    editingUser.value = user;
    userForm.clearErrors();
    userForm.name = user.name;
    userForm.email = user.email;
    userForm.is_active = user.is_active;
    userForm.role_ids = user.roles.map((role) => role.id);
    userForm.password = userForm.password_confirmation = '';
    userOpen.value = true;
};
const saveUser = () => {
    if (editingUser.value)
        userForm.put(`/settings/users/${editingUser.value.public_id}`, {
            preserveScroll: true,
            onSuccess: () => (userOpen.value = false),
        });
    else userForm.post('/settings/users', { preserveScroll: true, onSuccess: () => (userOpen.value = false) });
};
const openPassword = (user: User) => {
    selectedUser.value = user;
    passwordForm.reset();
    passwordForm.clearErrors();
    passwordOpen.value = true;
};
const savePassword = () => {
    if (selectedUser.value)
        passwordForm.put(`/settings/users/${selectedUser.value.public_id}/password`, {
            preserveScroll: true,
            onSuccess: () => (passwordOpen.value = false),
        });
};
const openCreateRole = () => {
    editingRole.value = null;
    roleForm.reset();
    roleForm.clearErrors();
    roleOpen.value = true;
};
const openEditRole = (role: Role) => {
    editingRole.value = role;
    roleForm.clearErrors();
    roleForm.name = role.name;
    roleForm.display_name = role.display_name;
    roleForm.permission_ids = role.permissions.map((permission) => permission.id);
    roleOpen.value = true;
};
const saveRole = () => {
    if (editingRole.value)
        roleForm.put(`/settings/roles/${editingRole.value.id}`, {
            preserveScroll: true,
            onSuccess: () => (roleOpen.value = false),
        });
    else roleForm.post('/settings/roles', { preserveScroll: true, onSuccess: () => (roleOpen.value = false) });
};
const deleteRole = (role: Role) => {
    if (window.confirm(`Rolle „${role.display_name}“ wirklich löschen?`))
        router.delete(`/settings/roles/${role.id}`, { preserveScroll: true });
};
const formatActivity = (value: number | null) =>
    value
        ? new Intl.DateTimeFormat('de-DE', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value * 1000))
        : 'Keine aktive Sitzung';
</script>

<template>
    <Head title="Einstellungen" />
    <AppLayout>
        <PageHeader
            eyebrow="Administration"
            title="Einstellungen"
            description="Benutzer, Rollen und zentrale Verwaltungsoptionen der Registry."
        />

        <div class="mt-6 flex gap-2 border-b border-slate-200">
            <button
                v-if="canManageUsers"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold"
                :class="tab === 'users' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500'"
                @click="tab = 'users'"
            >
                <Users :size="17" /> Benutzerverwaltung
            </button>
            <button
                v-if="canManageRoles"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold"
                :class="tab === 'roles' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500'"
                @click="tab = 'roles'"
            >
                <ShieldCheck :size="17" /> Rollen & Berechtigungen
            </button>
        </div>

        <template v-if="tab === 'users' && canManageUsers && users">
            <section class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Benutzer gesamt</p>
                    <p class="mt-1 text-2xl font-semibold">{{ users.total }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Aktiv auf dieser Seite</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ activeUsers }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Definierte Rollen</p>
                    <p class="mt-1 text-2xl font-semibold">{{ roles.length }}</p>
                </div>
            </section>
            <section class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="grid gap-3 p-4 md:grid-cols-[minmax(240px,1fr)_220px_220px_auto_auto]">
                    <div class="relative">
                        <Search :size="17" class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" /><input
                            v-model="search"
                            class="w-full rounded-xl border border-slate-300 py-2.5 pr-3 pl-10 text-sm"
                            placeholder="Name oder E-Mail"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <select v-model="status" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Alle Status</option>
                        <option value="active">Aktiv</option>
                        <option value="inactive">Deaktiviert</option>
                    </select>
                    <select v-model="roleFilter" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Alle Rollen</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.display_name }}</option>
                    </select>
                    <button
                        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
                        @click="applyFilters"
                    >
                        Filtern
                    </button>
                    <button
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-3 text-sm font-semibold"
                        @click="resetFilters"
                    >
                        <RotateCcw :size="16" /> Zurücksetzen
                    </button>
                </div>
                <div class="flex justify-end border-t border-slate-100 px-4 py-3">
                    <button
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
                        @click="openCreateUser"
                    >
                        <Plus :size="17" /> Benutzer anlegen
                    </button>
                </div>
            </section>
            <section class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3">Benutzer</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Rollen</th>
                                <th class="hidden px-4 py-3 lg:table-cell">Letzte Aktivität</th>
                                <th class="px-4 py-3 text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in users.data" :key="user.public_id">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">
                                        {{ user.name }}
                                        <span
                                            v-if="user.public_id === currentUserId"
                                            class="text-xs font-normal text-blue-600"
                                            >(Sie)</span
                                        >
                                    </p>
                                    <p class="text-xs text-slate-500">{{ user.email }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="
                                            user.is_active
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-slate-100 text-slate-600'
                                        "
                                        >{{ user.is_active ? 'Aktiv' : 'Deaktiviert' }}</span
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="role in user.roles"
                                            :key="role.id"
                                            class="rounded-full bg-blue-50 px-2 py-1 text-xs text-blue-700"
                                            >{{ role.display_name }}</span
                                        ><span v-if="user.roles.length === 0" class="text-xs text-slate-400"
                                            >Keine Rolle</span
                                        >
                                    </div>
                                </td>
                                <td class="hidden px-4 py-3 text-slate-500 lg:table-cell">
                                    {{ formatActivity(user.last_activity) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <button
                                            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                                            title="Bearbeiten"
                                            @click="openEditUser(user)"
                                        >
                                            <Pencil :size="16" /></button
                                        ><button
                                            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                                            title="Passwort setzen"
                                            @click="openPassword(user)"
                                        >
                                            <KeyRound :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                                    Keine Benutzer für diese Auswahl.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="users.links.length > 3" class="border-t border-slate-200 p-4">
                    <Pagination :links="users.links" />
                </div>
            </section>
        </template>

        <template v-else-if="tab === 'roles' && canManageRoles">
            <div class="mt-5 flex justify-end">
                <button
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
                    @click="openCreateRole"
                >
                    <Plus :size="17" /> Rolle anlegen
                </button>
            </div>
            <section class="mt-4 grid gap-4 xl:grid-cols-2">
                <article
                    v-for="role in roles"
                    :key="role.id"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <ShieldCheck :size="18" class="text-blue-600" />
                                <h2 class="font-semibold text-slate-900">{{ role.display_name }}</h2>
                            </div>
                            <p class="mt-1 font-mono text-xs text-slate-400">{{ role.name }}</p>
                        </div>
                        <div v-if="role.name !== 'system-administrator'" class="flex gap-1">
                            <button
                                class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                                @click="openEditRole(role)"
                            >
                                <Pencil :size="16" /></button
                            ><button
                                class="rounded-lg p-2 text-red-500 hover:bg-red-50 disabled:opacity-30"
                                :disabled="role.users_count > 0"
                                @click="deleteRole(role)"
                            >
                                <Trash2 :size="16" />
                            </button>
                        </div>
                        <span v-else class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                            >Geschützt</span
                        >
                    </div>
                    <p class="mt-4 text-sm text-slate-500">
                        {{ role.users_count }} Benutzer · {{ role.permissions.length }} Berechtigungen
                    </p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <span
                            v-for="permission in role.permissions"
                            :key="permission.id"
                            class="rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-600"
                            >{{ permission.display_name }}</span
                        ><span v-if="role.permissions.length === 0" class="text-xs text-slate-400"
                            >Keine Berechtigungen</span
                        >
                    </div>
                </article>
            </section>
        </template>

        <Teleport to="body"
            ><div
                v-if="userOpen"
                class="fixed inset-0 z-50 flex justify-end bg-slate-950/30"
                @click.self="userOpen = false"
            >
                <aside class="h-full w-full max-w-xl overflow-y-auto bg-white p-6 shadow-2xl">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">
                                Benutzerverwaltung
                            </p>
                            <h2 class="mt-1 text-xl font-semibold">
                                {{ editingUser ? 'Benutzer bearbeiten' : 'Benutzer anlegen' }}
                            </h2>
                        </div>
                        <button @click="userOpen = false"><X :size="20" /></button>
                    </div>
                    <form class="mt-6 space-y-4" @submit.prevent="saveUser">
                        <label class="block text-sm font-medium"
                            >Name<input
                                v-model="userForm.name"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                        /></label>
                        <p v-if="userForm.errors.name" class="text-sm text-red-600">{{ userForm.errors.name }}</p>
                        <label class="block text-sm font-medium"
                            >E-Mail<input
                                v-model="userForm.email"
                                type="email"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                        /></label>
                        <p v-if="userForm.errors.email" class="text-sm text-red-600">{{ userForm.errors.email }}</p>
                        <template v-if="!editingUser"
                            ><label class="block text-sm font-medium"
                                >Initiales Passwort<input
                                    v-model="userForm.password"
                                    type="password"
                                    autocomplete="new-password"
                                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label
                            ><label class="block text-sm font-medium"
                                >Passwort bestätigen<input
                                    v-model="userForm.password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                            /></label>
                            <p v-if="userForm.errors.password" class="text-sm text-red-600">
                                {{ userForm.errors.password }}
                            </p></template
                        >
                        <fieldset v-if="canManageRoles">
                            <legend class="text-sm font-medium">Rollen</legend>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                <label
                                    v-for="role in roles"
                                    :key="role.id"
                                    class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm"
                                    ><input v-model="userForm.role_ids" type="checkbox" :value="role.id" />
                                    {{ role.display_name }}</label
                                >
                            </div>
                        </fieldset>
                        <label class="flex items-center gap-2 text-sm"
                            ><input
                                v-model="userForm.is_active"
                                type="checkbox"
                                :disabled="editingUser?.public_id === currentUserId"
                            />
                            Konto aktiv</label
                        >
                        <p v-if="userForm.errors.is_active || userForm.errors.role_ids" class="text-sm text-red-600">
                            {{ userForm.errors.is_active || userForm.errors.role_ids }}
                        </p>
                        <div class="flex justify-end gap-2 pt-4">
                            <button
                                type="button"
                                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold"
                                @click="userOpen = false"
                            >
                                Abbrechen</button
                            ><button
                                :disabled="userForm.processing"
                                class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
                            >
                                Speichern
                            </button>
                        </div>
                    </form>
                </aside>
            </div></Teleport
        >

        <Teleport to="body"
            ><div
                v-if="passwordOpen && selectedUser"
                class="fixed inset-0 z-50 grid place-items-center bg-slate-950/30 p-4"
                @click.self="passwordOpen = false"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-xs font-semibold text-blue-700 uppercase">Passwort setzen</p>
                            <h2 class="mt-1 text-xl font-semibold">{{ selectedUser.name }}</h2>
                        </div>
                        <button @click="passwordOpen = false"><X :size="20" /></button>
                    </div>
                    <form class="mt-5 space-y-4" @submit.prevent="savePassword">
                        <p class="text-sm text-slate-500">
                            Alle bestehenden Sitzungen dieses Benutzers werden beendet.
                        </p>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Neues Passwort"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5"
                        /><input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Passwort bestätigen"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5"
                        />
                        <p v-if="passwordForm.errors.password" class="text-sm text-red-600">
                            {{ passwordForm.errors.password }}
                        </p>
                        <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">
                            Passwort speichern
                        </button>
                    </form>
                </div>
            </div></Teleport
        >

        <Teleport to="body"
            ><div
                v-if="roleOpen"
                class="fixed inset-0 z-50 flex justify-end bg-slate-950/30"
                @click.self="roleOpen = false"
            >
                <aside class="h-full w-full max-w-2xl overflow-y-auto bg-white p-6 shadow-2xl">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-xs font-semibold text-blue-700 uppercase">Rollenverwaltung</p>
                            <h2 class="mt-1 text-xl font-semibold">
                                {{ editingRole ? 'Rolle bearbeiten' : 'Rolle anlegen' }}
                            </h2>
                        </div>
                        <button @click="roleOpen = false"><X :size="20" /></button>
                    </div>
                    <form class="mt-6 space-y-4" @submit.prevent="saveRole">
                        <label class="block text-sm font-medium"
                            >Technischer Name<input
                                v-model="roleForm.name"
                                placeholder="registry-editor"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono" /></label
                        ><label class="block text-sm font-medium"
                            >Anzeigename<input
                                v-model="roleForm.display_name"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"
                        /></label>
                        <p v-if="roleForm.errors.name || roleForm.errors.display_name" class="text-sm text-red-600">
                            {{ roleForm.errors.name || roleForm.errors.display_name }}
                        </p>
                        <fieldset>
                            <legend class="text-sm font-semibold">Berechtigungen</legend>
                            <div v-for="(items, group) in permissions" :key="group" class="mt-4">
                                <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ group }}</p>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    <label
                                        v-for="permission in items"
                                        :key="permission.id"
                                        class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm"
                                        ><input
                                            v-model="roleForm.permission_ids"
                                            type="checkbox"
                                            :value="permission.id"
                                            class="mt-0.5"
                                        /><span
                                            ><span class="font-medium">{{ permission.display_name }}</span
                                            ><span class="block font-mono text-[11px] text-slate-400">{{
                                                permission.name
                                            }}</span></span
                                        ></label
                                    >
                                </div>
                            </div>
                        </fieldset>
                        <p v-if="roleForm.errors.permission_ids" class="text-sm text-red-600">
                            {{ roleForm.errors.permission_ids }}
                        </p>
                        <div class="flex justify-end gap-2 pt-4">
                            <button
                                type="button"
                                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold"
                                @click="roleOpen = false"
                            >
                                Abbrechen</button
                            ><button
                                :disabled="roleForm.processing"
                                class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
                            >
                                Speichern
                            </button>
                        </div>
                    </form>
                </aside>
            </div></Teleport
        >
    </AppLayout>
</template>
