<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ChevronLeft, Clock, Image as ImageIcon, Lock, Mail, Phone } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({
    request: { type: Object, required: true },
    match: { type: Object, required: true },
})

const { date, deadline } = useGermanFormat()
const { confirm } = useConfirm()

const accept = useForm({})
const decline = useForm({ reason: '' })
const declineOpen = ref(false)

const rows = computed(() => [
    { label: 'Standort des Fahrzeugs', value: props.request.location },
    { label: 'Fahrzeug', value: props.request.vehicle },
    { label: 'Kennzeichen', value: props.request.vehicle_plate, mono: true },
    { label: 'Schadenart', value: props.request.description },
    { label: 'Dringlichkeit', value: props.request.urgency_label },
    { label: 'Wunschtermin', value: props.request.preferred_date ? date(props.request.preferred_date) : null, mono: true },
    { label: 'Eingegangen', value: props.request.created_at_label, mono: true },
].filter((row) => row.value !== null && row.value !== undefined && row.value !== ''))

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

    <PortalLayout title="Anfrage-Detail" back-href="/portal/anfragen">
        <Link
            href="/portal/anfragen"
            class="inline-flex items-center gap-2 pb-4 text-sm font-medium text-navy-700 hover:text-navy-500"
        >
            <ChevronLeft :size="16" :stroke-width="1.5" aria-hidden="true" />
            Zurück zu den Anfragen
        </Link>

        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
            <section class="rounded-card border border-gray-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-4 pb-4">
                    <div>
                        <p class="font-mono text-h4 text-navy-700">{{ request.reference }}</p>
                        <p class="pt-1 text-sm text-gray-600">{{ request.service_type?.name }}</p>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-2 rounded-sm border border-gray-200 bg-gray-50 px-2.5 py-1">
                        <span class="block h-1.5 w-1.5 rounded-full bg-navy-700" aria-hidden="true" />
                        <span class="text-sm text-gray-800">Vermittelt · {{ match.outcome_label }}</span>
                    </span>
                </div>

                <table class="w-full">
                    <tbody>
                        <tr v-for="row in rows" :key="row.label" class="border-t border-gray-100">
                            <td class="w-52 py-3 pr-4 align-top text-sm text-gray-600">{{ row.label }}</td>
                            <td class="py-3 align-top text-sm text-gray-800" :class="row.mono ? 'font-mono' : ''">
                                {{ row.value }}
                            </td>
                        </tr>
                        <tr class="border-t border-gray-100">
                            <td class="py-3 pr-4 align-top text-sm text-gray-600">Kundendaten</td>
                            <td class="py-3 align-top">
                                <span v-if="!request.contact_released" class="inline-flex items-center gap-2">
                                    <span class="block h-2.5 w-22 rounded-xs bg-gray-200" aria-hidden="true" />
                                    <Lock :size="14" :stroke-width="1.5" class="text-gray-400" aria-hidden="true" />
                                    <span class="text-sm text-gray-600">Sichtbar nach Annahme</span>
                                </span>
                                <span v-else class="text-sm text-gray-800">{{ request.customer.name }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="request.image_count" class="border-t border-gray-200 pt-4">
                    <p class="text-eyebrow font-semibold uppercase text-gray-600">
                        Fotos der anfragenden Person · {{ request.image_count }}
                    </p>
                    <div class="flex flex-wrap gap-2.5 pt-3">
                        <span
                            v-for="n in request.image_count"
                            :key="n"
                            class="grid h-20 w-28 place-items-center rounded-sm border border-gray-200 bg-gray-100"
                        >
                            <ImageIcon :size="18" :stroke-width="1.25" class="text-gray-400" aria-hidden="true" />
                            <span class="sr-only">Foto {{ n }}</span>
                        </span>
                    </div>
                </div>
            </section>

            <aside class="flex flex-col gap-6 xl:sticky xl:top-6">
                <div v-if="match.is_open" class="rounded-card border border-gray-200 bg-white p-5">
                    <p class="text-eyebrow font-semibold uppercase text-gray-600">Frist zur Annahme</p>
                    <p class="flex items-center gap-2.5 pt-3">
                        <Clock :size="20" :stroke-width="1.5" class="shrink-0 text-navy-700" aria-hidden="true" />
                        <span class="text-h4 font-semibold tabular-nums text-navy-700">
                            {{ deadline(request.accept_deadline_at) }}
                        </span>
                    </p>
                    <p class="pt-2 text-sm leading-normal text-gray-600">
                        Danach geht die Anfrage an weitere Partner im Gebiet.
                    </p>

                    <div class="flex flex-col gap-3 pt-5">
                        <BaseButton size="cta" block :loading="accept.processing" @click="doAccept">Auftrag annehmen</BaseButton>
                        <BaseButton variant="secondary" size="cta" block @click="declineOpen = !declineOpen">Ablehnen</BaseButton>
                    </div>

                    <form v-if="declineOpen" class="pt-5" @submit.prevent="decline.post(`/portal/anfragen/${request.id}/ablehnen`)">
                        <BaseTextarea
                            v-model="decline.reason"
                            label="Begründung"
                            hint="Nur intern sichtbar."
                            :rows="3"
                            :error="decline.errors.reason"
                            optional
                        />
                        <BaseButton type="submit" size="compact" block class="mt-3" :loading="decline.processing">
                            Ablehnung senden
                        </BaseButton>
                    </form>
                </div>

                <div v-else class="rounded-card border border-gray-200 bg-gray-50 p-5">
                    <p class="text-sm leading-normal text-gray-800">
                        Diese Anfrage ist für Sie abgeschlossen. Sie wurde bereits vergeben oder von Ihnen abgelehnt.
                    </p>
                    <BaseButton href="/portal/anfragen" variant="secondary" size="compact" block class="mt-4">
                        Zu den offenen Anfragen
                    </BaseButton>
                </div>

                <div v-if="request.contact_released" class="rounded-card border border-gray-200 bg-white p-5">
                    <p class="text-eyebrow font-semibold uppercase text-gray-600">Kontaktdaten</p>
                    <div class="pt-3">
                        <p class="text-base font-medium text-gray-800">{{ request.customer.name }}</p>
                        <p class="flex items-center gap-2.5 pt-2">
                            <Phone :size="16" :stroke-width="1.5" class="shrink-0 text-gray-600" aria-hidden="true" />
                            <a :href="`tel:${request.customer.phone}`" class="font-mono text-sm text-navy-700">
                                {{ request.customer.phone_label }}
                            </a>
                        </p>
                        <p class="flex items-center gap-2.5 pt-1.5">
                            <Mail :size="16" :stroke-width="1.5" class="shrink-0 text-gray-600" aria-hidden="true" />
                            <a :href="`mailto:${request.customer.email}`" class="text-sm text-navy-700">
                                {{ request.customer.email }}
                            </a>
                        </p>
                    </div>
                    <p class="pt-3 text-sm leading-normal text-gray-600">
                        Mit der Annahme werden die Kontaktdaten freigegeben und alle anderen Partner informiert.
                    </p>
                </div>
            </aside>
        </div>
    </PortalLayout>
</template>
