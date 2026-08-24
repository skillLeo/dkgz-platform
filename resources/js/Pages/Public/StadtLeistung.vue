<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Check, MapPin } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
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

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

const title = computed(() => props.city.meta_title
    || `${props.serviceType.name_de} ${props.city.name} — Kfz-Sachverständige finden`)

const description = computed(() => props.city.meta_description
    || `${props.serviceType.name_de} in ${props.city.name}: DKGZ vermittelt geprüfte Kfz-Sachverständige `
        + 'in Ihrer Region. Kostenlos und unverbindlich anfragen.')

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

                <h1 class="pt-6 text-h1 font-bold text-navy-700">
                    {{ serviceType.name_de }} in {{ city.name }}
                </h1>

                <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">
                    {{ city.intro || serviceType.description_de }}
                </p>

                <div class="flex flex-wrap items-center gap-4 pt-8">
                    <BaseButton href="/anfrage" size="cta">
                        {{ t('leistung', 'cta', 'Jetzt Gutachter anfragen') }}
                    </BaseButton>

                    <p v-if="city.partners" class="flex items-center gap-2 text-sm text-gray-600">
                        <MapPin :size="16" :stroke-width="1.5" class="shrink-0 text-navy-700" aria-hidden="true" />
                        {{ city.partners }} verfügbare Sachverständige im Gebiet {{ city.postal_code }}
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
                        {{ t('leistung', 'ablauf_ueberschrift', 'So läuft die Vermittlung') }}
                    </h2>
                    <ol class="flex flex-col gap-3 pt-4">
                        <li v-for="n in [1, 2, 3]" :key="n" class="flex gap-3">
                            <Check :size="18" :stroke-width="1.5" class="mt-0.5 shrink-0 text-navy-700" aria-hidden="true" />
                            <span class="measure text-base leading-normal text-gray-800">
                                {{ t('leistung', `schritt_${n}`) }}
                            </span>
                        </li>
                    </ol>
                </section>
            </div>

            <aside class="flex flex-col gap-8">
                <div v-if="otherServices.length">
                    <p class="text-eyebrow font-semibold uppercase text-gray-600">
                        Weitere Leistungen in {{ city.name }}
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
                        {{ serviceType.name_de }} andernorts
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
