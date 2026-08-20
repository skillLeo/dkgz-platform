<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ChevronLeft, Download, FileText, Mail, Phone, Upload } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import StatusRail from '../../Components/Data/StatusRail.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import AssignmentTimeline from '../../Components/Domain/AssignmentTimeline.vue'
import CompleteAssignmentDialog from '../../Components/Domain/CompleteAssignmentDialog.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseFileUpload from '../../Components/Base/BaseFileUpload.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({
    assignment: { type: Object, required: true },
    request: { type: Object, required: true },
    documents: { type: Array, default: () => [] },
    timeline: { type: Array, default: () => [] },
    commission: { type: Object, default: null },
    dkgzFeeLabel: { type: String, default: null },
    feeBounds: { type: Object, default: () => ({}) },
})

const { stamp } = useGermanFormat()
const { confirm } = useConfirm()

const start = useForm({ status: 'in_progress', note: '' })
const report = useForm({ type: 'report', document: null })
const invoice = useForm({ type: 'customer_invoice', document: null })
const complete = useForm({ fee_cents: null, notes: '' })
const completeOpen = ref(false)

const reportDoc = computed(() => props.documents.find((d) => d.type === 'report'))
const invoiceDoc = computed(() => props.documents.find((d) => d.type === 'customer_invoice'))
const documentCount = computed(() => [reportDoc.value, invoiceDoc.value].filter(Boolean).length)

/** What is still missing, named exactly — "fast fertig" helps nobody. */
const missingLabel = computed(() => {
    if (!reportDoc.value && !invoiceDoc.value) return 'Gutachten und Rechnung fehlen noch'
    if (!reportDoc.value) return 'Gutachten fehlt noch'
    if (!invoiceDoc.value) return 'Rechnung fehlt noch'

    return null
})

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

        <section v-if="assignment.status === 'accepted'" class="mt-6 rounded-card border border-gray-200 bg-white p-5">
            <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Bearbeitung</h2>
            <p class="pt-3 text-sm leading-normal text-gray-600">
                Markieren Sie den Auftrag als in Bearbeitung, sobald Sie begonnen haben.
            </p>
            <BaseButton
                size="compact"
                class="mt-4"
                :loading="start.processing"
                @click="start.post(`/portal/auftraege/${assignment.id}/status`, { preserveScroll: true })"
            >Bearbeitung beginnen</BaseButton>
        </section>

        <section v-if="assignment.is_open" class="mt-6 rounded-card border border-gray-200 bg-white p-5">
            <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Abschluss</h2>
            <p class="measure pt-3 text-sm leading-normal text-gray-600">
                Nach dem Hochladen beider Dokumente erfassen Sie das tatsächlich berechnete Honorar.
            </p>

            <p v-if="missingLabel" class="flex items-center gap-2.5 pt-4">
                <Upload :size="16" :stroke-width="1.5" class="shrink-0 text-warning" aria-hidden="true" />
                <span class="text-sm text-warning">{{ missingLabel }}</span>
            </p>

            <BaseButton
                size="cta"
                class="mt-4 hidden md:inline-flex"
                :disabled="!assignment.can_complete"
                @click="completeOpen = true"
            >Auftrag abschließen</BaseButton>
        </section>

        <!--
            On mobile the action lives in a sticky bar above the tab bar, so it
            stays reachable while the partner scrolls through the uploads.
        -->
        <div
            v-if="assignment.is_open"
            class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white px-4 pb-[calc(env(safe-area-inset-bottom)+0.75rem)] pt-3 md:hidden"
        >
            <p v-if="missingLabel" class="pb-2 text-center text-sm text-warning">{{ missingLabel }}</p>
            <BaseButton
                size="cta"
                block
                :disabled="!assignment.can_complete"
                @click="completeOpen = true"
            >Auftrag abschließen</BaseButton>
        </div>

        <section v-if="commission" class="mt-6 rounded-card border border-gray-200 bg-white p-5">
            <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Provision</h2>
            <dl class="max-w-lg pt-3">
                <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                    <dt class="text-sm text-gray-600">Honorar netto</dt>
                    <dd><MoneyValue :cents="commission.fee_cents" /></dd>
                </div>
                <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                    <dt class="text-sm text-gray-600">DKGZ-Gebühr</dt>
                    <dd><MoneyValue :cents="commission.commission_cents" /></dd>
                </div>
                <div class="flex items-baseline justify-between gap-4 pt-3">
                    <dt class="text-sm font-medium text-navy-700">Verbleibt bei Ihnen</dt>
                    <dd><MoneyValue :cents="commission.assessor_share_cents" emphasis /></dd>
                </div>
            </dl>
            <p class="pt-3"><StatusDot :status="commission.status" :label="commission.status_label" /></p>
        </section>

        <section class="mt-6 rounded-card border border-gray-200 bg-white p-5">
            <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Verlauf</h2>
            <AssignmentTimeline :events="timeline" class="pt-4" />
        </section>

        <CompleteAssignmentDialog
            v-model="complete.fee_cents"
            :open="completeOpen"
            :reference="request.reference"
            :dkgz-fee-label="dkgzFeeLabel"
            :error="complete.errors.fee_cents"
            :processing="complete.processing"
            @close="completeOpen = false"
            @submit="submitCompletion"
        />
    </PortalLayout>
</template>
