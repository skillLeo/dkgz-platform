<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { FileText, MapPin } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import { fill } from '../../Support/placeholders.js'
import BaseButton from '../../Components/Base/BaseButton.vue'

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
                        {{ t('stadt', 'cta', 'Gutachter in {stadt} anfragen') }}
                    </BaseButton>

                    <p v-if="city.partners" class="flex items-center gap-2 text-sm text-gray-600">
                        <MapPin :size="16" :stroke-width="1.5" class="shrink-0 text-navy-700" aria-hidden="true" />
                        {{ city.partners }} verfügbare Sachverständige rund um {{ city.name }}
                    </p>
                </div>
            </div>
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
                    <FileText :size="20" :stroke-width="1.5" class="text-navy-700" aria-hidden="true" />
                    <h3 class="pt-4 text-h4 font-semibold text-navy-700">
                        {{ service.name }} in {{ city.name }}
                    </h3>
                    <p class="flex-1 pt-2 text-base leading-normal text-gray-600">{{ service.description }}</p>
                    <span class="pt-5 text-base font-medium text-navy-700">Mehr erfahren →</span>
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
