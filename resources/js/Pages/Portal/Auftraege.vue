<script setup>
import { Head } from '@inertiajs/vue3'
import { FileText } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import ReferenceNumber from '../../Components/Data/ReferenceNumber.vue'

const props = defineProps({ assignments: { type: Object, required: true } })

const columns = [
    { key: 'reference', label: 'Referenz', mono: true, cardRole: 'primary' },
    { key: 'status', label: 'Status', cardRole: 'status' },
    { key: 'service_type', label: 'Art', cardRole: 'meta' },
    { key: 'location', label: 'Standort', cardRole: 'meta' },
    { key: 'fee_cents', label: 'Honorar', align: 'right', mono: true, cardRole: 'meta' },
]

const rows = props.assignments.data.map((a) => ({
    ...a,
    reference: a.request.reference,
    service_type: a.request.service_type,
    location: a.request.location,
}))
</script>

<template>
    <Head title="Aufträge" />

    <PortalLayout title="Aufträge">
        <PageHeader title="Aufträge" description="Die Anfragen, die Sie übernommen haben." />

        <DataTable
            :columns="columns"
            :rows="rows"
            :meta="assignments"
            :row-href="(row) => `/portal/auftraege/${row.id}`"
            empty-title="Noch keine Aufträge"
            empty-description="Sobald Sie eine Anfrage annehmen, erscheint sie hier als Auftrag."
            :empty-icon="FileText"
        >
            <template #cell-reference="{ row }"><ReferenceNumber :value="row.reference" /></template>
            <template #cell-status="{ row }"><StatusDot :status="row.status" :label="row.status_label" /></template>
            <template #cell-fee_cents="{ row }"><MoneyValue :cents="row.fee_cents" /></template>
        </DataTable>
    </PortalLayout>
</template>
