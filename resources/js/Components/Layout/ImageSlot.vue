<script setup>
import { Image as ImageIcon } from 'lucide-vue-next'

/**
 * The production counterpart to the design's <image-slot>.
 *
 * When the admin has uploaded a picture it fills the frame. When they have
 * not, the frame keeps the design's empty state — a 1.5px dashed ring at 35 %
 * opacity, a centred icon and the caption describing what belongs here — so
 * the layout never collapses and whoever maintains the site can see at a
 * glance which slot is still unfilled.
 */
defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: '' },
    caption: { type: String, default: 'Bild hinterlegen' },
    rounded: { type: Boolean, default: true },
})
</script>

<template>
    <div
        class="relative h-full w-full overflow-hidden bg-gray-50"
        :class="rounded ? 'rounded-card' : ''"
    >
        <img v-if="src" :src="src" :alt="alt" class="h-full w-full object-cover">

        <template v-else>
            <span
                class="pointer-events-none absolute inset-0 border-(length:--spacing-hairline) border-dashed border-gray-400 opacity-35"
                :class="rounded ? 'rounded-card' : ''"
                aria-hidden="true"
            />
            <div class="flex h-full w-full flex-col items-center justify-center gap-3 px-8 text-center">
                <ImageIcon :size="24" :stroke-width="1.5" class="text-gray-400" aria-hidden="true" />
                <p class="measure-note text-sm leading-normal text-gray-600">{{ caption }}</p>
            </div>
        </template>
    </div>
</template>
