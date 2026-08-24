<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'

/**
 * Every city with pages of its own.
 *
 * Chiefly a parent for the city pages so none of them is orphaned, and a place
 * for a search engine to find all of them at once.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    cities: { type: Array, default: () => [] },
})

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback
</script>

<template>
    <Head>
        <title>{{ t('uebersicht', 'meta_titel', 'Kfz-Gutachter nach Stadt — DKGZ') }}</title>
        <meta name="description" :content="t('uebersicht', 'meta_text', 'Geprüfte Kfz-Sachverständige in Ihrer Stadt finden. DKGZ vermittelt bundesweit, kostenlos und unverbindlich.')">
    </Head>

    <PublicLayout>
        <div class="mx-auto w-full max-w-(--container-shell) px-4 py-20 md:px-6">
            <SectionLabel :text="t('uebersicht', 'eyebrow', 'Regionen')" />

            <h1 class="pt-6 text-h1 font-bold text-navy-700">
                {{ t('uebersicht', 'ueberschrift', 'Kfz-Gutachter nach Stadt') }}
            </h1>

            <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">
                {{ t('uebersicht', 'text', 'Wählen Sie Ihre Stadt. Wir vermitteln in ganz Deutschland — auch dort, wo noch keine eigene Seite besteht.') }}
            </p>

            <ul v-if="cities.length" class="grid grid-cols-1 gap-4 pt-12 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="city in cities" :key="city.url">
                    <Link
                        :href="city.url"
                        class="flex flex-col border border-gray-200 bg-white p-5 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:border-navy-700"
                    >
                        <span class="text-h4 font-semibold text-navy-700">{{ city.name }}</span>
                        <span v-if="city.state" class="pt-1 text-sm text-gray-600">{{ city.state }}</span>
                        <span class="pt-3 text-sm text-gray-600">{{ city.services }} Leistungen</span>
                    </Link>
                </li>
            </ul>

            <p v-else class="measure pt-10 text-base leading-normal text-gray-600">
                {{ t('uebersicht', 'leer', 'Es sind noch keine Städte hinterlegt. Eine Anfrage können Sie trotzdem jederzeit stellen — wir vermitteln bundesweit.') }}
            </p>
        </div>
    </PublicLayout>
</template>
