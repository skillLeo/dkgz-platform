<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Check, Plus, Trash2 } from 'lucide-vue-next'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import BasePasswordInput from '../../Components/Base/BasePasswordInput.vue'
import BasePostalCodeInput from '../../Components/Base/BasePostalCodeInput.vue'
import BaseVatInput from '../../Components/Base/BaseVatInput.vue'
import BaseDatePicker from '../../Components/Base/BaseDatePicker.vue'
import BaseFileUpload from '../../Components/Base/BaseFileUpload.vue'
import BaseCheckbox from '../../Components/Base/BaseCheckbox.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

/**
 * Four steps, one screen. Each step validates server-side on "Weiter" and the
 * answers are held in the session, so a partner can leave and come back.
 * The step transition is the 200ms specified in Foundations.
 */
const props = defineProps({
    draft: { type: Object, default: () => ({}) },
    step: { type: Number, default: 1 },
    serviceTypes: { type: Array, default: () => [] },
    legalForms: { type: Object, default: () => ({}) },
    certificationBodies: { type: Object, default: () => ({}) },
})

const { time } = useGermanFormat()

const current = ref(props.step)
const savedAt = ref(null)

const form = useForm({
    first_name: props.draft.first_name ?? '',
    last_name: props.draft.last_name ?? '',
    email: props.draft.email ?? '',
    phone: props.draft.phone ?? '',
    password: '',
    password_confirmation: '',
    company_name: props.draft.company_name ?? '',
    legal_form: props.draft.legal_form ?? '',
    street: props.draft.street ?? '',
    house_number: props.draft.house_number ?? '',
    postal_code: props.draft.postal_code ?? '',
    city: props.draft.city ?? '',
    vat_id: props.draft.vat_id ?? '',
    website: props.draft.website ?? '',
    certification_body: props.draft.certification_body ?? '',
    certification_number: props.draft.certification_number ?? '',
    certification_valid_until: props.draft.certification_valid_until ?? '',
    years_experience: props.draft.years_experience ?? '',
    qualification_document: null,
    service_type_ids: props.draft.service_type_ids ?? [],
    service_areas: props.draft.service_areas ?? [{ from: '', to: '', label: '' }],
    terms: false,
    privacy: false,
})

const steps = [
    { number: 1, label: 'Zugang' },
    { number: 2, label: 'Unternehmen' },
    { number: 3, label: 'Qualifikation' },
    { number: 4, label: 'Leistungen' },
]

const fieldsForStep = {
    1: ['first_name', 'last_name', 'email', 'phone', 'password', 'password_confirmation'],
    2: ['company_name', 'legal_form', 'street', 'house_number', 'postal_code', 'city', 'vat_id', 'website'],
    3: ['certification_body', 'certification_number', 'certification_valid_until', 'years_experience', 'qualification_document'],
    4: ['service_type_ids', 'service_areas', 'terms', 'privacy'],
}

const labels = {
    first_name: 'Vorname', last_name: 'Nachname', email: 'E-Mail-Adresse', phone: 'Telefonnummer',
    password: 'Passwort', company_name: 'Firmenname', legal_form: 'Rechtsform', street: 'Straße',
    house_number: 'Hausnummer', postal_code: 'Postleitzahl', city: 'Ort', vat_id: 'USt-IdNr.',
    certification_body: 'Zertifizierungsstelle', certification_number: 'Zertifizierungsnummer',
    service_type_ids: 'Leistungen', service_areas: 'Einsatzgebiet', terms: 'AGB', privacy: 'Datenschutzerklärung',
}

const legalFormOptions = computed(() =>
    Object.entries(props.legalForms).map(([value, label]) => ({ value, label }))
)

const certificationOptions = computed(() =>
    Object.entries(props.certificationBodies).map(([value, label]) => ({ value, label }))
)

const toggleServiceType = (id) => {
    const index = form.service_type_ids.indexOf(id)
    if (index === -1) form.service_type_ids.push(id)
    else form.service_type_ids.splice(index, 1)
}

const addArea = () => form.service_areas.push({ from: '', to: '', label: '' })
const removeArea = (index) => form.service_areas.splice(index, 1)

const next = () => {
    form.transform((data) => Object.fromEntries(
        Object.entries(data).filter(([key]) => fieldsForStep[current.value].includes(key))
    )).post(`/registrieren/schritt/${current.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            savedAt.value = new Date()
            if (current.value < 4) current.value += 1
        },
        onFinish: () => form.transform((data) => data),
    })
}

const back = () => { if (current.value > 1) current.value -= 1 }

const submit = () => form.post('/registrieren', { forceFormData: true })

const stepErrors = computed(() =>
    Object.fromEntries(
        Object.entries(form.errors).filter(([key]) =>
            fieldsForStep[current.value].some((field) => key === field || key.startsWith(`${field}.`))
        )
    )
)
</script>

<template>
    <Head title="Als Partner registrieren" />

    <AuthLayout
        eyebrow="Partnernetz"
        title="Als Partner registrieren"
        description="Vier Schritte, etwa fünf Minuten. Die Freigabe erfolgt nach Prüfung Ihrer Nachweise."
        panel-title="Aufträge aus Ihrer Region. Ohne Akquise."
        panel-text="Sie legen Einsatzgebiet und Leistungen fest, wir leiten passende Anfragen weiter."
    >
        <!-- Step indicator -->
        <ol class="flex items-center gap-2 pb-6">
            <li v-for="s in steps" :key="s.number" class="flex flex-1 items-center gap-2">
                <span
                    class="grid h-6 w-6 shrink-0 place-items-center rounded-full border font-mono text-xs tabular-nums"
                    :class="s.number < current
                        ? 'border-navy-700 bg-navy-700 text-white'
                        : s.number === current
                            ? 'border-navy-700 text-navy-700'
                            : 'border-gray-300 text-gray-400'"
                >
                    <Check v-if="s.number < current" :size="12" :stroke-width="1.5" aria-hidden="true" />
                    <template v-else>{{ s.number }}</template>
                </span>
                <span
                    class="hidden text-xs sm:block"
                    :class="s.number === current ? 'font-medium text-navy-700' : 'text-gray-400'"
                >{{ s.label }}</span>
                <span v-if="s.number < 4" class="h-px flex-1 bg-gray-200" aria-hidden="true" />
            </li>
        </ol>

        <ErrorSummary v-if="Object.keys(stepErrors).length" :errors="stepErrors" :labels="labels" class="mb-6" />

        <form novalidate @submit.prevent="current === 4 ? submit() : next()">
            <div :key="current" style="animation: dkgz-rise 200ms cubic-bezier(0.4,0,0.2,1) both">
                <!-- 1 · Zugang -->
                <div v-if="current === 1" class="flex flex-col gap-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <BaseInput id="first_name" v-model="form.first_name" label="Vorname" :error="form.errors.first_name" autocomplete="given-name" required />
                        <BaseInput id="last_name" v-model="form.last_name" label="Nachname" :error="form.errors.last_name" autocomplete="family-name" required />
                    </div>
                    <BaseInput id="email" v-model="form.email" label="E-Mail-Adresse" type="email" :error="form.errors.email" autocomplete="username" placeholder="name@buero.de" required />
                    <BaseInput id="phone" v-model="form.phone" label="Telefonnummer" :error="form.errors.phone" autocomplete="tel" placeholder="+49 179 0000000" numeric required />
                    <BasePasswordInput id="password" v-model="form.password" label="Passwort" autocomplete="new-password" :error="form.errors.password" show-meter show-checklist required />
                    <BasePasswordInput id="password_confirmation" v-model="form.password_confirmation" label="Passwort wiederholen" autocomplete="new-password" :error="form.errors.password_confirmation" required />
                </div>

                <!-- 2 · Unternehmen -->
                <div v-else-if="current === 2" class="flex flex-col gap-5">
                    <BaseInput id="company_name" v-model="form.company_name" label="Firmenname" :error="form.errors.company_name" autocomplete="organization" required />
                    <BaseSelect id="legal_form" v-model="form.legal_form" label="Rechtsform" :options="legalFormOptions" :error="form.errors.legal_form" required />
                    <div class="grid grid-cols-[minmax(0,1fr)_120px] gap-4">
                        <BaseInput id="street" v-model="form.street" label="Straße" :error="form.errors.street" autocomplete="address-line1" required />
                        <BaseInput id="house_number" v-model="form.house_number" label="Hausnummer" :error="form.errors.house_number" required />
                    </div>
                    <div class="grid grid-cols-[160px_minmax(0,1fr)] gap-4">
                        <BasePostalCodeInput id="postal_code" v-model="form.postal_code" v-model:city="form.city" :error="form.errors.postal_code" required />
                        <BaseInput id="city" v-model="form.city" label="Ort" :error="form.errors.city" autocomplete="address-level2" required />
                    </div>
                    <BaseVatInput id="vat_id" v-model="form.vat_id" :error="form.errors.vat_id" />
                    <BaseInput id="website" v-model="form.website" label="Internetadresse" :error="form.errors.website" placeholder="www.buero.de" optional />
                </div>

                <!-- 3 · Qualifikation -->
                <div v-else-if="current === 3" class="flex flex-col gap-5">
                    <BaseSelect id="certification_body" v-model="form.certification_body" label="Zertifizierungsstelle" :options="certificationOptions" :error="form.errors.certification_body" required />
                    <BaseInput id="certification_number" v-model="form.certification_number" label="Zertifizierungsnummer" :error="form.errors.certification_number" mono required />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <BaseDatePicker id="certification_valid_until" v-model="form.certification_valid_until" label="Gültig bis" :error="form.errors.certification_valid_until" optional />
                        <BaseInput id="years_experience" v-model="form.years_experience" label="Berufserfahrung in Jahren" :error="form.errors.years_experience" inputmode="numeric" numeric optional />
                    </div>
                    <BaseFileUpload
                        v-model="form.qualification_document"
                        label="Nachweis der Qualifikation"
                        accept=".pdf,.jpg,.jpeg,.png"
                        accept-label="PDF, JPG oder PNG · max. 10 MB"
                        :error="form.errors.qualification_document"
                    />
                </div>

                <!-- 4 · Leistungen und Einsatzgebiet -->
                <div v-else class="flex flex-col gap-6">
                    <div>
                        <p class="pb-2 text-sm font-medium text-gray-800">Welche Gutachten erstellen Sie? <span class="text-danger">*</span></p>
                        <div class="flex flex-col gap-2">
                            <label
                                v-for="type in serviceTypes"
                                :key="type.id"
                                class="flex cursor-pointer items-start gap-3 rounded-sm border p-3 transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                                :class="form.service_type_ids.includes(type.id) ? 'border-navy-700 bg-navy-100' : 'border-gray-200 hover:border-gray-300'"
                            >
                                <input
                                    type="checkbox"
                                    :checked="form.service_type_ids.includes(type.id)"
                                    class="sr-only"
                                    @change="toggleServiceType(type.id)"
                                >
                                <span
                                    class="mt-0.5 grid h-(--spacing-check) w-(--spacing-check) shrink-0 place-items-center rounded-sm border"
                                    :class="form.service_type_ids.includes(type.id) ? 'border-navy-700 bg-navy-700 text-white' : 'border-gray-300'"
                                    aria-hidden="true"
                                >
                                    <Check v-if="form.service_type_ids.includes(type.id)" :size="12" :stroke-width="1.5" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-base text-gray-800">{{ type.name_de }}</span>
                                    <span v-if="type.description_de" class="block pt-0.5 text-xs leading-normal text-gray-600">{{ type.description_de }}</span>
                                </span>
                            </label>
                        </div>
                        <p v-if="form.errors.service_type_ids" class="pt-1.5 text-xs text-danger" role="alert">{{ form.errors.service_type_ids }}</p>
                    </div>

                    <div>
                        <p class="pb-2 text-sm font-medium text-gray-800">Ihr Einsatzgebiet nach PLZ <span class="text-danger">*</span></p>
                        <div class="flex flex-col gap-3">
                            <div
                                v-for="(area, index) in form.service_areas"
                                :key="index"
                                class="grid grid-cols-[100px_100px_minmax(0,1fr)_40px] items-end gap-2"
                            >
                                <BaseInput v-model="area.from" label="von" placeholder="40000" inputmode="numeric" maxlength="5" numeric :error="form.errors[`service_areas.${index}.from`]" />
                                <BaseInput v-model="area.to" label="bis" placeholder="40999" inputmode="numeric" maxlength="5" numeric :error="form.errors[`service_areas.${index}.to`]" />
                                <BaseInput v-model="area.label" label="Bezeichnung" placeholder="Düsseldorf und Umgebung" optional />
                                <button
                                    v-if="form.service_areas.length > 1"
                                    type="button"
                                    class="grid h-(--spacing-control) w-10 place-items-center rounded-sm border border-gray-300 text-gray-600 hover:border-danger hover:text-danger"
                                    aria-label="Gebiet entfernen"
                                    @click="removeArea(index)"
                                >
                                    <Trash2 :size="16" :stroke-width="1.5" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                        <button type="button" class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-navy-700 hover:text-navy-500" @click="addArea">
                            <Plus :size="16" :stroke-width="1.5" aria-hidden="true" />
                            Weiteres Gebiet hinzufügen
                        </button>
                        <p v-if="form.errors.service_areas" class="pt-1.5 text-xs text-danger" role="alert">{{ form.errors.service_areas }}</p>
                    </div>

                    <div class="flex flex-col gap-4">
                        <BaseCheckbox id="terms" v-model="form.terms" :error="form.errors.terms">
                            Ich akzeptiere die
                            <a href="/agb" class="border-b border-navy-700 pb-0.5 text-navy-700">Allgemeinen Geschäftsbedingungen</a>
                            und die Provisionsvereinbarung auf abgeschlossene Aufträge.
                        </BaseCheckbox>
                        <BaseCheckbox id="privacy" v-model="form.privacy" :error="form.errors.privacy">
                            Ich habe die
                            <a href="/datenschutz" class="border-b border-navy-700 pb-0.5 text-navy-700">Datenschutzerklärung</a>
                            gelesen und stimme der Verarbeitung meiner Daten zu.
                        </BaseCheckbox>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-6">
                <BaseButton v-if="current > 1" variant="secondary" type="button" @click="back">Zurück</BaseButton>
                <BaseButton type="submit" class="flex-1" :loading="form.processing" loading-label="Wird geprüft…">
                    {{ current === 4 ? 'Registrierung abschließen' : 'Weiter' }}
                </BaseButton>
            </div>

            <p v-if="savedAt" class="flex items-center gap-2 pt-4 font-mono text-xs text-gray-400">
                <Check :size="12" :stroke-width="1.5" aria-hidden="true" />
                Automatisch gespeichert · {{ time(savedAt) }}
            </p>
        </form>

        <template #footer>
            Bereits Partner?
            <Link href="/anmelden" class="font-medium text-navy-700 hover:text-navy-500">Zur Anmeldung</Link>
        </template>
    </AuthLayout>
</template>
