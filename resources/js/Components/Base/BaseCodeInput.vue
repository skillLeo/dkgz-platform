<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import FieldError from '../Feedback/FieldError.vue'

/**
 * Auth Shell §Teil B: six 52×56px cells, mono, the active cell carries the
 * focus ring. Validates live — one of only two fields that does.
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    length: { type: Number, default: 6 },
    label: { type: String, default: 'Bestätigungscode' },
    error: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'complete'])

const inputs = ref([])
const digits = computed(() => {
    const chars = String(props.modelValue ?? '').split('')

    return Array.from({ length: props.length }, (_, i) => chars[i] ?? '')
})

const write = (values) => {
    const next = values.join('').slice(0, props.length)
    emit('update:modelValue', next)

    if (next.length === props.length) emit('complete', next)
}

const onInput = (index, event) => {
    const raw = event.target.value.replace(/[^0-9A-Za-z]/g, '').toUpperCase()
    const current = digits.value.slice()

    if (raw.length > 1) {
        // A paste: spread it across the remaining cells.
        raw.split('').forEach((char, offset) => {
            if (index + offset < props.length) current[index + offset] = char
        })
        write(current)
        nextTick(() => inputs.value[Math.min(index + raw.length, props.length - 1)]?.focus())

        return
    }

    current[index] = raw
    write(current)

    if (raw && index < props.length - 1) {
        nextTick(() => inputs.value[index + 1]?.focus())
    }
}

const onKeydown = (index, event) => {
    if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
        inputs.value[index - 1]?.focus()
    }
    if (event.key === 'ArrowLeft' && index > 0) inputs.value[index - 1]?.focus()
    if (event.key === 'ArrowRight' && index < props.length - 1) inputs.value[index + 1]?.focus()
}

watch(() => props.modelValue, (value) => {
    if (!value) inputs.value[0]?.focus?.()
})
</script>

<template>
    <div>
        <span class="block pb-2 text-sm font-medium text-gray-800">{{ label }}</span>

        <div class="flex gap-2" role="group" :aria-label="label">
            <input
                v-for="(digit, index) in digits"
                :key="index"
                :ref="(el) => (inputs[index] = el)"
                :value="digit"
                type="text"
                inputmode="numeric"
                maxlength="1"
                :disabled="disabled"
                :aria-label="`Zeichen ${index + 1} von ${length}`"
                :aria-invalid="error ? 'true' : undefined"
                class="h-14 w-13 rounded-sm border text-center font-mono text-[22px] tabular-nums text-gray-800 outline-none transition-colors duration-(--duration-focus) ease-(--ease-dkgz)"
                :class="error
                    ? 'border-danger bg-danger/3'
                    : 'border-gray-300 bg-white focus:border-navy-700 focus:outline-2 focus:outline-navy-500 focus:outline-offset-2'"
                @input="onInput(index, $event)"
                @keydown="onKeydown(index, $event)"
            >
        </div>

        <FieldError v-if="error" :message="error" />
    </div>
</template>
