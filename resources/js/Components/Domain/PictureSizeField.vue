<script setup>
import { computed } from 'vue'

/**
 * How large a picture beside a headline is drawn, set right next to the upload.
 *
 * A slider rather than a number box: somebody adjusting a photograph thinks
 * "a bit smaller", not "87". The number is still shown, and so is roughly how
 * wide that makes the picture, so two pages can be matched by eye.
 *
 * Empty means the size this picture would otherwise get. The slider then rests
 * on that size, so it starts where the page actually is rather than at an
 * arbitrary middle, and moving it is what gives the picture a size of its own.
 */
const props = defineProps({
    modelValue: { type: [String, Number], default: null },
    /** The size that applies while this one is empty. */
    inherited: { type: [String, Number], default: 100 },
    /** Says where the inherited size comes from. */
    inheritedLabel: { type: String, default: 'Standardgröße' },
    hint: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const MIN = 60
const MAX = 140
const STANDARD_WIDTH = 400

const uid = `groesse-${Math.random().toString(36).slice(2, 9)}`

const parse = (value) => {
    const typed = Number.parseInt(String(value ?? ''), 10)

    return Number.isFinite(typed) ? Math.min(MAX, Math.max(MIN, typed)) : null
}

const own = computed(() => parse(props.modelValue))
const fallback = computed(() => parse(props.inherited) ?? 100)
const shown = computed(() => own.value ?? fallback.value)
const width = computed(() => Math.round(STANDARD_WIDTH * shown.value / 100))
</script>

<template>
    <div class="rounded-sm border border-gray-200 bg-white p-3">
        <div class="flex items-baseline justify-between gap-3">
            <label :for="uid" class="text-sm font-medium text-gray-800">Bildgröße</label>
            <span class="font-mono text-sm tabular-nums text-navy-700">{{ shown }} %</span>
        </div>

        <input
            :id="uid"
            type="range"
            :min="MIN"
            :max="MAX"
            step="1"
            :value="shown"
            :disabled="disabled"
            class="mt-2 w-full cursor-pointer accent-navy-700 disabled:cursor-not-allowed"
            @input="emit('update:modelValue', String($event.target.value))"
        >

        <div class="flex justify-between text-xs text-gray-400" aria-hidden="true">
            <span>kleiner</span>
            <span>größer</span>
        </div>

        <p class="pt-2 text-xs leading-normal text-gray-600">
            <template v-if="own === null">{{ inheritedLabel }}. </template>
            Auf großen Bildschirmen etwa {{ width }} px breit, auf dem Handy immer klein.
            <template v-if="hint">{{ hint }}</template>
        </p>

        <button
            v-if="own !== null && ! disabled"
            type="button"
            class="pt-1.5 text-sm text-navy-700 underline underline-offset-2 hover:text-navy-500"
            @click="emit('update:modelValue', '')"
        >Größe zurücksetzen</button>
    </div>
</template>
