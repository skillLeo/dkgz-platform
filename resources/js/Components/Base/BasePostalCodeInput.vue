<script setup>
import { computed, ref, watch } from 'vue'
import BaseInput from './BaseInput.vue'

/**
 * Five digits, resolved against the seeded PLZ table so the city fills itself
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

const hint = computed(() => {
    if (resolving.value) return 'Ort wird ermittelt…'
    if (notFound.value) return 'Diese Postleitzahl kennen wir nicht.'
    if (resolvedCity.value) return `${props.modelValue} · ${resolvedCity.value}`

    return 'Fünfstellig, z. B. 40589'
})
</script>

<template>
    <BaseInput
        :model-value="modelValue"
        :label="label"
        :error="error || (notFound ? 'Diese Postleitzahl kennen wir nicht.' : '')"
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
