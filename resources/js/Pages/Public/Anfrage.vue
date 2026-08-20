<script setup>
import { onMounted, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Check } from 'lucide-vue-next'
import RequestFlowLayout from '../../Layouts/RequestFlowLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
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
        <div class="bg-gray-50">
            <div class="mx-auto grid w-full max-w-(--container-wide) grid-cols-1 gap-12 px-4 py-16 md:px-6 lg:grid-cols-[minmax(0,720px)_300px] lg:items-start">
                <div class="min-w-0">
                    <SectionLabel :text="t('kopf', 'eyebrow', 'Kostenlose Anfrage')" />
                    <h1 class="pt-6 text-h1 font-bold text-navy-700">{{ t('kopf', 'ueberschrift', 'Gutachter anfragen') }}</h1>
                    <p class="measure-lead pt-3 text-lead leading-relaxed text-gray-600">{{ t('kopf', 'text') }}</p>

                    <form class="mt-8 rounded-card border border-gray-200 bg-white p-6 md:p-8" novalidate @submit.prevent="submit">
                        <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-6" />

                        <!-- Honeypot: visually and programmatically hidden -->
                        <div class="hidden" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" v-model="form.website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="border-b border-gray-200 pb-5">
                            <SectionLabel :text="t('formular', 'abschnitt_anliegen', 'Ihr Anliegen')" tone="muted" :with-rule="false" />
                        </div>

                        <div class="flex flex-col gap-5 pt-6">
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

                            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                                <BaseInput id="vehicle_make" v-model="form.vehicle_make" label="Marke" placeholder="VW" :error="form.errors.vehicle_make" required />
                                <BaseInput id="vehicle_model" v-model="form.vehicle_model" label="Modell" placeholder="Passat B8" :error="form.errors.vehicle_model" required />
                                <BaseInput id="vehicle_year" v-model="form.vehicle_year" label="Baujahr" placeholder="2019" inputmode="numeric" maxlength="4" numeric :error="form.errors.vehicle_year" />
                                <BaseInput id="vehicle_plate" v-model="form.vehicle_plate" label="Kennzeichen" placeholder="D-AB 1234" :error="form.errors.vehicle_plate" />
                            </div>
                        </div>

                        <div class="mt-8 border-b border-gray-200 pb-5">
                            <SectionLabel :text="t('formular', 'abschnitt_kontakt', 'Ihre Kontaktdaten')" tone="muted" :with-rule="false" />
                        </div>

                        <div class="flex flex-col gap-5 pt-6">
                            <BaseInput id="customer_name" v-model="form.customer_name" label="Vor- und Nachname" placeholder="Martina Reinhardt" autocomplete="name" :error="form.errors.customer_name" required />
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <BaseInput id="customer_phone" v-model="form.customer_phone" label="Telefon" placeholder="+49 179 0000000" autocomplete="tel" numeric :error="form.errors.customer_phone" required />
                                <BaseInput id="customer_email" v-model="form.customer_email" label="E-Mail" type="email" placeholder="name@beispiel.de" autocomplete="email" :error="form.errors.customer_email" required />
                            </div>
                        </div>

                        <div class="mt-8 border-b border-gray-200 pb-5">
                            <SectionLabel :text="t('formular', 'abschnitt_optional', 'Optional')" tone="muted" :with-rule="false" />
                        </div>

                        <div class="flex flex-col gap-5 pt-6">
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

                            <BaseSelect
                                id="urgency"
                                v-model="form.urgency"
                                label="Dringlichkeit"
                                :options="urgencyOptions"
                                :error="form.errors.urgency"
                                optional
                                class="max-w-80"
                            />
                        </div>

                        <div class="pt-8">
                            <BaseCheckbox id="consent" v-model="form.consent" :error="form.errors.consent">
                                Ich willige ein, dass DKGZ meine Angaben zur Vermittlung an geeignete Sachverständige
                                verarbeitet. Die
                                <a href="/datenschutz" class="border-b border-navy-700 pb-0.5 text-navy-700">Datenschutzerklärung</a>
                                habe ich gelesen.
                            </BaseCheckbox>
                        </div>

                        <BaseButton type="submit" size="cta" block class="mt-8" :loading="form.processing" loading-label="Wird gesendet…">
                            {{ t('formular', 'cta', 'Anfrage absenden') }}
                        </BaseButton>

                        <p class="measure pt-3 text-sm leading-normal text-gray-600">{{ t('formular', 'datenschutzhinweis') }}</p>
                    </form>
                </div>

                <aside class="rounded-card border border-gray-200 bg-white p-6 lg:sticky lg:top-24">
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
