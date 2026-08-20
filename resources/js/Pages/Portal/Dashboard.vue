<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import StatCard from '../../Components/Data/StatCard.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    stats: { type: Object, required: true },
    latestMatches: { type: Array, default: () => [] },
    activity: { type: Array, default: () => [] },
    availability: { type: Boolean, default: true },
})

const { money, relativeTime, dateTime } = useGermanFormat()

const cards = computed(() => [
    {
        label: 'Neue Anfragen',
        value: props.stats.open_requests,
        hint: props.stats.due_today ? `${props.stats.due_today} mit Frist heute` : 'Keine Frist heute',
        href: '/portal/anfragen',
    },
    {
        label: 'Aktive Aufträge',
        value: props.stats.open_assignments,
        hint: `${props.stats.in_progress} in Bearbeitung`,
        href: '/portal/auftraege',
    },
    {
        label: `Abgeschlossen im ${props.stats.month_label}`,
        value: props.stats.completed_this_month,
        hint: `Vormonat: ${props.stats.completed_previous_month}`,
        href: '/portal/auftraege',
    },
    {
        label: `Provision im ${props.stats.month_label}`,
        value: money(props.stats.commission_this_month_cents),
        hint: `auf ${money(props.stats.fee_this_month_cents)} Honorar`,
        href: '/portal/provisionen',
    },
])
</script>

<template>
    <Head title="Dashboard" />

    <PortalLayout title="Dashboard" :open-requests="stats.open_requests">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                v-for="card in cards"
                :key="card.label"
                :label="card.label"
                :value="card.value"
                :hint="card.hint"
                :href="card.href"
            />
        </div>

        <div
            v-if="!availability"
            class="mt-6 rounded-card border border-warning bg-warning/5 p-4"
            role="status"
        >
            <p class="text-base font-medium text-gray-800">Sie sind derzeit auf „Nicht verfügbar“ gesetzt</p>
            <p class="measure pt-1 text-sm leading-normal text-gray-600">
                Solange das so bleibt, erhalten Sie keine neuen Anfragen aus Ihrem Einsatzgebiet.
            </p>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 pt-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
            <section class="rounded-card border border-gray-200 bg-white">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4">
                    <h2 class="text-lead font-semibold text-navy-700">Neueste passende Anfragen</h2>
                    <Link href="/portal/anfragen" class="shrink-0 text-sm font-medium text-navy-700 hover:text-navy-500">
                        Alle ansehen
                    </Link>
                </div>

                <p v-if="!latestMatches.length" class="px-5 py-8 text-center text-sm text-gray-600">
                    Zurzeit liegen keine offenen Anfragen für Ihr Einsatzgebiet vor.
                </p>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-5 py-2.5 text-left text-eyebrow font-semibold uppercase text-gray-600">Referenz</th>
                                <th class="px-5 py-2.5 text-left text-eyebrow font-semibold uppercase text-gray-600">Ort</th>
                                <th class="px-5 py-2.5 text-left text-eyebrow font-semibold uppercase text-gray-600">Art</th>
                                <th class="px-5 py-2.5 text-left text-eyebrow font-semibold uppercase text-gray-600">Eingegangen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="match in latestMatches"
                                :key="match.id"
                                class="cursor-pointer border-b border-gray-100 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) last:border-b-0 hover:bg-gray-50"
                                @click="router.visit(match.href)"
                            >
                                <td class="px-5 py-3">
                                    <Link :href="match.href" class="font-mono text-meta text-navy-700">{{ match.reference }}</Link>
                                </td>
                                <td class="px-5 py-3 text-sm text-gray-800">{{ match.location }}</td>
                                <td class="px-5 py-3 text-sm text-gray-800">{{ match.service_type }}</td>
                                <td class="px-5 py-3 text-sm whitespace-nowrap text-gray-600">{{ relativeTime(match.notified_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-card border border-gray-200 bg-white">
                <h2 class="border-b border-gray-200 px-5 py-4 text-lead font-semibold text-navy-700">Verlauf</h2>

                <div class="px-5 pb-4 pt-1">
                    <p v-if="!activity.length" class="py-6 text-center text-sm text-gray-600">
                        Noch keine Vorgänge erfasst.
                    </p>

                    <div
                        v-for="entry in activity"
                        :key="entry.id"
                        class="border-b border-gray-100 py-3 last:border-b-0"
                    >
                        <p class="text-sm text-gray-800">
                            {{ entry.label }}<template v-if="entry.reference"> · {{ entry.reference }}</template>
                        </p>
                        <p class="font-mono text-meta tabular-nums text-gray-400">{{ dateTime(entry.at) }}</p>
                    </div>
                </div>
            </section>
        </div>
    </PortalLayout>
</template>
