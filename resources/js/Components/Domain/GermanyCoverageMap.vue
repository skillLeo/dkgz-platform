<script setup>
import { computed } from 'vue'
import { Check } from 'lucide-vue-next'
import { BORDER_PATH, MAP } from '../../Support/germany.js'

/**
 * That DKGZ covers the whole country.
 *
 * This used to draw Germany in grey with ten small markers on it, which reads
 * as ten places rather than as one country — the opposite of the claim the
 * section exists to make, and the reason it was worth redoing. The country is
 * now filled solid, because the shape itself is the statement: a whole Germany
 * in the brand's own navy says "bundesweit" before anybody reads a word.
 *
 * The border is real — longitude and latitude projected through Web Mercator,
 * the same projection as every map of Germany anyone has seen.
 *
 * The regions stay, as a list beside the shape rather than as pins on it. They
 * name the cities somebody is actually looking for, which is worth having on
 * the page in words; they are simply not what the picture is about.
 */
const props = defineProps({
    regions: { type: Array, default: () => [] },
    /** Both editable: they make the claim the section exists to make. */
    heading: { type: String, default: 'In jeder Postleitregion vertreten' },
    text: {
        type: String,
        default: 'Geben Sie Ihre Postleitzahl an — wir finden einen Sachverständigen, dessen Einsatzgebiet Ihren Standort abdeckt.',
    },
})

const covered = computed(() => props.regions.filter((region) => region.covered))

/** Whole-country coverage is a different claim from most-of-it. */
const complete = computed(() => props.regions.length > 0 && covered.value.length === props.regions.length)

const headline = computed(() => (complete.value
    ? 'Bundesweit'
    : `${covered.value.length} von ${props.regions.length} Regionen`))
</script>

<template>
    <div class="grid grid-cols-1 items-center gap-10 md:grid-cols-[minmax(0,260px)_minmax(0,1fr)] md:gap-14">
        <figure class="relative mx-auto w-full max-w-64 md:mx-0">
            <svg
                :viewBox="`0 0 ${MAP.width} ${MAP.height}`"
                class="h-auto w-full"
                role="img"
                :aria-label="complete
                    ? 'Deutschlandkarte: DKGZ vermittelt in allen Postleitregionen'
                    : `Deutschlandkarte: DKGZ vermittelt in ${covered.length} von ${regions.length} Postleitregionen`"
            >
                <defs>
                    <linearGradient id="dkgz-coverage" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="var(--color-navy-500)" />
                        <stop offset="100%" stop-color="var(--color-navy-700)" />
                    </linearGradient>
                </defs>

                <!--
                    One filled shape. Not a base layer with things drawn on top
                    of it: the country is the whole of the message, and anything
                    sitting over it invites the eye to read the marks instead of
                    the outline.
                -->
                <path
                    :d="BORDER_PATH"
                    :fill="complete ? 'url(#dkgz-coverage)' : 'var(--color-navy-100)'"
                    stroke="var(--color-navy-700)"
                    stroke-width="1.2"
                    stroke-linejoin="round"
                />
            </svg>

            <!--
                The claim, over the middle of the country. Small, because the
                filled shape has already made it and repeating it loudly would
                be the graphic arguing with itself.
            -->
            <figcaption
                v-if="complete"
                class="pointer-events-none absolute inset-0 grid place-items-center"
            >
                <span class="rounded-card bg-white/95 px-4 py-2 text-center shadow-(--shadow-1)">
                    <span class="block text-h4 font-bold leading-none text-navy-700">{{ headline }}</span>
                    <span class="block pt-1 text-xs text-gray-600">alle PLZ-Gebiete</span>
                </span>
            </figcaption>
        </figure>

        <div class="min-w-0">
            <p class="text-h3 font-semibold text-navy-700">
                {{ complete ? heading : headline }}
            </p>
            <p v-if="text" class="measure pt-2 text-base leading-normal text-gray-600">{{ text }}</p>

            <!--
                The regions in words. A search engine and a visitor both want to
                see their own city named, and neither of them can read a pin.
            -->
            <ul class="grid grid-cols-1 gap-x-6 gap-y-2.5 pt-6 sm:grid-cols-2">
                <li
                    v-for="region in regions"
                    :key="region.digit"
                    class="flex items-start gap-2.5"
                    :class="region.covered ? '' : 'opacity-45'"
                >
                    <Check
                        v-if="region.covered"
                        :size="15"
                        :stroke-width="2"
                        class="mt-1 shrink-0 text-success"
                        aria-hidden="true"
                    />
                    <span v-else class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400" aria-hidden="true" />
                    <span class="min-w-0 text-sm leading-normal text-gray-800">
                        <span class="font-mono text-xs text-gray-400">{{ region.digit }}</span>
                        <span class="pl-1.5">{{ region.places }}</span>
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>
