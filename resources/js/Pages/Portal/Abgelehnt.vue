<script setup>
import { Head } from '@inertiajs/vue3'
import { ThumbsDown } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import ReferenceNumber from '../../Components/Data/ReferenceNumber.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({ requests: { type: Object, required: true } })

const { dateTime } = useGermanFormat()

const columns = [
    { key: 'reference', label: 'Referenz', mono: true, cardRole: 'primary' },
    { key: 'service_type_name', label: 'Art', cardRole: 'meta' },
    { key: 'location', label: 'Standort', cardRole: 'meta' },
    { key: 'decline_reason', label: 'Begründung', cardRole: 'meta' },
    { key: 'responded_at', label: 'Abgelehnt am', mono: true },
]

const rows = props.requests.data.map((r) => ({ ...r, service_type_name: r.service_type?.name }))
</script>

<template>
    <Head title="Abgelehnte Anfragen" />

    <PortalLayout title="Abgelehnte Anfragen">
        <PageHeader
            title="Abgelehnte Anfragen"
            description="Eine Ablehnung wirkt sich nicht auf die weitere Verteilung aus."
        />

        <DataTable
            :columns="columns"
            :rows="rows"
            :meta="requests"
            empty-title="Keine abgelehnten Anfragen"
            :empty-icon="ThumbsDown"
        >
            <template #cell-reference="{ row }"><ReferenceNumber :value="row.reference" /></template>
            <template #cell-decline_reason="{ row }"><span class="text-sm text-gray-600">{{ row.decline_reason ?? '—' }}</span></template>
            <template #cell-responded_at="{ row }"><span class="font-mono text-sm tabular-nums text-gray-600">{{ dateTime(row.responded_at) }}</span></template>
        </DataTable>
    </PortalLayout>
</template>
