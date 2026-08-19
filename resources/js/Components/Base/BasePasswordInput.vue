<script setup>
import { computed, ref } from 'vue'
import FieldError from '../Feedback/FieldError.vue'
import { AlertTriangle, Check } from 'lucide-vue-next'

/**
 * Auth Shell §Teil B: the strength meter is the one thing that updates live —
 * every other check runs on blur. Four segments, four levels.
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: 'Passwort' },
    id: { type: String, default: null },
    error: { type: String, default: '' },
    autocomplete: { type: String, default: 'current-password' },
    showMeter: { type: Boolean, default: false },
    showChecklist: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'blur'])

const uid = `pw-${Math.random().toString(36).slice(2, 9)}`
const fieldId = computed(() => props.id ?? uid)

const visible = ref(false)
const capsLock = ref(false)

const checks = computed(() => {
    const value = props.modelValue ?? ''

    return [
        { label: 'Mindestens 10 Zeichen', ok: value.length >= 10 },
        { label: 'Groß- und Kleinbuchstaben', ok: /[a-zäöüß]/.test(value) && /[A-ZÄÖÜ]/.test(value) },
        { label: 'Mindestens eine Zahl', ok: /[0-9]/.test(value) },
        { label: 'Mindestens ein Sonderzeichen', ok: /[^A-Za-z0-9]/.test(value) },
    ]
})

const score = computed(() => checks.value.filter((c) => c.ok).length)

const levels = [
    { label: '', bar: 'bg-gray-200', text: 'text-gray-400' },
    { label: 'Schwach', bar: 'bg-danger', text: 'text-danger' },
    { label: 'Ausreichend', bar: 'bg-warning', text: 'text-warning' },
    { label: 'Gut', bar: 'bg-navy-500', text: 'text-navy-500' },
    { label: 'Sehr gut', bar: 'bg-success', text: 'text-success' },
]

const level = computed(() => levels[score.value])

const onKey = (event) => {
    capsLock.value = event.getModifierState?.('CapsLock') ?? false
}
</script>

<template>
    <div>
        <label :for="fieldId" class="block pb-2 text-sm font-medium text-gray-800">
            {{ label }}<span v-if="required" class="text-danger"> *</span>
        </label>

        <div class="relative">
            <input
                :id="fieldId"
                :value="modelValue"
                :type="visible ? 'text' : 'password'"
                :autocomplete="autocomplete"
                :required="required"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="error ? `${fieldId}-fehler` : undefined"
                class="w-full h-(--spacing-control) rounded-sm border pl-3.5 pr-25 text-base text-gray-800 outline-none transition-colors duration-(--duration-focus) ease-(--ease-dkgz)"
                :class="error
                    ? 'bg-danger/3 border-danger focus:outline-2 focus:outline-danger focus:outline-offset-2'
                    : 'bg-white border-gray-300 hover:border-gray-400 focus:border-navy-700 focus:outline-2 focus:outline-navy-500 focus:outline-offset-2'"
                @input="emit('update:modelValue', $event.target.value)"
                @keyup="onKey"
                @keydown="onKey"
                @blur="emit('blur', $event)"
            >
            <button
                type="button"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-medium text-navy-700"
                :aria-pressed="visible"
                @click="visible = !visible"
            >
                {{ visible ? 'Verbergen' : 'Anzeigen' }}
            </button>
        </div>

        <div v-if="showMeter && modelValue" class="flex items-center gap-3 pt-2.5">
            <span class="flex flex-1 gap-1">
                <span
                    v-for="segment in 4"
                    :key="segment"
                    class="h-[3px] flex-1 transition-colors duration-(--duration-meter) ease-(--ease-dkgz)"
                    :class="segment <= score ? level.bar : 'bg-gray-200'"
                />
            </span>
            <span class="w-18 shrink-0 text-right text-xs" :class="level.text">{{ level.label }}</span>
        </div>

        <div v-if="showChecklist" class="flex flex-col gap-1.5 pt-3.5">
            <span v-for="check in checks" :key="check.label" class="flex items-center gap-2">
                <Check v-if="check.ok" :size="14" :stroke-width="1.5" class="shrink-0 text-gray-600" aria-hidden="true" />
                <span v-else class="h-3.5 w-3.5 shrink-0 rounded-full border border-gray-300" aria-hidden="true" />
                <span class="text-xs" :class="check.ok ? 'text-gray-600' : 'text-gray-400'">{{ check.label }}</span>
            </span>
        </div>

        <div v-if="capsLock" class="mt-3.5 flex items-start gap-1.5 border-t border-gray-100 pt-3.5">
            <AlertTriangle :size="14" :stroke-width="1.5" class="mt-[3px] shrink-0 text-warning" aria-hidden="true" />
            <span class="text-xs leading-normal text-warning">Feststelltaste ist aktiviert.</span>
        </div>

        <FieldError v-if="error" :id="`${fieldId}-fehler`" :message="error" />
    </div>
</template>
