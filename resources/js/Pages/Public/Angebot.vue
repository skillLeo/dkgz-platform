<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Check, Clock, MapPin, X } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'

/**
 * A single request, offered by hand to somebody with no account.
 *
 * They are being asked to commit to work before they have a login, so the page
 * has one job: show enough of the job to decide on, and nothing about the
 * customer. The contact details arrive only once they have registered and the
 * assignment is theirs — that boundary is enforced server-side, and this page
 * simply never receives them.
 */
const props = defineProps({
    offer: { type: Object, required: true },
    request: { type: Object, required: true },
})

const declineOpen = ref(false)

const accept = useForm({})
const decline = useForm({ decline_reason: '' })

const URGENCY = {
    immediate: 'Sofort',
    soon: 'In den nächsten Tagen',
    flexible: 'Zeitlich flexibel',
}

/** What the visitor is told when the offer is no longer theirs to take. */
const CLOSED = {
    uebernommen: 'Dieser Auftrag wurde Ihnen bereits zugeteilt. Melden Sie sich in Ihrem Portal an.',
    abgelehnt: 'Sie haben diesen Auftrag abgelehnt. Vielen Dank für Ihre Rückmeldung.',
    reserviert: 'Der Auftrag ist für Sie reserviert. Schließen Sie Ihre Registrierung ab, um ihn zu übernehmen.',
    frist_abgelaufen: 'Die Reservierung ist abgelaufen, weil die Registrierung nicht abgeschlossen wurde.',
    abgelaufen: 'Dieser Link ist nicht mehr gültig.',
    vergeben: 'Dieser Auftrag wurde inzwischen an einen anderen Sachverständigen vergeben.',
}
</script>

<template>
    <PublicLayout>
        <Head :title="`Auftrag ${request.reference}`" />

        <div class="mx-auto w-full max-w-(--container-shell) px-4 py-12 md:px-6 md:py-16">
            <div class="mx-auto max-w-2xl">
                <p class="text-eyebrow font-semibold uppercase text-gray-600">Auftragsangebot</p>
                <h1 class="text-balance pt-2 text-h1 font-bold leading-tight text-navy-700">
                    {{ request.service_type }} in {{ request.location }}
                </h1>

                <p v-if="offer.name" class="pt-4 text-lead leading-normal text-gray-800">
                    Guten Tag {{ offer.name }},
                </p>
                <p class="measure pt-3 leading-relaxed text-gray-800">
                    die Deutsche KFZ-Gutachterzentrale vermittelt Aufträge an geprüfte Sachverständige.
                    Diesen Auftrag möchten wir Ihnen anbieten.
                </p>

                <blockquote
                    v-if="offer.message"
                    class="mt-6 border-l-2 border-accent pl-5 text-sm leading-relaxed text-gray-800"
                >
                    {{ offer.message }}
                </blockquote>

                <!-- The job, and only the job. -->
                <section class="mt-8 rounded-card border border-gray-200 bg-white p-5 md:p-6">
                    <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Der Auftrag</h2>
                    <dl class="pt-4">
                        <div class="flex items-baseline justify-between gap-6 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Vorgang</dt>
                            <dd class="font-mono text-sm tabular-nums text-gray-800">{{ request.reference }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-6 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Leistung</dt>
                            <dd class="text-sm text-gray-800">{{ request.service_type }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-6 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Ort</dt>
                            <dd class="flex items-center gap-2 text-sm text-gray-800">
                                <MapPin :size="15" :stroke-width="1.5" class="shrink-0 text-gray-400" aria-hidden="true" />
                                {{ request.location }}
                            </dd>
                        </div>
                        <div v-if="request.vehicle" class="flex items-baseline justify-between gap-6 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Fahrzeug</dt>
                            <dd class="text-sm text-gray-800">{{ request.vehicle }}</dd>
                        </div>
                        <div v-if="request.urgency" class="flex items-baseline justify-between gap-6 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Zeitrahmen</dt>
                            <dd class="text-sm text-gray-800">{{ URGENCY[request.urgency] ?? request.urgency }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-6 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Eingegangen</dt>
                            <dd class="font-mono text-sm tabular-nums text-gray-800">{{ request.created_at_label }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-6 py-2.5">
                            <dt class="text-sm text-gray-600">DKGZ-Gebühr</dt>
                            <dd class="text-sm text-gray-800">{{ request.dkgz_fee_label }}</dd>
                        </div>
                    </dl>

                    <p v-if="request.description" class="measure pt-4 text-sm leading-relaxed text-gray-800">
                        {{ request.description }}
                    </p>

                    <p class="flex items-start gap-2.5 pt-5 text-sm leading-normal text-gray-600">
                        <Clock :size="15" :stroke-width="1.5" class="mt-0.5 shrink-0 text-gray-400" aria-hidden="true" />
                        Die Kontaktdaten des Kunden erhalten Sie, sobald Ihnen der Auftrag zugeteilt ist.
                    </p>
                </section>

                <p v-if="accept.errors.offer || decline.errors.offer" class="pt-4 text-sm text-danger">
                    {{ accept.errors.offer || decline.errors.offer }}
                </p>

                <!-- Still open: the two things they can do. -->
                <template v-if="offer.state === 'offen'">
                    <div class="flex flex-col gap-3 pt-8 sm:flex-row sm:items-center">
                        <BaseButton
                            size="cta"
                            class="w-full sm:w-auto"
                            :loading="accept.processing"
                            @click="accept.post(`/auftrag-angebot/${offer.token}/annehmen`)"
                        >
                            <Check :size="18" :stroke-width="2" aria-hidden="true" />
                            Auftrag annehmen
                        </BaseButton>
                        <BaseButton
                            variant="secondary"
                            size="cta"
                            class="w-full sm:w-auto"
                            @click="declineOpen = !declineOpen"
                        >
                            <X :size="18" :stroke-width="1.5" aria-hidden="true" />
                            Ablehnen
                        </BaseButton>
                    </div>

                    <p class="measure pt-4 text-sm leading-normal text-gray-600">
                        Nach der Annahme ist der Auftrag {{ offer.hold_hours }} Stunden für Sie reserviert,
                        während Sie Ihre Registrierung abschließen. Dieser Link ist bis zum
                        {{ offer.expires_at_label }} gültig.
                    </p>

                    <form
                        v-if="declineOpen"
                        class="mt-6 rounded-card border border-gray-200 bg-white p-5"
                        @submit.prevent="decline.post(`/auftrag-angebot/${offer.token}/ablehnen`)"
                    >
                        <BaseTextarea
                            v-model="decline.decline_reason"
                            label="Grund für die Ablehnung"
                            optional
                            :rows="3"
                            hint="Hilft uns, Ihnen künftig passendere Aufträge zu senden."
                            :error="decline.errors.decline_reason"
                        />
                        <BaseButton
                            variant="secondary"
                            size="compact"
                            type="submit"
                            class="mt-4"
                            :loading="decline.processing"
                        >Ablehnung senden</BaseButton>
                    </form>
                </template>

                <!-- Anything else: say plainly what happened. -->
                <p v-else class="mt-8 rounded-card border border-gray-200 bg-gray-50 p-5 text-sm leading-relaxed text-gray-800">
                    {{ CLOSED[offer.state] }}
                </p>

                <div v-if="offer.state === 'reserviert'" class="pt-5">
                    <BaseButton size="cta" href="/registrieren" class="w-full sm:w-auto">
                        Registrierung abschließen
                    </BaseButton>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
