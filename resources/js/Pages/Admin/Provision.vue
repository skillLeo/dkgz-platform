<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { AlertTriangle } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import ReferenceNumber from '../../Components/Data/ReferenceNumber.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({
    commission: { type: Object, required: true },
    assessor: { type: Object, required: true },
    assignment: { type: Object, required: true },
    can: { type: Object, default: () => ({}) },
})

const { percent, dateTime, date } = useGermanFormat()
const { confirm } = useConfirm()

const invoice = useForm({})
const settle = useForm({})
const waive = useForm({ reason: '' })
const waiveOpen = ref(false)

const doInvoice = async () => {
    const ok = await confirm({
        title: 'Abrechnung erzeugen?',
        message: 'Es wird eine fortlaufend nummerierte Rechnung als PDF erstellt und dem Sachverständigen per E-Mail zugestellt.',
        confirmLabel: 'Abrechnung erzeugen',
    })

    if (ok) invoice.post(`/admin/provisionen/${props.commission.id}/rechnung`, { preserveScroll: true })
}

const doSettle = async () => {
    const ok = await confirm({
        title: 'Als beglichen markieren?',
        message: 'Der Zahlungseingang wird vermerkt. Diese Angabe erscheint im Register und beim Partner.',
        confirmLabel: 'Als beglichen markieren',
    })

    if (ok) settle.post(`/admin/provisionen/${props.commission.id}/beglichen`, { preserveScroll: true })
}
</script>

<template>
    <Head title="Provision" />

    <AdminLayout :title="commission.invoice_number ?? 'Provision'" back-href="/admin/provisionen">
        <PageHeader :title="commission.invoice_number ?? 'Provision'" :eyebrow="assessor.company_name">
            <template #actions>
                <StatusDot :status="commission.status" :label="commission.status_label" />
                <BaseButton v-if="can.invoice" size="compact" :loading="invoice.processing" @click="doInvoice">Abrechnung erzeugen</BaseButton>
                <BaseButton v-if="commission.has_invoice" variant="secondary" size="compact" :href="`/admin/provisionen/${commission.id}/rechnung`">PDF herunterladen</BaseButton>
                <BaseButton v-if="can.settle" variant="secondary" size="compact" :loading="settle.processing" @click="doSettle">Beglichen</BaseButton>
                <BaseButton v-if="can.waive" variant="ghost" size="compact" @click="waiveOpen = !waiveOpen">Erlassen</BaseButton>
            </template>
        </PageHeader>

        <div v-if="commission.needs_review" class="mb-6 flex items-start gap-3 border border-warning bg-warning/5 p-4">
            <AlertTriangle :size="18" :stroke-width="1.5" class="mt-0.5 shrink-0 text-warning" aria-hidden="true" />
            <p class="text-sm leading-normal text-gray-800">
                Das erfasste Honorar liegt über der üblichen Spanne. Bitte prüfen Sie den Betrag, bevor Sie abrechnen.
            </p>
        </div>

        <form
            v-if="waiveOpen"
            class="mb-6 border border-gray-200 bg-white p-5"
            @submit.prevent="waive.post(`/admin/provisionen/${commission.id}/erlassen`, { preserveScroll: true, onSuccess: () => (waiveOpen = false) })"
        >
            <SectionLabel text="Provision erlassen" tone="muted" />
            <p class="pb-4 pt-2 text-sm text-gray-600">Der Erlass wird protokolliert. Eine Begründung ist erforderlich.</p>
            <BaseTextarea v-model="waive.reason" label="Begründung" :error="waive.errors.reason" required />
            <div class="flex gap-3 pt-4">
                <BaseButton type="submit" size="compact" :loading="waive.processing">Erlassen</BaseButton>
                <BaseButton type="button" variant="ghost" size="compact" @click="waiveOpen = false">Abbrechen</BaseButton>
            </div>
        </form>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <section class="border border-gray-200 bg-white p-5">
                <SectionLabel text="Berechnung" tone="muted" />
                <table class="w-full border-collapse pt-4">
                    <tbody>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 text-sm text-gray-600">Berechnetes Honorar (netto)</td>
                            <td class="py-3 text-right"><MoneyValue :cents="commission.fee_cents" /></td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 text-sm text-gray-600">Vermittlungsprovision</td>
                            <td class="py-3 text-right font-mono text-sm tabular-nums text-gray-800">{{ percent(commission.rate_percent) }}</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 text-sm text-gray-600">Anteil des Sachverständigen</td>
                            <td class="py-3 text-right"><MoneyValue :cents="commission.assessor_share_cents" muted /></td>
                        </tr>
                        <tr>
                            <td class="border-t-2 border-navy-700 py-3 text-sm font-medium text-navy-700">Provisionsbetrag</td>
                            <td class="border-t-2 border-navy-700 py-3 text-right"><MoneyValue :cents="commission.commission_cents" emphasis /></td>
                        </tr>
                    </tbody>
                </table>
                <p class="pt-4 text-xs leading-normal text-gray-600">
                    Der Satz von {{ percent(commission.rate_percent) }} wurde beim Abschluss festgehalten und bleibt für
                    diese Abrechnung unverändert, auch wenn der Satz später angepasst wird.
                </p>
            </section>

            <div class="flex flex-col gap-6">
                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Vorgang" tone="muted" />
                    <dl class="pt-4">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Referenz</dt><dd><ReferenceNumber :value="assignment.reference ?? '—'" :href="assignment.id ? `/admin/auftraege/${assignment.id}` : null" /></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Art</dt><dd class="text-sm text-gray-800">{{ assignment.service_type }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 py-2.5">
                            <dt class="text-sm text-gray-600">Abgeschlossen</dt><dd class="font-mono text-sm tabular-nums text-gray-800">{{ date(assignment.completed_at) }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Empfänger" tone="muted" />
                    <div class="pt-4 text-sm leading-relaxed text-gray-800">
                        {{ assessor.company_name }}<br>
                        {{ assessor.address }}
                        <span v-if="assessor.vat_id" class="block pt-1 font-mono text-xs text-gray-400">USt-IdNr. {{ assessor.vat_id }}</span>
                    </div>
                </section>

                <section v-if="commission.invoiced_at || commission.settled_at || commission.notes" class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Verlauf" tone="muted" />
                    <dl class="pt-4">
                        <div v-if="commission.invoiced_at" class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Berechnet</dt><dd class="font-mono text-sm tabular-nums text-gray-800">{{ dateTime(commission.invoiced_at) }}</dd>
                        </div>
                        <div v-if="commission.settled_at" class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Beglichen</dt><dd class="font-mono text-sm tabular-nums text-gray-800">{{ dateTime(commission.settled_at) }}</dd>
                        </div>
                        <div v-if="commission.settled_by" class="flex items-baseline justify-between gap-4 py-2.5">
                            <dt class="text-sm text-gray-600">Erfasst von</dt><dd class="text-sm text-gray-800">{{ commission.settled_by }}</dd>
                        </div>
                    </dl>
                    <p v-if="commission.notes" class="pt-3 text-sm leading-normal text-gray-800">{{ commission.notes }}</p>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
