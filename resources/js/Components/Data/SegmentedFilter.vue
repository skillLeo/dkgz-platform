<script setup>
import { Link } from '@inertiajs/vue3'

/**
 * The joined segment control from the portal lists: one bordered group whose
 * active segment fills navy. Segments are links, so a filtered list stays
 * shareable and survives a reload.
 */
defineProps({
    segments: { type: Array, required: true },
    current: { type: [String, null], default: null },
})
</script>

<template>
    <div class="inline-flex overflow-hidden rounded-sm border border-gray-300">
        <Link
            v-for="(segment, index) in segments"
            :key="segment.label"
            :href="segment.href"
            preserve-scroll
            class="inline-flex h-8 items-center px-3.5 text-sm transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
            :class="[
                index > 0 ? 'border-l border-gray-300' : '',
                segment.value === current
                    ? 'bg-navy-700 font-medium text-white'
                    : 'bg-white text-gray-800 hover:bg-gray-50',
            ]"
            :aria-current="segment.value === current ? 'true' : undefined"
        >{{ segment.label }}</Link>
    </div>
</template>
