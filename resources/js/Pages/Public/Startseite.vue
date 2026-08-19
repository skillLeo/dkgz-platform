<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import {
    Camera, Car, Check, ChevronDown, ClipboardCheck, Euro, History, Phone, Shield, ShieldCheck, Wrench,
} from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

/**
 * Built from "DKGZ Homepage.dc.html", section for section: hero on a 58/42
 * grid, a four-column figure band, the four-step process, a sticky services
 * column beside a two-column card grid, the navy trust panel, the partner
 * strip and the FAQ disclosure list.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    serviceTypes: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
})

const page = usePage()
const postalCode = ref('')
const openFaq = ref(0)

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

const start = () => router.get('/anfrage', postalCode.value ? { plz: postalCode.value } : {})

// The design assigns a specific icon per service; anything the admin adds later
// falls back to the generic document mark.
const icons = {
    unfallgutachten: Car,
    haftpflichtgutachten: ShieldCheck,
    kaskogutachten: Shield,
    'fahrzeugschadengutachten': Wrench,
    reparaturbestaetigung: Wrench,
    wertgutachten: Euro,
    oldtimergutachten: History,
    'gebrauchtwagen-check': ClipboardCheck,
    beweissicherung: Camera,
}

const iconFor = (slug) => icons[slug] ?? ClipboardCheck

const steps = computed(() => [1, 2, 3, 4].map((n) => ({
    number: String(n).padStart(2, '0'),
    title: t('ablauf', `schritt_${n}_titel`),
    text: t('ablauf', `schritt_${n}_text`),
})))

const figures = computed(() => [1, 2, 3, 4].map((n) => ({
    value: t('kennzahlen', `wert_${n}`),
    label: t('kennzahlen', `text_${n}`),
})))

const trustPoints = computed(() => [1, 2, 3].map((n) => ({
    title: t('ueber', `punkt_${n}_titel`),
    text: t('ueber', `punkt_${n}_text`),
})))

const telHref = computed(() => `tel:${String(page.props.app?.phone ?? '').replace(/\s/g, '')}`)
</script>

<template>
    <Head :title="`${t('hero', 'zeile_1', 'Kfz-Gutachter finden.')} ${t('hero', 'zeile_2', '')}`.trim()" />

    <PublicLayout>
        <!-- Hero. The one 420ms entrance in the product. -->
        <section style="animation: dkgz-enter 420ms cubic-bezier(0.4,0,0.2,1) both">
            <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 items-start gap-16 px-4 py-16 md:px-6 lg:grid-cols-[minmax(0,58fr)_minmax(0,42fr)] lg:py-24">
                <div>
                    <p class="text-eyebrow font-semibold uppercase tracking-[0.09em]" style="color: var(--dkgz-accent)">
                        {{ t('hero', 'eyebrow') }}
                    </p>
                    <div class="rule-accent mt-2.5" aria-hidden="true" />

                    <h1 class="text-h1 font-bold text-navy-700 pt-8 pb-6 lg:text-display">
                        {{ t('hero', 'zeile_1') }}<br>{{ t('hero', 'zeile_2') }}<br>{{ t('hero', 'zeile_3') }}
                    </h1>

                    <p class="measure-lead text-lead leading-relaxed text-gray-600">{{ t('hero', 'text') }}</p>

                    <form class="mt-10 max-w-[520px] rounded-card border border-gray-300 bg-white p-5" @submit.prevent="start">
                        <label for="hero-plz" class="block pb-2 text-base font-medium text-gray-800">
                            {{ t('hero', 'plz_label', 'Ihre Postleitzahl') }}
                        </label>
                        <div class="flex gap-3">
                            <input
                                id="hero-plz"
                                v-model="postalCode"
                                type="text"
                                inputmode="numeric"
                                maxlength="5"
                                :placeholder="t('hero', 'plz_platzhalter', 'z. B. 40589')"
                                class="h-12 min-w-0 flex-1 rounded-sm border border-gray-300 bg-white px-3.5 text-lead tabular-nums text-gray-800 outline-none transition-colors duration-(--duration-focus) ease-(--ease-dkgz) placeholder:text-gray-400 hover:border-gray-400 focus:border-navy-700 focus:outline-2 focus:outline-navy-500 focus:outline-offset-2"
                            >
                            <button
                                type="submit"
                                class="h-12 shrink-0 whitespace-nowrap rounded-sm border border-navy-700 bg-navy-700 px-5.5 text-base font-medium text-white transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:border-navy-500 hover:bg-navy-500"
                            >{{ t('hero', 'cta', 'Gutachter anfragen') }}</button>
                        </div>
                    </form>

                    <p class="pt-3 text-sm text-gray-400">{{ t('hero', 'hinweis') }}</p>
                </div>

                <!-- Image column with the overlapping seal card -->
                <div class="relative hidden pb-6 pl-6 lg:block">
                    <div class="h-140 overflow-hidden rounded-card border border-gray-200 bg-gray-50">
                        <img
                            v-if="t('hero', 'bild')"
                            :src="t('hero', 'bild')"
                            alt="Kfz-Sachverständiger dokumentiert einen Fahrzeugschaden"
                            class="h-full w-full object-cover"
                        >
                    </div>
                    <div class="absolute bottom-0 left-0 flex items-center gap-3.5 rounded-card border border-gray-200 bg-white px-5 py-4 shadow-(--shadow-1)">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full border border-navy-700" aria-hidden="true">
                            <span class="grid h-[34px] w-[34px] place-items-center rounded-full border" style="border-color: var(--dkgz-accent)">
                                <span class="text-seal-xs font-bold tracking-[0.04em] text-navy-700">DKGZ</span>
                            </span>
                        </span>
                        <span>
                            <span class="block text-base font-semibold leading-snug text-navy-700">{{ t('hero', 'siegel_titel') }}</span>
                            <span class="block text-sm leading-snug text-gray-600">{{ t('hero', 'siegel_text') }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Figure band -->
        <section class="border-y border-gray-200 bg-gray-50">
            <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-2 px-4 py-8 md:px-6 lg:grid-cols-4">
                <div
                    v-for="(figure, index) in figures"
                    :key="figure.label"
                    class="px-4 py-3 lg:py-0"
                    :class="index === 0 ? 'lg:pl-0 lg:pr-8' : 'lg:border-l lg:border-gray-200 lg:px-8'"
                >
                    <p class="text-h3 font-semibold tabular-nums text-navy-700">{{ figure.value }}</p>
                    <p class="pt-1 text-sm text-gray-600">{{ figure.label }}</p>
                </div>
            </div>
        </section>

        <!-- Process -->
        <section id="ablauf" class="bg-white">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 lg:py-24">
                <div class="flex flex-wrap items-end justify-between gap-12 pb-14">
                    <h2 class="text-h2 font-semibold text-navy-700">{{ t('ablauf', 'ueberschrift') }}</h2>
                    <p class="measure-hero text-base leading-normal text-gray-600">{{ t('ablauf', 'text') }}</p>
                </div>

                <ol class="grid grid-cols-1 gap-8 border-t border-gray-200 sm:grid-cols-2 lg:grid-cols-4">
                    <li v-for="step in steps" :key="step.number">
                        <span class="grid h-7 w-7 -translate-y-3.5 place-items-center rounded-full bg-navy-700 font-mono text-eyebrow font-medium text-white">
                            {{ step.number }}
                        </span>
                        <h3 class="pt-2.5 text-lead font-semibold leading-snug text-navy-700">{{ step.title }}</h3>
                        <p class="pt-2 text-base leading-normal text-gray-600">{{ step.text }}</p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Services: sticky left column, two-column card grid -->
        <section id="leistungen" class="border-t border-gray-200 bg-white">
            <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 items-start gap-16 px-4 py-16 md:px-6 lg:grid-cols-[380px_minmax(0,1fr)] lg:py-24">
                <div class="lg:sticky lg:top-26">
                    <h2 class="text-h2 font-semibold text-navy-700">{{ t('leistungen', 'ueberschrift') }}</h2>
                    <p class="pt-4 text-base leading-normal text-gray-600">{{ t('leistungen', 'text') }}</p>
                    <BaseButton href="/leistungen" variant="secondary" size="compact" class="mt-6">
                        {{ t('leistungen', 'cta', 'Alle Leistungen ansehen') }}
                    </BaseButton>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Link
                        v-for="type in serviceTypes"
                        :key="type.id"
                        :href="`/leistungen/${type.slug}`"
                        class="flex gap-3.5 rounded-card border border-gray-200 p-4 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:border-navy-700"
                    >
                        <component :is="iconFor(type.slug)" :size="20" :stroke-width="1.5" class="mt-0.5 shrink-0 text-navy-700" aria-hidden="true" />
                        <span class="min-w-0">
                            <span class="block text-lead font-semibold leading-snug text-navy-700">{{ type.name_de }}</span>
                            <span class="block pt-1 text-sm leading-normal text-gray-600">{{ type.description_de }}</span>
                        </span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Trust panel, navy with the watermark seal -->
        <section id="ueber" class="relative overflow-hidden bg-navy-900">
            <div class="pointer-events-none absolute right-24 top-24 hidden h-45 w-45 place-items-center rounded-full border border-white/14 lg:grid" aria-hidden="true">
                <div class="grid h-35 w-35 place-items-center rounded-full" style="border: 1px solid rgba(176,138,46,0.30)">
                    <span class="text-h3 font-bold tracking-[0.04em] text-white/10">DKGZ</span>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 lg:py-24">
                <h2 class="max-w-[22ch] text-h2 font-semibold text-white">{{ t('ueber', 'ueberschrift') }}</h2>

                <div class="grid max-w-[1000px] grid-cols-1 gap-12 pt-16 md:grid-cols-3">
                    <div v-for="point in trustPoints" :key="point.title">
                        <div class="rule-accent" aria-hidden="true" />
                        <h3 class="pt-4 text-lead font-semibold leading-snug text-white">{{ point.title }}</h3>
                        <p class="pt-2 text-base leading-relaxed text-white/72">{{ point.text }}</p>
                    </div>
                </div>

                <p class="mt-12 max-w-[78ch] border-t border-white/12 pt-12 text-sm leading-normal text-white/45">
                    {{ t('ueber', 'hinweis') }}
                </p>
            </div>
        </section>

        <!-- Partner strip -->
        <section id="sachverstaendige" class="border-b border-gray-200 bg-gray-50">
            <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 items-center gap-16 px-4 py-16 md:px-6 lg:grid-cols-[minmax(0,1fr)_420px] lg:py-24">
                <div>
                    <h2 class="text-h2 font-semibold text-navy-700">{{ t('partner', 'ueberschrift') }}</h2>
                    <ul class="flex flex-col gap-3.5 pt-6">
                        <li v-for="n in [1, 2, 3]" :key="n" class="flex items-start gap-3">
                            <Check :size="20" :stroke-width="1.5" class="mt-0.5 shrink-0 text-navy-700" aria-hidden="true" />
                            <span class="text-base leading-normal text-gray-800">{{ t('partner', `punkt_${n}`) }}</span>
                        </li>
                    </ul>
                </div>

                <div class="rounded-card border border-gray-200 bg-white p-6">
                    <p class="text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">{{ t('partner', 'karte_eyebrow') }}</p>
                    <p class="pt-3 text-base leading-normal text-gray-800">{{ t('partner', 'karte_text') }}</p>
                    <BaseButton href="/fuer-sachverstaendige" variant="secondary" size="compact" class="mt-5">
                        {{ t('partner', 'cta', 'Partner werden') }}
                    </BaseButton>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section v-if="faqs.length" class="bg-white">
            <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 items-start gap-16 px-4 py-16 md:px-6 lg:grid-cols-[380px_minmax(0,1fr)] lg:py-24">
                <div>
                    <h2 class="text-h2 font-semibold text-navy-700">{{ t('faq', 'ueberschrift', 'Häufige Fragen') }}</h2>
                    <p class="pt-4 text-base leading-normal text-gray-600">{{ t('faq', 'text') }}</p>
                    <a v-if="page.props.app?.phone" :href="telHref" class="flex items-center gap-2.5 pt-4">
                        <Phone :size="18" :stroke-width="1.5" class="shrink-0 text-navy-700" aria-hidden="true" />
                        <span class="font-mono text-base tabular-nums text-navy-700">{{ page.props.app.phone }}</span>
                    </a>
                </div>

                <dl class="border-t border-gray-200">
                    <div v-for="(faq, index) in faqs" :key="faq.id" class="border-b border-gray-200">
                        <dt>
                            <button
                                type="button"
                                class="flex w-full cursor-pointer items-center justify-between gap-6 py-5 text-left"
                                :aria-expanded="openFaq === index"
                                @click="openFaq = openFaq === index ? null : index"
                            >
                                <span class="text-lead font-semibold leading-snug text-navy-700">{{ faq.question_de }}</span>
                                <ChevronDown
                                    :size="20"
                                    :stroke-width="1.5"
                                    class="shrink-0 text-gray-600 transition-transform duration-(--duration-disclosure) ease-(--ease-dkgz)"
                                    :class="openFaq === index ? 'rotate-180' : ''"
                                    aria-hidden="true"
                                />
                            </button>
                        </dt>
                        <dd v-if="openFaq === index" class="measure pb-5 text-base leading-relaxed text-gray-600">{{ faq.answer_de }}</dd>
                    </div>
                </dl>
            </div>
        </section>
    </PublicLayout>
</template>
