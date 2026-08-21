<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Lock } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import ReferenceNumber from '../../Components/Data/ReferenceNumber.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({
    request: { type: Object, required: true },
    trail: { type: Array, default: () => [] },
    matching: { type: Object, default: null },
    offers: { type: Array, default: () => [] },
    canOffer: { type: Boolean, default: false },
    assignment: { type: Object, default: null },
    customerNotifiedAt: { type: String, default: null },
    canNotifyCustomer: { type: Boolean, default: false },
    canClose: { type: Boolean, default: false },
})

const offerForm = useForm({ email: '', name: '', message: '' })

function sendOffer() {
    offerForm.post(`/admin/anfragen/${props.request.id}/externer-sachverstaendiger`, {
        preserveScroll: true,
        onSuccess: () => offerForm.reset(),
    })
}

const { dateTime, date } = useGermanFormat()
const { confirm } = useConfirm()

const rematch = useForm({})
const notifyCustomer = useForm({})
const closeForm = useForm({ reason: '' })
const closing = ref(false)

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
                <!--
                    Whether the customer knows. A request nobody took and nobody
                    explained is the one state that must never sit unnoticed.
                -->
                <!--
                    Nothing closes a request on a timer any more, so ending one
                    is a decision somebody makes and signs.
                -->
                <section v-if="canClose" class="rounded-card border border-gray-200 bg-white p-5">
                    <SectionLabel text="Vorgang schließen" tone="muted" />
                    <p class="measure pt-3 text-sm leading-normal text-gray-600">
                        Diese Anfrage bleibt offen, bis ein Partner sie übernimmt. Schließen Sie sie, wenn sich die
                        Sache anderweitig erledigt hat — der Kunde wird dabei benachrichtigt.
                    </p>

                    <BaseButton v-if="!closing" variant="secondary" size="compact" class="mt-4" @click="closing = true">
                        Anfrage manuell schließen
                    </BaseButton>

                    <form
                        v-else
                        class="pt-4"
                        @submit.prevent="closeForm.post(`/admin/anfragen/${request.id}/schliessen`, { preserveScroll: true })"
                    >
                        <BaseTextarea
                            v-model="closeForm.reason"
                            label="Grund"
                            hint="Wird im Protokoll festgehalten."
                            :rows="3"
                            :error="closeForm.errors.reason"
                            required
                        />
                        <div class="flex flex-wrap gap-3 pt-3">
                            <BaseButton type="submit" size="compact" variant="danger" :loading="closeForm.processing">
                                Schließen und Kunde benachrichtigen
                            </BaseButton>
                            <BaseButton variant="ghost" size="compact" @click="closing = false">Abbrechen</BaseButton>
                        </div>
                    </form>
                </section>

                <section v-if="canNotifyCustomer" class="rounded-card border border-gray-200 bg-white p-5">
                    <SectionLabel text="Kunde benachrichtigt" tone="muted" />
                    <p v-if="customerNotifiedAt" class="flex items-center gap-2.5 pt-3">
                        <span class="block h-1.5 w-1.5 shrink-0 rounded-full bg-success" aria-hidden="true" />
                        <span class="text-sm text-gray-800">
                            Ja — am <span class="font-mono tabular-nums">{{ dateTime(customerNotifiedAt) }}</span>
                        </span>
                    </p>
                    <p v-else class="flex items-center gap-2.5 pt-3">
                        <span class="block h-1.5 w-1.5 shrink-0 rounded-full bg-warning" aria-hidden="true" />
                        <span class="text-sm text-warning">Noch nicht — der Kunde wartet ohne Rückmeldung.</span>
                    </p>
                    <BaseButton
                        variant="secondary"
                        size="compact"
                        class="mt-4"
                        :loading="notifyCustomer.processing"
                        @click="notifyCustomer.post(`/admin/anfragen/${request.id}/kunde-benachrichtigen`, { preserveScroll: true })"
                    >Kunde erneut benachrichtigen</BaseButton>
                </section>

                <!--
                    Why the partners who cover this postal code did not all get
                    it. "The postal code matching is unreliable" is almost always
                    a partner who is unavailable or does not offer this
                    assessment type, and without this the office cannot tell the
                    two apart.
                -->
                <section v-if="matching && matching.excluded.length" class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Nicht angeschrieben" tone="muted" />
                    <p class="measure pt-2 text-sm text-gray-600">
                        {{ matching.covering_count }} Sachverständige decken die PLZ {{ matching.postal_code }} ab,
                        {{ matching.eligible_count }} davon kamen für diese Anfrage infrage. Die übrigen wurden aus
                        diesen Gründen nicht angeschrieben:
                    </p>
                    <ul class="flex flex-col gap-2 pt-4">
                        <li
                            v-for="entry in matching.excluded"
                            :key="`aus-${entry.id}`"
                            class="flex flex-wrap items-baseline justify-between gap-x-5 gap-y-1 border-b border-gray-100 pb-2 last:border-b-0"
                        >
                            <a :href="`/admin/sachverstaendige/${entry.id}`" class="text-sm text-navy-700 hover:text-navy-500">
                                {{ entry.company_name }}
                            </a>
                            <span class="text-sm text-gray-600">{{ entry.reasons.join(' · ') }}</span>
                        </li>
                    </ul>
                </section>

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
                                    <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Sachverständiger</th>
                                    <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Benachrichtigt</th>
                                    <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Geöffnet</th>
                                    <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Antwort</th>
                                    <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Ergebnis</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="entry in trail" :key="entry.id" class="border-b border-gray-100 last:border-b-0">
                                    <td class="px-5 py-3.5">
                                        <a :href="`/admin/sachverstaendige/${entry.assessor.id}`" class="text-sm font-medium text-navy-700 hover:text-navy-500">
                                            {{ entry.assessor.company_name }}
                                        </a>
                                        <span class="block text-xs text-gray-600">
                                            {{ [entry.assessor.contact, entry.assessor.city].filter(Boolean).join(' · ') }}
                                        </span>
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

                <!--
                    Matching only reaches partners who already registered, so a
                    request nobody covers used to have nowhere to go. An admin
                    can send it straight to somebody they know of; that person
                    accepts from the e-mail and registers afterwards.
                -->
                <section class="border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 p-5">
                        <SectionLabel text="Externe Sachverständige" tone="muted" />
                        <p class="measure pt-2 text-sm text-gray-600">
                            Senden Sie diese Anfrage an einen Sachverständigen, der noch keinen Zugang
                            hat. Er kann den Auftrag direkt annehmen und sich danach registrieren.
                        </p>
                    </div>

                    <ul v-if="offers.length" class="px-5">
                        <li
                            v-for="offer in offers"
                            :key="offer.id"
                            class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1.5 border-b border-gray-100 py-3.5 last:border-b-0"
                        >
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-navy-700">
                                    {{ offer.name || offer.email }}
                                </span>
                                <span v-if="offer.name" class="block text-xs text-gray-600">{{ offer.email }}</span>
                                <span class="block pt-0.5 text-xs text-gray-600">
                                    Gesendet {{ offer.sent_at_label }}
                                    <template v-if="offer.viewed_at_label"> · geöffnet {{ offer.viewed_at_label }}</template>
                                    <template v-if="offer.answered_at_label"> · beantwortet {{ offer.answered_at_label }}</template>
                                </span>
                                <span v-if="offer.decline_reason" class="block pt-0.5 text-xs text-gray-600">
                                    Grund: {{ offer.decline_reason }}
                                </span>
                            </span>
                            <StatusDot :status="offer.status" :label="offer.status_label" />
                        </li>
                    </ul>

                    <form v-if="canOffer" class="grid gap-4 p-5 sm:grid-cols-2" @submit.prevent="sendOffer">
                        <BaseInput
                            v-model="offerForm.email"
                            label="E-Mail-Adresse"
                            type="email"
                            required
                            :error="offerForm.errors.email"
                        />
                        <BaseInput
                            v-model="offerForm.name"
                            label="Name oder Firma"
                            optional
                            :error="offerForm.errors.name"
                        />
                        <div class="sm:col-span-2">
                            <BaseTextarea
                                v-model="offerForm.message"
                                label="Persönliche Nachricht"
                                optional
                                :rows="3"
                                :error="offerForm.errors.message"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <BaseButton size="compact" type="submit" :loading="offerForm.processing">
                                Anfrage senden
                            </BaseButton>
                        </div>
                    </form>

                    <p v-else-if="!offers.length" class="p-5 text-sm text-gray-600">
                        Diese Anfrage ist bereits vergeben; es können keine weiteren Einladungen versendet werden.
                    </p>
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
