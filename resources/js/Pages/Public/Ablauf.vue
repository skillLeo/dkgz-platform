<script setup>
import { Head } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    homeContent: { type: Object, default: () => ({}) },
})

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback
const h = (section, field, fallback = '') => props.homeContent?.[section]?.[field] ?? fallback
</script>

<template>
    <Head title="Ablauf" />

    <PublicLayout>
        <div class="mx-auto w-full max-w-(--container-shell) px-4 py-20 md:px-6">
            <SectionLabel text="Ablauf" />
            <h1 class="pt-6 text-h1 font-bold text-navy-700">{{ t('kopf', 'ueberschrift', 'So funktioniert die Vermittlung') }}</h1>
            <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">{{ t('kopf', 'text') }}</p>

            <ol class="border-t border-gray-200 pt-2 mt-12">
                <li v-for="n in [1, 2, 3]" :key="n" class="grid grid-cols-[40px_minmax(0,1fr)] gap-5 border-b border-gray-200 py-8">
                    <span class="pt-1 font-mono text-lead tabular-nums text-gray-400">0{{ n }}</span>
                    <div>
                        <h2 class="text-h3 font-semibold text-navy-700">{{ h('ablauf', `schritt_${n}_titel`) }}</h2>
                        <p class="measure pt-2 text-base leading-normal text-gray-600">{{ h('ablauf', `schritt_${n}_text`) }}</p>
                    </div>
                </li>
            </ol>

            <div class="mt-12 border border-navy-700 p-8">
                <h2 class="text-h2 font-semibold text-navy-700">Was danach passiert</h2>
                <p class="measure pt-3 text-base leading-normal text-gray-600">
                    Ab der Annahme läuft die Abstimmung unmittelbar zwischen Ihnen und dem Sachverständigen. Er nimmt
                    den Schaden auf, erstellt das Gutachten und rechnet direkt mit Ihnen oder Ihrer Versicherung ab.
                    DKGZ tritt dabei nicht mehr dazwischen.
                </p>
                <BaseButton href="/anfrage" size="cta" class="mt-6">Anfrage starten</BaseButton>
            </div>
        </div>
    </PublicLayout>
</template>
