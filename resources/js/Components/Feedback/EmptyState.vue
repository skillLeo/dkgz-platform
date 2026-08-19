<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
})
</script>

<template>
    <div class="flex flex-col items-center border border-gray-200 bg-white px-6 py-16 text-center">
        <component
            :is="icon"
            v-if="icon"
            :size="24"
            :stroke-width="1.5"
            class="text-gray-400"
            aria-hidden="true"
        />
        <p class="pt-4 text-base font-medium text-navy-700">{{ title }}</p>
        <p v-if="description" class="measure-lead pt-2 text-sm leading-normal text-gray-600">{{ description }}</p>
        <div class="pt-6 empty:hidden">
            <slot />
        </div>
    </div>
</template>
