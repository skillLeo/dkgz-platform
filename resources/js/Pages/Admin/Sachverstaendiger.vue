<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({
    assessor: { type: Object, required: true },
    can: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
})

const { date, dateTime } = useGermanFormat()
const { confirm } = useConfirm()

const approve = useForm({})
const reject = useForm({ reason: '' })
const suspend = useForm({ reason: '' })
const unsuspend = useForm({})
const notes = useForm({ internal_notes: props.assessor.internal_notes ?? '' })

const rejectOpen = ref(false)
const suspendOpen = ref(false)

const doApprove = async () => {
    const ok = await confirm({
        title: 'Sachverständigen freigeben?',
        message: `${props.assessor.company_name} erhält ab sofort passende Anfragen aus dem hinterlegten Einsatzgebiet.`,
        confirmLabel: 'Freigeben',
    })

    if (ok) approve.post(`/admin/sachverstaendige/${props.assessor.id}/freigeben`, { preserveScroll: true })
}

const doUnsuspend = async () => {
    const ok = await confirm({
        title: 'Sperre aufheben?',
        message: 'Der Partner erhält wieder Zugang zum Portal.',
        confirmLabel: 'Sperre aufheben',
    })

    if (ok) unsuspend.post(`/admin/sachverstaendige/${props.assessor.id}/entsperren`, { preserveScroll: true })
}
</script>

<template>
    <Head :title="assessor.company_name" />

    <AdminLayout :title="assessor.company_name" back-href="/admin/sachverstaendige">
        <PageHeader :title="assessor.company_name" :eyebrow="assessor.address">
            <template #actions>
                <StatusDot :status="assessor.approval_status" />
                <BaseButton v-if="can.approve" size="compact" :loading="approve.processing" @click="doApprove">Freigeben</BaseButton>
                <BaseButton v-if="can.reject" variant="secondary" size="compact" @click="rejectOpen = !rejectOpen">Ablehnen</BaseButton>
                <BaseButton v-if="can.suspend" variant="secondary" size="compact" @click="suspendOpen = !suspendOpen">Sperren</BaseButton>
                <BaseButton v-if="can.unsuspend" size="compact" :loading="unsuspend.processing" @click="doUnsuspend">Sperre aufheben</BaseButton>
            </template>
        </PageHeader>

        <!-- Reject: a reason is mandatory, because the partner is told why. -->
        <form
            v-if="rejectOpen"
            class="mb-6 border border-danger bg-danger/4 p-5"
            @submit.prevent="reject.post(`/admin/sachverstaendige/${assessor.id}/ablehnen`, { preserveScroll: true, onSuccess: () => (rejectOpen = false) })"
        >
            <SectionLabel text="Registrierung ablehnen" tone="muted" />
            <div class="pt-4">
                <BaseTextarea
                    v-model="reject.reason"
                    label="Begründung"
                    hint="Diese Begründung erhält der Sachverständige per E-Mail."
                    :error="reject.errors.reason"
                    required
                />
            </div>
            <div class="flex gap-3 pt-4">
                <BaseButton type="submit" variant="danger" size="compact" :loading="reject.processing">Ablehnen</BaseButton>
                <BaseButton type="button" variant="ghost" size="compact" @click="rejectOpen = false">Abbrechen</BaseButton>
            </div>
        </form>

        <form
            v-if="suspendOpen"
            class="mb-6 border border-danger bg-danger/4 p-5"
            @submit.prevent="suspend.post(`/admin/sachverstaendige/${assessor.id}/sperren`, { preserveScroll: true, onSuccess: () => (suspendOpen = false) })"
        >
            <SectionLabel text="Zugang sperren" tone="muted" />
            <div class="pt-4">
                <BaseTextarea
                    v-model="suspend.reason"
                    label="Begründung"
                    hint="Der Partner erhält keine weiteren Anfragen und wird per E-Mail informiert."
                    :error="suspend.errors.reason"
                    required
                />
            </div>
            <div class="flex gap-3 pt-4">
                <BaseButton type="submit" variant="danger" size="compact" :loading="suspend.processing">Sperren</BaseButton>
                <BaseButton type="button" variant="ghost" size="compact" @click="suspendOpen = false">Abbrechen</BaseButton>
            </div>
        </form>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="flex flex-col gap-6">
                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Unternehmen" tone="muted" />
                    <dl class="grid grid-cols-1 gap-x-8 pt-4 sm:grid-cols-2">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Rechtsform</dt><dd class="text-sm text-gray-800">{{ assessor.legal_form }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">USt-IdNr.</dt><dd class="font-mono text-sm text-gray-800">{{ assessor.vat_id ?? '—' }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Zertifizierung</dt><dd class="text-sm text-gray-800">{{ assessor.certification_body }} · {{ assessor.certification_number }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Gültig bis</dt><dd class="font-mono text-sm tabular-nums text-gray-800">{{ assessor.certification_valid_until ? date(assessor.certification_valid_until) : '—' }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Berufserfahrung</dt><dd class="font-mono text-sm tabular-nums text-gray-800">{{ assessor.years_experience ?? '—' }} Jahre</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Nachweis</dt>
                            <dd class="text-sm" :class="assessor.has_document ? 'text-success' : 'text-warning'">
                                {{ assessor.has_document ? 'hinterlegt' : 'fehlt' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Einsatzgebiet und Leistungen" tone="muted" />
                    <div class="pt-4">
                        <p class="text-eyebrow font-semibold uppercase text-gray-400">Gebiete</p>
                        <ul class="pt-2">
                            <li v-for="area in assessor.service_areas" :key="area.id" class="border-b border-gray-100 py-2 font-mono text-sm tabular-nums text-gray-800 last:border-b-0">
                                {{ area.range }}
                            </li>
                            <li v-if="!assessor.service_areas.length" class="py-2 text-sm text-gray-400">Noch kein Gebiet hinterlegt.</li>
                        </ul>
                    </div>
                    <div class="pt-5">
                        <p class="text-eyebrow font-semibold uppercase text-gray-400">Leistungen</p>
                        <ul class="flex flex-wrap gap-2 pt-2">
                            <li v-for="type in assessor.service_types" :key="type" class="rounded-sm border border-gray-200 px-2.5 py-1 text-sm text-gray-800">{{ type }}</li>
                            <li v-if="!assessor.service_types.length" class="py-1 text-sm text-gray-400">Noch keine Leistung hinterlegt.</li>
                        </ul>
                    </div>
                </section>

                <form class="border border-gray-200 bg-white p-5" @submit.prevent="notes.post(`/admin/sachverstaendige/${assessor.id}`, { preserveScroll: true })">
                    <SectionLabel text="Interne Notiz" tone="muted" />
                    <p class="pb-3 pt-2 text-sm text-gray-600">Nur intern sichtbar. Der Sachverständige sieht diese Notiz nie.</p>
                    <BaseTextarea v-model="notes.internal_notes" label="Notiz" :rows="4" optional :error="notes.errors.internal_notes" />
                    <BaseButton type="submit" size="compact" class="mt-4" :loading="notes.processing">Notiz speichern</BaseButton>
                </form>
            </div>

            <div class="flex flex-col gap-6">
                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Ansprechpartner" tone="muted" />
                    <dl class="pt-4">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Name</dt><dd class="text-sm text-gray-800">{{ assessor.contact.name }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">E-Mail</dt><dd class="truncate text-sm text-gray-800">{{ assessor.contact.email }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Telefon</dt><dd class="font-mono text-sm tabular-nums text-gray-800">{{ assessor.contact.phone }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 py-2.5">
                            <dt class="text-sm text-gray-600">Letzte Anmeldung</dt>
                            <dd class="font-mono text-sm tabular-nums text-gray-800">{{ assessor.contact.last_login_at ? dateTime(assessor.contact.last_login_at) : 'nie' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="border border-gray-200 bg-white p-5">
                    <SectionLabel text="Bilanz" tone="muted" />
                    <dl class="pt-4">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Aufträge</dt><dd class="font-mono text-sm tabular-nums text-gray-800">{{ stats.assignments }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Abgeschlossen</dt><dd class="font-mono text-sm tabular-nums text-gray-800">{{ stats.completed }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 py-2.5">
                            <dt class="text-sm text-gray-600">Provision gesamt</dt><dd><MoneyValue :cents="stats.commission_cents" /></dd>
                        </div>
                    </dl>
                </section>

                <section v-if="assessor.rejection_reason || assessor.suspension_reason" class="border border-danger bg-danger/4 p-5">
                    <SectionLabel :text="assessor.rejection_reason ? 'Ablehnungsgrund' : 'Sperrgrund'" tone="muted" />
                    <p class="pt-3 text-sm leading-normal text-gray-800">{{ assessor.rejection_reason ?? assessor.suspension_reason }}</p>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
