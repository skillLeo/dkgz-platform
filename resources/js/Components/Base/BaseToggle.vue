<script setup>
import { computed } from 'vue'

/**
 * The availability switch in the portal top bar, and every boolean setting in
 * the admin. Radius stays inside the 5px ceiling — this is a rounded track,
 * not a pill.
 */
const props = defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: '' },
    description: { type: String, default: '' },
    onLabel: { type: String, default: 'Verfügbar' },
    offLabel: { type: String, default: 'Nicht verfügbar' },
    disabled: { type: Boolean, default: false },
    showState: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue'])

const uid = `tg-${Math.random().toString(36).slice(2, 9)}`
const stateLabel = computed(() => (props.modelValue ? props.onLabel : props.offLabel))
</script>

<template>
    <div class="flex items-start justify-between gap-4">
        <div v-if="label || description" class="min-w-0">
            <label :for="uid" class="block text-sm font-medium text-gray-800">{{ label }}</label>
            <p v-if="description" class="pt-1 text-xs leading-normal text-gray-600">{{ description }}</p>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <span v-if="showState" class="text-sm" :class="modelValue ? 'text-success' : 'text-gray-600'">
                {{ stateLabel }}
            </span>
            <button
                :id="uid"
                type="button"
                role="switch"
                :aria-checked="modelValue"
                :aria-label="label || stateLabel"
                :disabled="disabled"
                class="relative h-6 w-11 shrink-0 rounded-sm border transition-colors duration-(--duration-hover) ease-(--ease-dkgz) focus-visible:outline-2 focus-visible:outline-navy-500 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                :class="modelValue ? 'border-navy-700 bg-navy-700' : 'border-gray-300 bg-white'"
                @click="emit('update:modelValue', !modelValue)"
            >
                <span
                    class="absolute top-1/2 h-4 w-4 -translate-y-1/2 rounded-xs transition-[left] duration-(--duration-hover) ease-(--ease-dkgz)"
                    :class="modelValue ? 'left-6 bg-white' : 'left-1 bg-gray-400'"
                    aria-hidden="true"
                />
            </button>
        </div>
    </div>
</template>
