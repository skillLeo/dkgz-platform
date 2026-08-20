<script setup>
import { computed } from 'vue'

import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Check, ChevronDown } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

const props = defineProps({
    serviceType: { type: Object, required: true },
    serviceTypes: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
})

const openFaq = ref(null)

/** Only the sections the client has actually filled in. */
const contentBlocks = computed(() => [
    { title: 'Was enthalten ist', text: props.serviceType.includes_de },
    { title: 'Für wen geeignet', text: props.serviceType.target_audience_de },
    { title: 'Typische Situationen', text: props.serviceType.typical_situations_de },
    { title: 'Abgrenzung zu anderen Leistungen', text: props.serviceType.differences_de },
    { title: 'Gut zu wissen', text: props.serviceType.additional_info_de },
].filter((block) => block.text))
</script>

<template>
    <Head :title="serviceType.name_de" />

    <PublicLayout>
        <section class="border-b border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 md:py-20">
                <SectionLabel text="Leistung" />
                <h1 class="pt-6 text-h1 font-bold text-navy-700">{{ serviceType.name_de }}</h1>
                <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">{{ serviceType.description_de }}</p>
                <BaseButton href="/anfrage" size="cta" class="mt-8">Gutachter finden</BaseButton>
            </div>
        </section>

        <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 gap-12 px-4 py-16 md:px-6 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-start">
            <div class="min-w-0">
                <h2 class="text-h2 font-semibold text-navy-700">So läuft die Vermittlung</h2>
                <ol class="border-t border-gray-200 pt-2">
                    <li v-for="(step, index) in [
                        { title: 'Kostenlose Anfrage stellen', text: 'Sie nennen uns Art des Gutachtens, den Standort des Fahrzeugs und Ihre Kontaktdaten.' },
                        { title: 'Passenden Sachverständigen vermitteln', text: 'Die Anfrage geht automatisch an alle Partner, deren Einsatzgebiet Ihre Postleitzahl abdeckt.' },
                        { title: 'Sachverständiger nimmt an', text: 'Der erste verfügbare Partner übernimmt und meldet sich direkt bei Ihnen zur Terminabstimmung.' },
                    ]" :key="index" class="grid grid-cols-[32px_minmax(0,1fr)] gap-4 border-b border-gray-200 py-5">
                        <span class="pt-0.5 font-mono text-sm tabular-nums text-gray-400">0{{ index + 1 }}</span>
                        <div>
                            <h3 class="text-lead font-semibold text-navy-700">{{ step.title }}</h3>
                            <p class="measure pt-1 text-base leading-normal text-gray-600">{{ step.text }}</p>
                        </div>
                    </li>
                </ol>

                <section v-if="faqs.length" class="pt-12">
                    <h2 class="text-h2 font-semibold text-navy-700">Häufige Fragen</h2>
                    <dl class="border-t border-gray-200 pt-2">
                        <div v-for="faq in faqs" :key="faq.id" class="border-b border-gray-200">
                            <dt>
                                <button
                                    type="button"
                                    class="flex w-full items-start justify-between gap-4 py-5 text-left"
                                    :aria-expanded="openFaq === faq.id"
                                    @click="openFaq = openFaq === faq.id ? null : faq.id"
                                >
                                    <span class="text-base font-medium text-navy-700">{{ faq.question_de }}</span>
                                    <ChevronDown
                                        :size="18"
                                        :stroke-width="1.5"
                                        class="mt-0.5 shrink-0 text-gray-600 transition-transform duration-(--duration-disclosure) ease-(--ease-dkgz)"
                                        :class="openFaq === faq.id ? 'rotate-180' : ''"
                                        aria-hidden="true"
                                    />
                                </button>
                            </dt>
                            <dd v-if="openFaq === faq.id" class="measure pb-5 text-base leading-normal text-gray-600">{{ faq.answer_de }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <aside class="lg:sticky lg:top-24">
                <div class="rounded-card border border-navy-700 p-6">
                    <h2 class="text-h4 font-semibold text-navy-700">{{ serviceType.name_de }} anfragen</h2>
                    <ul class="flex flex-col gap-3 pt-4">
                        <li v-for="point in ['Anfrage und Vermittlung kostenfrei', 'Keine Registrierung nötig', 'Geprüfte Partner bundesweit']" :key="point" class="flex gap-2.5">
                            <Check :size="18" :stroke-width="1.5" class="mt-0.5 shrink-0 text-navy-700" aria-hidden="true" />
                            <span class="text-sm leading-normal text-gray-800">{{ point }}</span>
                        </li>
                    </ul>
                    <BaseButton href="/anfrage" size="cta" block class="mt-5">Anfrage starten</BaseButton>
                </div>

                <div class="pt-6">
                    <p class="text-eyebrow font-semibold uppercase text-gray-600">Weitere Leistungen</p>
                    <ul class="flex flex-col gap-2 pt-3">
                        <li v-for="other in serviceTypes.filter((t) => t.slug !== serviceType.slug).slice(0, 6)" :key="other.id">
                            <Link :href="`/leistungen/${other.slug}`" class="text-sm text-gray-600 hover:text-navy-700">{{ other.name_de }}</Link>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
        <!--
            The five questions a visitor actually has before choosing. Each is
            a separate field so the client can revise one without touching the
            rest, and each is hidden when empty rather than showing a heading
            over nothing.
        -->
        <section class="border-t border-gray-200 bg-white">
            <div class="mx-auto w-full max-w-(--container-wide) px-4 py-16 md:px-6">
                <dl class="grid gap-x-12 gap-y-10 md:grid-cols-2">
                    <div v-for="block in contentBlocks" :key="block.title" class="min-w-0">
                        <dt class="text-eyebrow font-semibold uppercase text-accent">{{ block.title }}</dt>
                        <dd class="measure pt-3 text-base leading-relaxed text-gray-800">{{ block.text }}</dd>
                    </div>
                </dl>
            </div>
        </section>

    </PublicLayout>
</template>
