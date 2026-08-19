<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { FileType } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'

const props = defineProps({
    activities: { type: [Object, Array], required: true },
    filters: { type: Object, default: () => ({}) },
})

const rows = props.activities.data ?? props.activities
const meta = props.activities.data ? props.activities : null

const columns = [
    { key: 'description', label: 'Vorgang', cardRole: 'primary' },
    { key: 'log_name', label: 'Bereich', cardRole: 'meta' },
    { key: 'subject_type', label: 'Objekt', cardRole: 'meta' },
    { key: 'causer', label: 'Urheber', cardRole: 'meta' },
    { key: 'created_at', label: 'Zeitpunkt', mono: true },
]
</script>

<template>
    <Head title="Protokoll" />

    <AdminLayout title="Protokoll">
        <PageHeader title="Protokoll" description="Jede berechtigungsrelevante Änderung mit Urheber, Objekt und geänderten Feldern." />

        <DataTable
            :columns="columns"
            :rows="rows"
            :meta="meta"
            empty-title="Keine Einträge"
            :empty-icon="FileType"
        >
            <template #cell-created_at="{ row }">
                <span class="font-mono text-sm tabular-nums text-gray-600">{{ new Date(row.created_at).toLocaleString('de-DE') }}</span>
            </template>
        </DataTable>
    </AdminLayout>
</template>
