<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], default: 0 },
    cents: { type: [Number, null], default: null },
    hint: { type: String, default: '' },
    href: { type: String, default: null },
    tone: { type: String, default: 'default' },
})

const { money } = useGermanFormat()

const display = computed(() => (props.cents !== null ? money(props.cents) : props.value))

const valueClass = computed(() => ({
    default: 'text-navy-700',
    warning: 'text-warning',
    danger: 'text-danger',
    success: 'text-success',
}[props.tone] ?? 'text-navy-700'))
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        class="block rounded-card border border-gray-200 bg-white p-5 transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
        :class="href ? 'hover:border-gray-300' : ''"
    >
        <p class="text-eyebrow font-semibold uppercase text-gray-600">{{ label }}</p>
        <p class="pt-3 text-h2 font-semibold tabular-nums" :class="valueClass">{{ display }}</p>
        <p v-if="hint" class="text-sm text-gray-600">{{ hint }}</p>
    </component>
</template>
