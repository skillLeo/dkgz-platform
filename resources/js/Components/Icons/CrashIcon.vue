<script setup>
/**
 * Two cars meeting, with the impact above them.
 *
 * Drawn rather than borrowed: the icon set has a car, a shield and a document,
 * and none of them says "this is the one after a crash" at a glance — which is
 * the whole job of the first choice on the homepage. Same 24-unit grid, same
 * round caps and joins and the same stroke width as everything around it, so it
 * sits in the set rather than beside it.
 */
defineProps({
    size: { type: Number, default: 24 },
    strokeWidth: { type: Number, default: 1.75 },
})
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        :stroke-width="strokeWidth"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <!--
            Drawn across nearly the whole grid rather than hunched in the middle
            of it. Sharing a pixel size with the borrowed icons is not the same
            as matching them: those fill their box, and this one sat in the upper
            two thirds of its own, so beside them it read a size smaller than it
            was.
        -->
        <!-- The two bonnets, stopped short of each other where they met. -->
        <path d="M2 17.4v-2.6l2.1-2.7h5.5l2.2 2.7v2.6" />
        <path d="M22 17.4v-2.6l-2.1-2.7h-5.5l-2.2 2.7v2.6" />
        <path d="M2 17.4h9.8" />
        <path d="M12.2 17.4H22" />
        <circle cx="5.2" cy="19.3" r="1.6" />
        <circle cx="9.5" cy="19.3" r="1.6" />
        <circle cx="14.5" cy="19.3" r="1.6" />
        <circle cx="18.8" cy="19.3" r="1.6" />
        <!-- The impact, thrown upwards from the point they touch. -->
        <path d="M12 2.6v3.1" />
        <path d="M8.1 4.1l1.7 2.6" />
        <path d="M15.9 4.1l-1.7 2.6" />
    </svg>
</template>
