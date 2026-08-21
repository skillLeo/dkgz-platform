<script setup>
import { computed } from 'vue'
import { OUTLINE, VIEW_BOX, ZONES } from '../../Support/germany.js'

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

const cells = computed(() => props.regions
    .filter((region) => ZONES[region.digit])
    .map((region) => ({ ...region, ...ZONES[region.digit] })))

const coveredCount = computed(() => props.regions.filter((r) => r.covered).length)
</script>

<template>
    <div class="flex flex-col gap-10 md:flex-row md:items-start md:gap-14">
        <figure class="w-full max-w-88 shrink-0">
            <svg
                :viewBox="VIEW_BOX"
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
