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
        class="block border border-gray-200 bg-white p-5 transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
        :class="href ? 'hover:border-gray-300' : ''"
    >
        <p class="text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">{{ label }}</p>
        <p class="pt-2.5 font-mono text-h3 tabular-nums" :class="valueClass">{{ display }}</p>
        <p v-if="hint" class="pt-1 text-xs text-gray-400">{{ hint }}</p>
    </component>
</template>
