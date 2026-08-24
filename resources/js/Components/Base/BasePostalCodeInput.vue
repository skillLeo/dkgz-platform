<script setup>
import { computed, ref, watch } from 'vue'
import BaseInput from './BaseInput.vue'

/**
 * Five digits, looked up against the seeded PLZ table so the city fills itself
 * where it can. An unknown code is accepted — the table is a convenience,
 * and an invalid code is caught before submit. The lookup is debounced and
 * only fires on a complete code.
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    city: { type: String, default: '' },
    label: { type: String, default: 'Postleitzahl' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    resolveUrl: { type: String, default: '/api/plz' },
})

const emit = defineEmits(['update:modelValue', 'update:city', 'resolved', 'blur'])

const resolving = ref(false)
const resolvedCity = ref(props.city)
const notFound = ref(false)

let timer = null

const onInput = (value) => {
    const digits = String(value).replace(/[^0-9]/g, '').slice(0, 5)
    emit('update:modelValue', digits)

    resolvedCity.value = ''
    notFound.value = false

    if (timer) clearTimeout(timer)
    if (digits.length !== 5) return

    timer = setTimeout(() => resolve(digits), 250)
}

const resolve = async (code) => {
    resolving.value = true

    try {
        const response = await fetch(`${props.resolveUrl}/${code}`, {
            headers: { Accept: 'application/json' },
        })

        if (!response.ok) {
            notFound.value = true

            return
        }

        const data = await response.json()

        if (data?.city) {
            resolvedCity.value = data.city
            emit('update:city', data.city)
            emit('resolved', data)
        } else {
            // Unknown to the lookup table is not invalid — the table holds a
            // fraction of Germany's codes and only saves typing. The field
            // simply stops offering a city.
            resolvedCity.value = ''
            notFound.value = true
        }
    } catch {
        // A failed lookup must never block the form; the server validates too.
        notFound.value = false
    } finally {
        resolving.value = false
    }
}

watch(() => props.city, (value) => { resolvedCity.value = value })

/**
 * Only speaks when it has something to say.
 *
 * The resting hint restated what the placeholder already shows — "Fünfstellig,
 * z. B. 40589" under a field reading 40589 — so it was a line of text that
 * carried nothing. What is left is the lookup: working, found, or not found.
 */
const hint = computed(() => {
    if (resolving.value) return 'Ort wird ermittelt…'
    if (notFound.value) return 'Ort konnte nicht automatisch ermittelt werden — bitte ergänzen.'
    if (resolvedCity.value) return `${props.modelValue} · ${resolvedCity.value}`

    return ''
})
</script>

<template>
    <BaseInput
        :model-value="modelValue"
        :label="label"
        :error="error"
        :hint="hint"
        :required="required"
        :disabled="disabled"
        placeholder="40589"
        inputmode="numeric"
        autocomplete="postal-code"
        maxlength="5"
        numeric
        @update:model-value="onInput"
        @blur="emit('blur', $event)"
    />
</template>
