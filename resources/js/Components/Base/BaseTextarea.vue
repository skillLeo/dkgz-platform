<script setup>
import { computed } from 'vue'
import FieldError from '../Feedback/FieldError.vue'

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, required: true },
    id: { type: String, default: null },
    placeholder: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    rows: { type: Number, default: 4 },
    required: { type: Boolean, default: false },
    optional: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    maxlength: { type: [String, Number], default: null },
})

const emit = defineEmits(['update:modelValue', 'blur'])

const uid = `ta-${Math.random().toString(36).slice(2, 9)}`
const fieldId = computed(() => props.id ?? uid)

const remaining = computed(() =>
    props.maxlength ? Number(props.maxlength) - (props.modelValue?.length ?? 0) : null
)
</script>

<template>
    <div>
        <div class="flex items-baseline justify-between gap-4 pb-2">
            <label :for="fieldId" class="text-sm font-medium text-gray-800">
                {{ label }}<span v-if="required" class="text-danger"> *</span>
            </label>
            <span v-if="optional" class="text-sm text-gray-400">optional</span>
        </div>

        <textarea
            :id="fieldId"
            :value="modelValue"
            :rows="rows"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :maxlength="maxlength"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? `${fieldId}-fehler` : (hint ? `${fieldId}-hinweis` : undefined)"
            class="w-full resize-y rounded-sm border p-3 text-base leading-normal outline-none transition-colors duration-(--duration-focus) ease-(--ease-dkgz) placeholder:text-gray-400"
            :class="disabled
                ? 'bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed'
                : error
                    ? 'bg-danger/3 text-gray-800 border-danger focus:outline-2 focus:outline-danger focus:outline-offset-2'
                    : 'bg-white text-gray-800 border-gray-300 hover:border-gray-400 focus:border-navy-700 focus:outline-2 focus:outline-navy-500 focus:outline-offset-2'"
            @input="emit('update:modelValue', $event.target.value)"
            @blur="emit('blur', $event)"
        />

        <div class="flex items-start justify-between gap-4">
            <p v-if="hint && !error" :id="`${fieldId}-hinweis`" class="pt-1.5 text-xs text-gray-400">{{ hint }}</p>
            <FieldError v-if="error" :id="`${fieldId}-fehler`" :message="error" class="flex-1" />
            <span v-if="remaining !== null" class="shrink-0 pt-1.5 font-mono text-xs tabular-nums text-gray-400">
                {{ remaining }}
            </span>
        </div>
    </div>
</template>
