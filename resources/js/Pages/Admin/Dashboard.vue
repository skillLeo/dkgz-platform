<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { AlertTriangle } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import StatCard from '../../Components/Data/StatCard.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'

defineProps({ stats: { type: Object, required: true } })
</script>

<template>
    <Head title="Übersicht" />

    <AdminLayout title="Übersicht" :pending-assessors="stats.pending_assessors">
        <PageHeader title="Übersicht" description="Der aktuelle Stand der Vermittlung." />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <StatCard label="Offene Anfragen" :value="stats.open_requests" href="/admin/anfragen" />
            <StatCard
                label="Nicht vermittelt"
                :value="stats.needs_attention"
                :tone="stats.needs_attention ? 'warning' : 'default'"
                hint="Kein Partner im Gebiet oder alle abgelehnt"
                href="/admin/anfragen?nicht_vermittelt=1"
            />
            <StatCard label="Laufende Aufträge" :value="stats.open_assignments" href="/admin/auftraege" />
            <StatCard
                label="Wartende Partner"
                :value="stats.pending_assessors"
                :tone="stats.pending_assessors ? 'warning' : 'default'"
                href="/admin/sachverstaendige?status=pending"
            />
            <StatCard label="Offene Provision" :cents="stats.open_commission_cents" href="/admin/provisionen?status=open" />
        </div>

        <div v-if="stats.needs_attention" class="mt-6 flex items-start gap-3 border border-warning bg-warning/5 p-4">
            <AlertTriangle :size="18" :stroke-width="1.5" class="mt-0.5 shrink-0 text-warning" aria-hidden="true" />
            <div class="min-w-0">
                <p class="text-base font-medium text-gray-800">
                    {{ stats.needs_attention }} Anfragen warten auf eine manuelle Zuordnung
                </p>
                <p class="measure pt-1 text-sm leading-normal text-gray-600">
                    Für diese Anfragen deckt kein Partner das Gebiet ab, oder alle angefragten Partner haben abgelehnt.
                </p>
                <Link href="/admin/anfragen?nicht_vermittelt=1" class="mt-3 inline-block text-sm font-medium text-navy-700 hover:text-navy-500">
                    Anfragen ansehen
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
