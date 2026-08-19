<script setup>
import { Head } from '@inertiajs/vue3'
import { Inbox } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import ReferenceNumber from '../../Components/Data/ReferenceNumber.vue'

const props = defineProps({ requests: { type: Object, required: true } })

const columns = [
    { key: 'reference', label: 'Referenz', mono: true, cardRole: 'primary' },
    { key: 'service_type_name', label: 'Art des Gutachtens', cardRole: 'meta' },
    { key: 'location', label: 'Standort', cardRole: 'meta' },
    { key: 'vehicle', label: 'Fahrzeug', cardRole: 'meta' },
    { key: 'created_at_label', label: 'Eingegangen', mono: true },
]

// The resource nests the service type; flatten it for the table.
const rows = props.requests.data.map((r) => ({ ...r, service_type_name: r.service_type?.name }))
</script>

<template>
    <Head title="Neue Anfragen" />

    <PortalLayout title="Neue Anfragen" :open-requests="requests.total">
        <PageHeader
            title="Neue Anfragen"
            description="Anfragen aus Ihrem Einsatzgebiet. Kontaktdaten werden sichtbar, sobald Sie annehmen."
        />

        <DataTable
            :columns="columns"
            :rows="rows"
            :meta="requests"
            :row-href="(row) => `/portal/anfragen/${row.id}`"
            empty-title="Keine offenen Anfragen"
            empty-description="Sobald eine passende Anfrage aus Ihrem Einsatzgebiet eingeht, erscheint sie hier und Sie erhalten eine E-Mail."
            :empty-icon="Inbox"
        >
            <template #cell-reference="{ row }"><ReferenceNumber :value="row.reference" /></template>
        </DataTable>
    </PortalLayout>
</template>
