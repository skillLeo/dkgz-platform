<script setup>
import { Head } from '@inertiajs/vue3'
import StatCard from '../../Components/Data/StatCard.vue'

defineProps({
    stats: { type: Object, required: true },
    availability: { type: Boolean, default: true },
})
</script>

<template>
    <Head title="Übersicht" />

    <div class="min-h-screen bg-gray-100 p-6">
        <div class="mx-auto max-w-(--container-shell)">
            <h1 class="text-h2 font-semibold text-navy-700">Übersicht</h1>

            <div class="grid grid-cols-1 gap-4 pt-6 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Offene Anfragen" :value="stats.open_requests" />
                <StatCard label="Laufende Aufträge" :value="stats.open_assignments" />
                <StatCard label="Abgeschlossen" :value="stats.completed_assignments" />
                <StatCard label="Offene Provision" :cents="stats.open_commission_cents" />
            </div>
        </div>
    </div>
</template>
