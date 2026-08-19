<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Download, Trash2 } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import AssignmentTimeline from '../../Components/Domain/AssignmentTimeline.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseCurrencyInput from '../../Components/Base/BaseCurrencyInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseFileUpload from '../../Components/Base/BaseFileUpload.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({
    assignment: { type: Object, required: true },
    request: { type: Object, required: true },
    documents: { type: Array, default: () => [] },
    timeline: { type: Array, default: () => [] },
    commission: { type: Object, default: null },
    feeBounds: { type: Object, default: () => ({}) },
})

const { dateTime, money, percent } = useGermanFormat()
const { confirm } = useConfirm()

const start = useForm({ status: 'in_progress', note: '' })
const report = useForm({ type: 'report', document: null })
const invoice = useForm({ type: 'customer_invoice', document: null })
const complete = useForm({ fee_cents: null, notes: '' })

const reportDoc = () => props.documents.find((d) => d.type === 'report')
const invoiceDoc = () => props.documents.find((d) => d.type === 'customer_invoice')

const upload = (form) => form.post(`/portal/auftraege/${props.assignment.id}/dokumente`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => form.reset('document'),
})

const removeDoc = async (doc) => {
    const ok = await confirm({
        title: 'Unterlage entfernen?',
        message: `${doc.original_name} wird dauerhaft gelöscht.`,
        confirmLabel: 'Entfernen',
        tone: 'danger',
    })

    if (ok) useForm({}).delete(`/portal/auftraege/${props.assignment.id}/dokumente/${doc.id}`, { preserveScroll: true })
}

const doComplete = async () => {
    const ok = await confirm({
        title: 'Auftrag abschließen?',
        message: `Das erfasste Honorar von ${money(complete.fee_cents ?? 0)} wird festgeschrieben und die Provision berechnet. Der Kunde erhält Gutachten und Rechnung per E-Mail.`,
        confirmLabel: 'Abschließen',
    })

    if (ok) complete.post(`/portal/auftraege/${props.assignment.id}/abschliessen`)
}
</script>

<template>
    <Head :title="request.reference" />

    <PortalLayout :title="request.reference" back-href="/portal/auftraege">
        <PageHeader :title="request.reference" :eyebrow="request.service_type?.name">
            <template #actions>
                <StatusDot :status="assignment.status" :label="assignment.status_label" />
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="flex flex-col gap-6">
                <!-- Contact data, released by the acceptance -->
                <section v-if="request.contact_released" class="border border-navy-700 bg-navy-100 p-5">
                    <SectionLabel text="Kontaktdaten" tone="muted" />
                    <dl class="pt-4">
                        <div class="flex items-baseline justify-between gap-4 border-b border-navy-700/10 py-2.5">
                            <dt class="text-sm text-gray-600">Name</dt>
                            <dd class="text-sm font-medium text-gray-800">{{ request.customer.name }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-navy-700/10 py-2.5">
                            <dt class="text-sm text-gray-600">Telefon</dt>
                            <dd><a :href="`tel:${request.customer.phone.replace(/\s/g, '')}`" class="font-mono text-sm tabular-nums text-navy-700">{{ request.customer.phone_label }}</a></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 py-2.5">
                            <dt class="text-sm text-gray-600">E-Mail</dt>
                            <dd><a :href="`mailto:${request.customer.email}`" class="text-sm text-navy-700">{{ request.customer.email }}</a></dd>
                        </div>
                    </dl>
                </section>

                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Anfragedaten" tone="muted" />
                    <dl class="pt-4">
                        <div v-for="row in [
                            { label: 'Standort', value: request.location },
                            { label: 'Fahrzeug', value: request.vehicle },
                            { label: 'Kennzeichen', value: request.vehicle_plate, mono: true },
                            { label: 'Dringlichkeit', value: request.urgency_label },
                        ].filter((r) => r.value)" :key="row.label" class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5 last:border-b-0">
                            <dt class="text-sm text-gray-600">{{ row.label }}</dt>
                            <dd class="text-sm text-gray-800" :class="row.mono ? 'font-mono' : ''">{{ row.value }}</dd>
                        </div>
                    </dl>
                    <p v-if="request.description" class="measure border-t border-gray-200 pt-4 mt-4 text-base leading-normal text-gray-800">{{ request.description }}</p>
                </section>

                <!-- Documents: both are required before completion -->
                <section v-if="assignment.is_open" class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Unterlagen" tone="muted" />
                    <p class="pb-5 pt-2 text-sm leading-normal text-gray-600">
                        Der Auftrag lässt sich erst abschließen, wenn Gutachten und die von Ihnen gestellte Rechnung
                        hinterlegt sind.
                    </p>

                    <div class="flex flex-col gap-6">
                        <div>
                            <BaseFileUpload
                                v-model="report.document"
                                label="Gutachten"
                                accept-label="PDF, JPG oder PNG · max. 10 MB"
                                :existing="reportDoc() ? [reportDoc()] : []"
                                :error="report.errors.document"
                                @remove-existing="removeDoc"
                            />
                            <BaseButton v-if="report.document" size="compact" class="mt-3" :loading="report.processing" @click="upload(report)">Gutachten hochladen</BaseButton>
                        </div>

                        <div>
                            <BaseFileUpload
                                v-model="invoice.document"
                                label="Rechnung an den Kunden"
                                accept-label="PDF, JPG oder PNG · max. 10 MB"
                                :existing="invoiceDoc() ? [invoiceDoc()] : []"
                                :error="invoice.errors.document"
                                @remove-existing="removeDoc"
                            />
                            <BaseButton v-if="invoice.document" size="compact" class="mt-3" :loading="invoice.processing" @click="upload(invoice)">Rechnung hochladen</BaseButton>
                        </div>
                    </div>
                </section>

                <section v-else class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Unterlagen" tone="muted" />
                    <ul class="pt-4">
                        <li v-for="doc in documents" :key="doc.id" class="flex items-center justify-between gap-4 border-b border-gray-100 py-3 last:border-b-0">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-800">{{ doc.original_name }}</p>
                                <p class="font-mono text-xs text-gray-400">{{ doc.type_label }} · {{ doc.size_label }}</p>
                            </div>
                            <a :href="doc.download_url" class="flex shrink-0 items-center gap-2 text-sm font-medium text-navy-700 hover:text-navy-500">
                                <Download :size="16" :stroke-width="1.5" aria-hidden="true" />Herunterladen
                            </a>
                        </li>
                    </ul>
                </section>

                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Verlauf" tone="muted" />
                    <AssignmentTimeline :events="timeline" class="pt-4" />
                </section>
            </div>

            <aside class="flex flex-col gap-6 lg:sticky lg:top-6 lg:self-start">
                <div v-if="assignment.status === 'accepted'" class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Bearbeitung" tone="muted" />
                    <p class="pt-3 text-sm leading-normal text-gray-600">Markieren Sie den Auftrag als in Bearbeitung, sobald Sie begonnen haben.</p>
                    <BaseButton size="cta" block class="mt-4" :loading="start.processing" @click="start.post(`/portal/auftraege/${assignment.id}/status`, { preserveScroll: true })">
                        Bearbeitung beginnen
                    </BaseButton>
                </div>

                <!-- Completion: fee capture and the commission preview -->
                <form v-if="assignment.is_open" class="border border-navy-700 p-5" @submit.prevent="doComplete">
                    <SectionLabel text="Abschluss" tone="muted" />
                    <p class="pb-4 pt-3 text-sm leading-normal text-gray-600">
                        Erfassen Sie das tatsächlich berechnete Netto-Honorar. Daraus errechnet sich die Vermittlungsprovision.
                    </p>

                    <BaseCurrencyInput
                        v-model="complete.fee_cents"
                        label="Berechnetes Honorar (netto)"
                        :error="complete.errors.fee_cents"
                        hint="Zwischen 50,00 € und 50.000,00 €."
                        required
                    />

                    <BaseTextarea v-model="complete.notes" label="Anmerkung" :rows="3" class="pt-4" :error="complete.errors.notes" optional />

                    <BaseButton
                        type="submit"
                        size="cta"
                        block
                        class="mt-5"
                        :loading="complete.processing"
                        :disabled="!assignment.can_complete"
                    >Auftrag abschließen</BaseButton>

                    <p v-if="!assignment.can_complete" class="pt-3 text-sm leading-normal text-warning">
                        Bitte hinterlegen Sie zuerst Gutachten und Rechnung.
                    </p>
                </form>

                <div v-if="commission" class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Provision" tone="muted" />
                    <dl class="pt-4">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Honorar netto</dt><dd><MoneyValue :cents="commission.fee_cents" /></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Provision {{ percent(commission.rate_percent) }}</dt><dd><MoneyValue :cents="commission.commission_cents" /></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 pt-3">
                            <dt class="text-sm font-medium text-navy-700">Ihr Anteil</dt><dd><MoneyValue :cents="commission.assessor_share_cents" emphasis /></dd>
                        </div>
                    </dl>
                    <p class="pt-3"><StatusDot :status="commission.status" :label="commission.status_label" /></p>
                </div>
            </aside>
        </div>
    </PortalLayout>
</template>
