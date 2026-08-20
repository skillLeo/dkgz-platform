<script setup>
import { computed } from 'vue'

/**
 * Where DKGZ currently has partners, drawn on Germany.
 *
 * The outline is a simplified national silhouette — enough to be recognised at
 * a glance, not a survey drawing. Each of the ten postal regions sits at its
 * real approximate position, so the map tells the truth about geography without
 * claiming precision it does not have. Covered regions are filled; uncovered
 * ones stay hollow, which is the distinction the whole graphic exists to make.
 */
const props = defineProps({
    regions: { type: Array, default: () => [] },
})

/**
 * Approximate centre of each postal zone in the outline's coordinate space.
 * Zone 0 is Saxony, 2 the north coast, 8 Bavaria — the German postal zones run
 * roughly west-to-east and north-to-south in that order.
 */
const positions = {
    0: { x: 76, y: 63, label: 'Dresden' },
    1: { x: 75, y: 41, label: 'Berlin' },
    2: { x: 41, y: 18, label: 'Hamburg' },
    3: { x: 41, y: 44, label: 'Hannover' },
    4: { x: 19, y: 53, label: 'Düsseldorf' },
    5: { x: 16, y: 66, label: 'Köln' },
    6: { x: 31, y: 77, label: 'Frankfurt' },
    7: { x: 33, y: 97, label: 'Stuttgart' },
    8: { x: 57, y: 101, label: 'München' },
    9: { x: 52, y: 82, label: 'Nürnberg' },
}

/** A simplified outline of Germany, traced clockwise from the Danish border. */
const OUTLINE = `
  M46,3 L52,2 L55,8 L60,7 L63,11 L70,9 L74,13 L72,18 L78,20 L82,26
  L80,32 L85,36 L84,42 L88,47 L86,53 L90,58 L88,64 L83,68 L85,74
  L80,79 L83,86 L79,92 L73,95 L68,101 L64,108 L57,112 L50,110
  L44,113 L38,110 L34,112 L30,106 L26,99 L22,95 L25,88 L20,84
  L16,78 L12,72 L9,65 L6,58 L10,52 L8,45 L12,39 L17,34 L15,27
  L20,21 L27,17 L33,13 L39,11 L43,6 Z
`.trim().replace(/\s+/g, ' ')

const cells = computed(() => props.regions
    .filter((region) => positions[region.digit])
    .map((region) => ({ ...region, ...positions[region.digit] })))

const coveredCount = computed(() => props.regions.filter((r) => r.covered).length)
</script>

<template>
    <div class="flex flex-col gap-10 md:flex-row md:items-start md:gap-14">
        <figure class="w-full max-w-88 shrink-0">
            <svg
                viewBox="0 0 100 120"
                class="h-auto w-full"
                role="img"
                :aria-label="`Abdeckung in Deutschland: ${coveredCount} von ${regions.length} Postleitregionen`"
            >
                <path
                    :d="OUTLINE"
                    class="fill-gray-100 stroke-gray-300"
                    stroke-width="0.8"
                    stroke-linejoin="round"
                />

                <g v-for="cell in cells" :key="cell.digit">
                    <circle
                        :cx="cell.x"
                        :cy="cell.y"
                        r="5.2"
                        :class="cell.covered
                            ? 'fill-navy-700 stroke-navy-700'
                            : 'fill-white stroke-gray-300'"
                        stroke-width="0.8"
                    />
                    <text
                        :x="cell.x"
                        :y="cell.y + 1.9"
                        text-anchor="middle"
                        class="font-mono"
                        style="font-size: 5px"
                        :class="cell.covered ? 'fill-white' : 'fill-gray-400'"
                    >{{ cell.digit }}</text>
                </g>
            </svg>

            <figcaption class="flex flex-wrap items-center gap-x-5 gap-y-2 pt-4">
                <span class="inline-flex items-center gap-2">
                    <span class="block h-2.5 w-2.5 rounded-full bg-navy-700" aria-hidden="true" />
                    <span class="text-sm text-gray-800">Partner verfügbar</span>
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="block h-2.5 w-2.5 rounded-full border border-gray-300 bg-white" aria-hidden="true" />
                    <span class="text-sm text-gray-600">Noch kein Partner</span>
                </span>
            </figcaption>
        </figure>

        <div class="min-w-0">
            <ul class="grid gap-x-8 gap-y-2.5 sm:grid-cols-2">
                <li
                    v-for="region in cells"
                    :key="`legende-${region.digit}`"
                    class="flex items-baseline gap-2.5"
                >
                    <span
                        class="mt-1.5 block h-1.5 w-1.5 shrink-0 rounded-full"
                        :class="region.covered ? 'bg-navy-700' : 'bg-gray-300'"
                        aria-hidden="true"
                    />
                    <span class="min-w-0 text-sm" :class="region.covered ? 'text-gray-800' : 'text-gray-400'">
                        <span class="font-mono tabular-nums">{{ region.digit }}</span> · {{ region.places }}
                    </span>
                </li>
            </ul>

            <p class="measure pt-6 text-sm leading-normal text-gray-600">
                Die Karte zeigt, in welchen Postleitregionen derzeit freigegebene und verfügbare
                Sachverständige vermittelt werden können. Sie entsteht aus den hinterlegten
                Einsatzgebieten unserer Partner und wächst mit dem Netz.
            </p>
        </div>
    </div>
</template>
