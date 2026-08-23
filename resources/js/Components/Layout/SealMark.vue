<script setup>
import { computed } from 'vue'

/**
 * The DKGZ seal: a navy ring, a gold inner ring, and the letters inside.
 *
 * The header, the badge over the hero photograph and the wordmark lockup each
 * built this by hand at slightly different proportions, so the seal in the
 * header read as a plain circle next to the badge on the same page. One
 * component, one set of ratios, scaled from a single number — whatever size it
 * is asked for, it is recognisably the same mark.
 */
const props = defineProps({
    /** Outer diameter in pixels. */
    size: { type: Number, default: 44 },
    /** On a dark ground the rings and letters invert. */
    inverted: { type: Boolean, default: false },
})

// Ratios taken from the badge over the hero image, which is the version the
// design treats as canonical.
const inner = computed(() => Math.round(props.size * 0.77))
const lettering = computed(() => Math.max(6, Math.round(props.size * 0.182)))
</script>

<template>
    <span
        class="grid shrink-0 place-items-center rounded-full border"
        :class="inverted ? 'border-white/70' : 'border-navy-700'"
        :style="{ width: `${size}px`, height: `${size}px` }"
        aria-hidden="true"
    >
        <span
            class="grid place-items-center rounded-full border"
            :style="{
                width: `${inner}px`,
                height: `${inner}px`,
                borderColor: 'var(--dkgz-accent)',
            }"
        >
            <span
                class="font-bold tracking-label"
                :class="inverted ? 'text-white' : 'text-navy-700'"
                :style="{ fontSize: `${lettering}px`, lineHeight: 1 }"
            >DKGZ</span>
        </span>
    </span>
</template>
