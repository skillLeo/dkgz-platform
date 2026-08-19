<script setup>
import { Head } from '@inertiajs/vue3'
import { FileText } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

defineProps({ pages: { type: Array, default: () => [] } })

const { dateTime } = useGermanFormat()

const columns = [
    { key: 'title', label: 'Seite', cardRole: 'primary' },
    { key: 'slug', label: 'Adresse', mono: true, cardRole: 'meta' },
    { key: 'is_published', label: 'Status', cardRole: 'status' },
    { key: 'updated_at', label: 'Zuletzt geändert', mono: true },
]
</script>

<template>
    <Head title="Rechtliche Seiten" />

    <AdminLayout title="Rechtliche Seiten">
        <PageHeader title="Rechtliche Seiten" description="Impressum, Datenschutz, AGB und Widerrufsbelehrung." />

        <DataTable :columns="columns" :rows="pages" :row-href="(row) => `/admin/seiten/${row.slug}`" empty-title="Keine Seiten" :empty-icon="FileText">
            <template #cell-slug="{ row }"><span class="font-mono text-sm text-gray-600">/{{ row.slug }}</span></template>
            <template #cell-is_published="{ row }">
                <StatusDot :status="row.is_published ? 'approved' : 'closed'" :label="row.is_published ? 'Veröffentlicht' : 'Entwurf'" />
            </template>
            <template #cell-updated_at="{ row }"><span class="font-mono text-sm tabular-nums text-gray-600">{{ dateTime(row.updated_at) }}</span></template>
        </DataTable>
    </AdminLayout>
</template>
