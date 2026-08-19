<script setup>
import { computed } from 'vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    cents: { type: [Number, null], default: null },
    emphasis: { type: Boolean, default: false },
    muted: { type: Boolean, default: false },
})

const { money } = useGermanFormat()
const formatted = computed(() => (props.cents === null ? '—' : money(props.cents)))
</script>

<template>
    <span
        class="whitespace-nowrap font-mono tabular-nums"
        :class="[
            emphasis ? 'text-base font-medium text-navy-700' : 'text-sm',
            muted ? 'text-gray-400' : (emphasis ? '' : 'text-gray-800'),
        ]"
    >{{ formatted }}</span>
</template>
