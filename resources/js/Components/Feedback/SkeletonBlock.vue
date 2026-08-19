<script setup>
defineProps({
    lines: { type: Number, default: 3 },
    height: { type: String, default: 'h-4' },
})
</script>

<template>
    <div class="flex flex-col gap-2" aria-hidden="true">
        <span
            v-for="line in lines"
            :key="line"
            class="block bg-gray-100"
            :class="[height, line === lines ? 'w-2/3' : 'w-full']"
        />
    </div>
</template>
