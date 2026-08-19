<script setup>
import { Head } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

const props = defineProps({ content: { type: Object, default: () => ({}) } })

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback
</script>

<template>
    <Head title="Über uns" />

    <PublicLayout>
        <div class="mx-auto w-full max-w-(--container-shell) px-4 py-20 md:px-6">
            <SectionLabel text="Über DKGZ" />
            <h1 class="pt-6 text-h1 font-bold text-navy-700">{{ t('kopf', 'ueberschrift') }}</h1>
            <p class="measure pt-6 text-lead leading-relaxed text-gray-800">{{ t('kopf', 'text') }}</p>

            <div class="grid grid-cols-1 gap-8 pt-16 md:grid-cols-3">
                <div v-for="item in [
                    { title: 'Kein Vergleichsportal', text: 'Wir stellen keine Angebote gegenüber. Der erste verfügbare Sachverständige übernimmt, damit Sie nicht warten müssen.' },
                    { title: 'Kein eigener Gutachter', text: 'DKGZ erstellt keine Gutachten. Die Begutachtung erbringt der vermittelte Sachverständige in eigener Verantwortung.' },
                    { title: 'Kostenfrei für Anfragende', text: 'Die Vermittlung ist für Sie kostenlos. Die Kosten der Begutachtung rechnet der Sachverständige direkt ab.' },
                ]" :key="item.title" class="border-t border-gray-200 pt-5">
                    <h2 class="text-h4 font-semibold text-navy-700">{{ item.title }}</h2>
                    <p class="pt-2 text-base leading-normal text-gray-600">{{ item.text }}</p>
                </div>
            </div>

            <div class="mt-16 border border-navy-700 p-8">
                <h2 class="text-h2 font-semibold text-navy-700">Sie sind Kfz-Sachverständiger?</h2>
                <p class="measure pt-3 text-base leading-normal text-gray-600">
                    Werden Sie Teil des Partnernetzes und erhalten Sie passende Anfragen aus Ihrem Einsatzgebiet — ohne
                    Grundgebühr und ohne Vertragslaufzeit.
                </p>
                <BaseButton href="/fuer-sachverstaendige" size="cta" class="mt-6">Zum Partnerbereich</BaseButton>
            </div>
        </div>
    </PublicLayout>
</template>
