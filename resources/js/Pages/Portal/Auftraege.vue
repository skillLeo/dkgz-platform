<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { FileText } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import SegmentedFilter from '../../Components/Data/SegmentedFilter.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import TablePagination from '../../Components/Data/TablePagination.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    assignments: { type: Object, required: true },
    counts: { type: Object, default: () => ({ active: 0, completed: 0 }) },
    filters: { type: Object, default: () => ({}) },
})

const { date } = useGermanFormat()

const segments = computed(() => [
    { label: 'Alle', value: null, href: '/portal/auftraege' },
    { label: 'In Bearbeitung', value: 'aktiv', href: '/portal/auftraege?status=aktiv' },
    { label: 'Abgeschlossen', value: 'abgeschlossen', href: '/portal/auftraege?status=abgeschlossen' },
])

const rows = computed(() => props.assignments.data)
</script>

<template>
    <Head title="Meine Aufträge" />

    <PortalLayout title="Meine Aufträge" search-href="/portal/auftraege">
        <div class="flex flex-wrap items-center justify-between gap-4 pb-4">
            <SegmentedFilter :segments="segments" :current="filters.status ?? null" />
            <span class="font-mono text-sm tabular-nums text-gray-600">
                {{ counts.active }} aktive · {{ counts.completed }} abgeschlossen
            </span>
        </div>

        <div v-if="!rows.length" class="rounded-card border border-gray-200 bg-white px-6 py-16 text-center">
            <FileText :size="32" :stroke-width="1.25" class="mx-auto text-gray-300" aria-hidden="true" />
            <p class="pt-4 text-lead font-semibold text-navy-700">Noch keine Aufträge</p>
            <p class="measure mx-auto pt-2 text-sm leading-normal text-gray-600">
                Sobald Sie eine Anfrage annehmen, erscheint sie hier als Auftrag.
            </p>
        </div>

        <div v-else class="overflow-x-auto rounded-card border border-gray-200 bg-white">
            <table class="w-full min-w-225">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="whitespace-nowrap px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Referenz</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Kunde</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Art</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Angenommen</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Status</th>
                        <th class="whitespace-nowrap px-5 py-3 text-right text-eyebrow font-semibold uppercase text-gray-600">Honorar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in rows"
                        :key="row.id"
                        class="border-b border-gray-100 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) last:border-b-0 hover:bg-gray-50"
                    >
                        <td class="whitespace-nowrap px-5 py-3.5">
                            <Link :href="`/portal/auftraege/${row.id}`" class="font-mono text-sm text-navy-700">
                                {{ row.request.reference }}
                            </Link>
                        </td>
                        <td class="px-3 py-3.5 text-sm text-gray-800">{{ row.request.customer_initial ?? '—' }}</td>
                        <td class="px-3 py-3.5 text-sm text-gray-800">{{ row.request.service_type }}</td>
                        <td class="whitespace-nowrap px-3 py-3.5 font-mono text-sm tabular-nums text-gray-600">
                            {{ date(row.accepted_at) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-3.5">
                            <StatusDot :status="row.status" :label="row.status_label" />
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right">
                            <MoneyValue v-if="row.fee_cents" :cents="row.fee_cents" />
                            <span v-else class="font-mono text-sm text-gray-400">offen</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <TablePagination v-if="rows.length" :meta="assignments" class="pt-4" />
    </PortalLayout>
</template>
