<script setup>
import { computed, ref, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { Upload } from 'lucide-vue-next'
import BaseButton from '../Base/BaseButton.vue'
import BaseCheckbox from '../Base/BaseCheckbox.vue'
import BaseTextarea from '../Base/BaseTextarea.vue'
import FieldError from '../Feedback/FieldError.vue'

/**
 * Bulk invitations from a partner list.
 *
 * Always two steps. A hundred e-mails cannot be recalled, so the operator sees
 * exactly who will be written to — and who is being skipped, and why — before
 * anything leaves. The file itself is never trusted as the instruction; the
 * confirmed list is.
 */
const DEFAULT_MESSAGE = `Carspector erweitert sein Angebot: Mit der Deutschen KFZ-Gutachterzentrale (DKGZ) `
    + `starten wir eine eigene Plattform, über die Gutachtenanfragen direkt an geprüfte Sachverständige `
    + `in der jeweiligen Region vermittelt werden.\n\n`
    + `Als Partner von Carspector laden wir Sie ein, von Beginn an dabei zu sein. Die Vermittlung ist `
    + `kostenfrei; je vermitteltem und abgeschlossenem Auftrag berechnen wir eine feste Gebühr, die Sie `
    + `vor jeder Annahme sehen. Es gibt keine Grundgebühr und keine Kosten für Anfragen, die Sie ablehnen.\n\n`
    + `Über den Link unten richten Sie Ihren Zugang in wenigen Minuten ein.`

const page = usePage()

const fileInput = ref(null)
const upload = useForm({ file: null })
// Existing Carspector partners get the same invitation with a different first
// sentence: they are being told the firm they already work with has opened a
// second line of business, not introduced to a stranger.
const KNOWN_PARTNER_MESSAGE = `Sie arbeiten bereits mit Carspector zusammen — vielen Dank dafür.\n\n`
    + `Mit der Deutschen KFZ-Gutachterzentrale (DKGZ) haben wir einen zweiten Geschäftsbereich `
    + `aufgebaut: eine bundesweite Vermittlung von Gutachtenaufträgen direkt an Sachverständige. `
    + `An der bestehenden Zusammenarbeit ändert sich dadurch nichts — DKGZ kommt als zusätzliche `
    + `Auftragsquelle hinzu.\n\n`
    + `Ihr Zugang ist bereits vorbereitet. Sie müssen nur noch Einsatzgebiet und Leistungen ergänzen.`

const send = useForm({ emails: [], message: DEFAULT_MESSAGE, known_partner: false })

// Switching the audience swaps the wording, unless it has been edited by hand.
watch(() => send.known_partner, (isKnown, wasKnown) => {
    const untouched = send.message === (wasKnown ? KNOWN_PARTNER_MESSAGE : DEFAULT_MESSAGE)

    if (untouched) send.message = isKnown ? KNOWN_PARTNER_MESSAGE : DEFAULT_MESSAGE
})

const preview = computed(() => page.props.flash?.invitationPreview ?? null)

const invitable = computed(() => (preview.value?.rows ?? []).filter((row) => row.status === 'neu'))

const skipped = computed(() => (preview.value?.rows ?? []).filter((row) => row.status !== 'neu'))

const tone = {
    neu: 'text-success',
    vorhanden: 'text-gray-400',
    eingeladen: 'text-gray-400',
    doppelt: 'text-gray-400',
    ungueltig: 'text-danger',
}

const pick = (file) => {
    if (!file) return

    upload.file = file
    upload.post('/admin/einladungen/import-vorschau', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => upload.reset('file'),
    })
}

const dispatchAll = () => {
    send.emails = invitable.value.map((row) => row.email)
    send.post('/admin/einladungen/import', { preserveScroll: true })
}
</script>

<template>
    <section class="mb-6 rounded-card border border-gray-200 bg-white p-6">
        <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Partnerliste importieren</h2>
        <p class="measure pt-2 text-sm leading-normal text-gray-600">
            CSV-Datei mit einer Spalte für die E-Mail-Adresse, optional mit Firmenname. Semikolon und Komma
            werden beide erkannt, eine Kopfzeile ist nicht erforderlich. Sie sehen zuerst eine Vorschau.
        </p>

        <!-- Step one: read the file -->
        <div v-if="!preview" class="pt-4">
            <BaseButton variant="secondary" size="compact" :loading="upload.processing" @click="fileInput?.click()">
                <Upload :size="14" :stroke-width="1.5" aria-hidden="true" />
                CSV-Datei auswählen
            </BaseButton>
            <FieldError :message="upload.errors.file" />

            <input
                ref="fileInput"
                type="file"
                accept=".csv,text/csv,text/plain"
                autocomplete="off"
                class="sr-only"
                @change="pick($event.target.files?.[0])"
            >
        </div>

        <!-- Step two: confirm exactly who is written to -->
        <div v-else class="pt-5">
            <div class="flex flex-wrap gap-x-6 gap-y-2 rounded-sm border border-gray-200 bg-gray-50 px-4 py-3">
                <span class="text-sm text-gray-800">
                    <span class="font-mono tabular-nums text-success">{{ preview.counts.neu }}</span> werden eingeladen
                </span>
                <span v-if="preview.counts.vorhanden" class="text-sm text-gray-600">
                    <span class="font-mono tabular-nums">{{ preview.counts.vorhanden }}</span> haben bereits einen Zugang
                </span>
                <span v-if="preview.counts.eingeladen" class="text-sm text-gray-600">
                    <span class="font-mono tabular-nums">{{ preview.counts.eingeladen }}</span> bereits eingeladen
                </span>
                <span v-if="preview.counts.doppelt" class="text-sm text-gray-600">
                    <span class="font-mono tabular-nums">{{ preview.counts.doppelt }}</span> doppelt in der Datei
                </span>
                <span v-if="preview.counts.ungueltig" class="text-sm text-danger">
                    <span class="font-mono tabular-nums">{{ preview.counts.ungueltig }}</span> ungültig
                </span>
            </div>

            <p v-if="preview.truncated" class="pt-2 text-sm text-warning">
                Die Datei enthält sehr viele Zeilen. Es wurden die ersten 2000 gelesen.
            </p>

            <div class="max-h-80 overflow-y-auto pt-4">
                <table class="w-full">
                    <thead class="sticky top-0 bg-white">
                        <tr class="border-b border-gray-200">
                            <th class="px-3 py-2 text-left text-eyebrow font-semibold uppercase text-gray-600">Adresse</th>
                            <th class="px-3 py-2 text-left text-eyebrow font-semibold uppercase text-gray-600">Name</th>
                            <th class="px-3 py-2 text-left text-eyebrow font-semibold uppercase text-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in [...invitable, ...skipped]"
                            :key="`${row.line}-${row.email}`"
                            class="border-b border-gray-100 last:border-b-0"
                        >
                            <td class="px-3 py-2 font-mono text-meta text-gray-800">{{ row.email }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ row.name ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm" :class="tone[row.status]">
                                {{ row.status === 'neu' ? 'Wird eingeladen' : row.reason }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="rounded-sm border border-gray-200 bg-gray-50 p-4 mt-5">
                <BaseCheckbox v-model="send.known_partner">
                    Diese Liste enthält bestehende Carspector-Partner
                </BaseCheckbox>
                <p class="measure pl-7 pt-1.5 text-sm leading-normal text-gray-600">
                    Dann wird die Einladung als Ankündigung eines zweiten Geschäftsbereichs
                    formuliert statt als Vorstellung eines unbekannten Anbieters — und die
                    Nachricht unten wird entsprechend vorbelegt.
                </p>
            </div>

            <BaseTextarea
                v-model="send.message"
                label="Nachricht an alle Eingeladenen"
                hint="Erscheint in jeder Einladungs-E-Mail über dem Zugangslink."
                :rows="8"
                :error="send.errors.message"
                class="pt-4"
            />

            <div class="flex flex-wrap items-center gap-3 pt-5">
                <BaseButton
                    size="cta"
                    :disabled="!invitable.length || send.processing"
                    :loading="send.processing"
                    @click="dispatchAll"
                >
                    {{ invitable.length }} Einladung(en) versenden
                </BaseButton>
                <BaseButton variant="ghost" size="compact" @click="$inertia.reload({ only: [] })">
                    Andere Datei wählen
                </BaseButton>
            </div>

            <p class="measure pt-3 text-sm leading-normal text-gray-600">
                Der Versand läuft gestaffelt, damit der Mailserver die Menge annimmt. Bei größeren Listen dauert
                es entsprechend einige Minuten, bis alle Nachrichten draußen sind.
            </p>
        </div>
    </section>
</template>
