<script setup>
import { computed } from 'vue'
import { Check } from 'lucide-vue-next'
import FieldError from '../Feedback/FieldError.vue'

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    id: { type: String, default: null },
    error: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const uid = `cb-${Math.random().toString(36).slice(2, 9)}`
const fieldId = computed(() => props.id ?? uid)
</script>

<template>
    <div>
        <div class="flex gap-3">
            <span class="relative mt-0.5 flex h-[18px] w-[18px] shrink-0">
                <input
                    :id="fieldId"
                    type="checkbox"
                    :checked="modelValue"
                    :disabled="disabled"
                    :aria-invalid="error ? 'true' : undefined"
                    class="peer absolute inset-0 h-full w-full cursor-pointer opacity-0 disabled:cursor-not-allowed"
                    @change="emit('update:modelValue', $event.target.checked)"
                >
                <span
                    class="pointer-events-none grid h-[18px] w-[18px] place-items-center rounded-sm border transition-colors duration-(--duration-hover) ease-(--ease-dkgz) peer-focus-visible:outline-2 peer-focus-visible:outline-navy-500 peer-focus-visible:outline-offset-2"
                    :class="modelValue
                        ? 'border-navy-700 bg-navy-700 text-white'
                        : error ? 'border-danger bg-white' : 'border-gray-300 bg-white'"
                    aria-hidden="true"
                >
                    <Check v-if="modelValue" :size="12" :stroke-width="1.5" />
                </span>
            </span>
            <label :for="fieldId" class="cursor-pointer text-sm leading-snug text-gray-600">
                <slot />
            </label>
        </div>
        <FieldError v-if="error" :message="error" />
    </div>
</template>
