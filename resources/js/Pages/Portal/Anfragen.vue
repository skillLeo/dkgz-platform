<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Filter, Inbox, Lock } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import TablePagination from '../../Components/Data/TablePagination.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    requests: { type: Object, required: true },
    serviceTypes: { type: Array, default: () => [] },
    areaLabel: { type: String, default: '' },
    filters: { type: Object, default: () => ({}) },
})

const { relativeTime } = useGermanFormat()

const rows = computed(() => props.requests.data)

const applyFilter = (key, value) => {
    router.get('/portal/anfragen', { ...props.filters, [key]: value || undefined }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const countLabel = computed(() => `${props.requests.total} ${props.requests.total === 1 ? 'offene Anfrage' : 'offene Anfragen'}`)
</script>

<template>
    <Head title="Neue Anfragen" />

    <PortalLayout title="Neue Anfragen" :open-requests="requests.total" search-href="/portal/anfragen">
        <div class="flex flex-col gap-3 pb-4 md:flex-row md:flex-wrap md:items-center md:justify-between md:gap-4">
            <div class="flex flex-wrap items-center gap-2.5">
                <label class="flex h-8 items-center gap-2 rounded-sm border border-gray-300 bg-white px-2.5">
                    <Filter :size="16" :stroke-width="1.5" class="shrink-0 text-gray-600" aria-hidden="true" />
                    <span class="sr-only">Art des Gutachtens</span>
                    <select
                        class="border-none bg-transparent py-0 pl-0 pr-6 text-sm text-gray-800 focus:outline-none focus:ring-0"
                        :value="filters.art ?? ''"
                        @change="applyFilter('art', $event.target.value)"
                    >
                        <option value="">Art: Alle</option>
                        <option v-for="type in serviceTypes" :key="type.id" :value="type.id">{{ type.name_de }}</option>
                    </select>
                </label>

                <span
                    v-if="areaLabel"
                    class="flex h-8 items-center rounded-sm border border-gray-300 bg-white px-2.5 text-sm text-gray-800"
                >PLZ: {{ areaLabel }}</span>

            </div>

            <span class="font-mono text-sm tabular-nums text-gray-600">{{ countLabel }}</span>
        </div>

        <div v-if="!rows.length" class="rounded-card border border-gray-200 bg-white px-6 py-16 text-center">
            <Inbox :size="32" :stroke-width="1.25" class="mx-auto text-gray-300" aria-hidden="true" />
            <p class="pt-4 text-lead font-semibold text-navy-700">Keine offenen Anfragen</p>
            <p class="measure mx-auto pt-2 text-sm leading-normal text-gray-600">
                Sobald eine passende Anfrage aus Ihrem Einsatzgebiet eingeht, erscheint sie hier und Sie erhalten
                eine E-Mail.
            </p>
        </div>

        <div v-else class="hidden overflow-x-auto rounded-card border border-gray-200 bg-white md:block">
            <table class="w-full min-w-250">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="whitespace-nowrap px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Referenz</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Ort / PLZ</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Art des Gutachtens</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Kunde</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">DKGZ-Gebühr</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Eingegangen</th>
                        <th class="px-5 py-3"><span class="sr-only">Aktionen</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in rows"
                        :key="row.id"
                        class="border-b border-gray-100 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) last:border-b-0 hover:bg-gray-50"
                    >
                        <td class="whitespace-nowrap px-5 py-3.5">
                            <Link :href="`/portal/anfragen/${row.id}`" class="font-mono text-sm text-navy-700">{{ row.reference }}</Link>
                        </td>
                        <td class="px-3 py-3.5 text-sm text-gray-800">{{ row.location }}</td>
                        <td class="px-3 py-3.5 text-sm text-gray-800">{{ row.service_type?.name }}</td>
                        <td class="px-3 py-3.5">
                            <!-- The contact is not merely hidden here: the resource never sent it. -->
                            <span class="inline-flex items-center gap-2" :title="'Sichtbar nach Annahme'">
                                <span class="block h-2.5 w-22 rounded-xs bg-gray-200" aria-hidden="true" />
                                <Lock :size="14" :stroke-width="1.5" class="text-gray-400" aria-hidden="true" />
                                <span class="sr-only">Sichtbar nach Annahme</span>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3.5 font-mono text-sm tabular-nums text-navy-700">
                            {{ row.dkgz_fee_label ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-3.5 font-mono text-sm text-gray-600">{{ relativeTime(row.created_at) }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right">
                            <span class="inline-flex items-center gap-2.5">
                                <Link :href="`/portal/anfragen/${row.id}`" class="text-sm font-medium text-navy-700 hover:text-navy-500">
                                    Details
                                </Link>
                                <BaseButton
                                    size="small"
                                    @click="router.post(`/portal/anfragen/${row.id}/annehmen`)"
                                >Annehmen</BaseButton>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ul v-if="rows.length" class="flex flex-col gap-3 md:hidden">
            <li
                v-for="row in rows"
                :key="`karte-${row.id}`"
                class="rounded-card border border-gray-200 bg-white"
            >
                <div class="flex items-baseline justify-between gap-3 px-4 pt-4">
                    <Link :href="`/portal/anfragen/${row.id}`" class="font-mono text-sm text-navy-700">
                        {{ row.reference }}
                    </Link>
                    <span class="shrink-0 font-mono text-meta text-gray-400">{{ relativeTime(row.created_at) }}</span>
                </div>

                <div class="px-4 pt-2">
                    <p class="text-base font-medium text-gray-800">{{ row.service_type?.name }}</p>
                    <p class="pt-0.5 text-sm text-gray-600">{{ row.location }}</p>

                    <p class="flex items-center gap-2 pt-2.5">
                        <Lock :size="14" :stroke-width="1.5" class="shrink-0 text-gray-400" aria-hidden="true" />
                        <span class="text-sm text-gray-600">Nach Annahme</span>
                    </p>

                    <div class="pb-4" />
                </div>

                <div class="grid grid-cols-2 border-t border-gray-200">
                    <Link
                        :href="`/portal/anfragen/${row.id}`"
                        class="flex h-12 items-center justify-center border-r border-gray-200 text-sm font-medium text-navy-700"
                    >Details</Link>
                    <button
                        type="button"
                        class="flex h-12 items-center justify-center bg-navy-700 text-sm font-medium text-white"
                        @click="router.post(`/portal/anfragen/${row.id}/annehmen`)"
                    >Annehmen</button>
                </div>
            </li>
        </ul>

        <TablePagination v-if="rows.length" :meta="requests" class="pt-4" />

        <p class="measure-wide pt-4 text-sm text-gray-600">
            Name, Telefon und E-Mail der anfragenden Person werden erst nach Ihrer Annahme freigegeben. Bis dahin
            sehen alle angefragten Sachverständigen dieselben anonymisierten Angaben.
        </p>
    </PortalLayout>
</template>
