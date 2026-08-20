<script setup>
import { Head } from '@inertiajs/vue3'
import { Euro } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import StatCard from '../../Components/Data/StatCard.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import ReferenceNumber from '../../Components/Data/ReferenceNumber.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

defineProps({
    commissions: { type: Object, required: true },
    totals: { type: Object, default: () => ({}) },
})

const { percent } = useGermanFormat()

const columns = [
    { key: 'reference', label: 'Vorgang', mono: true, cardRole: 'primary' },
    { key: 'status', label: 'Status', cardRole: 'status' },
    { key: 'fee_cents', label: 'Honorar', align: 'right', mono: true, cardRole: 'meta' },
    { key: 'fee_type', label: 'Modell', cardRole: 'meta' },
    { key: 'commission_cents', label: 'Provision', align: 'right', mono: true, cardRole: 'meta' },
    { key: 'assessor_share_cents', label: 'Ihr Anteil', align: 'right', mono: true },
]
</script>

<template>
    <Head title="Provisionen" />

    <PortalLayout title="Provisionen">
        <PageHeader
            title="Provisionen"
            description="Der Satz jeder Zeile ist der zum Abschluss gültige und ändert sich nachträglich nicht."
        />

        <div class="grid grid-cols-1 gap-4 pb-6 sm:grid-cols-2">
            <StatCard label="Offen" :cents="totals.open_cents" tone="warning" />
            <StatCard label="Bezahlt" :cents="totals.settled_cents" tone="success" />
        </div>

        <DataTable
            :columns="columns"
            :rows="commissions.data"
            :meta="commissions"
            empty-title="Noch keine Provisionen"
            empty-description="Eine Provision entsteht, sobald Sie einen Auftrag abschließen und das Honorar erfassen."
            :empty-icon="Euro"
        >
            <template #cell-reference="{ row }"><ReferenceNumber :value="row.reference ?? '—'" /></template>
            <template #cell-status="{ row }"><StatusDot :status="row.status" :label="row.status_label" /></template>
            <template #cell-fee_cents="{ row }"><MoneyValue :cents="row.fee_cents" /></template>
            <template #cell-fee_type="{ row }">
                <span class="text-sm text-gray-600">
                    {{ row.fee_type === 'percentage' ? `Provision ${percent(row.rate_percent)}` : 'Feste Gebühr' }}
                </span>
            </template>
            <template #cell-commission_cents="{ row }"><MoneyValue :cents="row.commission_cents" /></template>
            <template #cell-assessor_share_cents="{ row }"><MoneyValue :cents="row.assessor_share_cents" emphasis /></template>
        </DataTable>
    </PortalLayout>
</template>
