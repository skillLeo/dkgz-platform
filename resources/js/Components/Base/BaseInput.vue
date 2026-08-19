<script setup>
import { computed, ref } from 'vue'
import FieldError from '../Feedback/FieldError.vue'

/**
 * Foundations, Auth Shell §Teil B: height 46px, radius 3px, label always
 * present — never placeholder-only. Validation runs on blur; after a field has
 * errored once it re-validates live until it is valid again. There is no green
 * success state: confirmation is the absence of an error.
 */
const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, required: true },
    id: { type: String, default: null },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    optional: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    prefix: { type: String, default: '' },
    suffix: { type: String, default: '' },
    numeric: { type: Boolean, default: false },
    mono: { type: Boolean, default: false },
    autocomplete: { type: String, default: null },
    inputmode: { type: String, default: null },
    maxlength: { type: [String, Number], default: null },
})

const emit = defineEmits(['update:modelValue', 'blur', 'focus'])

const uid = `feld-${Math.random().toString(36).slice(2, 9)}`
const fieldId = computed(() => props.id ?? uid)
const describedBy = computed(() => {
    const ids = []
    if (props.error) ids.push(`${fieldId.value}-fehler`)
    else if (props.hint) ids.push(`${fieldId.value}-hinweis`)

    return ids.length ? ids.join(' ') : undefined
})

const focused = ref(false)

const inputClasses = computed(() => [
    'w-full h-(--spacing-control) px-3.5 text-base rounded-sm border outline-none',
    'transition-colors duration-(--duration-focus) ease-(--ease-dkgz)',
    'placeholder:text-gray-400',
    props.numeric || props.mono ? 'tabular-nums' : '',
    props.mono ? 'font-mono' : '',
    props.disabled
        ? 'bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed'
        : props.error
            ? 'bg-danger/3 text-gray-800 border-danger focus:outline-2 focus:outline-danger focus:outline-offset-2'
            : 'bg-white text-gray-800 border-gray-300 hover:border-gray-400 focus:border-navy-700 focus:outline-2 focus:outline-navy-500 focus:outline-offset-2',
])
</script>

<template>
    <div>
        <div class="flex items-baseline justify-between gap-4 pb-2">
            <label :for="fieldId" class="text-sm font-medium text-gray-800">
                {{ label }}<span v-if="required" class="text-danger"> *</span>
            </label>
            <span v-if="optional" class="text-sm text-gray-400">optional</span>
        </div>

        <div
            v-if="prefix"
            class="flex items-center h-(--spacing-control) rounded-sm border overflow-hidden bg-white"
            :class="error ? 'border-danger' : focused ? 'border-navy-700' : 'border-gray-300'"
        >
            <span class="flex h-full items-center border-r border-gray-200 px-3.5 text-base text-gray-600">{{ prefix }}</span>
            <input
                :id="fieldId"
                :value="modelValue"
                :type="type"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :required="required"
                :autocomplete="autocomplete"
                :inputmode="inputmode"
                :maxlength="maxlength"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="describedBy"
                class="h-full min-w-0 flex-1 border-none px-3.5 text-base text-gray-800 outline-none tabular-nums"
                :class="mono ? 'font-mono' : ''"
                @input="emit('update:modelValue', $event.target.value)"
                @focus="focused = true; emit('focus', $event)"
                @blur="focused = false; emit('blur', $event)"
            >
        </div>

        <div v-else class="relative">
            <input
                :id="fieldId"
                :value="modelValue"
                :type="type"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :required="required"
                :autocomplete="autocomplete"
                :inputmode="inputmode"
                :maxlength="maxlength"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="describedBy"
                :class="[inputClasses, suffix ? 'pr-24' : '']"
                @input="emit('update:modelValue', $event.target.value)"
                @focus="emit('focus', $event)"
                @blur="emit('blur', $event)"
            >
            <span
                v-if="suffix"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-600"
            >{{ suffix }}</span>
        </div>

        <p v-if="hint && !error" :id="`${fieldId}-hinweis`" class="pt-1.5 text-xs text-gray-400">{{ hint }}</p>
        <FieldError v-if="error" :id="`${fieldId}-fehler`" :message="error" />
    </div>
</template>
