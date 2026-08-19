<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    value: { type: String, required: true },
    href: { type: String, default: null },
    size: { type: String, default: 'sm' },
})
</script>

<template>
    <component
        :is="href ? Link : 'span'"
        :href="href"
        class="whitespace-nowrap font-mono tabular-nums"
        :class="[
            size === 'lg' ? 'text-h4' : size === 'md' ? 'text-base' : 'text-sm',
            href ? 'text-navy-700 hover:text-navy-500' : 'text-gray-800',
        ]"
    >{{ value }}</component>
</template>
