<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { Check, Clock, Loader2 } from 'lucide-vue-next'
import RequestFlowLayout from '../../Layouts/RequestFlowLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import RequestProgress from '../../Components/Domain/RequestProgress.vue'
import RequestStarter from '../../Components/Domain/RequestStarter.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { fill } from '../../Support/placeholders.js'

/**
 * Two steps: which assessment, then where and who.
 *
 * It used to be three, and the middle one asked for the make, the model, the
 * year, the plate, a description of what happened and photographs — a screen of
 * work between somebody arriving from an advert and the point where DKGZ has
 * their telephone number. The assessor telephones them and asks all of it
 * anyway.
 *
 * The first step is the same component as the homepage hero, so somebody who
 * started at the top and somebody who clicked "Gutachter anfragen" from a
 * service page meet the same question. Arriving with the service already chosen
 * — which is what every service page now does — skips it entirely.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    serviceTypes: { type: Array, default: () => [] },
    /** Filled in when the visitor arrives having already chosen. */
    selected: { type: Object, default: () => ({}) },
})

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

const form = useForm({
    service_type_id: props.selected.service_type_id ?? '',
    postal_code: '',
    city: '',
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
    { number: 1, label: 'Leistung', short: 'Leistung' },
    { number: 2, label: 'Standort und Kontakt', short: 'Kontakt' },
]

// A service already chosen means the first step has nothing left to ask.
const started = ref(Boolean(props.selected.service_type_id))
const step = computed(() => (started.value ? 2 : 1))

const service = computed(() => props.serviceTypes
    .find((type) => String(type.id) === String(form.service_type_id)) ?? null)

/** Answering the first step here rather than arriving with it answered. */
const onStart = ({ service_type_id }) => {
    form.service_type_id = service_type_id
    started.value = true

    // Counted once: going back and forward again is the same person on the
    // same form, not a second one reaching the step.
    //
    // Sent with axios rather than through Inertia's router. The endpoint
    // answers with JSON, and Inertia treats any response that is not an Inertia
    // response as a failure — it put an error dialog on screen at exactly the
    // moment somebody moved to the second step. Nothing here needs the page to
    // change, and a counter that cannot be recorded must never interrupt
    // somebody filling the form in.
    axios.post('/anfrage/schritt', { step: 'schritt_2' }).catch(() => {})

    document.activeElement?.blur?.()
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

/** Back to the first step, with what was chosen still chosen. */
const back = () => {
    started.value = false
    form.clearErrors()
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

/**
 * The town, looked up rather than typed.
 *
 * Asking for a postal code and a town invited them to disagree, and the code is
 * the one the matching runs on. Showing the town back is what makes the code
 * feel checked rather than merely entered.
 */
const resolving = ref(false)
const unknown = ref(false)

let timer = null

const lookUp = async (code) => {
    resolving.value = true
    unknown.value = false

    try {
        const response = await fetch(`/api/plz/${code}`, { headers: { Accept: 'application/json' } })
        const data = response.ok ? await response.json() : null

        if (data?.city) {
            form.city = data.city
        } else {
            form.city = ''
            unknown.value = true
        }
    } catch {
        // A lookup that cannot reach the server must not trap anybody on this
        // screen. The code goes through and the server checks it on submit.
        form.city = ''
        unknown.value = false
    } finally {
        resolving.value = false
    }
}

const onPostalInput = (value) => {
    form.postal_code = String(value).replace(/\D/g, '').slice(0, 5)
    form.city = ''
    unknown.value = false

    if (timer) clearTimeout(timer)
    if (form.postal_code.length !== 5) return

    timer = setTimeout(() => lookUp(form.postal_code), 250)
}

watch(() => form.postal_code, (value) => { if (value === '') unknown.value = false })

/**
 * What happens next, said before they press the button rather than after.
 *
 * Somebody has just typed their telephone number into a site they met a minute
 * ago and the next thing that happens is a stranger ringing it. Saying who will
 * ring, from where and how soon is the difference between that being reassuring
 * and being a surprise — and it is the last thing they read before deciding.
 */
const responseNote = computed(() => fill(
    t('formular', 'rueckmeldung', 'Ein Sachverständiger aus {stadt} meldet sich in der Regel innerhalb weniger Minuten telefonisch bei Ihnen.'),
    { stadt: form.city, plz: form.postal_code },
))

const REQUIRED = {
    postal_code: 'Bitte geben Sie die Postleitzahl an.',
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
    <RequestFlowLayout title="Anfrage" :dirty="form.isDirty">
        <template v-if="started" #progress>
            <RequestProgress :steps="STEPS" :current="step" :furthest="step" @go="(n) => n === 1 && back()" />
        </template>

        <div class="bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-prose) px-4 py-12 md:px-6 md:py-16">
                <!-- ── Step one: which assessment ── -->
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
                        :action="null"
                        :title="t('formular', 'cta_schritt_1', 'Jetzt Gutachter anfragen')"
                        :cta-label="t('formular', 'weiter', 'Weiter')"
                        :service-label="t('formular', 'frage_leistung', 'Welche Gutachtenart benötigen Sie?')"
                        :service-hint="t('formular', 'frage_hinweis', 'Wählen Sie die passende Leistung aus, damit wir den richtigen Sachverständigen für Sie finden.')"
                        :hint="t('formular', 'hinweis_schritt_1')"
                        @start="onStart"
                    />
                </template>

                <!-- ── Step two: where the car is, and who to call ── -->
                <template v-else>
                    <h1 class="text-h2 font-bold text-navy-700">
                        {{ t('formular', 'schritt_2_titel', 'Fast geschafft!') }}
                    </h1>
                    <p class="measure-lead pt-3 text-lead leading-relaxed text-gray-600">
                        {{ t('formular', 'schritt_2_text', 'Bitte vervollständigen Sie Ihre Daten, damit wir einen passenden Gutachter für Sie vermitteln können.') }}
                    </p>

                    <!--
                        What they chose, in one quiet line, with the way back to
                        change it. This is the only route back now that the
                        header carries a mark rather than an arrow — and it is
                        the more discoverable of the two, because it sits beside
                        the thing it would change.
                    -->
                    <p v-if="service" class="flex flex-wrap items-center gap-x-2 gap-y-1 pt-4 text-sm text-gray-600">
                        <Check :size="15" :stroke-width="2" class="shrink-0 text-success" aria-hidden="true" />
                        <span class="font-medium text-navy-700">{{ service.name_de }}</span>
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
                            <div>
                                <BaseInput
                                    id="postal_code"
                                    :model-value="form.postal_code"
                                    :label="t('formular', 'frage_plz', 'Postleitzahl des Fahrzeugstandorts')"
                                    placeholder="40210"
                                    inputmode="numeric"
                                    autocomplete="postal-code"
                                    maxlength="5"
                                    numeric
                                    required
                                    :error="form.errors.postal_code"
                                    @update:model-value="onPostalInput"
                                />

                                <p v-if="resolving" class="flex items-center gap-2 pt-2.5 text-sm text-gray-600">
                                    <Loader2 :size="15" :stroke-width="1.75" class="shrink-0 animate-spin" aria-hidden="true" />
                                    Ort wird ermittelt…
                                </p>
                                <p
                                    v-else-if="form.city"
                                    class="flex items-center gap-2 pt-2.5 text-sm font-medium text-success"
                                    style="animation: dkgz-enter 220ms cubic-bezier(0.4,0,0.2,1) both"
                                >
                                    <Check :size="16" :stroke-width="2" class="shrink-0" aria-hidden="true" />
                                    {{ form.postal_code }} {{ form.city }}
                                </p>
                                <p v-else-if="unknown" class="pt-2.5 text-sm text-danger">
                                    Diese Postleitzahl konnten wir nicht zuordnen. Bitte prüfen Sie Ihre Eingabe.
                                </p>
                            </div>

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

                        <!--
                            Only once the town is known, because "ein
                            Sachverständiger aus  meldet sich" is worse than
                            saying nothing.
                        -->
                        <p
                            v-if="form.city && responseNote"
                            class="mt-7 flex items-start gap-2.5 rounded-card border border-gray-200 bg-gray-50 p-4 text-sm leading-normal text-gray-800"
                            style="animation: dkgz-enter 260ms cubic-bezier(0.4,0,0.2,1) both"
                        >
                            <Clock :size="16" :stroke-width="1.75" class="mt-0.5 shrink-0" style="color: var(--dkgz-accent)" aria-hidden="true" />
                            <span>{{ responseNote }}</span>
                        </p>

                        <BaseButton
                            type="submit"
                            size="cta"
                            block
                            :class="form.city && responseNote ? 'mt-4' : 'mt-7'"
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
                </template>
            </div>
        </div>
    </RequestFlowLayout>
</template>
