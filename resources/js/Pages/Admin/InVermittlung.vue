<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { AlertTriangle, Search, Send } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import TablePagination from '../../Components/Data/TablePagination.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

/**
 * Everything still waiting for an assessor to say yes.
 *
 * Requests that go nowhere used to sit in the full list looking exactly like
 * requests already placed, so the only way to find one was to notice it. This
 * screen exists to be a queue: oldest first, because the number that matters is
 * how long a customer has been waiting, and the ones nobody could be sent to at
 * all are called out rather than merely sorted.
 */
const props = defineProps({
    requests: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
})

const { stamp } = useGermanFormat()

const search = ref(props.filters.suche ?? '')
let timer = null

watch(search, (value) => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get('/admin/in-vermittlung', value ? { suche: value } : {}, {
            preserveState: true,
            replace: true,
        })
    }, 300)
})
</script>

<template>
    <Head title="In Vermittlung" />

    <AdminLayout title="In Vermittlung">
        <PageHeader title="In Vermittlung">
            <template #description>
                Anfragen, die noch kein Sachverständiger angenommen hat — die älteste zuerst.
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-center gap-4 pb-5">
            <p class="text-sm text-gray-600">
                <span class="font-mono tabular-nums text-navy-700">{{ counts.total ?? 0 }}</span>
                offen<template v-if="counts.unmatched">,
                    <span class="font-mono tabular-nums text-warning">{{ counts.unmatched }}</span>
                    ohne passenden Partner</template>
            </p>

            <label class="ml-auto flex h-10 w-full max-w-xs items-center gap-2 rounded-sm border border-gray-300 bg-white px-3">
                <Search :size="16" :stroke-width="1.5" class="shrink-0 text-gray-400" aria-hidden="true" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Referenz, Name, Ort oder PLZ"
                    class="h-full min-w-0 flex-1 border-none bg-transparent p-0 text-sm text-gray-800 focus:outline-none focus:ring-0"
                    aria-label="Anfragen durchsuchen"
                >
            </label>
        </div>

        <div v-if="!requests.data.length" class="border border-gray-200 bg-white p-10 text-center">
            <p class="text-base text-gray-800">Nichts offen.</p>
            <p class="pt-1 text-sm text-gray-600">Jede eingegangene Anfrage ist einem Sachverständigen zugeteilt.</p>
        </div>

        <div v-else class="overflow-x-auto border border-gray-200 bg-white">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Vorgang</th>
                        <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Leistung</th>
                        <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Ort</th>
                        <th scope="col" class="px-5 py-3 text-right text-eyebrow font-semibold uppercase text-gray-600">Gesendet an</th>
                        <th scope="col" class="px-5 py-3 text-right text-eyebrow font-semibold uppercase text-gray-600">Offen</th>
                        <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Wartet seit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in requests.data"
                        :key="row.id"
                        class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50"
                    >
                        <td class="px-5 py-3.5">
                            <Link :href="`/admin/anfragen/${row.id}`" class="font-mono text-sm text-navy-700 hover:text-navy-500">
                                {{ row.reference }}
                            </Link>
                            <span class="block text-xs text-gray-600">{{ row.customer_name }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-sm text-gray-800">{{ row.service_type }}</td>
                        <td class="px-5 py-3.5 text-sm text-gray-800">{{ row.location }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <span
                                v-if="row.matched_count"
                                class="font-mono text-sm tabular-nums text-gray-800"
                            >{{ row.matched_count }}</span>
                            <span v-else class="inline-flex items-center gap-1.5 text-sm text-warning">
                                <AlertTriangle :size="14" :stroke-width="1.5" aria-hidden="true" />
                                keiner
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-sm tabular-nums text-gray-800">
                            {{ row.open_matches }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="font-mono text-sm tabular-nums" :class="row.needs_attention ? 'text-warning' : 'text-gray-600'">
                                {{ stamp(row.waiting_since) }}
                            </span>
                            <Link
                                :href="`/admin/anfragen/${row.id}`"
                                class="inline-flex items-center gap-1.5 pt-1 text-xs text-navy-700 hover:text-navy-500"
                            >
                                <Send :size="13" :stroke-width="1.5" aria-hidden="true" />
                                Weitere Partner anschreiben
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <TablePagination v-if="requests.data.length" :meta="requests" class="pt-6" />
    </AdminLayout>
</template>
