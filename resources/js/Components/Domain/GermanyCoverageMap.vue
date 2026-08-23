<script setup>
import { computed, ref } from 'vue'
import { BORDER_PATH, MAP, ZONE_POINTS, ZONES } from '../../Support/germany.js'

/**
 * Where DKGZ currently has partners, drawn on Germany.
 *
 * The border is real: longitude and latitude projected through Web Mercator,
 * the same projection every map of Germany people have seen uses. The markers
 * sit at the true coordinates of the city each postal zone is named for, so the
 * outline and the points are drawn from one set of numbers and cannot drift
 * apart — which is what made the earlier hand-traced version look wrong.
 *
 * It is interactive rather than decorative: pointing at a region names it and
 * says whether we can place work there, which is the question the graphic
 * exists to answer.
 */
const props = defineProps({
    regions: { type: Array, default: () => [] },
})

const active = ref(null)

/**
 * Every postal region is drawn as served.
 *
 * DKGZ vermittelt bundesweit and places a request by hand where the network is
 * still thin, so a hollow circle over a region said "not us" about somewhere we
 * do in fact cover — the opposite of what the map is for.
 */
const points = computed(() => props.regions
    .filter((region) => ZONE_POINTS[region.digit])
    .map((region) => ({
        ...region,
        ...ZONE_POINTS[region.digit],
        covered: true,
        label: ZONES[region.digit]?.label ?? '',
    })))

const activePoint = computed(() => points.value.find((p) => p.digit === active.value) ?? null)
</script>

<template>
    <div class="flex flex-col gap-10 md:flex-row md:items-start md:gap-14">
        <figure class="relative w-full max-w-88 shrink-0">
            <svg
                :viewBox="`0 0 ${MAP.width} ${MAP.height}`"
                class="h-auto w-full"
                role="img"
                aria-label="Bundesweite Abdeckung: DKGZ vermittelt in allen Postleitregionen"
            >
                <path
                    :d="BORDER_PATH"
                    class="fill-gray-100 stroke-gray-300"
                    stroke-width="1.2"
                    stroke-linejoin="round"
                />

                <g
                    v-for="point in points"
                    :key="point.digit"
                    class="cursor-pointer"
                    tabindex="0"
                    role="button"
                    :aria-label="`Region ${point.digit}: ${point.places}`"
                    @mouseenter="active = point.digit"
                    @mouseleave="active = null"
                    @focus="active = point.digit"
                    @blur="active = null"
                >
                    <!-- A soft halo marks the live region without moving anything. -->
                    <circle
                        v-if="point.covered"
                        :cx="point.x"
                        :cy="point.y"
                        :r="active === point.digit ? 15 : 11"
                        class="fill-navy-700/12 transition-all duration-(--duration-hover) ease-(--ease-dkgz)"
                    />
                    <circle
                        :cx="point.x"
                        :cy="point.y"
                        :r="active === point.digit ? 8 : 6.5"
                        :class="point.covered
                            ? 'fill-navy-700 stroke-white'
                            : 'fill-white stroke-gray-400'"
                        stroke-width="1.4"
                        class="transition-all duration-(--duration-hover) ease-(--ease-dkgz)"
                    />
                    <text
                        :x="point.x"
                        :y="point.y + 2.4"
                        text-anchor="middle"
                        class="pointer-events-none font-mono font-semibold"
                        style="font-size: 7px"
                        :class="point.covered ? 'fill-white' : 'fill-gray-500'"
                    >{{ point.digit }}</text>
                </g>
            </svg>

            <!-- Named on hover, so the map answers rather than decorates. -->
            <figcaption class="min-h-8 pt-3 text-center" aria-live="polite">
                <span v-if="activePoint" class="text-sm text-gray-800">
                    <span class="font-mono">{{ activePoint.digit }}xxxx</span> · {{ activePoint.places }}
                </span>
            </figcaption>
        </figure>

        <div class="min-w-0">
            <ul class="grid gap-x-8 gap-y-2.5 sm:grid-cols-2">
                <li
                    v-for="region in points"
                    :key="`legende-${region.digit}`"
                    class="flex items-baseline gap-2.5"
                    @mouseenter="active = region.digit"
                    @mouseleave="active = null"
                >
                    <span class="mt-1.5 block h-1.5 w-1.5 shrink-0 rounded-full bg-navy-700" aria-hidden="true" />
                    <span class="min-w-0 text-sm text-gray-800">
                        <span class="font-mono tabular-nums">{{ region.digit }}</span> · {{ region.places }}
                    </span>
                </li>
            </ul>


        </div>
    </div>
</template>
