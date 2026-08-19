<script setup>
import { ref } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import BaseCheckbox from '../../Components/Base/BaseCheckbox.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    token: { type: String, required: true },
    review: { type: Object, required: true },
    context: { type: Object, default: () => ({}) },
})

const page = usePage()
const { date } = useGermanFormat()
const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

// The server flashes this once a low rating has been recorded.
const feedbackStep = ref(Boolean(page.props.flash?.feedback_step) || (props.review.submitted && props.review.rating !== null && props.review.rating < 8))

const rating = useForm({ rating: props.review.rating ?? null, feedback: '' })
const feedback = useForm({ feedback_category: '', feedback: '', may_contact: true })

const categories = [
    'Erreichbarkeit des Sachverständigen',
    'Terminfindung',
    'Dauer bis zum Gutachten',
    'Qualität der Unterlagen',
    'Kommunikation',
    'Kosten',
    'Sonstiges',
].map((c) => ({ value: c, label: c }))
</script>

<template>
    <Head title="Bewertung" />

    <PublicLayout :sticky-cta="false">
        <div class="mx-auto w-full max-w-(--container-review) px-4 py-20 md:px-6">
            <p class="font-mono text-sm tabular-nums text-gray-600">
                <template v-if="feedbackStep">
                    {{ context.reference }} · Bewertung {{ review.rating ?? rating.rating }} von 10
                </template>
                <template v-else>
                    {{ context.reference }}<template v-if="context.service_type"> · {{ context.service_type }}</template><template v-if="context.city"> · {{ context.city }}</template>
                </template>
            </p>

            <!-- Step one: the rating -->
            <form v-if="!feedbackStep" novalidate @submit.prevent="rating.post(`/bewertung/${token}`)">
                <h1 class="pt-4 text-h2 font-semibold text-navy-700">{{ t('kopf', 'ueberschrift') }}</h1>
                <p class="measure pt-3 text-base leading-normal text-gray-600">{{ t('kopf', 'text') }}</p>

                <fieldset class="pt-8">
                    <legend class="sr-only">Bewertung von 1 bis 10</legend>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="n in 10"
                            :key="n"
                            type="button"
                            class="h-12 w-12 rounded-sm border text-base font-medium tabular-nums transition-colors duration-(--duration-hover) ease-(--ease-dkgz) focus-visible:outline-2 focus-visible:outline-navy-500 focus-visible:outline-offset-2"
                            :class="rating.rating === n
                                ? 'border-navy-700 bg-navy-700 text-white'
                                : 'border-gray-300 bg-white text-gray-800 hover:border-navy-700'"
                            :aria-pressed="rating.rating === n"
                            @click="rating.rating = n"
                        >{{ n }}</button>
                    </div>
                    <div class="flex measure-scale justify-between pt-2.5">
                        <span class="text-sm text-gray-400">{{ t('skala', 'min_label', 'Sehr unzufrieden') }}</span>
                        <span class="text-sm text-gray-400">{{ t('skala', 'max_label', 'Sehr zufrieden') }}</span>
                    </div>
                    <p v-if="rating.errors.rating" class="pt-2 text-xs text-danger" role="alert">{{ rating.errors.rating }}</p>
                </fieldset>

                <div class="pt-8">
                    <BaseTextarea v-model="rating.feedback" label="Anmerkung" placeholder="Was ist Ihnen aufgefallen?" :error="rating.errors.feedback" optional />
                </div>

                <BaseButton type="submit" size="cta" class="mt-6" :loading="rating.processing">{{ t('skala', 'cta', 'Bewertung absenden') }}</BaseButton>
            </form>

            <!-- Step two: only for a low rating, and only DKGZ quality assurance reads it -->
            <form v-else novalidate @submit.prevent="feedback.post(`/bewertung/${token}/feedback`)">
                <h1 class="pt-4 text-h2 font-semibold text-navy-700">{{ t('feedback', 'ueberschrift') }}</h1>
                <p class="measure pt-3 text-base leading-normal text-gray-600">{{ t('feedback', 'text') }}</p>

                <div class="mt-8 flex flex-col gap-5 rounded-card border border-gray-200 p-6 md:p-8">
                    <BaseSelect
                        v-model="feedback.feedback_category"
                        label="Woran lag es?"
                        :options="categories"
                        :error="feedback.errors.feedback_category"
                        hint="Weitere Kategorien: Terminfindung · Dauer bis zum Gutachten · Qualität der Unterlagen · Kommunikation · Kosten · Sonstiges"
                        required
                    />
                    <BaseTextarea v-model="feedback.feedback" label="Beschreibung" placeholder="Bitte schildern Sie kurz, was passiert ist." :rows="5" :error="feedback.errors.feedback" optional />
                    <BaseCheckbox v-model="feedback.may_contact">DKGZ darf mich zu diesem Vorgang telefonisch kontaktieren.</BaseCheckbox>
                </div>

                <div class="flex flex-wrap items-center gap-4 pt-6">
                    <BaseButton type="submit" size="cta" :loading="feedback.processing">{{ t('feedback', 'cta', 'Rückmeldung senden') }}</BaseButton>
                    <BaseButton :href="`/bewertung/${token}/danke`" variant="ghost" size="cta">Überspringen</BaseButton>
                </div>
            </form>
        </div>
    </PublicLayout>
</template>
