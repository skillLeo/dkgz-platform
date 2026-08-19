<script setup>
import { Head } from '@inertiajs/vue3'
import { Mail } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'

defineProps({ templates: { type: Array, default: () => [] } })

const columns = [
    { key: 'name', label: 'Vorlage', cardRole: 'primary' },
    { key: 'subject', label: 'Betreff', cardRole: 'meta' },
    { key: 'is_active', label: 'Status', cardRole: 'status' },
]
</script>

<template>
    <Head title="E-Mail-Vorlagen" />

    <AdminLayout title="E-Mail-Vorlagen">
        <PageHeader
            title="E-Mail-Vorlagen"
            description="Betreff und Inhalt jeder Nachricht. Kopfband, Fußzeile und Rechtszeile stammen aus dem gemeinsamen Baustein und ändern sich für alle Vorlagen zugleich."
        />

        <DataTable
            :columns="columns"
            :rows="templates"
            :row-href="(row) => `/admin/email-vorlagen/${row.key}`"
            empty-title="Keine Vorlagen"
            :empty-icon="Mail"
        >
            <template #cell-is_active="{ row }">
                <StatusDot :status="row.is_active ? 'approved' : 'closed'" :label="row.is_active ? 'Aktiv' : 'Inaktiv'" />
            </template>
        </DataTable>
    </AdminLayout>
</template>
