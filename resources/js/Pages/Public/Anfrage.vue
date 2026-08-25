<script setup>
import { computed, onMounted, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { Check } from 'lucide-vue-next'
import RequestFlowLayout from '../../Layouts/RequestFlowLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import RequestProgress from '../../Components/Domain/RequestProgress.vue'
import RequestStarter from '../../Components/Domain/RequestStarter.vue'
import TrustRow from '../../Components/Domain/TrustRow.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

/**
 * Two steps: what and where, then who.
 *
 * It used to be three, and the middle one asked for the make, the model, the
 * year, the plate, a description of what happened and photographs — a screen of
 * work between somebody arriving from an advert and the point where DKGZ has
 * their telephone number. The assessor telephones them and asks all of it
 * anyway. Everything the matching actually runs on is the service and the
 * postal code, and both are answered before the visitor is asked for anything
 * about themselves.
 *
 * The first step is the same component as the homepage hero, so somebody who
 * started at the top and somebody who clicked "Gutachter anfragen" from a
 * service page meet the same two questions in the same order. Arriving with
 * both already answered skips straight to the contact details.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    serviceTypes: { type: Array, default: () => [] },
    /** Filled in when the visitor arrives having already chosen. */
    selected: { type: Object, default: () => ({}) },
})

const page = usePage()
const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

const form = useForm({
    service_type_id: props.selected.service_type_id ?? '',
    postal_code: props.selected.postal_code ?? '',
    city: props.selected.city ?? '',
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    // Honeypot plus the time the form was rendered — a submission inside three
    // seconds was not typed by a person. No third-party captcha: that would
    // send visitor data to someone else, which the GDPR brief rules out.
    website: '',
    rendered_at: 0,
})

onMounted(() => { form.rendered_at = Date.now() })

const STEPS = [
    { number: 1, label: 'Leistung und Standort', short: 'Leistung' },
    { number: 2, label: 'Ihre Kontaktdaten', short: 'Kontakt' },
]

// Both answers already in hand means the first step has nothing left to ask.
const started = ref(Boolean(props.selected.service_type_id && props.selected.city))
const step = computed(() => (started.value ? 2 : 1))

const service = computed(() => props.serviceTypes
    .find((type) => String(type.id) === String(form.service_type_id)) ?? null)

/** Answering the first step here rather than arriving with it answered. */
const onStart = ({ service_type_id, postal_code, city }) => {
    form.service_type_id = service_type_id
    form.postal_code = postal_code
    form.city = city
    started.value = true

    // Counted once: going back and forward again is the same person on the
    // same form, not a second one reaching the step.
    router.post('/anfrage/schritt', { step: 'schritt_2' }, {
        preserveState: true,
        preserveScroll: true,
        only: [],
    })

    document.activeElement?.blur?.()
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

/** Back to the first step, with what was chosen still chosen. */
const back = () => {
    started.value = false
    form.clearErrors()
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const REQUIRED = {
    customer_name: 'Bitte geben Sie Ihren Namen an.',
    customer_email: 'Bitte geben Sie eine E-Mail-Adresse an.',
    customer_phone: 'Bitte geben Sie eine Telefonnummer an.',
}

const labels = {
    service_type_id: 'Art des Gutachtens',
    postal_code: 'Postleitzahl',
    city: 'Standort des Fahrzeugs',
    customer_name: 'Vor- und Nachname',
    customer_phone: 'Telefon',
    customer_email: 'E-Mail',
}

const submit = () => {
    const missing = Object.keys(REQUIRED).filter((field) => String(form[field] ?? '').trim() === '')

    if (missing.length) {
        // Shown the same way the server shows its own, so the two never look
        // like different kinds of problem.
        form.setError(Object.fromEntries(missing.map((f) => [f, REQUIRED[f]])))

        return
    }

    form.clearErrors()
    form.post('/anfrage')
}
</script>

<template>
    <RequestFlowLayout
        title="Anfrage"
        :dirty="form.isDirty"
        :can-go-back="started"
        @back="back"
    >
        <template v-if="started" #progress>
            <p class="pb-2 text-center text-sm text-gray-600">Schritt {{ step }} von {{ STEPS.length }}</p>
            <RequestProgress :steps="STEPS" :current="step" :furthest="step" @go="(n) => n === 1 && back()" />
        </template>

        <div class="bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-prose) px-4 py-12 md:px-6 md:py-16">
                <!-- ── Step one: what, and where ── -->
                <template v-if="! started">
                    <SectionLabel :text="t('kopf', 'eyebrow', 'Kostenlose Anfrage')" />
                    <h1 class="pt-6 text-h2 font-bold text-navy-700 sm:text-h1">
                        {{ t('kopf', 'ueberschrift', 'Gutachter anfragen') }}
                    </h1>
                    <p class="measure-lead pt-3 text-lead leading-relaxed text-gray-600">{{ t('kopf', 'text') }}</p>

                    <RequestStarter
                        class="mt-8"
                        :service-types="serviceTypes"
                        :initial-service="form.service_type_id"
                        :initial-postal-code="form.postal_code"
                        :action="null"
                        :cta-label="t('formular', 'cta_schritt_1', 'Jetzt Gutachter anfragen')"
                        @start="onStart"
                    />

                    <TrustRow class="pt-5" />
                </template>

                <!-- ── Step two: who to call ── -->
                <template v-else>
                    <h1 class="text-h2 font-bold text-navy-700">
                        {{ t('formular', 'schritt_2_titel', 'Fast geschafft!') }}
                    </h1>
                    <p class="measure-lead pt-3 text-lead leading-relaxed text-gray-600">
                        {{ t('formular', 'schritt_2_text', 'Bitte vervollständigen Sie Ihre Daten, damit wir einen passenden Gutachter für Sie vermitteln können.') }}
                    </p>

                    <!--
                        What they chose, in one quiet line. It is the only thing
                        carried over from the step before, and without it the
                        contact screen gives no sign that the first answer was
                        kept.
                    -->
                    <p v-if="service" class="flex flex-wrap items-center gap-x-2 gap-y-1 pt-4 text-sm text-gray-600">
                        <Check :size="15" :stroke-width="2" class="shrink-0 text-success" aria-hidden="true" />
                        <span class="font-medium text-navy-700">{{ service.name_de }}</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ form.postal_code }} {{ form.city }}</span>
                        <button
                            type="button"
                            class="border-b border-gray-300 text-gray-600 hover:border-navy-700 hover:text-navy-700"
                            @click="back"
                        >ändern</button>
                    </p>

                    <form class="mt-8 rounded-card border border-gray-200 bg-white p-5 sm:p-7" novalidate @submit.prevent="submit">
                        <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-6" />

                        <!-- Honeypot: visually and programmatically hidden -->
                        <div class="hidden" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" v-model="form.website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-5">
                            <BaseInput
                                id="customer_name"
                                v-model="form.customer_name"
                                label="Ihr Name"
                                placeholder="Vor- und Nachname"
                                autocomplete="name"
                                :error="form.errors.customer_name"
                                required
                            />
                            <BaseInput
                                id="customer_email"
                                v-model="form.customer_email"
                                label="E-Mail-Adresse"
                                type="email"
                                placeholder="E-Mail eingeben"
                                autocomplete="email"
                                :error="form.errors.customer_email"
                                required
                            />
                            <BaseInput
                                id="customer_phone"
                                v-model="form.customer_phone"
                                label="Telefonnummer"
                                placeholder="Telefonnummer eingeben"
                                autocomplete="tel"
                                hint="Für die direkte Kontaktaufnahme durch den Sachverständigen."
                                numeric
                                :error="form.errors.customer_phone"
                                required
                            />
                        </div>

                        <BaseButton
                            type="submit"
                            size="cta"
                            block
                            class="mt-7"
                            :loading="form.processing"
                            loading-label="Wird gesendet…"
                        >{{ t('formular', 'cta', 'Kostenfrei anfragen') }}</BaseButton>

                        <!--
                            The consent wording sits above the fold of the
                            button rather than behind a tick box, which is the
                            ordinary German pattern for a request somebody has
                            asked to be contacted about. The moment it was given
                            is still recorded against the request.
                        -->
                        <p class="measure pt-4 text-sm leading-normal text-gray-600">
                            {{ t('formular', 'datenschutzhinweis', 'Mit dem Absenden willigen Sie ein, dass DKGZ Ihre Angaben zur Vermittlung an geeignete Sachverständige verarbeitet.') }}
                            <a href="/datenschutz" class="border-b border-navy-700 pb-0.5 text-navy-700">Datenschutzerklärung</a>
                        </p>

                        <p class="flex items-start gap-2 pt-4 text-sm leading-normal text-gray-600">
                            <Check :size="15" :stroke-width="2" class="mt-0.5 shrink-0 text-success" aria-hidden="true" />
                            {{ t('formular', 'kurzhinweis', 'Ihre Anfrage ist kostenfrei und unverbindlich. Es entstehen für Sie keine Kosten.') }}
                        </p>
                    </form>

                    <p v-if="page.props.app?.phone" class="pt-6 text-center text-sm text-gray-600">
                        {{ t('seitenleiste', 'telefon_titel', 'Lieber telefonisch?') }}
                        <a
                            :href="`tel:${page.props.app.phone.replace(/\s/g, '')}`"
                            class="font-mono tabular-nums text-navy-700 underline underline-offset-2"
                        >{{ page.props.app.phone }}</a>
                    </p>
                </template>
            </div>
        </div>
    </RequestFlowLayout>
</template>
