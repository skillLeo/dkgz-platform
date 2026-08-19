<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Inbox } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import TableFilters from '../../Components/Data/TableFilters.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import ReferenceNumber from '../../Components/Data/ReferenceNumber.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import { usePermissions } from '../../Composables/usePermissions.js'

const props = defineProps({
    requests: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    serviceTypes: { type: Array, default: () => [] },
    statusOptions: { type: Object, default: () => ({}) },
})

const { can } = usePermissions()
const activeFilters = ref({ ...props.filters })

const columns = [
    { key: 'reference', label: 'Referenz', mono: true, sortable: true, cardRole: 'primary' },
    { key: 'status', label: 'Status', sortable: true, cardRole: 'status' },
    { key: 'service_type', label: 'Art des Gutachtens', cardRole: 'meta' },
    { key: 'location', label: 'Standort', cardRole: 'meta' },
    { key: 'matched_count', label: 'Vermittelt an', align: 'right', mono: true },
    { key: 'created_at_label', label: 'Eingegangen', mono: true, sortable: true, cardRole: 'meta' },
]

const statusSelectOptions = Object.entries(props.statusOptions).map(([value, label]) => ({ value, label }))
</script>

<template>
    <Head title="Anfragen" />

    <AdminLayout title="Anfragen">
        <PageHeader title="Anfragen" description="Alle eingegangenen Kundenanfragen und ihr Vermittlungsstand.">
            <template #actions>
                <BaseButton v-if="can('requests.export')" variant="secondary" size="compact" href="/admin/anfragen-export">
                    Export
                </BaseButton>
            </template>
        </PageHeader>

        <DataTable
            :columns="columns"
            :rows="requests.data"
            :meta="requests"
            :sort="filters.sort"
            :direction="filters.direction"
            :row-href="(row) => `/admin/anfragen/${row.id}`"
            empty-title="Keine Anfragen gefunden"
            empty-description="Sobald eine Anfrage über das Formular eingeht, erscheint sie hier."
            :empty-icon="Inbox"
        >
            <template #toolbar>
                <TableFilters v-model="activeFilters" search-placeholder="Referenz, Name oder Ort">
                    <template #filters="{ draft, apply }">
                        <BaseSelect
                            v-model="draft.status"
                            label="Status"
                            :options="statusSelectOptions"
                            placeholder="Alle"
                            class="min-w-44"
                            @update:model-value="apply({ ...draft, status: $event })"
                        />
                    </template>
                </TableFilters>
            </template>

            <template #cell-status="{ row }">
                <StatusDot :status="row.status" :pulse="row.needs_attention" />
            </template>

            <template #cell-reference="{ row }">
                <ReferenceNumber :value="row.reference" />
            </template>
        </DataTable>
    </AdminLayout>
</template>
