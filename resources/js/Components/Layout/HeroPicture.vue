<script setup>
import { computed } from 'vue'
import BrandSeal from './BrandSeal.vue'
import SealMark from './SealMark.vue'
import ImageSlot from './ImageSlot.vue'

/**
 * The picture beside the headline on the service and city pages, with the seal
 * card on its corner.
 *
 * The same frame as the homepage's, and answering to the homepage's settings:
 * the size the operator sets there and its switch for phones apply here too,
 * so the pictures read as one family and there is one place to change them.
 * One component for the three pages, so those at least cannot drift apart.
 */
const props = defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: '' },
    caption: { type: String, default: 'Bild hinterlegen' },
    /** The operator's size in percent, as typed. Anything unreadable is 100. */
    size: { type: [String, Number], default: '' },
    onMobile: { type: Boolean, default: false },
    sealTitle: { type: String, default: '' },
    sealText: { type: String, default: '' },
})

const WIDTH = 400
const HEIGHT = 480

const scale = computed(() => {
    const typed = Number.parseInt(String(props.size ?? ''), 10)

    if (! Number.isFinite(typed)) return 1

    return Math.min(140, Math.max(60, typed)) / 100
})

const frameSize = computed(() => ({
    '--hero-w': `${Math.round(WIDTH * scale.value)}px`,
    '--hero-h': `${Math.round(HEIGHT * scale.value)}px`,
}))
</script>

<template>
    <!--
        Hidden on a phone unless the switch says otherwise, and then only small:
        at full width it pushed what the hero is for off the first screen, which
        is the one thing the hero cannot afford.
    -->
    <div class="relative lg:pb-6 lg:pl-6" :class="onMobile ? 'pt-10 lg:pt-0' : 'hidden lg:block'">
        <!--
            Sized to the picture, and the seal hangs off this rather than off the
            column.

            Pinned to the column it was pinned to the column's bottom-left corner,
            and the picture is centred inside a column wider than it is — so the
            seal drifted out into the empty margin beside the photograph and read
            as a card that had come loose from it.

            Left on a phone, centred from lg up. A small picture centred under a
            column of left-aligned text reads as something that fell there rather
            than as part of the page. On a wide screen it is centred in a column
            wider than itself, which is a different problem with a different
            answer.
        -->
        <div class="relative max-w-[220px] lg:mx-auto lg:max-w-(--hero-w)" :style="frameSize">
            <!--
                The frame holds the briefed 4:5 ratio rather than a fixed height.
                Locked to a pixel height it stretched or cropped as the column
                widened, which is what made the hero look wrong on a large screen.

                The operator's size applies from lg up, as a pair of custom
                properties — an inline max-width would beat any class and there
                would be no way to hold it smaller on a phone.
            -->
            <div class="aspect-4/5 w-full overflow-hidden rounded-card border border-gray-200 lg:max-h-(--hero-h)">
                <ImageSlot :src="src" :alt="alt" :caption="caption" />
            </div>
            <!--
                Overlapping the picture's own corner by a fixed amount, so it
                reads as sitting on the photograph however wide the column happens
                to be. Hidden on a phone, where a 220px picture has no corner to
                spare.
            -->
            <div
                v-if="sealTitle || sealText"
                class="absolute -bottom-6 -left-6 hidden items-center gap-3.5 rounded-card border border-gray-200 bg-white px-5 py-4 shadow-(--shadow-1) lg:flex"
            >
                <BrandSeal :size="44">
                    <SealMark :size="44" />
                </BrandSeal>
                <span>
                    <span class="block text-base font-semibold leading-snug text-navy-700">{{ sealTitle }}</span>
                    <span class="block text-sm leading-snug text-gray-600">{{ sealText }}</span>
                </span>
            </div>
        </div>
    </div>
</template>
