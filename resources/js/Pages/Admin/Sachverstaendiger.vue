<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Download, FileText } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
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

/**
 * What the public directory says about this partner, and whether it says
 * anything at all. Kept apart from the internal notes above it, which are the
 * office's and appear nowhere public.
 */
const listing = useForm({
    is_listed: props.assessor.is_listed,
    public_profile: props.assessor.public_profile ?? '',
    internal_notes: props.assessor.internal_notes ?? '',
})

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
                            <dt class="text-sm text-gray-600">Partner-ID</dt><dd class="font-mono text-sm text-gray-800">{{ assessor.partner_id }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                            <dt class="text-sm text-gray-600">Annahmequote</dt>
                            <dd class="font-mono text-sm tabular-nums text-gray-800">
                                <template v-if="assessor.acceptance_rate === null">noch keine Anfragen</template>
                                <template v-else>{{ assessor.acceptance_rate }} %</template>
                            </dd>
                        </div>
                    </dl>

                    <div
                        v-if="assessor.cover_has_lapsed"
                        class="mt-4 rounded-sm border border-danger bg-danger/5 p-3"
                        role="status"
                    >
                        <p class="text-sm font-medium text-danger">Haftpflichtnachweis abgelaufen</p>
                        <p class="pt-1 text-sm leading-normal text-gray-800">
                            Dieser Partner erhält keine neuen Anfragen mehr, bis ein gültiger Nachweis vorliegt.
                            Laufende Aufträge bleiben bestehen.
                        </p>
                    </div>

                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <p class="text-eyebrow font-semibold uppercase text-gray-600">Nachweise</p>

                        <p v-if="!assessor.documents.length" class="pt-2 text-sm text-warning">
                            Es wurden keine Nachweise eingereicht.
                        </p>

                        <ul v-else class="pt-2">
                            <li
                                v-for="doc in assessor.documents"
                                :key="doc.id"
                                class="flex items-center gap-3 border-b border-gray-100 py-2.5 last:border-b-0"
                            >
                                <FileText :size="18" :stroke-width="1.5" class="shrink-0 text-gray-600" aria-hidden="true" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm text-gray-800">{{ doc.type_label }}</span>
                                    <span class="block truncate font-mono text-meta text-gray-400">
                                        {{ doc.original_name }} · {{ doc.size_label }}<template v-if="doc.valid_until_label"> · {{ doc.valid_until_label }}</template>
                                    </span>
                                </span>
                                <span
                                    class="shrink-0 text-xs"
                                    :class="{
                                        'text-danger': doc.state === 'lapsed',
                                        'text-warning': doc.state === 'expiring',
                                        'text-success': doc.state === 'ok',
                                    }"
                                >{{ doc.state_label }}</span>
                                <a
                                    :href="doc.download_url"
                                    class="shrink-0 text-gray-600 hover:text-navy-700"
                                    :aria-label="`${doc.type_label} herunterladen`"
                                >
                                    <Download :size="18" :stroke-width="1.5" aria-hidden="true" />
                                </a>
                            </li>
                        </ul>
                    </div>
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

                            <!--
                                Signed up for, but the platform no longer offers
                                it. Shown apart rather than mixed in, so nobody
                                reads a retired service as one this partner is
                                being sent work for — and kept visible, because
                                turning the service back on restores it.
                            -->
                            <li
                                v-for="type in assessor.retired_service_types"
                                :key="`retired-${type}`"
                                class="rounded-sm border border-dashed border-gray-300 px-2.5 py-1 text-sm text-gray-400"
                                title="Diese Leistung ist deaktiviert und wird nicht vermittelt."
                            >{{ type }} · deaktiviert</li>
                        </ul>
                    </div>
                </section>

                <form class="border border-gray-200 bg-white p-5" @submit.prevent="notes.post(`/admin/sachverstaendige/${assessor.id}`, { preserveScroll: true })">
                    <SectionLabel text="Interne Notiz" tone="muted" />
                    <p class="pb-3 pt-2 text-sm text-gray-600">Nur intern sichtbar. Der Sachverständige sieht diese Notiz nie.</p>
                    <BaseTextarea v-model="notes.internal_notes" label="Notiz" :rows="4" optional :error="notes.errors.internal_notes" />
                    <BaseButton type="submit" size="compact" class="mt-4" :loading="notes.processing">Notiz speichern</BaseButton>
                </form>

                <!--
                    The public page. A partner signed up before the directory
                    existed, so this is the switch that says whether they appear
                    in it — and the text below is theirs, not the notes above.
                -->
                <form class="border border-gray-200 bg-white p-5" @submit.prevent="listing.post(`/admin/sachverstaendige/${assessor.id}`, { preserveScroll: true })">
                    <SectionLabel text="Öffentliches Profil" tone="muted" />
                    <p class="pb-4 pt-2 text-sm text-gray-600">
                        Erscheint im Verzeichnis unter dkgz.de/sachverstaendige. Kontaktdaten werden dort
                        nie angezeigt — Anfragen laufen ausschließlich über DKGZ.
                    </p>

                    <BaseToggle
                        v-model="listing.is_listed"
                        label="Im Verzeichnis anzeigen"
                        on-label="Sichtbar"
                        off-label="Nicht gelistet"
                    />

                    <BaseTextarea
                        v-model="listing.public_profile"
                        label="Kurzprofil"
                        :rows="4"
                        class="mt-4"
                        hint="Ein paar Sätze zur Vorstellung. Öffentlich sichtbar."
                        :error="listing.errors.public_profile"
                        optional
                    />

                    <div class="flex flex-wrap items-center gap-4 pt-4">
                        <BaseButton type="submit" size="compact" :loading="listing.processing">Profil speichern</BaseButton>
                        <a
                            v-if="assessor.directory_url && assessor.is_listed"
                            :href="assessor.directory_url"
                            target="_blank"
                            rel="noopener"
                            class="text-sm text-gray-600 underline underline-offset-2 hover:text-navy-700"
                        >Profil ansehen</a>
                    </div>
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
