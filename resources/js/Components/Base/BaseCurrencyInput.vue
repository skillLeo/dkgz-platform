<script setup>
import { computed, ref, watch } from 'vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import FieldError from '../Feedback/FieldError.vue'

/**
 * Binds an integer number of cents. The visible value is German notation; the
 * model value is never a float. Formatting settles on blur so typing stays
 * unimpeded.
 */
const props = defineProps({
    modelValue: { type: [Number, null], default: null },
    label: { type: String, required: true },
    id: { type: String, default: null },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    placeholder: { type: String, default: '0,00' },
})

const emit = defineEmits(['update:modelValue', 'blur'])

const { amount, parseMoney } = useGermanFormat()

const uid = `cur-${Math.random().toString(36).slice(2, 9)}`
const fieldId = computed(() => props.id ?? uid)

const display = ref(props.modelValue === null ? '' : amount(props.modelValue))

watch(() => props.modelValue, (cents) => {
    const parsed = parseMoney(display.value)

    // Only rewrite the visible text when the model moved independently of the
    // keystrokes already in the field.
    if (parsed !== cents) {
        display.value = cents === null ? '' : amount(cents)
    }
})

const onInput = (event) => {
    display.value = event.target.value
    emit('update:modelValue', parseMoney(event.target.value))
}

const onBlur = (event) => {
    const cents = parseMoney(display.value)
    display.value = cents === null ? '' : amount(cents)
    emit('update:modelValue', cents)
    emit('blur', event)
}
</script>

<template>
    <div>
        <label :for="fieldId" class="block pb-2 text-sm font-medium text-gray-800">
            {{ label }}<span v-if="required" class="text-danger"> *</span>
        </label>

        <div
            class="flex items-center h-(--spacing-control) overflow-hidden rounded-sm border bg-white transition-colors duration-(--duration-focus) ease-(--ease-dkgz)"
            :class="disabled
                ? 'border-gray-300 bg-gray-100'
                : error ? 'border-danger bg-danger/3' : 'border-gray-300 focus-within:border-navy-700 focus-within:outline-2 focus-within:outline-navy-500 focus-within:outline-offset-2 hover:border-gray-400'"
        >
            <input
                :id="fieldId"
                :value="display"
                type="text"
                inputmode="decimal"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="error ? `${fieldId}-fehler` : (hint ? `${fieldId}-hinweis` : undefined)"
                class="h-full min-w-0 flex-1 border-none bg-transparent px-3.5 text-right font-mono text-base tabular-nums text-gray-800 outline-none"
                @input="onInput"
                @blur="onBlur"
            >
            <span class="flex h-full items-center border-l border-gray-200 px-3.5 text-base text-gray-600" aria-hidden="true">€</span>
        </div>

        <p v-if="hint && !error" :id="`${fieldId}-hinweis`" class="pt-1.5 text-xs text-gray-400">{{ hint }}</p>
        <FieldError v-if="error" :id="`${fieldId}-fehler`" :message="error" />
    </div>
</template>
