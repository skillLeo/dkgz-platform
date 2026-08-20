<script setup>
import { computed } from 'vue'

/**
 * Where DKGZ currently has partners, by postal region.
 *
 * Drawn in the same flat language as the partner portal's coverage raster:
 * navy-100 fill for covered, a hairline outline, no shadow and no illustration.
 * The regions are the ten German postal zones, laid out roughly as they sit on
 * the map — 0 south-east, 2 north, 8 south — so the shape reads as Germany
 * without pretending to be a survey drawing.
 */
const props = defineProps({
    regions: { type: Array, default: () => [] },
})

/** Grid position per leading digit, approximating where each zone sits. */
const placement = {
    2: { col: 3, row: 1 },
    1: { col: 4, row: 2 },
    3: { col: 2, row: 2 },
    4: { col: 1, row: 3 },
    5: { col: 1, row: 4 },
    0: { col: 4, row: 3 },
    6: { col: 2, row: 4 },
    9: { col: 3, row: 4 },
    7: { col: 2, row: 5 },
    8: { col: 3, row: 5 },
}

const cells = computed(() => props.regions.map((region) => ({
    ...region,
    style: {
        gridColumn: placement[region.digit]?.col ?? 1,
        gridRow: placement[region.digit]?.row ?? 1,
    },
})))

const coveredCount = computed(() => props.regions.filter((r) => r.covered).length)
</script>

<template>
    <div class="flex flex-col gap-8 md:flex-row md:items-start md:gap-12">
        <div
            class="grid w-full max-w-70 shrink-0 grid-cols-4 gap-(--spacing-raster)"
            role="img"
            :aria-label="`Abdeckung: ${coveredCount} von ${regions.length} Postleitzahlregionen`"
        >
            <span
                v-for="cell in cells"
                :key="cell.digit"
                class="grid aspect-square place-items-center rounded-xs border font-mono text-meta tabular-nums"
                :class="cell.covered
                    ? 'border-navy-500 bg-navy-100 text-navy-700'
                    : 'border-gray-200 bg-gray-50 text-gray-400'"
                :style="cell.style"
                :title="`${cell.label} · ${cell.places}`"
            >{{ cell.digit }}</span>
        </div>

        <div class="min-w-0">
            <ul class="grid gap-x-8 gap-y-2 sm:grid-cols-2">
                <li
                    v-for="region in regions"
                    :key="`legende-${region.digit}`"
                    class="flex items-baseline gap-2.5"
                >
                    <span
                        class="mt-1.5 block h-1.5 w-1.5 shrink-0 rounded-full"
                        :class="region.covered ? 'bg-navy-500' : 'bg-gray-300'"
                        aria-hidden="true"
                    />
                    <span class="min-w-0 text-sm" :class="region.covered ? 'text-gray-800' : 'text-gray-400'">
                        <span class="font-mono tabular-nums">{{ region.digit }}</span> · {{ region.places }}
                    </span>
                </li>
            </ul>

            <p class="measure pt-5 text-sm leading-normal text-gray-600">
                Die Karte zeigt, in welchen Postleitzahlregionen derzeit freigegebene und verfügbare
                Sachverständige vermittelt werden können. Sie wird aus den hinterlegten Einsatzgebieten
                unserer Partner gebildet und ändert sich mit dem Netz.
            </p>
        </div>
    </div>
</template>
