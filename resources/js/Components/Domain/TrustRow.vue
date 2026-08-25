<script setup>
import { BadgeCheck, Clock, ShieldCheck } from 'lucide-vue-next'

/**
 * The three things somebody wants to know before they type anything.
 *
 * It sits under the opening question rather than further down the page,
 * because that is where the hesitation is: a stranger being asked for their
 * telephone number wants to know what it costs, who is on the other end, and
 * how long this is going to take, and by the time they have scrolled to find
 * out they have gone.
 */
defineProps({
    /** Quieter, for inside the request flow where the page is already trusted. */
    compact: { type: Boolean, default: false },
})

const POINTS = [
    { icon: BadgeCheck, label: 'Kostenfrei & unverbindlich' },
    { icon: ShieldCheck, label: 'Zertifizierte Partner' },
    { icon: Clock, label: 'In wenigen Schritten' },
]
</script>

<template>
    <ul
        class="flex flex-wrap items-center gap-x-6 gap-y-2.5 text-gray-600"
        :class="compact ? 'text-xs' : 'text-sm'"
    >
        <li v-for="point in POINTS" :key="point.label" class="flex items-center gap-2">
            <component
                :is="point.icon"
                :size="compact ? 14 : 16"
                :stroke-width="1.5"
                class="shrink-0"
                style="color: var(--dkgz-accent)"
                aria-hidden="true"
            />
            {{ point.label }}
        </li>
    </ul>
</template>
