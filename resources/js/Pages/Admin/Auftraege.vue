<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { FileText } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import TableFilters from '../../Components/Data/TableFilters.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import ReferenceNumber from '../../Components/Data/ReferenceNumber.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import { usePermissions } from '../../Composables/usePermissions.js'

const props = defineProps({
    assignments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Object, default: () => ({}) },
})

const { can } = usePermissions()
const activeFilters = ref({ ...props.filters })

const columns = [
    { key: 'reference', label: 'Referenz', mono: true, cardRole: 'primary' },
    { key: 'status', label: 'Status', sortable: true, cardRole: 'status' },
    { key: 'assessor', label: 'Sachverständiger', cardRole: 'meta' },
    { key: 'service_type', label: 'Art', cardRole: 'meta' },
    { key: 'fee_cents', label: 'Honorar', align: 'right', mono: true, cardRole: 'meta' },
    { key: 'commission_cents', label: 'Provision', align: 'right', mono: true },
]

const statusSelectOptions = Object.entries(props.statusOptions).map(([value, label]) => ({ value, label }))
</script>

<template>
    <Head title="Aufträge" />

    <AdminLayout title="Aufträge">
        <PageHeader title="Aufträge" description="Angenommene Anfragen und ihr Bearbeitungsstand.">
            <template #actions>
                <BaseButton v-if="can('assignments.export')" variant="secondary" size="compact" href="/admin/auftraege-export">Export</BaseButton>
            </template>
        </PageHeader>

        <DataTable
            :columns="columns"
            :rows="assignments.data"
            :meta="assignments"
            :sort="filters.sort"
            :direction="filters.direction"
            :row-href="(row) => `/admin/auftraege/${row.id}`"
            empty-title="Keine Aufträge gefunden"
            empty-description="Sobald ein Sachverständiger eine Anfrage annimmt, entsteht hier ein Auftrag."
            :empty-icon="FileText"
        >
            <template #toolbar>
                <TableFilters v-model="activeFilters" search-placeholder="Referenz oder Büro">
                    <template #filters="{ draft, apply }">
                        <BaseSelect v-model="draft.status" label="Status" :options="statusSelectOptions" placeholder="Alle" class="min-w-52" @update:model-value="apply({ ...draft, status: $event })" />
                    </template>
                </TableFilters>
            </template>

            <template #cell-reference="{ row }"><ReferenceNumber :value="row.reference" /></template>
            <template #cell-status="{ row }"><StatusDot :status="row.status" /></template>
            <template #cell-fee_cents="{ row }"><MoneyValue :cents="row.fee_cents" /></template>
            <template #cell-commission_cents="{ row }"><MoneyValue :cents="row.commission_cents" muted /></template>
        </DataTable>
    </AdminLayout>
</template>
