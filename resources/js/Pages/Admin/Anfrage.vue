<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Lock } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import ReferenceNumber from '../../Components/Data/ReferenceNumber.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({
    request: { type: Object, required: true },
    trail: { type: Array, default: () => [] },
    assignment: { type: Object, default: null },
})

const { dateTime, date } = useGermanFormat()
const { confirm } = useConfirm()

const rematch = useForm({})

const doRematch = async () => {
    const ok = await confirm({
        title: 'Anfrage erneut vermitteln?',
        message: 'Offene Benachrichtigungen werden zurückgezogen und die Anfrage wird erneut an alle passenden Partner gesendet. Bereits abgelehnte Partner bleiben unberührt.',
        confirmLabel: 'Erneut vermitteln',
    })

    if (ok) rematch.post(`/admin/anfragen/${props.request.id}/erneut-vermitteln`, { preserveScroll: true })
}
</script>

<template>
    <Head :title="request.reference" />

    <AdminLayout :title="request.reference" back-href="/admin/anfragen">
        <PageHeader :title="request.reference" :eyebrow="request.service_type">
            <template #actions>
                <StatusDot :status="request.status" />
                <BaseButton
                    v-if="request.can_rematch && ['new', 'matched'].includes(request.status)"
                    variant="secondary"
                    size="compact"
                    :loading="rematch.processing"
                    @click="doRematch"
                >Erneut vermitteln</BaseButton>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="flex flex-col gap-6">
                <!-- The forensic trail -->
                <section class="border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 p-5">
                        <SectionLabel text="Vermittlungsverlauf" tone="muted" />
                        <p class="pt-2 text-sm text-gray-600">
                            An {{ request.matched_count }} Sachverständige gesendet. Wer benachrichtigt wurde, wann er
                            die Anfrage geöffnet hat und wie er entschieden hat.
                        </p>
                    </div>

                    <div v-if="!trail.length" class="p-5">
                        <p class="text-sm text-gray-600">
                            Diese Anfrage wurde noch an keinen Sachverständigen vermittelt.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">Sachverständiger</th>
                                    <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">Benachrichtigt</th>
                                    <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">Geöffnet</th>
                                    <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">Antwort</th>
                                    <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">Ergebnis</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="entry in trail" :key="entry.id" class="border-b border-gray-100 last:border-b-0">
                                    <td class="px-5 py-3.5">
                                        <a :href="`/admin/sachverstaendige/${entry.assessor.id}`" class="text-sm font-medium text-navy-700 hover:text-navy-500">
                                            {{ entry.assessor.company_name }}
                                        </a>
                                        <span class="block text-xs text-gray-600">{{ entry.assessor.city }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-sm tabular-nums text-gray-800">{{ entry.notified_at_label ?? '—' }}</td>
                                    <td class="px-5 py-3.5 font-mono text-sm tabular-nums" :class="entry.viewed_at_label ? 'text-gray-800' : 'text-gray-400'">
                                        {{ entry.viewed_at_label ?? 'nicht geöffnet' }}
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-sm tabular-nums" :class="entry.responded_at_label ? 'text-gray-800' : 'text-gray-400'">
                                        {{ entry.responded_at_label ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <StatusDot :status="entry.outcome" :label="entry.outcome_label" />
                                        <span v-if="entry.decline_reason" class="block pt-1 text-xs text-gray-600">{{ entry.decline_reason }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section v-if="assignment" class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Auftrag" tone="muted" />
                    <dl class="grid grid-cols-1 gap-x-6 pt-4 sm:grid-cols-2">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Sachverständiger</dt>
                            <dd class="text-sm text-gray-800">{{ assignment.assessor }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Status</dt>
                            <dd><StatusDot :status="assignment.status" :label="assignment.status_label" /></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Angenommen am</dt>
                            <dd class="font-mono text-sm tabular-nums text-gray-800">{{ dateTime(assignment.accepted_at) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Honorar netto</dt>
                            <dd><MoneyValue :cents="assignment.fee_cents" /></dd>
                        </div>
                    </dl>
                    <BaseButton variant="secondary" size="compact" class="mt-4" :href="`/admin/auftraege/${assignment.id}`">
                        Auftrag öffnen
                    </BaseButton>
                </section>
            </div>

            <div class="flex flex-col gap-6">
                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Anfragedaten" tone="muted" />
                    <dl class="pt-4">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Referenz</dt>
                            <dd><ReferenceNumber :value="request.reference" /></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Standort</dt>
                            <dd class="text-sm text-gray-800">{{ request.city }} · {{ request.postal_code }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Fahrzeug</dt>
                            <dd class="text-sm text-gray-800">{{ request.vehicle }}</dd>
                        </div>
                        <div v-if="request.vehicle_plate" class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Kennzeichen</dt>
                            <dd class="font-mono text-sm text-gray-800">{{ request.vehicle_plate }}</dd>
                        </div>
                        <div v-if="request.preferred_date" class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Wunschtermin</dt>
                            <dd class="font-mono text-sm tabular-nums text-gray-800">{{ date(request.preferred_date) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 py-2.5">
                            <dt class="text-sm text-gray-600">Fotos</dt>
                            <dd class="font-mono text-sm tabular-nums text-gray-800">{{ request.image_count }}</dd>
                        </div>
                    </dl>
                    <p v-if="request.description" class="measure pt-3 text-sm leading-normal text-gray-800">{{ request.description }}</p>
                </section>

                <section class="border border-gray-200 bg-white p-5">
                    <div class="flex items-center gap-2">
                        <Lock :size="14" :stroke-width="1.5" class="text-gray-600" aria-hidden="true" />
                        <SectionLabel text="Kontaktdaten" tone="muted" :with-rule="false" />
                    </div>
                    <dl class="pt-3">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Name</dt>
                            <dd class="text-sm text-gray-800">{{ request.customer_name }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Telefon</dt>
                            <dd class="font-mono text-sm tabular-nums text-gray-800">{{ request.customer_phone }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 py-2.5">
                            <dt class="text-sm text-gray-600">E-Mail</dt>
                            <dd class="truncate text-sm text-gray-800">{{ request.customer_email }}</dd>
                        </div>
                    </dl>
                    <p class="pt-3 font-mono text-xs text-gray-400">
                        Einwilligung erteilt am {{ dateTime(request.consent_at) }}
                    </p>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
