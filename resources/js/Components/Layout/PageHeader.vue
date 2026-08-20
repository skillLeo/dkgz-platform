<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
})
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-6 pb-6">
        <div class="min-w-0">
            <p v-if="eyebrow" class="text-eyebrow font-semibold uppercase text-gray-600">{{ eyebrow }}</p>
            <h1 class="text-h2 font-semibold text-navy-700" :class="eyebrow ? 'pt-2' : ''">{{ title }}</h1>
            <p v-if="description" class="measure pt-2 text-base leading-normal text-gray-600">{{ description }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-3 empty:hidden">
            <slot name="actions" />
        </div>
    </div>
</template>
