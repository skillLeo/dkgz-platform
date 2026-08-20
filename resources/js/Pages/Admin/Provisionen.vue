<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Euro } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import TableFilters from '../../Components/Data/TableFilters.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import StatCard from '../../Components/Data/StatCard.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import ReferenceNumber from '../../Components/Data/ReferenceNumber.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import { usePermissions } from '../../Composables/usePermissions.js'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    commissions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Object, default: () => ({}) },
    totals: { type: Object, default: () => ({}) },
    summary: { type: Object, default: null },
    monthOptions: { type: Array, default: () => [] },
    assessorOptions: { type: Array, default: () => [] },
})

const { can } = usePermissions()
const { percent, money } = useGermanFormat()
const activeFilters = ref({ ...props.filters })

const columns = [
    { key: 'reference', label: 'Vorgang', mono: true, cardRole: 'primary' },
    { key: 'status', label: 'Status', sortable: true, cardRole: 'status' },
    { key: 'assessor', label: 'Sachverständiger', cardRole: 'meta' },
    { key: 'fee_cents', label: 'Honorar', align: 'right', mono: true, cardRole: 'meta' },
    { key: 'rate_percent', label: 'Satz', align: 'right', mono: true },
    { key: 'commission_cents', label: 'Provision', align: 'right', mono: true, cardRole: 'meta' },
]

const statusSelectOptions = Object.entries(props.statusOptions).map(([value, label]) => ({ value, label }))
</script>

<template>
    <Head title="Provisionen" />

    <AdminLayout title="Provisionen">
        <PageHeader title="Provisionen" description="Das Provisionsregister. Der Satz jeder Zeile ist der zum Abschluss gültige.">
            <template #actions>
                <BaseButton v-if="can('commissions.export')" variant="secondary" size="compact" href="/admin/provisionen-export">Register exportieren</BaseButton>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-4 pb-6 sm:grid-cols-3">
            <StatCard label="Offen" :cents="totals.open_cents" tone="warning" />
            <StatCard label="Abgerechnet" :cents="totals.invoiced_cents" />
            <StatCard label="Bezahlt" :cents="totals.settled_cents" tone="success" />
        </div>

        <DataTable
            :columns="columns"
            :rows="commissions.data"
            :meta="commissions"
            :active-filters="filters"
            reset-href="/admin/provisionen"
            :sort="filters.sort"
            :direction="filters.direction"
            :row-href="(row) => `/admin/provisionen/${row.id}`"
            empty-title="Keine Provisionen"
            empty-description="Eine Provision entsteht, sobald ein Auftrag abgeschlossen und das Honorar erfasst wurde."
            :empty-icon="Euro"
        >
            <template #toolbar>
                <TableFilters v-model="activeFilters" search-placeholder="Rechnungsnummer, Büro oder Referenz">
                    <template #filters="{ draft, apply }">
                        <BaseSelect v-model="draft.status" label="Status" :options="statusSelectOptions" placeholder="Alle" class="min-w-44" @update:model-value="apply({ ...draft, status: $event })" />
                    </template>
                </TableFilters>
            </template>

            <template #cell-reference="{ row }"><ReferenceNumber :value="row.reference ?? '—'" /></template>
            <template #cell-status="{ row }"><StatusDot :status="row.status" :pulse="row.needs_review" /></template>
            <template #cell-fee_cents="{ row }"><MoneyValue :cents="row.fee_cents" /></template>
            <template #cell-rate_percent="{ row }"><span class="font-mono text-sm tabular-nums text-gray-600">{{ percent(row.rate_percent) }}</span></template>
            <template #cell-commission_cents="{ row }"><MoneyValue :cents="row.commission_cents" emphasis /></template>
        </DataTable>

        <section
            v-if="summary"
            class="sticky bottom-0 z-20 mt-6 border-t-2 border-t-navy-700 bg-white px-4 py-3 pb-[calc(0.75rem+4rem+env(safe-area-inset-bottom))] md:static md:rounded-card md:border md:border-gray-200 md:border-t-2 md:border-t-navy-700 md:px-5 md:pb-3"
        >
            <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                <p class="text-base font-medium text-navy-700">
                    {{ summary.label }}
                    <span class="font-mono text-meta tabular-nums text-gray-400">
                        · {{ summary.assignments }} {{ summary.assignments === 1 ? 'Auftrag' : 'Aufträge' }}
                    </span>
                </p>
                <dl class="flex flex-wrap items-baseline gap-x-6 gap-y-1">
                    <div class="flex items-baseline gap-2">
                        <dt class="text-sm text-gray-600">Honorar</dt>
                        <dd class="font-mono text-sm tabular-nums text-gray-800">{{ money(summary.fee_cents) }}</dd>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <dt class="text-sm text-gray-600">
                            Provision <span class="text-gray-400">· davon offen {{ money(summary.open_cents) }}</span>
                        </dt>
                        <dd class="font-mono text-base font-medium tabular-nums text-navy-700">
                            {{ money(summary.commission_cents) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>
    </AdminLayout>
</template>
