<script setup>
import { computed } from 'vue'
import { BORDER_PATH, MAP, ZONE_POINTS, zoneCoverage } from '../../Support/germany.js'

/**
 * Where this partner works, drawn on Germany.
 *
 * The screen used to show a ten-by-ten raster of postal prefixes, which is a
 * true picture of the numbers and a useless one of the country: a partner
 * covering Düsseldorf saw three shaded squares in an arbitrary corner and could
 * not tell whether their ranges were right. This draws the same coverage on the
 * outline everyone recognises, with each zone shaded by how much of it the
 * partner's ranges actually reach, so a wrong range is visible as a wrong place.
 */
const props = defineProps({
    areas: { type: Array, default: () => [] },
})

const zones = computed(() => zoneCoverage(props.areas)
    .map((zone) => ({ ...zone, ...ZONE_POINTS[zone.digit] })))

const fullCount = computed(() => zones.value.filter((z) => z.tone === 'full').length)
const partialCount = computed(() => zones.value.filter((z) => z.tone === 'partial').length)
const reached = computed(() => zones.value.filter((z) => z.tone !== 'none'))

const summary = computed(() => {
    if (! reached.value.length) return 'Noch kein Gebiet hinterlegt'
    if (fullCount.value === 10) return 'Bundesweit'

    return `${reached.value.length} von 10 Postleitregionen`
})

const fillFor = (tone) => ({
    full: 'fill-navy-700 stroke-white',
    partial: 'fill-navy-500/40 stroke-navy-500',
    none: 'fill-white stroke-gray-400',
}[tone])
</script>

<template>
    <div>
        <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 pb-4">
            <p class="text-eyebrow font-semibold uppercase text-gray-600">Abdeckung</p>
            <p class="text-sm text-gray-800">{{ summary }}</p>
        </div>

        <div class="flex flex-col gap-8 sm:flex-row sm:items-start sm:gap-8">
            <figure class="mx-auto w-full max-w-56 shrink-0 sm:mx-0 sm:max-w-44">
                <svg
                    :viewBox="`0 0 ${MAP.width} ${MAP.height}`"
                    class="h-auto w-full"
                    role="img"
                    :aria-label="`Ihr Einsatzgebiet: ${summary}`"
                >
                    <path
                        :d="BORDER_PATH"
                        class="fill-gray-50 stroke-gray-300"
                        stroke-width="1.2"
                        stroke-linejoin="round"
                    />

                    <g v-for="zone in zones" :key="zone.digit">
                        <title>{{ zone.digit }}xxxx · {{ zone.label }}</title>
                        <circle
                            :cx="zone.x"
                            :cy="zone.y"
                            r="6.5"
                            :class="fillFor(zone.tone)"
                            stroke-width="1.4"
                        />
                        <text
                            :x="zone.x"
                            :y="zone.y + 2.4"
                            text-anchor="middle"
                            class="font-mono font-semibold"
                            style="font-size: 7px"
                            :class="zone.tone === 'full' ? 'fill-white' : zone.tone === 'partial' ? 'fill-navy-900' : 'fill-gray-400'"
                        >{{ zone.digit }}</text>
                    </g>
                </svg>
            </figure>

            <div class="min-w-0 flex-1">
                <ul v-if="reached.length" class="flex flex-col gap-2">
                    <li
                        v-for="zone in reached"
                        :key="`zone-${zone.digit}`"
                        class="flex items-baseline gap-2.5"
                    >
                        <span
                            class="mt-1.5 block h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="zone.tone === 'full' ? 'bg-navy-700' : 'bg-navy-500/50'"
                            aria-hidden="true"
                        />
                        <span class="min-w-0 text-sm text-gray-800">
                            <span class="font-mono tabular-nums">{{ zone.digit }}xxxx</span>
                            <span class="text-gray-600"> · {{ zone.label }}</span>
                            <span v-if="zone.tone === 'partial'" class="text-gray-500">
                                (teilweise)
                            </span>
                        </span>
                    </li>
                </ul>

                <p v-else class="text-sm leading-normal text-gray-600">
                    Sobald Sie ein Postleitzahlgebiet hinterlegen, zeigt die Karte, welche
                    Regionen Sie damit erreichen.
                </p>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 pt-5">
                    <span class="inline-flex items-center gap-2">
                        <span class="block h-2.5 w-2.5 rounded-full bg-navy-700" aria-hidden="true" />
                        <span class="text-xs text-gray-600">Vollständig</span>
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="block h-2.5 w-2.5 rounded-full bg-navy-500/40" aria-hidden="true" />
                        <span class="text-xs text-gray-600">Teilweise</span>
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="block h-2.5 w-2.5 rounded-full border border-gray-300 bg-white" aria-hidden="true" />
                        <span class="text-xs text-gray-600">Nicht abgedeckt</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
