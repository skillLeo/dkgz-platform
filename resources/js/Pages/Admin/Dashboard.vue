<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import StatCard from '../../Components/Data/StatCard.vue'
import WeeklyBars from '../../Components/Data/WeeklyBars.vue'

const props = defineProps({
    stats: { type: Object, required: true },
    attention: { type: Array, default: () => [] },
    attentionCount: { type: Number, default: 0 },
    weekly: { type: Array, default: () => [] },
    funnel: { type: Array, default: () => [] },
})

/**
 * Six figures at a higher density than the portal, per DKGZ Mobil: 2 x 3 on a
 * phone with hairline seams, spreading to six across on a wide screen.
 */
const cards = [
    { label: 'Offene Anfragen', value: () => props.stats.open_requests, href: '/admin/anfragen' },
    { label: 'Heute vermittelt', value: () => props.stats.matched_today, href: '/admin/anfragen' },
    { label: 'Aktive Aufträge', value: () => props.stats.open_assignments, href: '/admin/auftraege' },
    { label: 'Sachverständige', value: () => props.stats.assessors, href: '/admin/sachverstaendige' },
    { label: 'Wartend auf Freigabe', value: () => props.stats.pending_assessors, href: '/admin/sachverstaendige?status=pending', warn: true },
    { label: 'Provision offen', cents: () => props.stats.open_commission_cents, href: '/admin/provisionen?status=open' },
]
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout title="Dashboard" :pending-assessors="stats.pending_assessors">
        <div class="grid grid-cols-2 gap-px bg-gray-200 md:grid-cols-3 md:gap-4 md:bg-transparent xl:grid-cols-6">
            <StatCard
                v-for="card in cards"
                :key="card.label"
                :label="card.label"
                :value="card.cents ? undefined : card.value()"
                :cents="card.cents ? card.cents() : null"
                :href="card.href"
                :tone="card.warn && card.value() ? 'warning' : 'default'"
                class="rounded-none md:rounded-card"
            />
        </div>

        <div class="grid grid-cols-1 items-start gap-6 pt-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
            <section class="rounded-card border border-gray-200 bg-white p-5">
                <div class="flex flex-wrap items-baseline justify-between gap-3 pb-5">
                    <h2 class="text-lead font-semibold text-navy-700">Anfragen pro Woche</h2>
                    <span v-if="weekly.length" class="font-mono text-meta tabular-nums text-gray-400">
                        KW {{ weekly[0].week }}–{{ weekly[weekly.length - 1].week }}
                    </span>
                </div>
                <WeeklyBars :weeks="weekly" />
            </section>

            <!--
                How far people get through the request form. Anonymous counters,
                so this is everybody rather than only the visitors who accepted
                the cookie banner — which is the group most analytics can see.
            -->
            <section v-if="funnel.length" class="border border-gray-200 bg-white p-5">
                <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Anfrageformular · letzte 30 Tage</h2>

                <ul class="flex flex-col gap-4 pt-5">
                    <li v-for="row in funnel" :key="row.step">
                        <div class="flex items-baseline justify-between gap-4 pb-1.5">
                            <span class="text-sm text-gray-800">{{ row.label }}</span>
                            <span class="shrink-0 font-mono text-sm tabular-nums text-navy-700">
                                {{ row.count }}<span v-if="row.share !== null" class="pl-2 text-gray-500">{{ row.share }}%</span>
                            </span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-100">
                            <div
                                class="h-2 rounded-full bg-navy-700 transition-[width] duration-(--duration-hover) ease-(--ease-dkgz)"
                                :style="{ width: `${row.share ?? 0}%` }"
                            />
                        </div>
                    </li>
                </ul>

                <p class="measure pt-4 text-sm leading-normal text-gray-600">
                    Gezählt wird anonym: kein Name, keine Adresse, keine Zuordnung zu einer Person.
                    Deshalb erscheinen hier alle Besucher, nicht nur die mit Cookie-Zustimmung.
                </p>
            </section>

            <section class="rounded-card border border-gray-200 bg-white">
                <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <h2 class="text-lead font-semibold text-navy-700">Erfordert Aufmerksamkeit</h2>
                    <span class="font-mono text-meta tabular-nums text-gray-400">
                        {{ attentionCount }} {{ attentionCount === 1 ? 'Vorgang' : 'Vorgänge' }}
                    </span>
                </div>

                <p v-if="!attention.length" class="px-5 py-10 text-center text-sm text-gray-600">
                    Nichts offen. Kein Vorgang wartet derzeit auf einen Eingriff.
                </p>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-5 py-2.5 text-left text-eyebrow font-semibold uppercase text-gray-600">Referenz</th>
                                <th class="px-3 py-2.5 text-left text-eyebrow font-semibold uppercase text-gray-600">Sachverhalt</th>
                                <th class="px-5 py-2.5 text-right text-eyebrow font-semibold uppercase text-gray-600">Seit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in attention"
                                :key="`${item.reference}-${item.matter}`"
                                class="border-b border-gray-100 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) last:border-b-0 hover:bg-gray-50"
                            >
                                <td class="whitespace-nowrap px-5 py-3">
                                    <Link :href="item.href" class="font-mono text-sm text-navy-700">{{ item.reference }}</Link>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-800">{{ item.matter }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right font-mono text-sm tabular-nums text-gray-600">
                                    {{ item.since_label }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
