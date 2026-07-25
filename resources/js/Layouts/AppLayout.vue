<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppHeader from '../Components/layout/AppHeader.vue';
import AppSidebar from '../Components/layout/AppSidebar.vue';
import type { PageProps } from '../types';
const page = usePage<PageProps>();
</script>
<template>
    <div class="min-h-screen bg-slate-50">
        <AppSidebar />
        <div class="lg:pl-72">
            <AppHeader />
            <main class="p-5 lg:p-8">
                <div
                    v-if="page.props.flash.success"
                    class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
                >
                    {{ page.props.flash.success }}
                </div>
                <div
                    v-if="page.props.flash.error"
                    class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
                >
                    {{ page.props.flash.error }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
