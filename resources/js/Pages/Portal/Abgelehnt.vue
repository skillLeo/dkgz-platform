<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { X } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import TablePagination from '../../Components/Data/TablePagination.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    requests: { type: Object, required: true },
    visibleDays: { type: Number, default: 90 },
})

const { date } = useGermanFormat()

const rows = computed(() => props.requests.data)
</script>

<template>
    <Head title="Abgelehnte Anfragen" />

    <PortalLayout title="Abgelehnte Anfragen">
        <p class="measure-wide pb-4 text-sm leading-normal text-gray-600">
            Abgelehnte oder abgelaufene Anfragen bleiben {{ visibleDays }} Tage sichtbar. Eine Ablehnung wirkt sich
            nicht auf die weitere Verteilung aus.
        </p>

        <div v-if="!rows.length" class="rounded-card border border-gray-200 bg-white px-6 py-16 text-center">
            <X :size="32" :stroke-width="1.25" class="mx-auto text-gray-300" aria-hidden="true" />
            <p class="pt-4 text-lead font-semibold text-navy-700">Keine abgelehnten Anfragen</p>
            <p class="measure mx-auto pt-2 text-sm leading-normal text-gray-600">
                Hier erscheinen Anfragen, die Sie abgelehnt haben, die ein anderer Partner übernommen hat oder deren
                Annahmefrist abgelaufen ist.
            </p>
        </div>

        <div v-else class="overflow-x-auto rounded-card border border-gray-200 bg-white">
            <table class="w-full min-w-200">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="whitespace-nowrap px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Referenz</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Ort</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Art</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Grund</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Datum</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-b border-gray-100 last:border-b-0">
                        <td class="whitespace-nowrap px-5 py-3.5 font-mono text-sm text-gray-800">{{ row.reference }}</td>
                        <td class="px-3 py-3.5 text-sm text-gray-800">{{ row.location }}</td>
                        <td class="px-3 py-3.5 text-sm text-gray-800">{{ row.service_type?.name }}</td>
                        <td class="px-3 py-3.5 text-sm text-gray-600">
                            {{ row.outcome_label }}
                            <span v-if="row.decline_reason" class="block text-xs text-gray-400">{{ row.decline_reason }}</span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 font-mono text-sm tabular-nums text-gray-600">
                            {{ date(row.responded_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <TablePagination v-if="rows.length" :meta="requests" class="pt-4" />
    </PortalLayout>
</template>
