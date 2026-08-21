<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Check, ChevronLeft, Download, FileText, Mail, Phone } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import StatusRail from '../../Components/Data/StatusRail.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import AssignmentTimeline from '../../Components/Domain/AssignmentTimeline.vue'
import CompleteAssignmentDialog from '../../Components/Domain/CompleteAssignmentDialog.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseFileUpload from '../../Components/Base/BaseFileUpload.vue'
import BaseCurrencyInput from '../../Components/Base/BaseCurrencyInput.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({
    assignment: { type: Object, required: true },
    request: { type: Object, required: true },
    documents: { type: Array, default: () => [] },
    timeline: { type: Array, default: () => [] },
    commission: { type: Object, default: null },
    dkgzInvoices: { type: Array, default: () => [] },
    dkgzFeeLabel: { type: String, default: null },
})

const { stamp } = useGermanFormat()
const { confirm } = useConfirm()

const confirmForm = useForm({ note: '' })
const customerInvoice = useForm({
    customer_invoice_cents: props.assignment.customer_invoice_cents ?? null,
    customer_invoice_recipient: props.assignment.customer_invoice_recipient ?? '',
    customer_invoice_number: props.assignment.customer_invoice_number ?? '',
})
const report = useForm({ type: 'report', document: null })
const invoice = useForm({ type: 'customer_invoice', document: null })
const complete = useForm({ notes: '' })
const completeOpen = ref(false)

const RECIPIENTS = [
    { value: 'kunde', label: 'Kunde' },
    { value: 'versicherung', label: 'Versicherung' },
]

async function submitConfirmation() {
    const ok = await confirm({
        title: 'Auftrag als zustande gekommen bestätigen?',
        message: 'Damit bestätigen Sie, dass der Kunde Sie beauftragt hat. Der Auftrag geht in '
            + 'die Bearbeitung über und Sie erhalten unsere Rechnung über die DKGZ-Gebühr per E-Mail.',
        confirmLabel: 'Ja, zustande gekommen',
    })

    if (ok) {
        confirmForm.post(`/portal/auftraege/${props.assignment.id}/zustande-gekommen`, { preserveScroll: true })
    }
}

const reportDoc = computed(() => props.documents.find((d) => d.type === 'report'))
const invoiceDoc = computed(() => props.documents.find((d) => d.type === 'customer_invoice'))
const documentCount = computed(() => [reportDoc.value, invoiceDoc.value].filter(Boolean).length)

const steps = computed(() => {
    const accepted = props.assignment.accepted_at
    const completed = props.assignment.completed_at

    return [
        { label: 'Vermittelt', done: true, stamp: stamp(props.request.created_at) },
        { label: 'Angenommen', done: Boolean(accepted), stamp: accepted ? stamp(accepted) : 'offen' },
        {
            label: 'Unterlagen vollständig',
            done: documentCount.value === 2,
            stamp: `${documentCount.value} von 2`,
        },
        { label: 'Abgeschlossen', done: Boolean(completed), stamp: completed ? stamp(completed) : 'offen' },
    ]
})

const doneCount = computed(() => steps.value.filter((step) => step.done).length)

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

const submitCompletion = () => complete.post(`/portal/auftraege/${props.assignment.id}/abschliessen`, {
    onSuccess: () => { completeOpen.value = false },
})
</script>

<template>
    <Head :title="request.reference" />

    <PortalLayout title="Auftrag-Arbeitsfläche" back-href="/portal/auftraege">
        <Link
            href="/portal/auftraege"
            class="inline-flex items-center gap-2 pb-4 text-sm font-medium text-navy-700 hover:text-navy-500"
        >
            <ChevronLeft :size="16" :stroke-width="1.5" aria-hidden="true" />
            Zurück zu meinen Aufträgen
        </Link>

        <div class="flex flex-wrap items-start justify-between gap-4 pb-6">
            <div>
                <p class="font-mono text-h4 text-navy-700">{{ request.reference }}</p>
                <p class="pt-1 text-sm text-gray-600">{{ request.service_type?.name }} · {{ request.city }}</p>
            </div>
            <span class="flex items-center gap-3">
                <StatusDot :status="assignment.status" :label="assignment.status_label" />
                <span class="font-mono text-sm tabular-nums text-gray-400 lg:hidden">
                    {{ doneCount }} von {{ steps.length }}
                </span>
            </span>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2 xl:grid-cols-3">
            <section class="hidden rounded-card border border-gray-200 bg-white p-5 lg:block">
                <h2 class="pb-4 text-eyebrow font-semibold uppercase text-gray-600">Status</h2>
                <StatusRail :steps="steps" />
            </section>

            <section class="rounded-card border border-gray-200 bg-white p-5">
                <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Kundenkontakt</h2>
                <div v-if="request.contact_released" class="pt-3">
                    <p class="text-base font-medium text-gray-800">{{ request.customer.name }}</p>
                    <a
                        :href="`tel:${request.customer.phone}`"
                        class="flex min-h-11 items-center gap-2.5 border-b border-gray-100 md:min-h-0 md:border-b-0 md:pt-2"
                    >
                        <Phone :size="16" :stroke-width="1.5" class="shrink-0 text-gray-600" aria-hidden="true" />
                        <span class="font-mono text-sm text-navy-700">{{ request.customer.phone_label }}</span>
                    </a>
                    <a
                        :href="`mailto:${request.customer.email}`"
                        class="flex min-h-11 items-center gap-2.5 md:min-h-0 md:pt-1.5"
                    >
                        <Mail :size="16" :stroke-width="1.5" class="shrink-0 text-gray-600" aria-hidden="true" />
                        <span class="break-all text-sm text-navy-700">{{ request.customer.email }}</span>
                    </a>
                </div>

                <h2 class="pt-6 text-eyebrow font-semibold uppercase text-gray-600">Fahrzeug</h2>
                <p class="pt-3 text-sm leading-relaxed text-gray-800">
                    {{ request.vehicle }}<br >
                    <span v-if="request.vehicle_plate" class="font-mono">{{ request.vehicle_plate }}<br ></span>
                    Standort: {{ request.location }}
                </p>
            </section>

            <section class="rounded-card border border-gray-200 bg-white p-5 lg:col-span-2 xl:col-span-1">
                <div v-if="assignment.is_open" class="flex flex-col gap-6">
                    <div>
                        <BaseFileUpload
                            v-model="report.document"
                            label="Gutachten hochladen"
                            required
                            accept-label="max. 10 MB"
                            :existing="reportDoc ? [reportDoc] : []"
                            :error="report.errors.document"
                            @remove-existing="removeDoc"
                        />
                        <BaseButton
                            v-if="report.document"
                            size="compact"
                            class="mt-3"
                            :loading="report.processing"
                            @click="upload(report)"
                        >Gutachten hochladen</BaseButton>
                    </div>

                    <div>
                        <BaseFileUpload
                            v-model="invoice.document"
                            label="Rechnung an den Kunden"
                            required
                            accept-label="max. 10 MB"
                            :existing="invoiceDoc ? [invoiceDoc] : []"
                            :error="invoice.errors.document"
                            @remove-existing="removeDoc"
                        />
                        <BaseButton
                            v-if="invoice.document"
                            size="compact"
                            class="mt-3"
                            :loading="invoice.processing"
                            @click="upload(invoice)"
                        >Rechnung hochladen</BaseButton>
                    </div>
                </div>

                <div v-else>
                    <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Unterlagen</h2>
                    <ul class="pt-3">
                        <li
                            v-for="doc in documents"
                            :key="doc.id"
                            class="flex items-center gap-3 border-b border-gray-100 py-3 last:border-b-0"
                        >
                            <FileText :size="20" :stroke-width="1.5" class="shrink-0 text-gray-600" aria-hidden="true" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-gray-800">{{ doc.original_name }}</span>
                                <span class="block font-mono text-meta text-gray-400">
                                    {{ doc.size_label }} · {{ doc.type_label }}
                                </span>
                            </span>
                            <a
                                :href="doc.download_url"
                                class="shrink-0 text-gray-600 hover:text-navy-700"
                                :aria-label="`${doc.original_name} herunterladen`"
                            >
                                <Download :size="18" :stroke-width="1.5" aria-hidden="true" />
                            </a>
                        </li>
                    </ul>
                </div>
            </section>
        </div>

        <!--
            The single most important thing a partner does on this screen, and
            for a long time the easiest to miss: it read "Bearbeitung beginnen"
            in a grey panel among five others. It is now the loudest element on
            the page while it is pending, because until it is pressed nothing
            downstream — billing, completion, the customer's expectation — can
            move.
        -->
        <section
            v-if="assignment.can_confirm"
            class="mt-6 rounded-card border-2 border-navy-700 bg-navy-900 p-5 md:p-6"
        >
            <h2 class="text-eyebrow font-semibold uppercase text-accent">Nächster Schritt</h2>
            <p class="pt-2 text-h3 font-bold leading-tight text-white">
                Ist der Auftrag zustande gekommen?
            </p>
            <p class="measure pt-3 text-sm leading-normal text-white/72">
                Sobald der Kunde Sie tatsächlich beauftragt hat, bestätigen Sie das hier. Der Auftrag
                wechselt dann in den Status „In Bearbeitung“ und Sie erhalten unsere Rechnung über die
                DKGZ-Gebühr von {{ dkgzFeeLabel }} per E-Mail.
            </p>

            <BaseButton
                size="cta"
                class="mt-5 w-full sm:w-auto"
                :loading="confirmForm.processing"
                @click="submitConfirmation"
            >
                <Check :size="18" :stroke-width="2" aria-hidden="true" />
                Zustande gekommen
            </BaseButton>

            <p v-if="confirmForm.errors.status" class="pt-3 text-sm text-white">
                {{ confirmForm.errors.status }}
            </p>
        </section>

        <p
            v-else-if="assignment.confirmed_at"
            class="mt-6 flex items-center gap-2.5 rounded-card border border-gray-200 bg-white p-5 text-sm text-gray-800"
        >
            <Check :size="16" :stroke-width="2" class="shrink-0 text-success" aria-hidden="true" />
            Als zustande gekommen bestätigt am {{ stamp(assignment.confirmed_at) }}.
        </p>

        <!-- What the partner billed their own customer or the insurer. -->
        <section class="mt-6 rounded-card border border-gray-200 bg-white p-5">
            <h2 class="text-eyebrow font-semibold uppercase text-gray-600">
                Rechnung an den Kunden / Versicherung
            </h2>
            <p class="measure pt-3 text-sm leading-normal text-gray-600">
                Ihre eigene Rechnung für diesen Auftrag. Die Angaben dienen Ihrer Übersicht und
                haben keinen Einfluss auf die DKGZ-Gebühr.
            </p>

            <div class="grid gap-4 pt-4 sm:grid-cols-2 lg:grid-cols-3">
                <BaseCurrencyInput
                    v-model="customerInvoice.customer_invoice_cents"
                    label="Rechnungsbetrag"
                    :error="customerInvoice.errors.customer_invoice_cents"
                />
                <BaseSelect
                    v-model="customerInvoice.customer_invoice_recipient"
                    label="Empfänger"
                    :options="RECIPIENTS"
                    optional
                    :error="customerInvoice.errors.customer_invoice_recipient"
                />
                <BaseInput
                    v-model="customerInvoice.customer_invoice_number"
                    label="Rechnungsnummer"
                    optional
                    :error="customerInvoice.errors.customer_invoice_number"
                />
            </div>

            <BaseButton
                variant="secondary"
                size="compact"
                class="mt-4"
                :loading="customerInvoice.processing"
                @click="customerInvoice.post(`/portal/auftraege/${assignment.id}/kundenrechnung`, { preserveScroll: true })"
            >Angaben speichern</BaseButton>
        </section>

        <section v-if="assignment.is_open" class="mt-6 rounded-card border border-gray-200 bg-white p-5">
            <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Abschluss</h2>
            <p class="measure pt-3 text-sm leading-normal text-gray-600">
                Sobald das Gutachten fertig ist, schließen Sie den Auftrag hier ab. Ihre Unterlagen
                senden Sie direkt an Ihren Kunden — hochladen müssen Sie sie hier nicht.
            </p>

            <BaseButton
                size="cta"
                class="mt-4 hidden md:inline-flex"
                :disabled="!assignment.can_complete"
                @click="completeOpen = true"
            >Gutachten fertiggestellt</BaseButton>
        </section>

        <!--
            On mobile the action lives in a sticky bar above the tab bar, so it
            stays reachable while the partner scrolls through the uploads.
        -->
        <div
            v-if="assignment.is_open"
            class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white px-4 pb-[calc(env(safe-area-inset-bottom)+0.75rem)] pt-3 md:hidden"
        >
            <BaseButton
                size="cta"
                block
                :disabled="!assignment.can_complete"
                @click="completeOpen = true"
            >Gutachten fertiggestellt</BaseButton>
        </div>

        <section v-if="commission" class="mt-6 rounded-card border border-gray-200 bg-white p-5">
            <h2 class="text-eyebrow font-semibold uppercase text-gray-600">DKGZ-Gebühr</h2>
            <dl class="max-w-lg pt-3">
                <div class="flex items-baseline justify-between gap-4 pt-1">
                    <dt class="text-sm font-medium text-navy-700">DKGZ-Gebühr</dt>
                    <dd><MoneyValue :cents="commission.commission_cents" emphasis /></dd>
                </div>
            </dl>
            <p class="pt-3"><StatusDot :status="commission.status" :label="commission.status_label" /></p>
        </section>

        <!-- Our invoices for this job, inside the job. -->
        <section v-if="dkgzInvoices.length" class="mt-6 rounded-card border border-gray-200 bg-white p-5">
            <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Rechnungen der DKGZ</h2>
            <ul class="pt-3">
                <li
                    v-for="doc in dkgzInvoices"
                    :key="doc.id"
                    class="flex flex-wrap items-center justify-between gap-x-5 gap-y-2 border-b border-gray-100 py-3 last:border-b-0"
                >
                    <span class="flex min-w-0 items-center gap-3">
                        <FileText :size="18" :stroke-width="1.5" class="shrink-0 text-gray-400" aria-hidden="true" />
                        <span class="min-w-0">
                            <span class="block font-mono text-sm text-gray-800">{{ doc.invoice_number }}</span>
                            <span class="block text-xs text-gray-600">{{ doc.issued_at_label }}</span>
                        </span>
                    </span>
                    <span class="flex items-center gap-4">
                        <MoneyValue :cents="doc.amount_cents" />
                        <StatusDot :status="doc.status" :label="doc.status_label" />
                        <BaseButton
                            v-if="doc.download_url"
                            variant="secondary"
                            size="compact"
                            :href="doc.download_url"
                        >
                            <Download :size="16" :stroke-width="1.5" aria-hidden="true" />
                            PDF
                        </BaseButton>
                    </span>
                </li>
            </ul>
        </section>

        <section class="mt-6 rounded-card border border-gray-200 bg-white p-5">
            <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Verlauf</h2>
            <AssignmentTimeline :events="timeline" class="pt-4" />
        </section>

        <CompleteAssignmentDialog
            :open="completeOpen"
            :reference="request.reference"
            :dkgz-fee-label="dkgzFeeLabel"
            :processing="complete.processing"
            @close="completeOpen = false"
            @submit="submitCompletion"
        />
    </PortalLayout>
</template>
