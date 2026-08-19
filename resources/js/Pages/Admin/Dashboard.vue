<script setup>
import { Head } from '@inertiajs/vue3'
import StatCard from '../../Components/Data/StatCard.vue'

defineProps({ stats: { type: Object, required: true } })
</script>

<template>
    <Head title="Übersicht" />

    <div class="min-h-screen bg-gray-100 p-6">
        <div class="mx-auto max-w-(--container-shell)">
            <h1 class="text-h2 font-semibold text-navy-700">Übersicht</h1>

            <div class="grid grid-cols-1 gap-4 pt-6 sm:grid-cols-2 lg:grid-cols-5">
                <StatCard label="Offene Anfragen" :value="stats.open_requests" />
                <StatCard label="Nicht vermittelt" :value="stats.needs_attention" :tone="stats.needs_attention ? 'warning' : 'default'" />
                <StatCard label="Laufende Aufträge" :value="stats.open_assignments" />
                <StatCard label="Wartende Partner" :value="stats.pending_assessors" :tone="stats.pending_assessors ? 'warning' : 'default'" />
                <StatCard label="Offene Provision" :cents="stats.open_commission_cents" />
            </div>
        </div>
    </div>
</template>
