<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Lock } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({
    request: { type: Object, required: true },
    match: { type: Object, required: true },
})

const { dateTime, date } = useGermanFormat()
const { confirm } = useConfirm()

const accept = useForm({})
const decline = useForm({ reason: '' })
const declineOpen = ref(false)

const doAccept = async () => {
    const ok = await confirm({
        title: 'Auftrag übernehmen?',
        message: 'Mit der Annahme werden die Kontaktdaten der anfragenden Person für Sie freigegeben, und die Anfrage schließt für alle anderen Partner.',
        confirmLabel: 'Auftrag übernehmen',
    })

    if (ok) accept.post(`/portal/anfragen/${props.request.id}/annehmen`)
}
</script>

<template>
    <Head :title="request.reference" />

    <PortalLayout :title="request.reference" back-href="/portal/anfragen">
        <PageHeader :title="request.reference" :eyebrow="request.service_type?.name" />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="flex flex-col gap-6">
                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Anfragedaten" tone="muted" />
                    <dl class="pt-4">
                        <div v-for="row in [
                            { label: 'Art des Gutachtens', value: request.service_type?.name },
                            { label: 'Standort', value: request.location },
                            { label: 'Fahrzeug', value: request.vehicle },
                            { label: 'Kennzeichen', value: request.vehicle_plate, mono: true },
                            { label: 'Dringlichkeit', value: request.urgency_label },
                            { label: 'Wunschtermin', value: request.preferred_date ? date(request.preferred_date) : null, mono: true },
                            { label: 'Fotos', value: request.image_count, mono: true },
                            { label: 'Eingegangen am', value: request.created_at_label, mono: true },
                        ].filter((r) => r.value !== null && r.value !== undefined && r.value !== '')" :key="row.label"
                            class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5 last:border-b-0">
                            <dt class="shrink-0 text-sm text-gray-600">{{ row.label }}</dt>
                            <dd class="min-w-0 text-right text-sm text-gray-800" :class="row.mono ? 'font-mono tabular-nums' : ''">{{ row.value }}</dd>
                        </div>
                    </dl>
                    <div v-if="request.description" class="border-t border-gray-200 pt-4 mt-4">
                        <p class="text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">Beschreibung</p>
                        <p class="measure pt-2 text-base leading-normal text-gray-800">{{ request.description }}</p>
                    </div>
                </section>

                <!-- The mask, exactly as the design specifies it -->
                <section v-if="!request.contact_released" class="rounded-sm border border-gray-200 bg-gray-50 p-4">
                    <div class="flex items-center gap-2.5">
                        <Lock :size="18" :stroke-width="1.5" class="shrink-0 text-gray-600" aria-hidden="true" />
                        <p class="text-sm font-medium text-gray-800">Kontaktdaten noch nicht sichtbar</p>
                    </div>
                    <p class="measure pt-2 text-sm leading-normal text-gray-600">
                        Name, Telefon und E-Mail der anfragenden Person werden freigegeben, sobald Sie den Auftrag annehmen.
                    </p>
                </section>
            </div>

            <aside class="lg:sticky lg:top-6 lg:self-start">
                <div v-if="match.is_open" class="border border-navy-700 p-5">
                    <SectionLabel text="Ihre Entscheidung" tone="muted" />
                    <p class="pt-3 text-sm leading-normal text-gray-600">
                        Der erste verfügbare Partner übernimmt. Eine Ablehnung wirkt sich nicht auf die weitere
                        Verteilung aus.
                    </p>
                    <div class="flex flex-col gap-3 pt-5">
                        <BaseButton size="cta" block :loading="accept.processing" @click="doAccept">Auftrag übernehmen</BaseButton>
                        <BaseButton variant="secondary" size="cta" block @click="declineOpen = !declineOpen">Ablehnen</BaseButton>
                    </div>

                    <form v-if="declineOpen" class="pt-5" @submit.prevent="decline.post(`/portal/anfragen/${request.id}/ablehnen`)">
                        <BaseTextarea v-model="decline.reason" label="Begründung" hint="Nur intern sichtbar." :rows="3" :error="decline.errors.reason" optional />
                        <BaseButton type="submit" size="compact" block class="mt-3" :loading="decline.processing">Ablehnung senden</BaseButton>
                    </form>
                </div>

                <div v-else class="border border-gray-200 bg-gray-50 p-5">
                    <p class="text-sm leading-normal text-gray-800">
                        Diese Anfrage ist für Sie abgeschlossen. Sie wurde bereits vergeben oder von Ihnen abgelehnt.
                    </p>
                    <BaseButton href="/portal/anfragen" variant="secondary" size="compact" block class="mt-4">Zu den offenen Anfragen</BaseButton>
                </div>
            </aside>
        </div>
    </PortalLayout>
</template>
