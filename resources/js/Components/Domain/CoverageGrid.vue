<script setup>
import { computed } from 'vue'

/**
 * The schematic coverage raster from the Einsatzgebiet screen.
 *
 * One cell per two-digit postal prefix, 00–99, laid out ten by ten. The design
 * draws a 16-wide raster of empty cells; ten columns is the same picture but
 * every cell stands for a real prefix, so the map actually reports coverage
 * instead of decorating the panel. It is still schematic — the caption says so,
 * and the real map wants PLZ geodata.
 */
const props = defineProps({
    areas: { type: Array, default: () => [] },
})

/** Prefixes fully inside a range are core; partly covered ones are the fringe. */
const cells = computed(() => Array.from({ length: 100 }, (unused, prefix) => {
    const low = prefix * 1000
    const high = low + 999

    let covered = 0

    for (const area of props.areas) {
        const from = Number(area.postal_code_from)
        const to = Number(area.postal_code_to)

        if (Number.isNaN(from) || Number.isNaN(to)) continue

        const overlap = Math.min(high, to) - Math.max(low, from) + 1

        if (overlap > 0) covered += overlap
    }

    return {
        prefix: String(prefix).padStart(2, '0'),
        tone: covered >= 1000 ? 'core' : covered > 0 ? 'edge' : 'none',
    }
}))

const coreCount = computed(() => cells.value.filter((c) => c.tone === 'core').length)
</script>

<template>
    <div>
        <p class="pb-3 text-eyebrow font-semibold uppercase text-gray-600">Abdeckung</p>

        <div class="grid grid-cols-10 gap-(--spacing-raster)" role="img" :aria-label="`Schematische Abdeckung: ${coreCount} von 100 Postleitzahlbereichen`">
            <span
                v-for="cell in cells"
                :key="cell.prefix"
                class="aspect-square rounded-xs"
                :class="{
                    'bg-navy-700': cell.tone === 'core',
                    'bg-navy-500/35': cell.tone === 'edge',
                    'bg-gray-100': cell.tone === 'none',
                }"
                :title="`PLZ ${cell.prefix}xxx`"
            />
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-3">
            <span class="inline-flex items-center gap-2">
                <span class="block h-2.5 w-2.5 rounded-xs bg-navy-700" aria-hidden="true" />
                <span class="text-xs text-gray-600">Ihr Kerngebiet</span>
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="block h-2.5 w-2.5 rounded-xs bg-navy-500/35" aria-hidden="true" />
                <span class="text-xs text-gray-600">Randgebiet</span>
            </span>
            <span class="font-mono text-meta text-gray-400">
                Schematische Rasterkarte · finale Karte auf PLZ-Geodaten
            </span>
        </div>
    </div>
</template>
