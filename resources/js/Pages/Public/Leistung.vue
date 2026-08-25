<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ChevronDown } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ServiceIcon from '../../Components/Domain/ServiceIcon.vue'
import { fill } from '../../Support/placeholders.js'

/**
 * One service, nationwide.
 *
 * The same shape as the city version of this page — heading, intro, the
 * service's own sections, the numbered route to getting one, then questions —
 * minus everything that only makes sense about a particular place. Two layouts
 * for the same thing would have drifted apart the first time either was
 * touched.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    serviceType: { type: Object, required: true },
    serviceTypes: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
})

const t = (section, field, fallback = '') => fill(
    props.content?.[section]?.[field] || fallback,
    { leistung: props.serviceType.name_de },
)

const openFaq = ref(null)

const title = computed(() => t('detail', 'meta_titel', '{leistung} — Kfz-Sachverständigen finden | DKGZ'))

const description = computed(() => props.serviceType.description_de
    || t('detail', 'meta_text', '{leistung} gesucht? DKGZ vermittelt Ihnen bundesweit einen geprüften '
        + 'Kfz-Sachverständigen. Kostenlos und unverbindlich.'))

/** Only the sections the service actually has copy for. */
const sections = computed(() => [
    { title: t('detail', 'abschnitt_enthalten', 'Was enthalten ist'), body: props.serviceType.includes_de },
    { title: t('detail', 'abschnitt_zielgruppe', 'Für wen geeignet'), body: props.serviceType.target_audience_de },
    { title: t('detail', 'abschnitt_situationen', 'Typische Situationen'), body: props.serviceType.typical_situations_de },
    { title: t('detail', 'abschnitt_abgrenzung', 'Abgrenzung'), body: props.serviceType.differences_de },
    { title: t('detail', 'abschnitt_hinweise', 'Weitere Hinweise'), body: props.serviceType.additional_info_de },
].filter((section) => section.body))

const steps = computed(() => [1, 2, 3]
    .map((number) => ({ number, text: t('detail', `schritt_${number}`) }))
    .filter((step) => step.text))

/** Questions about this service, not about DKGZ in general. */
const questions = computed(() => (props.serviceType.faqs ?? [])
    .filter((entry) => entry?.frage && entry?.antwort))

const others = computed(() => props.serviceTypes
    .filter((type) => type.slug !== props.serviceType.slug))
</script>

<template>
    <Head>
        <title>{{ title }}</title>
        <meta name="description" :content="description">
        <link rel="canonical" :href="`https://dkgz.de/leistungen/${serviceType.slug}`">
    </Head>

    <PublicLayout>
        <section class="border-b border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 md:py-20">
                <nav class="flex flex-wrap items-center gap-2 pb-6 text-sm text-gray-600" aria-label="Brotkrumen">
                    <Link href="/leistungen" class="hover:text-navy-700">Leistungen</Link>
                    <span aria-hidden="true">·</span>
                    <span class="text-gray-800">{{ serviceType.name_de }}</span>
                </nav>

                <SectionLabel :text="t('detail', 'eyebrow', 'Leistung')" />

                <h1 class="hyphens-auto break-words pt-6 text-h2 font-bold text-navy-700 sm:text-h1" lang="de">
                    {{ t('detail', 'ueberschrift', '{leistung}') }}
                </h1>

                <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">
                    {{ serviceType.description_de }}
                </p>

                <BaseButton href="/anfrage" size="cta" class="mt-8">
                    {{ t('detail', 'cta', '{leistung} anfragen') }}
                </BaseButton>
            </div>
        </section>

        <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 gap-12 px-4 py-16 md:px-6 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-start">
            <div class="min-w-0">
                <section v-for="section in sections" :key="section.title" class="pb-10">
                    <h2 class="text-h3 font-semibold text-navy-700">{{ section.title }}</h2>
                    <p class="measure whitespace-pre-line pt-3 text-base leading-normal text-gray-600">
                        {{ section.body }}
                    </p>
                </section>

                <section v-if="steps.length" class="border-t border-gray-200 pt-10">
                    <h2 class="text-h3 font-semibold text-navy-700">
                        {{ t('detail', 'ablauf_ueberschrift', 'So kommen Sie zum {leistung}') }}
                    </h2>

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
                        {{ t('detail', 'cta', '{leistung} anfragen') }}
                    </BaseButton>
                </section>

                <!--
                    Questions about this assessment specifically. The general FAQ
                    answers what DKGZ is; somebody reading this page wants to
                    know what this particular kind of report contains.
                -->
                <section v-if="questions.length" class="border-t border-gray-200 pt-10 mt-10">
                    <h2 class="text-h3 font-semibold text-navy-700">
                        {{ t('detail', 'faq_ueberschrift', 'Häufige Fragen zum {leistung}') }}
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
                <div class="rounded-card border border-navy-700 p-6">
                    <h2 class="text-h4 font-semibold text-navy-700">
                        {{ t('detail', 'seitenleiste_titel', '{leistung} anfragen') }}
                    </h2>
                    <ul class="flex flex-col gap-3 pt-4">
                        <li
                            v-for="n in [1, 2, 3]"
                            :key="n"
                            class="text-sm leading-normal text-gray-800"
                        >{{ t('detail', `punkt_${n}`) }}</li>
                    </ul>
                    <BaseButton href="/anfrage" size="cta" block class="mt-5">
                        {{ t('detail', 'cta', '{leistung} anfragen') }}
                    </BaseButton>
                </div>

                <div v-if="others.length">
                    <p class="text-eyebrow font-semibold uppercase text-gray-600">
                        {{ t('detail', 'weitere', 'Weitere Leistungen') }}
                    </p>
                    <ul class="flex flex-col gap-2.5 pt-3">
                        <li v-for="type in others" :key="type.slug">
                            <Link
                                :href="`/leistungen/${type.slug}`"
                                class="inline-flex items-center gap-2.5 text-sm text-navy-700 hover:text-navy-500"
                            >
                                <ServiceIcon :service="type" :size="16" class="shrink-0 text-gray-400" />
                                {{ type.name_de }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <div v-if="cities.length">
                    <p class="text-eyebrow font-semibold uppercase text-gray-600">
                        {{ t('detail', 'staedte', '{leistung} nach Stadt') }}
                    </p>
                    <ul class="flex flex-col gap-2 pt-3">
                        <li v-for="city in cities" :key="city.url">
                            <Link :href="city.url" class="text-sm text-navy-700 hover:text-navy-500">
                                {{ city.name }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </PublicLayout>
</template>
