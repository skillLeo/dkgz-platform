<script setup>
import { computed } from 'vue'
import { AlertCircle } from 'lucide-vue-next'

/**
 * Sits above the form on a failed submit. Each entry links to its field, so a
 * keyboard or screen-reader user reaches the problem in one step.
 */
const props = defineProps({
    errors: { type: Object, default: () => ({}) },
    labels: { type: Object, default: () => ({}) },
})

const entries = computed(() =>
    Object.entries(props.errors)
        .filter(([, message]) => Boolean(message))
        .map(([field, message]) => ({
            field,
            message,
            label: props.labels[field] ?? null,
        }))
)

const count = computed(() => entries.value.length)

const heading = computed(() => {
    if (count.value === 1) return 'Bitte prüfen Sie eine Angabe'

    const words = ['keine', 'eine', 'zwei', 'drei', 'vier', 'fünf', 'sechs', 'sieben', 'acht', 'neun', 'zehn']
    const word = words[count.value] ?? count.value

    return `Bitte prüfen Sie ${word} Angaben`
})

const focusField = (field) => {
    const el = document.querySelector(`#${CSS.escape(field)}, [name="${field}"]`)
    el?.focus()
    el?.scrollIntoView({ block: 'center', behavior: 'smooth' })
}
</script>

<template>
    <div
        v-if="count"
        class="rounded-sm border border-danger bg-danger/4 p-4"
        role="alert"
        aria-live="polite"
    >
        <div class="flex items-center gap-2">
            <AlertCircle :size="16" :stroke-width="1.5" class="shrink-0 text-danger" aria-hidden="true" />
            <span class="text-base font-semibold text-danger">{{ heading }}</span>
        </div>
        <div class="flex flex-col gap-1.5 pl-6 pt-3">
            <button
                v-for="entry in entries"
                :key="entry.field"
                type="button"
                class="w-fit border-b border-gray-300 text-left text-sm text-navy-700 hover:border-navy-500 hover:text-navy-500"
                @click="focusField(entry.field)"
            >
                <template v-if="entry.label">{{ entry.label }} — </template>{{ entry.message }}
            </button>
        </div>
    </div>
</template>
