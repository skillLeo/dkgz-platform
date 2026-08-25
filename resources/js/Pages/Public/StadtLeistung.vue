<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Check, ChevronDown, MapPin } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import { fill } from '../../Support/placeholders.js'
import BaseButton from '../../Components/Base/BaseButton.vue'

/**
 * One service in one city — the page this whole structure exists for.
 *
 * Somebody searching "Unfallgutachten Düsseldorf" should land on a page about
 * exactly that. Which means it has to say something true rather than swap a
 * name into a sentence: the service's own description, how many partners can
 * actually be sent work there, and whatever the operator has written about the
 * city. Every page links onward to the other services here and the same service
 * elsewhere, so none of them is a dead end.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    city: { type: Object, required: true },
    serviceType: { type: Object, required: true },
    otherServices: { type: Array, default: () => [] },
    otherCities: { type: Array, default: () => [] },
})

/**
 * Editable copy with the city and service filled in.
 *
 * Every string on this page goes through here, so the wording stays the
 * operator's to change while still naming the place and the service — which is
 * what makes the page worth finding.
 */
const t = (section, field, fallback = '') => fill(
    props.content?.[section]?.[field] || fallback,
    { stadt: props.city.name, leistung: props.serviceType.name_de, bundesland: props.city.state },
)

const title = computed(() => props.city.meta_title
    || t('leistung', 'meta_titel', '{leistung} in {stadt} — Kfz-Sachverständigen finden | DKGZ'))

const description = computed(() => props.city.meta_description
    || t('leistung', 'meta_text', '{leistung} in {stadt} gesucht? DKGZ vermittelt Ihnen einen geprüften '
        + 'Kfz-Sachverständigen in {stadt} und Umgebung. Kostenlos, unverbindlich und ohne Registrierung.'))

/** Questions belonging to this service, shown here as on its own page. */
const openFaq = ref(null)

const questions = computed(() => (props.serviceType.faqs ?? [])
    .filter((entry) => entry?.frage && entry?.antwort))

/** The reassurance points, skipping any the operator has emptied. */
const points = computed(() => [1, 2, 3]
    .map((n) => t('leistung', `punkt_${n}`))
    .filter(Boolean))

/** The steps, in order, skipping any the operator has emptied. */
const steps = computed(() => [1, 2, 3]
    .map((number) => ({ number, text: t('leistung', `schritt_${number}`) }))
    .filter((step) => step.text))

/** Only the sections the service actually has copy for. */
const sections = computed(() => [
    { title: 'Was enthalten ist', body: props.serviceType.includes_de },
    { title: 'Für wen geeignet', body: props.serviceType.target_audience_de },
    { title: 'Typische Situationen', body: props.serviceType.typical_situations_de },
    { title: 'Abgrenzung', body: props.serviceType.differences_de },
    { title: 'Weitere Hinweise', body: props.serviceType.additional_info_de },
].filter((section) => section.body))
</script>

<template>
    <Head>
        <title>{{ title }}</title>
        <meta name="description" :content="description">
        <link rel="canonical" :href="`https://dkgz.de/kfz-gutachter/${city.slug}/${serviceType.slug}`">
    </Head>

    <PublicLayout>
        <section class="border-b border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 md:py-20">
                <!-- Breadcrumb: the route in, and a way back up. -->
                <nav class="flex flex-wrap items-center gap-2 pb-6 text-sm text-gray-600" aria-label="Brotkrumen">
                    <Link href="/kfz-gutachter" class="hover:text-navy-700">Städte</Link>
                    <span aria-hidden="true">·</span>
                    <Link :href="city.url" class="hover:text-navy-700">{{ city.name }}</Link>
                    <span aria-hidden="true">·</span>
                    <span class="text-gray-800">{{ serviceType.name_de }}</span>
                </nav>

                <SectionLabel :text="city.label" />

                <!--
                    A compound like "Fahrzeugschadengutachten in Düsseldorf" is
                    longer than a phone is wide at 40px, so it stepped outside
                    the screen. It steps down a size below sm and is allowed to
                    hyphenate, which German needs more than most languages.
                -->
                <h1 class="hyphens-auto break-words pt-6 text-h2 font-bold text-navy-700 sm:text-h1" lang="de">
                    {{ t('leistung', 'ueberschrift', '{leistung} in {stadt}') }}
                </h1>

                <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">
                    {{ city.intro || t('leistung', 'einleitung') || serviceType.description_de }}
                </p>

                <div class="flex flex-wrap items-center gap-4 pt-8">
                    <BaseButton href="/anfrage" size="cta">
                        {{ t('leistung', 'cta', 'Jetzt Gutachter anfragen') }}
                    </BaseButton>

                    <p v-if="city.partners" class="flex items-center gap-2 text-sm text-gray-600">
                        <MapPin :size="16" :stroke-width="1.5" class="shrink-0 text-navy-700" aria-hidden="true" />
                        {{ city.partners }} verfügbare Sachverständige rund um {{ city.name }}
                    </p>
                </div>
            </div>
        </section>

        <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 gap-12 px-4 py-16 md:px-6 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-start">
            <div class="min-w-0">
                <section v-for="section in sections" :key="section.title" class="pb-10 last:pb-0">
                    <h2 class="text-h3 font-semibold text-navy-700">{{ section.title }}</h2>
                    <p class="measure whitespace-pre-line pt-3 text-base leading-normal text-gray-600">
                        {{ section.body }}
                    </p>
                </section>

                <section class="border-t border-gray-200 pt-10">
                    <h2 class="text-h3 font-semibold text-navy-700">
                        {{ t('leistung', 'ablauf_ueberschrift', 'So kommen Sie zum {leistung} in {stadt}') }}
                    </h2>

                    <!--
                        Numbered, because they happen in an order. They were
                        ticks, which reads as a list of things already done.
                    -->
                    <ol class="flex flex-col gap-5 pt-6">
                        <li v-for="step in steps" :key="step.number" class="flex gap-4">
                            <span
                                class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-navy-100 font-mono text-sm font-semibold text-navy-700"
                                aria-hidden="true"
                            >{{ step.number }}</span>
                            <span class="measure pt-1 text-base leading-normal text-gray-800">{{ step.text }}</span>
                        </li>
                    </ol>

                    <BaseButton href="/anfrage" size="cta" class="mt-8">
                        {{ t('leistung', 'cta', 'Jetzt Gutachter anfragen') }}
                    </BaseButton>
                </section>

                <section v-if="questions.length" class="border-t border-gray-200 pt-10 mt-10">
                    <h2 class="text-h3 font-semibold text-navy-700">
                        {{ t('leistung', 'faq_ueberschrift', 'Häufige Fragen zum {leistung}') }}
                    </h2>

                    <dl class="border-t border-gray-200 pt-2 mt-4">
                        <div v-for="(entry, index) in questions" :key="index" class="border-b border-gray-200">
                            <dt>
                                <button
                                    type="button"
                                    class="flex w-full items-start justify-between gap-4 py-5 text-left"
                                    :aria-expanded="openFaq === index"
                                    @click="openFaq = openFaq === index ? null : index"
                                >
                                    <span class="text-base font-medium text-navy-700">{{ entry.frage }}</span>
                                    <ChevronDown
                                        :size="18"
                                        :stroke-width="1.5"
                                        class="mt-0.5 shrink-0 text-gray-600 transition-transform duration-(--duration-disclosure) ease-(--ease-dkgz)"
                                        :class="openFaq === index ? 'rotate-180' : ''"
                                        aria-hidden="true"
                                    />
                                </button>
                            </dt>
                            <dd v-if="openFaq === index" class="measure whitespace-pre-line pb-5 text-base leading-normal text-gray-600">
                                {{ entry.antwort }}
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>

            <aside class="flex flex-col gap-8">
                <!--
                    The same box the nationwide service page carries. It was the
                    one thing the city version lacked, so the page that is meant
                    to convert had less of a call to action than the general one.
                -->
                <div class="rounded-card border border-navy-700 p-6">
                    <h2 class="text-h4 font-semibold text-navy-700">
                        {{ t('leistung', 'seitenleiste_titel', '{leistung} in {stadt} anfragen') }}
                    </h2>
                    <ul v-if="points.length" class="flex flex-col gap-3 pt-4">
                        <li v-for="point in points" :key="point" class="flex gap-2.5">
                            <Check :size="18" :stroke-width="1.5" class="mt-0.5 shrink-0 text-navy-700" aria-hidden="true" />
                            <span class="text-sm leading-normal text-gray-800">{{ point }}</span>
                        </li>
                    </ul>
                    <BaseButton href="/anfrage" size="cta" block class="mt-5">
                        {{ t('leistung', 'cta', 'Jetzt Gutachter anfragen') }}
                    </BaseButton>
                </div>

                <div v-if="otherServices.length">
                    <p class="text-eyebrow font-semibold uppercase text-gray-600">
                        {{ t('leistung', 'weitere_leistungen', 'Weitere Gutachten in {stadt}') }}
                    </p>
                    <ul class="flex flex-col gap-2 pt-3">
                        <li v-for="entry in otherServices" :key="entry.url">
                            <Link :href="entry.url" class="text-sm text-navy-700 hover:text-navy-500">
                                {{ entry.name }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <div v-if="otherCities.length">
                    <p class="text-eyebrow font-semibold uppercase text-gray-600">
                        {{ t('leistung', 'weitere_staedte', '{leistung} in anderen Städten') }}
                    </p>
                    <ul class="flex flex-col gap-2 pt-3">
                        <li v-for="entry in otherCities" :key="entry.url">
                            <Link :href="entry.url" class="text-sm text-navy-700 hover:text-navy-500">
                                {{ entry.name }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </PublicLayout>
</template>
