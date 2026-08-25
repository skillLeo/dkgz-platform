<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Wrench } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseCurrencyInput from '../../Components/Base/BaseCurrencyInput.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import ServiceIcon from '../../Components/Domain/ServiceIcon.vue'
import { ICON_CHOICES } from '../../Support/serviceIcons.js'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import EmptyState from '../../Components/Feedback/EmptyState.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({ serviceTypes: { type: Array, default: () => [] } })

/**
 * The article that goes in front of this service's name.
 *
 * German picks the word before a noun by that noun's gender, so the same
 * sentence gives "zum Unfallgutachten" and "zur Beweissicherung". The page copy
 * is written once for every service, which is where that bites. It is guessed
 * from the name — nearly everything here ends in "gutachten" — and this is only
 * for saying otherwise.
 */
const GENDERS = [
    { value: 'm', label: 'der (männlich)' },
    { value: 'f', label: 'die (weiblich)' },
    { value: 'n', label: 'das (sächlich)' },
]

const { confirm } = useConfirm()
const createOpen = ref(false)
const editing = ref(null)

const create = useForm({ name_de: '', gender: '', description_de: '', icon: '', faqs: [], is_active: true, dkgz_fee_cents: null })
const edit = useForm({ name_de: '', gender: '', description_de: '', icon: '', faqs: [], is_active: true, dkgz_fee_cents: null, includes_de: '', target_audience_de: '', typical_situations_de: '', differences_de: '', additional_info_de: '' })

const startEdit = (type) => {
    editing.value = type.id
    edit.name_de = type.name_de
    edit.gender = type.gender ?? ''
    edit.description_de = type.description_de ?? ''
    edit.icon = type.icon ?? ''
    edit.faqs = (type.faqs ?? []).map((entry) => ({ ...entry }))
    edit.is_active = type.is_active
    edit.dkgz_fee_cents = type.dkgz_fee_cents
    edit.includes_de = type.includes_de ?? ''
    edit.target_audience_de = type.target_audience_de ?? ''
    edit.typical_situations_de = type.typical_situations_de ?? ''
    edit.differences_de = type.differences_de ?? ''
    edit.additional_info_de = type.additional_info_de ?? ''
}

const remove = async (type) => {
    const ok = await confirm({
        title: `„${type.name_de}“ löschen?`,
        message: 'Eine Leistungsart lässt sich nur löschen, solange keine Anfrage sie verwendet — sonst verlöre die Historie ihre Bedeutung.',
        confirmLabel: 'Löschen',
        tone: 'danger',
    })

    if (ok) useForm({}).delete(`/admin/leistungsarten/${type.id}`, { preserveScroll: true })
}
</script>

<template>
    <Head title="Leistungsarten" />

    <AdminLayout title="Leistungsarten">
        <PageHeader title="Leistungsarten" description="Welche Gutachten über die Plattform angefragt werden können.">
            <template #actions>
                <BaseButton size="compact" @click="createOpen = !createOpen">Leistungsart anlegen</BaseButton>
            </template>
        </PageHeader>

        <form v-if="createOpen" class="mb-6 border border-gray-200 bg-white p-6" novalidate @submit.prevent="create.post('/admin/leistungsarten', { preserveScroll: true, onSuccess: () => { createOpen = false; create.reset() } })">
            <ErrorSummary v-if="create.hasErrors" :errors="create.errors" class="mb-5" />
            <div class="flex flex-col gap-5">
                <BaseInput v-model="create.name_de" label="Name" :error="create.errors.name_de" required />
                <BaseSelect
                    v-model="create.gender"
                    label="Artikel"
                    placeholder="Automatisch aus dem Namen"
                    empty-allowed
                    :options="GENDERS"
                    hint="Bestimmt, ob die Seitentexte „zum Unfallgutachten“ oder „zur Beweissicherung“ schreiben."
                    :error="create.errors.gender"
                    optional
                />
                <BaseTextarea v-model="create.description_de" label="Beschreibung" hint="Erscheint auf der öffentlichen Leistungsseite." :error="create.errors.description_de" optional />
                <BaseInput v-model="create.icon" label="Symbol" hint="Name eines lucide-Symbols, z. B. file-text." optional />
                <BaseCurrencyInput
                    v-model="create.dkgz_fee_cents"
                    label="DKGZ-Gebühr (netto)"
                    hint="Fester Betrag, den DKGZ je vermitteltem Auftrag dieser Art berechnet."
                    :error="create.errors.dkgz_fee_cents"
                    required
                />
            </div>
            <div class="flex gap-3 pt-5">
                <BaseButton type="submit" size="compact" :loading="create.processing">Anlegen</BaseButton>
                <BaseButton type="button" variant="ghost" size="compact" @click="createOpen = false">Abbrechen</BaseButton>
            </div>
        </form>

        <EmptyState v-if="!serviceTypes.length" title="Keine Leistungsarten" :icon="Wrench" />

        <div v-else class="flex flex-col gap-3">
            <section v-for="type in serviceTypes" :key="type.id" class="border border-gray-200 bg-white">
                <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-base font-medium text-navy-700">{{ type.name_de }}</h2>
                            <StatusDot :status="type.is_active ? 'approved' : 'closed'" :label="type.is_active ? 'Aktiv' : 'Inaktiv'" />
                        </div>
                        <p v-if="type.description_de" class="measure pt-1.5 text-sm leading-normal text-gray-600">{{ type.description_de }}</p>
                        <p class="pt-2 font-mono text-xs text-gray-400">
                            {{ type.artikel }} {{ type.name_de }} · {{ type.slug }} · {{ type.requests_count }} Anfragen · {{ type.assessors_count }} Partner
                        </p>
                        <p class="pt-2 text-sm">
                            <span class="text-gray-600">DKGZ-Gebühr</span>
                            <span v-if="type.dkgz_fee_label" class="font-mono tabular-nums text-navy-700">
                                {{ type.dkgz_fee_label }}
                            </span>
                            <span v-else class="text-danger">noch nicht festgelegt</span>
                        </p>
                        <p v-if="type.content_is_placeholder" class="pt-1 text-xs text-warning">
                            Seitentexte noch nicht überprüft
                        </p>
                        <p v-if="type.fee_missing" class="pt-1 text-xs leading-normal text-danger">
                            Diese Leistung ist aktiv, hat aber keine Gebühr — abgeschlossene Aufträge würden
                            0,00 € buchen.
                        </p>
                    </div>
                    <div class="flex shrink-0 gap-3">
                        <BaseButton variant="secondary" size="small" @click="startEdit(type)">Bearbeiten</BaseButton>
                        <BaseButton v-if="type.can_delete" variant="ghost" size="small" @click="remove(type)">Löschen</BaseButton>
                    </div>
                </div>

                <form v-if="editing === type.id" class="border-t border-gray-200 p-5" novalidate @submit.prevent="edit.post(`/admin/leistungsarten/${type.id}`, { preserveScroll: true, onSuccess: () => (editing = null) })">
                    <div class="flex flex-col gap-5">
                        <BaseInput v-model="edit.name_de" label="Name" :error="edit.errors.name_de" required />
                        <BaseSelect
                            v-model="edit.gender"
                            label="Artikel"
                            placeholder="Automatisch aus dem Namen"
                            empty-allowed
                            :options="GENDERS"
                            :hint="`Zurzeit: „${type.artikel} ${type.name_de}“. Bestimmt, ob die Seitentexte „zum“ oder „zur“ schreiben.`"
                            :error="edit.errors.gender"
                            optional
                        />
                        <BaseTextarea v-model="edit.description_de" label="Beschreibung" :error="edit.errors.description_de" optional />
                        <!--
                            A grid of the actual marks rather than a text field
                            expecting a name nobody can be expected to know. The
                            icon is stored on the service, so it survives a
                            rename — the old map was keyed by the address, which
                            changes with the name.
                        -->
                        <div>
                            <p class="pb-2 text-sm font-medium text-gray-800">Symbol</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="choice in ICON_CHOICES"
                                    :key="choice.value"
                                    type="button"
                                    class="grid h-11 w-11 place-items-center rounded-sm border transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                                    :class="edit.icon === choice.value
                                        ? 'border-navy-700 bg-navy-100 text-navy-700'
                                        : 'border-gray-200 bg-white text-gray-600 hover:border-navy-500'"
                                    :aria-pressed="edit.icon === choice.value"
                                    :title="choice.label"
                                    @click="edit.icon = edit.icon === choice.value ? '' : choice.value"
                                >
                                    <ServiceIcon :service="choice.value" :size="18" />
                                    <span class="sr-only">{{ choice.label }}</span>
                                </button>
                            </div>
                            <p class="pt-2 text-sm text-gray-600">
                                Erscheint auf der Startseite, der Leistungsübersicht und den Stadtseiten.
                                Ohne Auswahl wird ein Dokumentsymbol verwendet.
                            </p>
                        </div>

                        <!--
                            Questions about this assessment, shown on its own
                            page under the steps. The general FAQ answers what
                            DKGZ is; these answer what this report contains.
                        -->
                        <div>
                            <p class="pb-2 text-sm font-medium text-gray-800">Häufige Fragen zu dieser Leistung</p>

                            <div v-for="(entry, index) in edit.faqs" :key="index" class="flex flex-col gap-3 border-b border-gray-100 pb-4 pt-4 first:pt-0">
                                <BaseInput v-model="entry.frage" label="Frage" :error="edit.errors[`faqs.${index}.frage`]" />
                                <BaseTextarea v-model="entry.antwort" label="Antwort" :rows="3" :error="edit.errors[`faqs.${index}.antwort`]" />
                                <button type="button" class="self-start text-sm text-gray-600 hover:text-danger" @click="edit.faqs.splice(index, 1)">
                                    Frage entfernen
                                </button>
                            </div>

                            <BaseButton
                                v-if="edit.faqs.length < 12"
                                type="button"
                                variant="secondary"
                                size="small"
                                class="mt-4"
                                @click="edit.faqs.push({ frage: '', antwort: '' })"
                            >Frage hinzufügen</BaseButton>
                        </div>
                        <BaseCurrencyInput
                            v-model="edit.dkgz_fee_cents"
                            label="DKGZ-Gebühr (netto)"
                            hint="Gilt für neu angenommene Aufträge. Bereits angenommene behalten ihren Betrag."
                            :error="edit.errors.dkgz_fee_cents"
                            required
                        />
                        <div class="border-t border-gray-200 pt-5">
                            <p class="text-eyebrow font-semibold uppercase text-gray-600">Inhalte der Leistungsseite</p>
                            <p v-if="type.content_is_placeholder" class="measure pt-2 text-sm leading-normal text-warning">
                                Diese Texte sind Platzhalter und sollten überprüft werden. Der Hinweis verschwindet,
                                sobald Sie gespeichert haben.
                            </p>
                        </div>
                        <BaseTextarea v-model="edit.includes_de" label="Was enthalten ist" :rows="3" :error="edit.errors.includes_de" optional />
                        <BaseTextarea v-model="edit.target_audience_de" label="Für wen geeignet" :rows="2" :error="edit.errors.target_audience_de" optional />
                        <BaseTextarea v-model="edit.typical_situations_de" label="Typische Situationen" :rows="3" :error="edit.errors.typical_situations_de" optional />
                        <BaseTextarea v-model="edit.differences_de" label="Abgrenzung zu anderen Leistungen" :rows="3" :error="edit.errors.differences_de" optional />
                        <BaseTextarea v-model="edit.additional_info_de" label="Gut zu wissen" :rows="3" :error="edit.errors.additional_info_de" optional />

                        <BaseToggle v-model="edit.is_active" label="Aktiv" description="Inaktive Leistungsarten erscheinen nicht im Anfrageformular." on-label="Aktiv" off-label="Inaktiv" />
                    </div>
                    <div class="flex gap-3 pt-5">
                        <BaseButton type="submit" size="compact" :loading="edit.processing">Speichern</BaseButton>
                        <BaseButton type="button" variant="ghost" size="compact" @click="editing = null">Abbrechen</BaseButton>
                    </div>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
