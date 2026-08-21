<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Shows the uploaded logo where one has been set, and otherwise the designed
 * wordmark that the slot carries.
 *
 * The layouts each build the DKGZ lockup in their own markup, tuned to the
 * surface they sit on, and none of them looked at the branding settings — so an
 * operator could upload a logo, see it saved, and watch nothing change anywhere
 * they looked. Wrapping those blocks here keeps every one of those lockups
 * exactly as designed while giving all of them the same one line of truth: if a
 * logo exists for this background, it wins.
 *
 * A dark background asks for logo_dark and falls back to logo_light, because a
 * client who uploads only one file means it for the whole site.
 */
const props = defineProps({
    /** Sitting on a dark ground: prefer the inverted file. */
    inverted: { type: Boolean, default: false },
    /** Rendered height; matches the wordmark it replaces on each surface. */
    height: { type: String, default: 'h-9' },
})

const page = usePage()
const branding = computed(() => page.props.branding ?? {})

const source = computed(() => (props.inverted
    ? branding.value.logo_dark || branding.value.logo_light
    : branding.value.logo_light || branding.value.logo_dark) || null)
</script>

<template>
    <img
        v-if="source"
        :src="source"
        :alt="branding.platform_name ?? 'DKGZ'"
        class="block w-auto max-w-44 object-contain"
        :class="height"
    >
    <slot v-else />
</template>
