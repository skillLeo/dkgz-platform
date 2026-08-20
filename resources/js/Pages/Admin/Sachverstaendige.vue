<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Building2 } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import TableFilters from '../../Components/Data/TableFilters.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import { usePermissions } from '../../Composables/usePermissions.js'

const props = defineProps({
    assessors: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Object, default: () => ({}) },
    pendingCount: { type: Number, default: 0 },
})

const { can } = usePermissions()
const activeFilters = ref({ ...props.filters })

const columns = [
    { key: 'company_name', label: 'Büro', sortable: true, cardRole: 'primary' },
    { key: 'approval_status', label: 'Status', cardRole: 'status' },
    { key: 'location', label: 'Standort', cardRole: 'meta' },
    { key: 'contact', label: 'Ansprechpartner', cardRole: 'meta' },
    { key: 'service_areas_count', label: 'Gebiete', align: 'right', mono: true },
    { key: 'assignments_count', label: 'Aufträge', align: 'right', mono: true, cardRole: 'meta' },
]

const statusSelectOptions = Object.entries(props.statusOptions).map(([value, label]) => ({ value, label }))
</script>

<template>
    <Head title="Sachverständige" />

    <AdminLayout title="Sachverständige" :pending-assessors="pendingCount">
        <PageHeader title="Sachverständige" description="Das Partnernetz und der Stand jeder Registrierung.">
            <template #actions>
                <BaseButton v-if="can('assessors.export')" variant="secondary" size="compact" href="/admin/sachverstaendige-export">
                    Export
                </BaseButton>
            </template>
        </PageHeader>

        <DataTable
            :columns="columns"
            :rows="assessors.data"
            :meta="assessors"
            :active-filters="filters"
            reset-href="/admin/sachverstaendige"
            :sort="filters.sort"
            :direction="filters.direction"
            :row-href="(row) => `/admin/sachverstaendige/${row.id}`"
            empty-title="Keine Sachverständigen gefunden"
            empty-description="Sobald sich ein Partner registriert oder eine Einladung annimmt, erscheint er hier."
            :empty-icon="Building2"
        >
            <template #toolbar>
                <TableFilters v-model="activeFilters" search-placeholder="Büro, Ort oder E-Mail">
                    <template #filters="{ draft, apply }">
                        <BaseSelect
                            v-model="draft.status"
                            label="Status"
                            :options="statusSelectOptions"
                            placeholder="Alle"
                            class="min-w-48"
                            @update:model-value="apply({ ...draft, status: $event })"
                        />
                    </template>
                </TableFilters>
            </template>

            <template #cell-approval_status="{ row }">
                <StatusDot :status="row.approval_status" :pulse="row.approval_status === 'pending'" />
            </template>
        </DataTable>
    </AdminLayout>
</template>
