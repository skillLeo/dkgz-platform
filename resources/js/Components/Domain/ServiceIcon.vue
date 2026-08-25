<script setup>
import { computed } from 'vue'
import { iconFor } from '../../Support/serviceIcons.js'

/**
 * The icon belonging to one service.
 *
 * A component rather than a call in each template, so every surface that shows
 * a service — the homepage, the services page, a city page — draws the same
 * mark without anyone having to remember to.
 */
const props = defineProps({
    /** The service, or the icon name on its own. */
    service: { type: [Object, String], default: null },
    size: { type: Number, default: 20 },
    strokeWidth: { type: Number, default: 1.5 },
})

const component = computed(() => iconFor(props.service))
</script>

<template>
    <component :is="component" :size="size" :stroke-width="strokeWidth" aria-hidden="true" />
</template>
