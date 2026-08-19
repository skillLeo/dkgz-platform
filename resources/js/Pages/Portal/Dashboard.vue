<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Inbox } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import StatCard from '../../Components/Data/StatCard.vue'

const props = defineProps({
    stats: { type: Object, required: true },
    availability: { type: Boolean, default: true },
})
</script>

<template>
    <Head title="Übersicht" />

    <PortalLayout title="Übersicht" :open-requests="stats.open_requests">
        <PageHeader title="Übersicht" description="Ihr aktueller Stand im Partnernetz." />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Offene Anfragen" :value="stats.open_requests" href="/portal/anfragen" :tone="stats.open_requests ? 'warning' : 'default'" />
            <StatCard label="Laufende Aufträge" :value="stats.open_assignments" href="/portal/auftraege" />
            <StatCard label="Abgeschlossen" :value="stats.completed_assignments" />
            <StatCard label="Offene Provision" :cents="stats.open_commission_cents" href="/portal/provisionen" />
        </div>

        <div v-if="!availability" class="mt-6 border border-warning bg-warning/5 p-4">
            <p class="text-base font-medium text-gray-800">Sie sind derzeit auf „Nicht verfügbar“ gesetzt</p>
            <p class="measure pt-1 text-sm leading-normal text-gray-600">
                Solange das so bleibt, erhalten Sie keine neuen Anfragen aus Ihrem Einsatzgebiet.
            </p>
        </div>

        <div v-else-if="stats.open_requests" class="mt-6 flex items-start gap-3 border border-navy-700 bg-navy-100 p-4">
            <Inbox :size="18" :stroke-width="1.5" class="mt-0.5 shrink-0 text-navy-700" aria-hidden="true" />
            <div class="min-w-0">
                <p class="text-base font-medium text-gray-800">
                    {{ stats.open_requests }} {{ stats.open_requests === 1 ? 'Anfrage wartet' : 'Anfragen warten' }} auf Ihre Rückmeldung
                </p>
                <p class="measure pt-1 text-sm leading-normal text-gray-600">
                    Der erste verfügbare Partner übernimmt. Eine Ablehnung wirkt sich nicht auf die weitere Verteilung aus.
                </p>
                <Link href="/portal/anfragen" class="mt-3 inline-block text-sm font-medium text-navy-700 hover:text-navy-500">
                    Anfragen ansehen
                </Link>
            </div>
        </div>
    </PortalLayout>
</template>
