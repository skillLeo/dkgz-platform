<script setup>
import BaseInput from './BaseInput.vue'

/**
 * German VAT identification number. The DE prefix is fixed furniture, so only
 * the nine digits are editable and the model value carries the full string.
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: 'USt-IdNr.' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'blur'])

const digitsOnly = (value) => String(value ?? '').replace(/^DE/i, '').replace(/[^0-9]/g, '').slice(0, 9)

const onInput = (value) => {
    const digits = digitsOnly(value)
    emit('update:modelValue', digits ? `DE${digits}` : '')
}
</script>

<template>
    <BaseInput
        :model-value="digitsOnly(modelValue)"
        :label="label"
        :error="error"
        :required="required"
        :disabled="disabled"
        prefix="DE"
        placeholder="298114552"
        inputmode="numeric"
        maxlength="9"
        mono
        numeric
        hint="Neun Ziffern nach dem Länderkürzel."
        @update:model-value="onInput"
        @blur="emit('blur', $event)"
    />
</template>
