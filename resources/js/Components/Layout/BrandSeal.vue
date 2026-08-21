<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * The round mark at the left of the DKGZ lockup.
 *
 * Uploading a whole logo replaces the entire lockup, which is more than most
 * rebrands want: the wordmark, the gold rule and the subtitle are usually fine
 * and only the emblem needs changing. This swaps just that circle and leaves
 * everything to its right exactly as designed.
 *
 * The image is masked to a circle and contained rather than cropped, so an
 * emblem uploaded as a square with transparent corners lands the way its author
 * drew it instead of being trimmed at the edges.
 */
const props = defineProps({
    /** Rendered diameter in pixels; each surface has its own size. */
    size: { type: Number, default: 40 },
})

const page = usePage()
const branding = computed(() => page.props.branding ?? {})

const source = computed(() => branding.value.seal || null)
</script>

<template>
    <img
        v-if="source"
        :src="source"
        :alt="branding.platform_name ?? 'DKGZ'"
        class="shrink-0 rounded-full object-contain"
        :style="{ width: `${size}px`, height: `${size}px` }"
    >
    <slot v-else />
</template>
