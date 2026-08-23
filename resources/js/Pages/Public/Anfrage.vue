<script setup>
import { onMounted, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Check } from 'lucide-vue-next'
import RequestFlowLayout from '../../Layouts/RequestFlowLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import RequestProgress from '../../Components/Domain/RequestProgress.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BasePostalCodeInput from '../../Components/Base/BasePostalCodeInput.vue'
import BaseFileUpload from '../../Components/Base/BaseFileUpload.vue'
import BaseCheckbox from '../../Components/Base/BaseCheckbox.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    serviceTypes: { type: Array, default: () => [] },
    imageUploadsEnabled: { type: Boolean, default: true },
    maxImages: { type: Number, default: 5 },
    maxUploadMb: { type: Number, default: 10 },
})

const page = usePage()
const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

const form = useForm({
    service_type_id: '',
    postal_code: new URLSearchParams(window.location.search).get('plz') ?? '',
    city: '',
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    vehicle_make: '',
    vehicle_model: '',
    vehicle_year: '',
    vehicle_plate: '',
    description: '',
    urgency: '',
    images: null,
    consent: false,
    // Honeypot plus the time the form was rendered — a submission inside three
    // seconds was not typed by a person. No third-party captcha: that would
    // send visitor data to someone else, which the GDPR brief rules out.
    website: '',
    rendered_at: 0,
})

onMounted(() => { form.rendered_at = Date.now() })

/**
 * Three steps rather than one long form.
 *
 * The fields are unchanged and so is the submission: only the last step posts,
 * so a half-filled request is never sent and the server-side validation stays
 * the single source of truth. Moving forward checks the fields on the current
 * step so somebody is told about a missing postal code there, rather than four
 * screens later.
 */
const STEPS = [
    { number: 1, label: 'Leistung und Standort', short: 'Leistung' },
    { number: 2, label: 'Angaben zum Fahrzeug', short: 'Fahrzeug' },
    { number: 3, label: 'Kontakt und Dringlichkeit', short: 'Kontakt' },
]

const REQUIRED_BY_STEP = {
    1: ['service_type_id', 'postal_code', 'city'],
    2: ['vehicle_make', 'vehicle_model'],
    3: ['customer_name', 'customer_phone', 'customer_email', 'consent'],
}

const LABELS_BY_FIELD = {
    service_type_id: 'Bitte wählen Sie die Art des Gutachtens.',
    postal_code: 'Bitte geben Sie die Postleitzahl an.',
    city: 'Bitte geben Sie den Standort des Fahrzeugs an.',
    vehicle_make: 'Bitte geben Sie die Marke an.',
    vehicle_model: 'Bitte geben Sie das Modell an.',
    customer_name: 'Bitte geben Sie Ihren Namen an.',
    customer_phone: 'Bitte geben Sie eine Telefonnummer an.',
    customer_email: 'Bitte geben Sie eine E-Mail-Adresse an.',
    consent: 'Bitte bestätigen Sie die Einwilligung.',
}

const step = ref(1)
const furthest = ref(1)

const goTo = (target) => {
    step.value = target
    furthest.value = Math.max(furthest.value, target)
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const next = () => {
    const missing = REQUIRED_BY_STEP[step.value].filter((field) => {
        const value = form[field]

        return field === 'consent' ? value !== true : String(value ?? '').trim() === ''
    })

    if (missing.length) {
        // Shown the same way the server shows its own, so the two never look
        // like different kinds of problem.
        form.setError(Object.fromEntries(missing.map((f) => [f, LABELS_BY_FIELD[f]])))

        return
    }

    form.clearErrors()
    goTo(step.value + 1)
}

const back = () => goTo(step.value - 1)

const labels = {
    service_type_id: 'Art des Gutachtens',
    postal_code: 'Postleitzahl',
    city: 'Standort des Fahrzeugs',
    customer_name: 'Vor- und Nachname',
    customer_phone: 'Telefon',
    customer_email: 'E-Mail',
    vehicle_make: 'Marke',
    vehicle_model: 'Modell',
    consent: 'Einwilligung',
}

const typeOptions = props.serviceTypes.map((t) => ({ value: t.id, label: t.name_de }))

const urgencyOptions = [
    { value: 'normal', label: 'Keine besondere Eile' },
    { value: 'soon', label: 'Innerhalb von zwei Werktagen' },
    { value: 'urgent', label: 'So schnell wie möglich' },
]

const submit = () => form.post('/anfrage', { forceFormData: true })
</script>

<template>

    <RequestFlowLayout title="Anfrage" :dirty="form.isDirty">
        <template #progress>
            <RequestProgress :steps="STEPS" :current="step" :furthest="furthest" @go="goTo" />
        </template>

        <div class="bg-gray-50">
            <div class="mx-auto grid w-full max-w-(--container-wide) grid-cols-1 gap-12 px-4 py-16 md:px-6 lg:grid-cols-[minmax(0,720px)_300px] lg:items-start">
                <div class="min-w-0">
                    <!--
                        Only on the first step. Repeating the page's title and
                        its "here is what this is" paragraph above every step
                        says nothing new after the visitor has begun, and pushes
                        the fields they are actually working on down the screen.
                        The step indicator above carries the context from there.
                    -->
                    <template v-if="step === 1">
                        <SectionLabel :text="t('kopf', 'eyebrow', 'Kostenlose Anfrage')" />
                        <h1 class="pt-6 text-h1 font-bold text-navy-700">{{ t('kopf', 'ueberschrift', 'Gutachter anfragen') }}</h1>
                        <p class="measure-lead pt-3 text-lead leading-relaxed text-gray-600">{{ t('kopf', 'text') }}</p>
                    </template>

                    <form
                        class="rounded-card border border-gray-200 bg-white p-6 md:p-8"
                        :class="step === 1 ? 'mt-8' : ''"
                        novalidate
                        @submit.prevent="submit"
                    >
                        <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-6" />

                        <!-- Honeypot: visually and programmatically hidden -->
                        <div class="hidden" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" v-model="form.website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div v-show="step === 1" class="flex flex-col gap-5">
                            <BaseSelect
                                id="service_type_id"
                                v-model="form.service_type_id"
                                label="Art des Gutachtens"
                                :options="typeOptions"
                                :error="form.errors.service_type_id"
                                required
                            />

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-[200px_minmax(0,1fr)]">
                                <BasePostalCodeInput
                                    id="postal_code"
                                    v-model="form.postal_code"
                                    v-model:city="form.city"
                                    :error="form.errors.postal_code"
                                    required
                                />
                                <BaseInput
                                    id="city"
                                    v-model="form.city"
                                    label="Standort des Fahrzeugs"
                                    placeholder="Ort, Straße oder Werkstatt"
                                    :error="form.errors.city"
                                    required
                                />
                            </div>

                        </div>

                        <!-- Step 2 -->
                        <div v-show="step === 2" class="flex flex-col gap-5">
                            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                                <BaseInput id="vehicle_make" v-model="form.vehicle_make" label="Marke" placeholder="VW" :error="form.errors.vehicle_make" required />
                                <BaseInput id="vehicle_model" v-model="form.vehicle_model" label="Modell" placeholder="Passat B8" :error="form.errors.vehicle_model" required />
                                <BaseInput id="vehicle_year" v-model="form.vehicle_year" label="Baujahr" placeholder="2019" inputmode="numeric" maxlength="4" numeric :error="form.errors.vehicle_year" />
                                <BaseInput id="vehicle_plate" v-model="form.vehicle_plate" label="Kennzeichen" placeholder="D-AB 1234" :error="form.errors.vehicle_plate" />
                            </div>

                            <BaseTextarea
                                id="description"
                                v-model="form.description"
                                label="Kurze Beschreibung"
                                placeholder="Was ist passiert? Zwei bis drei Sätze genügen."
                                :error="form.errors.description"
                                optional
                            />

                            <BaseFileUpload
                                v-if="imageUploadsEnabled"
                                v-model="form.images"
                                label="Fotos"
                                accept=".jpg,.jpeg,.png,.webp"
                                :accept-label="`JPG, PNG · max. ${maxImages} Dateien · je ${maxUploadMb} MB`"
                                multiple
                                :max-files="maxImages"
                                :max-size-mb="maxUploadMb"
                                :error="form.errors.images"
                            />
                        </div>

                        <!-- Step 3 -->
                        <div v-show="step === 3" class="flex flex-col gap-5">
                            <BaseInput id="customer_name" v-model="form.customer_name" label="Vor- und Nachname" placeholder="Martina Reinhardt" autocomplete="name" :error="form.errors.customer_name" required />
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <BaseInput id="customer_phone" v-model="form.customer_phone" label="Telefon" placeholder="+49 179 0000000" autocomplete="tel" numeric :error="form.errors.customer_phone" required />
                                <BaseInput id="customer_email" v-model="form.customer_email" label="E-Mail" type="email" placeholder="name@beispiel.de" autocomplete="email" :error="form.errors.customer_email" required />
                            </div>

                            <BaseSelect
                                id="urgency"
                                v-model="form.urgency"
                                label="Dringlichkeit"
                                :options="urgencyOptions"
                                :error="form.errors.urgency"
                                optional
                                class="max-w-80"
                            />

                            <div class="pt-2">
                                <BaseCheckbox id="consent" v-model="form.consent" :error="form.errors.consent">
                                    Ich willige ein, dass DKGZ meine Angaben zur Vermittlung an geeignete Sachverständige
                                    verarbeitet. Die
                                    <a href="/datenschutz" class="border-b border-navy-700 pb-0.5 text-navy-700">Datenschutzerklärung</a>
                                    habe ich gelesen.
                                </BaseCheckbox>
                            </div>
                        </div>

                        <!--
                            Only the last step submits, so a half-filled request
                            can never be sent and the server stays the single
                            authority on what is valid.
                        -->
                        <div class="flex flex-col-reverse gap-3 pt-8 sm:flex-row sm:items-center">
                            <BaseButton
                                v-if="step > 1"
                                type="button"
                                variant="secondary"
                                size="cta"
                                class="w-full sm:w-auto"
                                @click="back"
                            >Zurück</BaseButton>

                            <BaseButton
                                v-if="step < STEPS.length"
                                type="button"
                                size="cta"
                                class="w-full sm:ml-auto sm:w-auto"
                                @click="next"
                            >Weiter</BaseButton>

                            <BaseButton
                                v-else
                                type="submit"
                                size="cta"
                                class="w-full sm:ml-auto sm:w-auto"
                                :loading="form.processing"
                                loading-label="Wird gesendet…"
                            >{{ t('formular', 'cta', 'Anfrage absenden') }}</BaseButton>
                        </div>

                        <p v-if="step === STEPS.length" class="measure pt-3 text-sm leading-normal text-gray-600">
                            {{ t('formular', 'datenschutzhinweis') }}
                        </p>
                        <p
                            v-else
                            class="flex items-center gap-2 pt-3 text-sm text-gray-600 lg:hidden"
                        >
                            <Check :size="15" :stroke-width="2" class="shrink-0 text-navy-700" aria-hidden="true" />
                            {{ t('formular', 'kurzhinweis', 'Kostenlos und unverbindlich') }}
                        </p>
                    </form>
                </div>

                <!--
                    Beside the form on a wide screen, gone on a phone: stacked
                    below the fields it is a screenful of reassurance somebody
                    has to scroll past after they have already started. The one
                    line that still earns its place moves under the button.
                -->
                <aside class="hidden rounded-card border border-gray-200 bg-white p-6 lg:sticky lg:top-24 lg:block">
                    <ul class="flex flex-col gap-4">
                        <li v-for="key in ['punkt_1', 'punkt_2', 'punkt_3']" :key="key" class="flex gap-2.5">
                            <Check :size="18" :stroke-width="1.5" class="mt-0.5 shrink-0 text-navy-700" aria-hidden="true" />
                            <span class="text-sm leading-normal text-gray-800">{{ t('seitenleiste', key) }}</span>
                        </li>
                    </ul>
                    <div v-if="page.props.app?.phone" class="mt-5 border-t border-gray-200 pt-4">
                        <SectionLabel :text="t('seitenleiste', 'telefon_titel', 'Lieber telefonisch?')" tone="muted" :with-rule="false" />
                        <a :href="`tel:${page.props.app.phone.replace(/\s/g, '')}`" class="block pt-2 font-mono text-base tabular-nums text-navy-700">{{ page.props.app.phone }}</a>
                        <p class="text-sm text-gray-600">{{ page.props.app.office_hours }}</p>
                    </div>
                </aside>
            </div>
        </div>
    </RequestFlowLayout>
</template>
