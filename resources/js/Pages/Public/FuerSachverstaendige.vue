<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Check } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import MoneyValue from '../../Components/Data/MoneyValue.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    commissionRate: { type: Number, default: 15 },
    registrationOpen: { type: Boolean, default: true },
})

const { percent, money } = useGermanFormat()
const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

// The worked example from the client's brief: 850,00 € at 15 % leaves 722,50 €.
const exampleFee = 85_000
const exampleCommission = Math.round(exampleFee * props.commissionRate / 100)

const requirements = ['punkt_1', 'punkt_2', 'punkt_3', 'punkt_4', 'punkt_5', 'punkt_6']
</script>

<template>
    <Head title="Für Sachverständige" />

    <PublicLayout :sticky-cta="false">
        <section class="bg-navy-900">
            <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 gap-16 px-4 md:px-6 lg:grid-cols-2 lg:items-center">
                <div class="py-16 md:py-20">
                    <SectionLabel :text="t('hero', 'eyebrow', 'Partnernetz')" />
                    <h1 class="pt-6 text-h1 font-bold text-white">{{ t('hero', 'ueberschrift') }}</h1>
                    <p class="max-w-[52ch] pt-4 text-lead leading-relaxed text-white/72">{{ t('hero', 'text') }}</p>
                    <div class="flex flex-wrap items-center gap-4 pt-8">
                        <BaseButton v-if="registrationOpen" href="/registrieren" variant="inverted" size="cta">{{ t('hero', 'cta_primaer') }}</BaseButton>
                        <BaseButton href="/anmelden" variant="outlineInverted" size="cta">{{ t('hero', 'cta_sekundaer') }}</BaseButton>
                    </div>
                </div>
                <div class="hidden min-h-[420px] items-center py-10 lg:flex">
                    <div class="h-full min-h-[340px] w-full border border-white/14">
                        <img v-if="t('hero', 'bild')" :src="t('hero', 'bild')" alt="Sachverständiger bei der Schadenaufnahme" class="h-full w-full object-cover">
                    </div>
                </div>
            </div>
        </section>

        <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 gap-12 px-4 py-20 md:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,400px)] lg:items-start">
            <div class="min-w-0">
                <h2 class="text-h2 font-semibold text-navy-700">{{ t('provision', 'ueberschrift') }}</h2>
                <p class="measure pt-4 text-base leading-normal text-gray-600">{{ t('provision', 'text') }}</p>

                <table class="mt-8 w-full border-collapse border-t border-b border-gray-200">
                    <thead>
                        <tr>
                            <th scope="col" class="border-b border-gray-200 py-3 pr-4 text-left text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">Position</th>
                            <th scope="col" class="border-b border-gray-200 px-4 py-3 text-left text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">Regel</th>
                            <th scope="col" class="border-b border-gray-200 py-3 pl-4 text-right text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">Beispiel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border-b border-gray-100 py-3.5 pr-4 text-sm text-gray-800">Auftragswert (netto)</td>
                            <td class="border-b border-gray-100 px-4 py-3.5 text-sm text-gray-600">Vom Sachverständigen nach Abschluss erfasst</td>
                            <td class="border-b border-gray-100 py-3.5 pl-4 text-right"><MoneyValue :cents="exampleFee" /></td>
                        </tr>
                        <tr>
                            <td class="border-b border-gray-100 py-3.5 pr-4 text-sm text-gray-800">Vermittlungsprovision</td>
                            <td class="border-b border-gray-100 px-4 py-3.5 text-sm text-gray-600">{{ percent(commissionRate) }} des Netto-Honorars</td>
                            <td class="border-b border-gray-100 py-3.5 pl-4 text-right"><MoneyValue :cents="exampleCommission" /></td>
                        </tr>
                        <tr>
                            <td class="border-b border-gray-100 py-3.5 pr-4 text-sm text-gray-800">Abgelehnte Anfragen</td>
                            <td class="border-b border-gray-100 px-4 py-3.5 text-sm text-gray-600">Keine Berechnung, keine Nachteile bei der Verteilung</td>
                            <td class="border-b border-gray-100 py-3.5 pl-4 text-right"><MoneyValue :cents="0" /></td>
                        </tr>
                        <tr>
                            <td class="border-t-2 border-navy-700 py-3.5 pr-4 text-sm font-medium text-navy-700">Ihr Anteil im Beispiel</td>
                            <td class="border-t-2 border-navy-700 px-4 py-3.5 text-sm text-gray-600">Abrechnung monatlich, Sammelrechnung</td>
                            <td class="border-t-2 border-navy-700 py-3.5 pl-4 text-right"><MoneyValue :cents="exampleFee - exampleCommission" emphasis /></td>
                        </tr>
                    </tbody>
                </table>

                <p class="pt-12 text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">{{ t('voraussetzungen', 'ueberschrift') }}</p>
                <div class="grid grid-cols-1 gap-x-8 gap-y-3.5 pt-5 sm:grid-cols-2">
                    <div v-for="key in requirements" :key="key" class="flex min-w-0 gap-3">
                        <Check :size="20" :stroke-width="1.5" class="mt-0.5 shrink-0 text-navy-700" aria-hidden="true" />
                        <span class="min-w-0 text-base leading-normal text-gray-800 hyphens-auto">{{ t('voraussetzungen', key) }}</span>
                    </div>
                </div>
            </div>

            <aside class="rounded-card border border-navy-700 p-6 lg:sticky lg:top-24">
                <h2 class="text-h3 font-semibold text-navy-700">{{ t('aufnahme', 'ueberschrift') }}</h2>
                <p class="pt-3 text-base leading-normal text-gray-600">{{ t('aufnahme', 'text') }}</p>
                <BaseButton v-if="registrationOpen" href="/registrieren" size="cta" block class="mt-5">{{ t('aufnahme', 'cta', 'Registrierung starten') }}</BaseButton>
                <p v-else class="mt-5 rounded-sm border border-warning bg-warning/5 p-4 text-sm leading-normal text-gray-800">
                    Die Registrierung ist derzeit geschlossen. Wenden Sie sich für eine Aufnahme an die Administration.
                </p>
                <p class="pt-3 text-sm leading-normal text-gray-600">{{ t('aufnahme', 'hinweis') }}</p>
            </aside>
        </div>
    </PublicLayout>
</template>
