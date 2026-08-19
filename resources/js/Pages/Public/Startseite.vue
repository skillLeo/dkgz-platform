<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Check, ChevronDown, FileText } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    serviceTypes: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
})

const page = usePage()
const postalCode = ref('')
const openFaq = ref(null)

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

const start = () => {
    router.get('/anfrage', postalCode.value ? { plz: postalCode.value } : {})
}
</script>

<template>
    <Head :title="t('hero', 'ueberschrift', 'Kfz-Sachverständigen finden')" />

    <PublicLayout>
        <!-- Hero: the one 420ms entrance in the product -->
        <section class="border-b border-gray-200 bg-gray-50" style="animation: dkgz-enter 420ms cubic-bezier(0.4,0,0.2,1) both">
            <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 gap-12 px-4 py-16 md:px-6 md:py-24 lg:grid-cols-[minmax(0,1fr)_minmax(0,440px)] lg:items-center">
                <div>
                    <SectionLabel :text="t('hero', 'eyebrow', 'Kostenlose Vermittlung')" />
                    <h1 class="text-h1 font-bold text-navy-700 pt-6">{{ t('hero', 'ueberschrift') }}</h1>
                    <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">{{ t('hero', 'text') }}</p>

                    <form class="flex flex-col gap-3 pt-8 sm:flex-row" @submit.prevent="start">
                        <label for="hero-plz" class="sr-only">{{ t('hero', 'plz_label', 'Postleitzahl oder Ort') }}</label>
                        <input
                            id="hero-plz"
                            v-model="postalCode"
                            type="text"
                            inputmode="numeric"
                            maxlength="5"
                            :placeholder="t('hero', 'plz_label', 'Postleitzahl oder Ort')"
                            class="h-(--spacing-cta) w-full rounded-sm border border-gray-300 bg-white px-4 text-base tabular-nums text-gray-800 outline-none transition-colors duration-(--duration-focus) ease-(--ease-dkgz) placeholder:text-gray-400 hover:border-gray-400 focus:border-navy-700 focus:outline-2 focus:outline-navy-500 focus:outline-offset-2 sm:max-w-56"
                        >
                        <BaseButton type="submit" size="cta" class="w-full sm:w-auto">{{ t('hero', 'cta', 'Gutachter finden') }}</BaseButton>
                    </form>

                    <ul class="flex flex-wrap gap-x-8 gap-y-3 pt-8">
                        <li v-for="key in ['vorteil_1', 'vorteil_2', 'vorteil_3']" :key="key" class="flex items-center gap-2.5">
                            <Check :size="18" :stroke-width="1.5" class="shrink-0 text-navy-700" aria-hidden="true" />
                            <span class="text-sm text-gray-800">{{ t('hero', key) }}</span>
                        </li>
                    </ul>
                </div>

                <div class="hidden aspect-[4/3] border border-gray-200 bg-white lg:block">
                    <img
                        v-if="t('hero', 'bild')"
                        :src="t('hero', 'bild')"
                        alt="Kfz-Sachverständiger bei der Schadenaufnahme"
                        class="h-full w-full object-cover"
                    >
                </div>
            </div>
        </section>

        <!-- Leistungen -->
        <section class="mx-auto w-full max-w-(--container-shell) px-4 py-20 md:px-6">
            <SectionLabel text="Leistungen" />
            <h2 class="pt-6 text-h2 font-semibold text-navy-700">{{ t('leistungen', 'ueberschrift') }}</h2>
            <p class="measure-lead pt-3 text-base leading-normal text-gray-600">{{ t('leistungen', 'text') }}</p>

            <div class="grid grid-cols-1 gap-4 pt-10 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="type in serviceTypes.slice(0, 4)"
                    :key="type.id"
                    :href="`/leistungen/${type.slug}`"
                    class="flex flex-col border border-gray-200 bg-white p-5 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:border-navy-700"
                >
                    <FileText :size="20" :stroke-width="1.5" class="text-navy-700" aria-hidden="true" />
                    <h3 class="pt-4 text-base font-medium text-navy-700">{{ type.name_de }}</h3>
                    <p class="pt-2 text-sm leading-normal text-gray-600">{{ type.description_de }}</p>
                    <span class="pt-4 text-sm font-medium text-navy-700">Gutachter finden →</span>
                </Link>
            </div>

            <Link href="/leistungen" class="mt-8 inline-block text-base font-medium text-navy-700 hover:text-navy-500">
                {{ t('leistungen', 'cta', 'Alle Leistungen ansehen') }} →
            </Link>
        </section>

        <!-- Ablauf -->
        <section class="border-y border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-20 md:px-6">
                <SectionLabel text="Ablauf" />
                <h2 class="pt-6 text-h2 font-semibold text-navy-700">{{ t('ablauf', 'ueberschrift') }}</h2>

                <ol class="border-t border-gray-200 pt-2">
                    <li v-for="n in [1, 2, 3]" :key="n" class="grid grid-cols-[40px_minmax(0,1fr)] gap-5 border-b border-gray-200 py-6">
                        <span class="pt-1 font-mono text-base tabular-nums text-gray-400">0{{ n }}</span>
                        <div>
                            <h3 class="text-h4 font-semibold text-navy-700">{{ t('ablauf', `schritt_${n}_titel`) }}</h3>
                            <p class="measure pt-1.5 text-base leading-normal text-gray-600">{{ t('ablauf', `schritt_${n}_text`) }}</p>
                        </div>
                    </li>
                </ol>

                <div class="flex flex-wrap items-center gap-6 pt-8">
                    <BaseButton href="/anfrage" size="cta">{{ t('ablauf', 'cta', 'Jetzt Gutachter anfragen') }}</BaseButton>
                    <span class="text-sm text-gray-600">{{ t('ablauf', 'hinweis') }}</span>
                </div>
            </div>
        </section>

        <!-- Unfall -->
        <section class="bg-navy-900">
            <div class="mx-auto flex w-full max-w-(--container-shell) flex-wrap items-center justify-between gap-8 px-4 py-16 md:px-6">
                <div class="min-w-0">
                    <h2 class="text-h2 font-semibold text-white">{{ t('unfall', 'ueberschrift') }}</h2>
                    <p class="measure-lead pt-3 text-lead leading-relaxed text-white/72">{{ t('unfall', 'text') }}</p>
                    <p class="pt-4 text-sm text-white/62">{{ t('unfall', 'hinweis') }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-4">
                    <BaseButton href="/anfrage" variant="inverted" size="cta">{{ t('unfall', 'cta', 'Unfallgutachter finden') }}</BaseButton>
                    <a v-if="page.props.app?.phone" :href="`tel:${page.props.app.phone.replace(/\s/g, '')}`" class="font-mono text-lead tabular-nums text-white">
                        {{ page.props.app.phone }}
                    </a>
                </div>
            </div>
        </section>

        <!-- Warum DKGZ -->
        <section class="mx-auto w-full max-w-(--container-shell) px-4 py-20 md:px-6">
            <SectionLabel text="Warum DKGZ" />
            <h2 class="pt-6 text-h2 font-semibold text-navy-700">{{ t('warum', 'ueberschrift') }}</h2>

            <div class="grid grid-cols-1 gap-x-12 gap-y-8 pt-10 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="n in [1, 2, 3, 4, 5, 6]" :key="n" class="flex gap-3">
                    <Check :size="20" :stroke-width="1.5" class="mt-0.5 shrink-0 text-navy-700" aria-hidden="true" />
                    <div class="min-w-0">
                        <h3 class="text-base font-medium text-navy-700">{{ t('warum', `punkt_${n}_titel`) }}</h3>
                        <p class="pt-1 text-sm leading-normal text-gray-600">{{ t('warum', `punkt_${n}_text`) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section v-if="faqs.length" class="border-y border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-20 md:px-6">
                <SectionLabel text="FAQ" />
                <h2 class="pt-6 text-h2 font-semibold text-navy-700">{{ t('faq', 'ueberschrift', 'Häufige Fragen') }}</h2>

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
            </div>
        </section>

        <!-- Abschluss -->
        <section class="mx-auto w-full max-w-(--container-shell) px-4 py-20 md:px-6">
            <div class="border border-navy-700 p-8 md:p-12">
                <h2 class="text-h2 font-semibold text-navy-700">{{ t('abschluss', 'ueberschrift') }}</h2>
                <p class="measure-lead pt-3 text-lead leading-relaxed text-gray-600">{{ t('abschluss', 'text') }}</p>
                <div class="flex flex-wrap items-center gap-6 pt-8">
                    <BaseButton href="/anfrage" size="cta">{{ t('abschluss', 'cta', 'Gutachter finden') }}</BaseButton>
                    <span class="text-sm text-gray-600">{{ t('abschluss', 'hinweis') }}</span>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
