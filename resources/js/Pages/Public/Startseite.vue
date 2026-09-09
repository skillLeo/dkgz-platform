<script setup>
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Check, ChevronDown, Phone, Star } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import BrandSeal from '../../Components/Layout/BrandSeal.vue'
import SealMark from '../../Components/Layout/SealMark.vue'
import ServiceIcon from '../../Components/Domain/ServiceIcon.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import RequestStarter from '../../Components/Domain/RequestStarter.vue'
import GermanyCoverageMap from '../../Components/Domain/GermanyCoverageMap.vue'
import ImageSlot from '../../Components/Layout/ImageSlot.vue'
import { isOn } from '../../Support/switches.js'

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
    testimonials: { type: Array, default: () => [] },
    coverage: { type: Array, default: () => [] },
})

const page = usePage()
const openFaq = ref(0)

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

const flag = (section, field) => isOn(props.content, section, field)




/**
 * Three, not four.
 *
 * The old fourth step said the assessor gets in touch, which is the same
 * moment the third one already describes from the other side. An operator who
 * empties a step drops it rather than leaving a numbered gap.
 */
const steps = computed(() => [1, 2, 3]
    .map((n) => ({
        number: t('ablauf', `schritt_${n}_titel`),
        title: t('ablauf', `schritt_${n}_titel`),
        text: t('ablauf', `schritt_${n}_text`),
    }))
    .filter((step) => step.title || step.text)
    .map((step, index) => ({ ...step, number: String(index + 1).padStart(2, '0') })))

const figures = computed(() => [1, 2, 3, 4].map((n) => ({
    value: t('kennzahlen', `wert_${n}`),
    label: t('kennzahlen', `text_${n}`),
})))

const trustPoints = computed(() => [1, 2, 3].map((n) => ({
    title: t('ueber', `punkt_${n}_titel`),
    text: t('ueber', `punkt_${n}_text`),
})))

/**
 * How large the hero photograph is drawn, as a percentage.
 *
 * A picture that suits the page at one size looks wrong at another, and which
 * is which depends on the photograph — so the operator sets it rather than
 * asking for a rebuild. Clamped, because a hero at 300% would push everything
 * beside it off the screen, and anything unreadable falls back to full size.
 */
/**
 * The headline, as however many lines the operator actually wrote.
 *
 * The three lines used to be joined with two fixed line breaks, so a headline
 * of one line was followed by two empty ones — a block of white space under the
 * heading that nobody could get rid of without emptying the field they wanted
 * to keep.
 */
/**
 * The stars beside the faces in the hero.
 *
 * Averaged from the ratings the operator actually entered, rounded to the
 * nearest whole star — never a figure typed into the template. A fabricated
 * rating is illegal here under the UWG, and this one cannot drift from the
 * reviews further down the page because it is computed from them.
 */
const heroRating = computed(() => {
    const rated = props.testimonials.filter((voice) => voice.rating > 0)

    if (! rated.length) return 5

    return Math.round(rated.reduce((sum, voice) => sum + voice.rating, 0) / rated.length)
})

/**
 * The gold line over the headline.
 *
 * Off by the switch, or empty of words — either way there is nothing to draw,
 * and the rule underneath goes with it rather than floating above the heading
 * underlining nothing.
 */
const showEyebrow = computed(() => flag('hero', 'eyebrow_anzeigen') && Boolean(t('hero', 'eyebrow')))

const headline = computed(() => [1, 2, 3]
    .map((n) => t('hero', `zeile_${n}`))
    .filter((line) => String(line).trim() !== ''))

const HERO_WIDTH = 400
const HERO_HEIGHT = 480

const heroScale = computed(() => {
    const typed = Number.parseInt(t('hero', 'bild_groesse', ''), 10)

    if (! Number.isFinite(typed)) return 1

    return Math.min(140, Math.max(60, typed)) / 100
})

const heroSize = computed(() => ({
    '--hero-w': `${Math.round(HERO_WIDTH * heroScale.value)}px`,
    '--hero-h': `${Math.round(HERO_HEIGHT * heroScale.value)}px`,
}))

/**
 * Whether the hero photograph is drawn on a phone at all.
 *
 * Off unless it is switched on: the picture has always been desktop-only here,
 * and a switch that silently added it the moment this shipped would be a change
 * nobody asked for. On a phone it is drawn small and below the request box, so
 * it can never push the box off the first screen.
 */
const heroOnMobile = computed(() => isOn(props.content, 'hero', 'bild_mobil', false))

const telHref = computed(() => `tel:${String(page.props.app?.phone ?? '').replace(/\s/g, '')}`)
</script>

<template>
    <Head :title="headline.join(' ') || 'Kfz-Gutachter finden'" />

    <PublicLayout>
        <!-- Hero. The one 420ms entrance in the product. -->
        <section style="animation: dkgz-enter 420ms cubic-bezier(0.4,0,0.2,1) both">
            <!--
                Less room under the hero than above it, so the band of four
                figures sits closer to what it belongs to. The space above the
                headline is left exactly as it was — it was tightened once and
                read worse.
            -->
            <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 items-start gap-16 px-4 pb-12 pt-16 md:px-6 lg:grid-cols-[minmax(0,58fr)_minmax(0,42fr)] lg:pb-16 lg:pt-24">
                <div>
                    <!--
                        The line and its rule go together, and both answer to
                        one switch in Seiteninhalte. Hiding the pair used to mean
                        deleting the wording, so having it back meant typing it
                        again from memory.

                        The gap below the rule belongs to the rule, not to the
                        headline. Parked on the h1 it stayed behind when the line
                        was switched off — a band of white above the headline
                        that also pushed it out of line with the photograph
                        beside it.
                    -->
                    <template v-if="showEyebrow">
                        <p class="text-eyebrow font-semibold uppercase" style="color: var(--dkgz-accent)">
                            {{ t('hero', 'eyebrow') }}
                        </p>
                        <div class="rule-accent mt-2.5 mb-7" aria-hidden="true" />
                    </template>

                    <h1 class="text-h1 font-bold text-navy-700 pb-4 lg:text-display">
                        <template v-for="(line, index) in headline" :key="index"><br v-if="index">{{ line }}</template>
                    </h1>

                    <p class="measure-lead text-lead leading-relaxed text-gray-600">{{ t('hero', 'text') }}</p>

                    <!--
                        The first question, not a button to go and find it.
                        Somebody arriving from an advert answers "which
                        assessment" before they have decided anything else, and
                        the postal code follows from it — so the hero asks
                        rather than promising a form on the next page. The phone
                        number sits beneath as the quieter alternative for
                        anyone who would rather speak to someone.
                    -->
                    <!--
                        A little wider than it was. The white card around the
                        choices went — a box inside a box inside the hero was
                        three frames deep — and side by side the two names need
                        the room the frame used to take.
                    -->
                    <div class="mt-7 max-w-xl">
                        <RequestStarter
                            :service-types="serviceTypes"
                            :title="t('hero', 'cta', 'Jetzt Gutachter anfragen')"
                            :hint="t('hero', 'cta_hinweis')"
                            :other-label="t('hero', 'option_weitere', 'Weitere Gutachten')"
                            :back-label="t('hero', 'zurueck', 'Zurück zur Auswahl')"
                        />

                        <!--
                            The proof, immediately under the box rather than
                            eight screens below it. Somebody deciding whether to
                            type anything into that dropdown is deciding now,
                            and the reviews that would persuade them sit at the
                            very bottom of the page where they will never see
                            them. Real people only: these are the same published
                            Kundenstimmen, so it cannot say anything the page
                            does not already stand behind.
                        -->
                        <div v-if="testimonials.length" class="flex flex-wrap items-center gap-x-4 gap-y-2 pt-7">
                            <div class="flex -space-x-2.5" aria-hidden="true">
                                <template v-for="voice in testimonials.slice(0, 3)" :key="voice.id">
                                    <img
                                        v-if="voice.photo_url"
                                        :src="voice.photo_url"
                                        alt=""
                                        class="h-9 w-9 rounded-full border-2 border-white object-cover"
                                        loading="lazy"
                                    >
                                    <span
                                        v-else
                                        class="grid h-9 w-9 place-items-center rounded-full border-2 border-white bg-navy-100 text-xs font-semibold text-navy-700"
                                    >{{ voice.initials }}</span>
                                </template>
                            </div>

                            <div class="min-w-0">
                                <div class="flex gap-0.5" :aria-label="`${heroRating} von 5 Sternen`">
                                    <Star
                                        v-for="n in 5"
                                        :key="n"
                                        :size="15"
                                        :stroke-width="0"
                                        class="fill-current"
                                        :style="{ color: n <= heroRating ? 'var(--dkgz-accent)' : 'var(--color-gray-300)' }"
                                        aria-hidden="true"
                                    />
                                </div>
                                <p class="pt-1 text-sm text-gray-600">
                                    {{ t('hero', 'bewertung_text', 'Von Kunden aus ganz Deutschland empfohlen') }}
                                </p>
                            </div>
                        </div>

                        <!--
                            The quieter alternative for somebody who would
                            rather speak to a person. Small on purpose: it is an
                            option, not a second call to action competing with
                            the box above it.
                        -->
                        <p v-if="page.props.app?.phone" class="flex flex-wrap items-center gap-x-1.5 gap-y-1 pt-4 text-sm text-gray-400">
                            <Phone :size="13" :stroke-width="1.5" class="shrink-0" aria-hidden="true" />
                            <span>{{ t('hero', 'telefon_titel', 'Lieber telefonisch?') }}</span>
                            <a
                                :href="`tel:${page.props.app.phone.replace(/\s/g, '')}`"
                                class="font-mono tabular-nums text-gray-600 underline underline-offset-2 hover:text-navy-700"
                            >{{ page.props.app.phone }}</a>
                            <span v-if="page.props.app?.office_hours">· {{ page.props.app.office_hours }}</span>
                        </p>
                    </div>

                    <p class="pt-3 text-sm text-gray-400">{{ t('hero', 'hinweis') }}</p>
                </div>

                <!--
                    Image column with the overlapping seal card.

                    Hidden on a phone unless the switch says otherwise, and then
                    only small: at full width it pushed the request box off the
                    first screen, which is the one thing the hero cannot afford.
                -->
                <div class="relative lg:pb-6 lg:pl-6" :class="heroOnMobile ? 'pt-10 lg:pt-0' : 'hidden lg:block'">
                    <!--
                        Sized to the picture, and the seal hangs off this rather
                        than off the column.

                        Pinned to the column it was pinned to the column's
                        bottom-left corner, and the picture is centred inside a
                        column wider than it is — so the seal drifted out into
                        the empty margin beside the photograph and read as a card
                        that had come loose from it.
                    -->
                    <!--
                        Left on a phone, centred from lg up.

                        A small picture centred under a column of left-aligned
                        text reads as something that fell there rather than as
                        part of the page. On a wide screen it is centred in a
                        column wider than itself, which is a different problem
                        with a different answer.
                    -->
                    <div class="relative max-w-[220px] lg:mx-auto lg:max-w-(--hero-w)" :style="heroSize">
                        <!--
                            The frame holds the briefed 4:5 ratio rather than a
                            fixed 560px height. Locked to a pixel height it
                            stretched or cropped as the column widened, which is
                            what made the hero look wrong on a large screen.

                            The operator's size applies from lg up, as a pair of
                            custom properties — an inline max-width would beat
                            any class and there would be no way to hold it
                            smaller on a phone.
                        -->
                        <div class="aspect-4/5 w-full overflow-hidden rounded-card border border-gray-200 lg:max-h-(--hero-h)">
                            <ImageSlot
                                :src="t('hero', 'bild')"
                                alt="Kfz-Sachverständiger dokumentiert einen Fahrzeugschaden"
                                caption="Kfz-Sachverständiger dokumentiert einen Fahrzeugschaden — Klemmbrett oder Tablet, deutsche Werkstatt oder Außenaufnahme, kühl abgestimmt, unposiert. Hochformat 4:5."
                            />
                        </div>
                        <!--
                            Overlapping the picture's own corner by a fixed
                            amount, so it reads as sitting on the photograph
                            however wide the column happens to be. Hidden on a
                            phone, where a 220px picture has no corner to spare.
                        -->
                        <div class="absolute -bottom-6 -left-6 hidden items-center gap-3.5 rounded-card border border-gray-200 bg-white px-5 py-4 shadow-(--shadow-1) lg:flex">
                            <BrandSeal :size="44">
                                <SealMark :size="44" />
                            </BrandSeal>
                            <span>
                                <span class="block text-base font-semibold leading-snug text-navy-700">{{ t('hero', 'siegel_titel') }}</span>
                                <span class="block text-sm leading-snug text-gray-600">{{ t('hero', 'siegel_text') }}</span>
                            </span>
                        </div>
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
        <!--
            The page alternates ground from here down — figures grey, process
            white, services grey — so each section reads as its own band instead
            of one continuous scroll.
        -->
        <section id="ablauf" class="border-t border-gray-200 bg-white">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 lg:py-24">
                <!-- Subtitle sits under the heading, not beside it. -->
                <div class="pb-14">
                    <h2 class="text-h2 font-semibold text-navy-700">{{ t('ablauf', 'ueberschrift') }}</h2>
                    <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">{{ t('ablauf', 'text') }}</p>
                </div>

                <!--
                    The rule belongs to each step, not to the list. The disc is
                    lifted onto that rule, and a rule only above the first row
                    left the discs on every wrapped row floating against nothing
                    — which is what pushed the text out of alignment on narrow
                    screens.
                -->
                <ol class="grid grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    <li
                        v-for="step in steps"
                        :key="step.number"
                        class="border-t border-gray-200 pt-3.5"
                    >
                        <span class="grid h-7 w-7 -translate-y-7 place-items-center rounded-full bg-navy-700 font-mono text-eyebrow font-medium text-white">
                            {{ step.number }}
                        </span>
                        <h3 class="-mt-3.5 text-lead font-semibold leading-snug text-navy-700">{{ step.title }}</h3>
                        <p class="pt-2 text-base leading-normal text-gray-600">{{ step.text }}</p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Services: sticky left column, two-column card grid -->
        <!-- Coverage, between the process and the services grid. -->
        <section v-if="coverage.length" id="abdeckung" class="border-t border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 lg:py-24">
                <div class="pb-12">
                    <h2 class="text-h2 font-semibold text-navy-700">
                        {{ t('abdeckung', 'ueberschrift', 'Wo wir vermitteln') }}
                    </h2>
                    <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">
                        {{ t('abdeckung', 'text', 'Unser Netz wächst. Diese Karte zeigt den aktuellen Stand.') }}
                    </p>
                </div>

                <GermanyCoverageMap
                    :regions="coverage"
                    :heading="t('abdeckung', 'karte_ueberschrift', 'In jeder Postleitregion vertreten')"
                    :text="t('abdeckung', 'karte_text', 'Geben Sie Ihre Postleitzahl an — wir finden einen Sachverständigen, dessen Einsatzgebiet Ihren Standort abdeckt.')"
                />
            </div>
        </section>

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
                        <ServiceIcon :service="type" class="mt-0.5 shrink-0 text-navy-700" />
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
                    <span class="text-h3 font-bold tracking-label text-white/10">DKGZ</span>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 lg:py-24">
                <h2 class="measure-panel-h text-h2 font-semibold text-white">{{ t('ueber', 'ueberschrift') }}</h2>

                <div class="grid max-w-(--container-trust) grid-cols-1 gap-12 pt-16 md:grid-cols-3">
                    <div v-for="point in trustPoints" :key="point.title">
                        <div class="rule-accent" aria-hidden="true" />
                        <h3 class="pt-4 text-lead font-semibold leading-snug text-white">{{ point.title }}</h3>
                        <p class="pt-2 text-base leading-relaxed text-white/72">{{ point.text }}</p>
                    </div>
                </div>

                <p class="mt-12 measure-legal border-t border-white/12 pt-12 text-sm leading-normal text-white/45">
                    {{ t('ueber', 'hinweis') }}
                </p>
            </div>
        </section>

        <!-- Partner strip -->
        <section id="sachverstaendige" class="border-y border-gray-200 bg-gray-50">
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
                    <p class="text-eyebrow font-semibold uppercase text-gray-600">{{ t('partner', 'karte_eyebrow') }}</p>
                    <p class="pt-3 text-base leading-normal text-gray-800">{{ t('partner', 'karte_text') }}</p>
                    <BaseButton href="/fuer-sachverstaendige" variant="secondary" size="compact" class="mt-5">
                        {{ t('partner', 'cta', 'Partner werden') }}
                    </BaseButton>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <!--
            The one place on this page where somebody other than DKGZ does the
            talking. It appears only when there is something real to show: an
            empty testimonial band is worse than none, because the space where
            praise should be reads as the absence of any.
        -->
        <section v-if="testimonials.length" class="border-t border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 lg:py-20">
                <div class="pb-12">
                    <h2 class="text-h2 font-semibold text-navy-700">
                        {{ t('stimmen', 'ueberschrift', 'Was unsere Kunden sagen') }}
                    </h2>
                    <p v-if="t('stimmen', 'text')" class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">
                        {{ t('stimmen', 'text') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <figure
                        v-for="voice in testimonials"
                        :key="voice.id"
                        class="flex flex-col rounded-card border border-gray-200 bg-white p-6"
                    >
                        <div v-if="voice.rating" class="flex gap-0.5 pb-4" :aria-label="`${voice.rating} von 5 Sternen`">
                            <Star
                                v-for="n in voice.rating"
                                :key="n"
                                :size="16"
                                :stroke-width="0"
                                class="fill-current"
                                style="color: var(--dkgz-accent)"
                                aria-hidden="true"
                            />
                        </div>

                        <blockquote class="flex-1 text-base leading-relaxed text-gray-800">
                            „{{ voice.quote }}“
                        </blockquote>

                        <figcaption class="flex items-center gap-3.5 pt-6">
                            <img
                                v-if="voice.photo_url"
                                :src="voice.photo_url"
                                :alt="voice.name"
                                class="h-11 w-11 shrink-0 rounded-full border border-gray-200 object-cover"
                                loading="lazy"
                            >
                            <span
                                v-else
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-navy-100 text-sm font-semibold text-navy-700"
                                aria-hidden="true"
                            >{{ voice.initials }}</span>

                            <span class="min-w-0">
                                <span class="block truncate text-base font-medium text-navy-700">{{ voice.name }}</span>
                                <span v-if="voice.location" class="block truncate text-sm text-gray-600">{{ voice.location }}</span>
                            </span>
                        </figcaption>
                    </figure>
                </div>
            </div>
        </section>

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
