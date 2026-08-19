<script setup>
import { computed } from 'vue'
import FieldError from '../Feedback/FieldError.vue'

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    name: { type: String, required: true },
    options: { type: Array, default: () => [] },
    legend: { type: String, default: '' },
    error: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const normalised = computed(() =>
    props.options.map((option) =>
        typeof option === 'object' && option !== null
            ? { value: option.value, label: option.label, description: option.description ?? null }
            : { value: option, label: String(option), description: null }
    )
)
</script>

<template>
    <fieldset :disabled="disabled">
        <legend v-if="legend" class="pb-2 text-sm font-medium text-gray-800">{{ legend }}</legend>

        <div class="flex flex-col gap-3">
            <label
                v-for="option in normalised"
                :key="option.value"
                class="flex cursor-pointer gap-3"
            >
                <span class="relative mt-0.5 flex h-(--spacing-check) w-(--spacing-check) shrink-0">
                    <input
                        type="radio"
                        :name="name"
                        :value="option.value"
                        :checked="modelValue === option.value"
                        class="peer absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        @change="emit('update:modelValue', option.value)"
                    >
                    <span
                        class="pointer-events-none grid h-(--spacing-check) w-(--spacing-check) place-items-center rounded-full border transition-colors duration-(--duration-hover) ease-(--ease-dkgz) peer-focus-visible:outline-2 peer-focus-visible:outline-navy-500 peer-focus-visible:outline-offset-2"
                        :class="modelValue === option.value ? 'border-navy-700' : 'border-gray-300'"
                        aria-hidden="true"
                    >
                        <span v-if="modelValue === option.value" class="h-2 w-2 rounded-full bg-navy-700" />
                    </span>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm text-gray-800">{{ option.label }}</span>
                    <span v-if="option.description" class="block text-xs text-gray-600">{{ option.description }}</span>
                </span>
            </label>
        </div>

        <FieldError v-if="error" :message="error" />
    </fieldset>
</template>
