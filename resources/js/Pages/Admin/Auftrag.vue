<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Download } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import ReferenceNumber from '../../Components/Data/ReferenceNumber.vue'
import AssignmentTimeline from '../../Components/Domain/AssignmentTimeline.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    assignment: { type: Object, required: true },
    assessor: { type: Object, required: true },
    request: { type: Object, default: null },
    documents: { type: Array, default: () => [] },
    timeline: { type: Array, default: () => [] },
    commission: { type: Object, default: null },
    review: { type: Object, default: null },
    can: { type: Object, default: () => ({}) },
})

const { dateTime } = useGermanFormat()
const cancel = useForm({ reason: '' })
const cancelOpen = ref(false)
</script>

<template>
    <Head :title="request?.reference ?? 'Auftrag'" />

    <AdminLayout :title="request?.reference ?? 'Auftrag'" back-href="/admin/auftraege">
        <PageHeader :title="request?.reference ?? 'Auftrag'" :eyebrow="assessor.company_name">
            <template #actions>
                <StatusDot :status="assignment.status" :label="assignment.status_label" />
                <BaseButton v-if="can.cancel" variant="ghost" size="compact" @click="cancelOpen = !cancelOpen">Stornieren</BaseButton>
            </template>
        </PageHeader>

        <form
            v-if="cancelOpen"
            class="mb-6 border border-danger bg-danger/4 p-5"
            @submit.prevent="cancel.post(`/admin/auftraege/${assignment.id}/stornieren`, { preserveScroll: true, onSuccess: () => (cancelOpen = false) })"
        >
            <SectionLabel text="Auftrag stornieren" tone="muted" />
            <div class="pt-4">
                <BaseTextarea v-model="cancel.reason" label="Begründung" :error="cancel.errors.reason" required />
            </div>
            <div class="flex gap-3 pt-4">
                <BaseButton type="submit" variant="danger" size="compact" :loading="cancel.processing">Stornieren</BaseButton>
                <BaseButton type="button" variant="ghost" size="compact" @click="cancelOpen = false">Abbrechen</BaseButton>
            </div>
        </form>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="flex flex-col gap-6">
                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Verlauf" tone="muted" />
                    <AssignmentTimeline :events="timeline" class="pt-4" />
                </section>

                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Unterlagen" tone="muted" />
                    <ul v-if="documents.length" class="pt-4">
                        <li v-for="doc in documents" :key="doc.id" class="flex items-center justify-between gap-4 border-b border-gray-100 py-3 last:border-b-0">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-800">{{ doc.original_name }}</p>
                                <p class="font-mono text-xs text-gray-400">{{ doc.type_label }} · {{ doc.size_label }} · {{ dateTime(doc.uploaded_at) }}</p>
                            </div>
                            <a :href="doc.download_url" class="flex shrink-0 items-center gap-2 text-sm font-medium text-navy-700 hover:text-navy-500">
                                <Download :size="16" :stroke-width="1.5" aria-hidden="true" />
                                Herunterladen
                            </a>
                        </li>
                    </ul>
                    <p v-else class="pt-4 text-sm text-gray-400">Noch keine Unterlagen hinterlegt.</p>
                </section>

                <section v-if="review" class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Bewertung" tone="muted" />
                    <p class="pt-4 font-mono text-h3 tabular-nums text-navy-700">{{ review.rating }} <span class="text-base text-gray-400">von 10</span></p>
                    <p v-if="review.feedback_category" class="pt-2 text-sm text-gray-600">{{ review.feedback_category }}</p>
                    <p v-if="review.feedback" class="measure pt-2 text-sm leading-normal text-gray-800">{{ review.feedback }}</p>
                </section>
            </div>

            <div class="flex flex-col gap-6">
                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Sachverständiger" tone="muted" />
                    <dl class="pt-4">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Büro</dt><dd class="text-sm text-gray-800">{{ assessor.company_name }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Kontakt</dt><dd class="text-sm text-gray-800">{{ assessor.contact }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 py-2.5">
                            <dt class="text-sm text-gray-600">Telefon</dt><dd class="font-mono text-sm tabular-nums text-gray-800">{{ assessor.phone }}</dd>
                        </div>
                    </dl>
                    <BaseButton variant="secondary" size="compact" class="mt-4" :href="`/admin/sachverstaendige/${assessor.id}`">Profil öffnen</BaseButton>
                </section>

                <section v-if="request" class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Anfrage" tone="muted" />
                    <dl class="pt-4">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Referenz</dt><dd><ReferenceNumber :value="request.reference" :href="`/admin/anfragen/${request.id}`" /></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Kunde</dt><dd class="text-sm text-gray-800">{{ request.customer_name }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 py-2.5">
                            <dt class="text-sm text-gray-600">Fahrzeug</dt><dd class="text-sm text-gray-800">{{ request.vehicle }}</dd>
                        </div>
                    </dl>
                </section>

                <section v-if="commission" class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Provision" tone="muted" />
                    <dl class="pt-4">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Honorar</dt><dd><MoneyValue :cents="assignment.fee_cents" /></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 py-2.5">
                            <dt class="text-sm text-gray-600">Provision</dt><dd><MoneyValue :cents="commission.commission_cents" emphasis /></dd>
                        </div>
                    </dl>
                    <BaseButton variant="secondary" size="compact" class="mt-4" :href="`/admin/provisionen/${commission.id}`">Abrechnung öffnen</BaseButton>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
