<script setup>
import { computed } from 'vue'
import BaseInput from './BaseInput.vue'

/**
 * A native date control, because it is keyboard-accessible and localised for
 * free. The value on the wire stays ISO; the design's 17.08.2026 rendering is
 * the browser's job here and useGermanFormat's everywhere else.
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, required: true },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    min: { type: String, default: null },
    max: { type: String, default: null },
    required: { type: Boolean, default: false },
    optional: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'blur'])

const uid = `dp-${Math.random().toString(36).slice(2, 9)}`
</script>

<template>
    <div>
        <div class="flex items-baseline justify-between gap-4 pb-2">
            <label :for="uid" class="text-sm font-medium text-gray-800">
                {{ label }}<span v-if="required" class="text-danger"> *</span>
            </label>
            <span v-if="optional" class="text-sm text-gray-400">optional</span>
        </div>

        <input
            :id="uid"
            :value="modelValue"
            type="date"
            :min="min"
            :max="max"
            :required="required"
            :disabled="disabled"
            :aria-invalid="error ? 'true' : undefined"
            class="w-full h-(--spacing-control) rounded-sm border px-3.5 text-base tabular-nums outline-none transition-colors duration-(--duration-focus) ease-(--ease-dkgz)"
            :class="disabled
                ? 'bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed'
                : error
                    ? 'bg-danger/3 text-gray-800 border-danger focus:outline-2 focus:outline-danger focus:outline-offset-2'
                    : 'bg-white text-gray-800 border-gray-300 hover:border-gray-400 focus:border-navy-700 focus:outline-2 focus:outline-navy-500 focus:outline-offset-2'"
            @input="emit('update:modelValue', $event.target.value)"
            @blur="emit('blur', $event)"
        >

        <p v-if="hint && !error" class="pt-1.5 text-xs text-gray-400">{{ hint }}</p>
        <p v-if="error" class="pt-1.5 text-xs text-danger" role="alert">{{ error }}</p>
    </div>
</template>
