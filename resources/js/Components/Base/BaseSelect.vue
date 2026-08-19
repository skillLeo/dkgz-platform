<script setup>
import { computed } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import FieldError from '../Feedback/FieldError.vue'

const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    label: { type: String, required: true },
    options: { type: Array, default: () => [] },
    id: { type: String, default: null },
    placeholder: { type: String, default: 'Bitte wählen' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    optional: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'blur'])

const uid = `sel-${Math.random().toString(36).slice(2, 9)}`
const fieldId = computed(() => props.id ?? uid)

// Accepts either primitives or {value, label} objects.
const normalised = computed(() =>
    props.options.map((option) =>
        typeof option === 'object' && option !== null
            ? { value: option.value, label: option.label }
            : { value: option, label: String(option) }
    )
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

        <div class="relative">
            <select
                :id="fieldId"
                :value="modelValue"
                :disabled="disabled"
                :required="required"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="error ? `${fieldId}-fehler` : (hint ? `${fieldId}-hinweis` : undefined)"
                class="w-full h-(--spacing-control) appearance-none rounded-sm border pl-3.5 pr-10 text-base outline-none transition-colors duration-(--duration-focus) ease-(--ease-dkgz)"
                :class="disabled
                    ? 'bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed'
                    : error
                        ? 'bg-danger/3 text-gray-800 border-danger focus:outline-2 focus:outline-danger focus:outline-offset-2'
                        : 'bg-white border-gray-300 hover:border-gray-400 focus:border-navy-700 focus:outline-2 focus:outline-navy-500 focus:outline-offset-2'"
                :style="{ color: modelValue === '' || modelValue === null ? 'var(--color-gray-400)' : 'var(--color-gray-800)' }"
                @change="emit('update:modelValue', $event.target.value)"
                @blur="emit('blur', $event)"
            >
                <option value="" disabled>{{ placeholder }}</option>
                <option v-for="option in normalised" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <ChevronDown
                :size="16"
                :stroke-width="1.5"
                class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-600"
                aria-hidden="true"
            />
        </div>

        <p v-if="hint && !error" :id="`${fieldId}-hinweis`" class="pt-1.5 text-xs text-gray-400">{{ hint }}</p>
        <FieldError v-if="error" :id="`${fieldId}-fehler`" :message="error" />
    </div>
</template>
