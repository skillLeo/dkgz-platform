<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ChevronDown, MapPin } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import { fill } from '../../Support/placeholders.js'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ServiceIcon from '../../Components/Domain/ServiceIcon.vue'

/**
 * Everything that can be arranged in one city.
 *
 * The hub above the individual service pages: it gives each of them a parent to
 * be linked from, and gives somebody who searched only for the city a page that
 * answers them.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    city: { type: Object, required: true },
    services: { type: Array, default: () => [] },
})

/** Editable copy with the city filled in. */
const t = (section, field, fallback = '') => fill(
    props.content?.[section]?.[field] || fallback,
    { stadt: props.city.name, bundesland: props.city.state },
)

const title = computed(() => props.city.meta_title
    || t('stadt', 'meta_titel', 'Kfz-Gutachter {stadt} — Sachverständigen finden | DKGZ'))

/** The steps, in order, skipping any the operator has emptied. */
const steps = computed(() => [1, 2, 3]
    .map((number) => ({ number, text: t('stadt', `schritt_${number}`) }))
    .filter((step) => step.text))

/**
 * The questions, city-specific ones first.
 *
 * A city with something of its own to answer says it before the three every
 * city page carries — and the shared three are still worth having, because a
 * page with no answers on it ranks like a page with nothing on it.
 */
const questions = computed(() => [
    ...(props.city.faqs ?? []).filter((entry) => entry?.frage && entry?.antwort),
    ...[1, 2, 3]
        .map((n) => ({ frage: t('stadt', `faq_${n}_frage`), antwort: t('stadt', `faq_${n}_antwort`) }))
        .filter((entry) => entry.frage && entry.antwort),
])

const openFaq = ref(null)

const description = computed(() => props.city.meta_description
    || t('stadt', 'meta_text', 'Kfz-Gutachter in {stadt} gesucht? DKGZ vermittelt Ihnen einen geprüften '
        + 'Sachverständigen in {stadt} und Umgebung. Kostenlos und unverbindlich.'))
</script>

<template>
    <Head>
        <title>{{ title }}</title>
        <meta name="description" :content="description">
        <link rel="canonical" :href="`https://dkgz.de/kfz-gutachter/${city.slug}`">
    </Head>

    <PublicLayout>
        <section class="border-b border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 md:py-20">
                <nav class="flex flex-wrap items-center gap-2 pb-6 text-sm text-gray-600" aria-label="Brotkrumen">
                    <Link href="/kfz-gutachter" class="hover:text-navy-700">Städte</Link>
                    <span aria-hidden="true">·</span>
                    <span class="text-gray-800">{{ city.name }}</span>
                </nav>

                <SectionLabel :text="city.label" />

                <h1 class="hyphens-auto break-words pt-6 text-h2 font-bold text-navy-700 sm:text-h1" lang="de">
                    {{ city.headline || t('stadt', 'ueberschrift', 'Kfz-Gutachter in {stadt}') }}
                </h1>

                <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">
                    {{ city.intro || t('stadt', 'text', 'Wir vermitteln Ihnen einen geprüften Kfz-Sachverständigen aus Ihrer Region — kostenlos und unverbindlich.') }}
                </p>

                <div class="flex flex-wrap items-center gap-4 pt-8">
                    <BaseButton href="/anfrage" size="cta">
                        {{ t('stadt', 'cta', 'Jetzt Gutachter anfragen') }}
                    </BaseButton>

                    <p v-if="city.partners" class="flex items-center gap-2 text-sm text-gray-600">
                        <MapPin :size="16" :stroke-width="1.5" class="shrink-0 text-navy-700" aria-hidden="true" />
                        {{ city.partners }} verfügbare Sachverständige rund um {{ city.name }}
                    </p>
                </div>
            </div>
        </section>

        <!--
            The city's own passage, where somebody has written one. The shared
            introduction and the box of notes beside it went: on a page that
            already carries the services, the steps and the questions they were
            one section too many, and the shared wording said the same thing on
            every city page anyway. What is left only appears when there is
            something particular to say.
        -->
        <section v-if="city.body" class="mx-auto w-full max-w-(--container-shell) px-4 pt-16 md:px-6">
            <div class="stadttext measure" v-html="city.body" />
        </section>

        <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6">
            <h2 class="text-h2 font-semibold text-navy-700">
                {{ t('stadt', 'leistungen_ueberschrift', 'Gutachten in {stadt}') }}
            </h2>

            <div class="grid grid-cols-1 gap-4 pt-8 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="service in services"
                    :key="service.url"
                    :href="service.url"
                    class="flex flex-col border border-gray-200 bg-white p-6 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:border-navy-700"
                >
                    <ServiceIcon :service="service" class="text-navy-700" />
                    <h3 class="pt-4 text-h4 font-semibold text-navy-700">
                        {{ service.name }} in {{ city.name }}
                    </h3>
                    <p class="flex-1 pt-2 text-base leading-normal text-gray-600">{{ service.description }}</p>
                    <span class="pt-5 text-base font-medium text-navy-700">Mehr erfahren →</span>
                </Link>
            </div>
        </div>

        <section v-if="steps.length" class="border-t border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6">
                <h2 class="text-h2 font-semibold text-navy-700">
                    {{ t('stadt', 'ablauf_ueberschrift', 'So kommen Sie in {stadt} zu Ihrem Gutachten') }}
                </h2>

                <!-- Numbered, because they happen in an order. -->
                <ol class="grid grid-cols-1 gap-8 pt-10 md:grid-cols-3">
                    <li v-for="step in steps" :key="step.number" class="border-t border-gray-200 pt-4">
                        <span class="grid h-8 w-8 -translate-y-8 place-items-center rounded-full bg-navy-700 font-mono text-sm font-semibold text-white">
                            {{ String(step.number).padStart(2, '0') }}
                        </span>
                        <p class="-mt-4 text-base leading-normal text-gray-800">{{ step.text }}</p>
                    </li>
                </ol>

                <BaseButton href="/anfrage" size="cta" class="mt-10">
                    {{ t('stadt', 'cta', 'Jetzt Gutachter anfragen') }}
                </BaseButton>
            </div>
        </section>

        <section v-if="questions.length" class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6">
            <h2 class="text-h2 font-semibold text-navy-700">
                {{ t('stadt', 'faq_ueberschrift', 'Häufige Fragen zum Kfz-Gutachten in {stadt}') }}
            </h2>

            <dl class="measure border-t border-gray-200 pt-2 mt-6">
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
                    <dd v-if="openFaq === index" class="whitespace-pre-line pb-5 text-base leading-normal text-gray-600">
                        {{ entry.antwort }}
                    </dd>
                </div>
            </dl>
        </section>
    </PublicLayout>
</template>

<style scoped>
/* The operator's own passage about this city, set like the article body. */
.stadttext :deep(p) {
    font-size: var(--text-base);
    line-height: 1.75;
    color: var(--color-gray-800);
    padding-bottom: 1rem;
}

.stadttext :deep(h3) {
    font-size: var(--text-lead);
    font-weight: 600;
    color: var(--color-navy-700);
    padding: 1rem 0 0.5rem;
}

.stadttext :deep(ul) { list-style: disc; padding: 0 0 1rem 1.5rem; }
.stadttext :deep(li) { font-size: var(--text-base); line-height: 1.75; color: var(--color-gray-800); padding-bottom: 0.375rem; }
.stadttext :deep(a) { color: var(--color-navy-700); border-bottom: 1px solid var(--color-gray-300); }
</style>
