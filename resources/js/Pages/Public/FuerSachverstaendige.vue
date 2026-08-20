<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Check } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ImageSlot from '../../Components/Layout/ImageSlot.vue'

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    registrationOpen: { type: Boolean, default: true },
})

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback


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
                    <p class="measure-hero pt-4 text-lead leading-relaxed text-white/72">{{ t('hero', 'text') }}</p>
                    <div class="flex flex-wrap items-center gap-4 pt-8">
                        <BaseButton v-if="registrationOpen" href="/registrieren" variant="inverted" size="cta">{{ t('hero', 'cta_primaer') }}</BaseButton>
                        <BaseButton href="/anmelden" variant="outlineInverted" size="cta">{{ t('hero', 'cta_sekundaer') }}</BaseButton>
                    </div>
                </div>
                <div class="hidden min-h-(--size-hero-image) items-center py-10 lg:flex">
                    <div class="h-full min-h-(--size-hero-image-min) w-full border border-white/14">
                        <img v-if="t('hero', 'bild')" :src="t('hero', 'bild')" alt="Sachverständiger bei der Schadenaufnahme" class="h-full w-full object-cover">
                    </div>
                </div>
            </div>
        </section>

        <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 gap-12 px-4 py-20 md:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,400px)] lg:items-start">
            <div class="min-w-0">
                <h2 class="text-h2 font-semibold text-navy-700">{{ t('provision', 'ueberschrift') }}</h2>
                <p class="measure pt-4 text-base leading-normal text-gray-600">{{ t('provision', 'text') }}</p>

                <dl class="border-t border-gray-200">
                    <div class="grid gap-1 border-b border-gray-100 py-4 sm:grid-cols-[minmax(0,18rem)_minmax(0,1fr)] sm:gap-6">
                        <dt class="text-sm font-medium text-gray-800">Feste Gebühr je Gutachtenart</dt>
                        <dd class="measure text-sm leading-normal text-gray-600">
                            DKGZ berechnet keinen Prozentsatz Ihres Honorars, sondern einen festen Betrag je
                            vermitteltem Auftrag. Wie hoch er ist, hängt von der Art des Gutachtens ab.
                        </dd>
                    </div>
                    <div class="grid gap-1 border-b border-gray-100 py-4 sm:grid-cols-[minmax(0,18rem)_minmax(0,1fr)] sm:gap-6">
                        <dt class="text-sm font-medium text-gray-800">Vor Ihrer Entscheidung sichtbar</dt>
                        <dd class="measure text-sm leading-normal text-gray-600">
                            Der genaue Betrag steht bei jeder Anfrage, die Sie erreicht — bevor Sie annehmen.
                            Nach der Annahme ändert er sich für diesen Auftrag nicht mehr.
                        </dd>
                    </div>
                    <div class="grid gap-1 border-b border-gray-100 py-4 sm:grid-cols-[minmax(0,18rem)_minmax(0,1fr)] sm:gap-6">
                        <dt class="text-sm font-medium text-gray-800">Nur bei Abschluss</dt>
                        <dd class="measure text-sm leading-normal text-gray-600">
                            Keine Grundgebühr, keine Kosten pro Anfrage und keine Berechnung für Anfragen, die
                            Sie ablehnen. Eine Ablehnung wirkt sich nicht auf die weitere Verteilung aus.
                        </dd>
                    </div>
                    <div class="grid gap-1 py-4 sm:grid-cols-[minmax(0,18rem)_minmax(0,1fr)] sm:gap-6">
                        <dt class="text-sm font-medium text-gray-800">Abrechnung</dt>
                        <dd class="measure text-sm leading-normal text-gray-600">
                            Monatlich als Sammelrechnung über die im Berichtsmonat abgeschlossenen Aufträge.
                        </dd>
                    </div>
                </dl>

                <p class="pt-12 text-eyebrow font-semibold uppercase text-gray-600">{{ t('voraussetzungen', 'ueberschrift') }}</p>
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
