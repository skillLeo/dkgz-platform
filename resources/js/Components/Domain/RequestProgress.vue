<script setup>
import { Check } from 'lucide-vue-next'

/**
 * Where the visitor is in the request, and how much is left.
 *
 * A single long form asks somebody to judge, before they start, whether it is
 * worth their time. Two named steps answer that at a glance — the filled bars
 * carry the count, so spelling it out in words as well only added noise.
 *
 * Nothing here is clickable. A completed step used to be a button back to
 * itself, which made the band read as navigation and put a second way back
 * beside the one that already exists — "ändern", which sits next to the answer
 * it would change and says what it does. This is a picture of progress, and a
 * picture is all it is.
 */
defineProps({
    steps: { type: Array, required: true },
    current: { type: Number, required: true },
})
</script>

<template>
    <ol class="flex gap-2">
        <li v-for="step in steps" :key="step.number" class="min-w-0 flex-1">
            <span
                class="block h-1 rounded-full transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                :class="step.number <= current ? 'bg-navy-700' : 'bg-gray-200'"
                :aria-current="step.number === current ? 'step' : undefined"
            />
            <span class="flex items-center gap-1.5 pt-2">
                <Check
                    v-if="step.number < current"
                    :size="13"
                    :stroke-width="2"
                    class="shrink-0 text-navy-700"
                    aria-hidden="true"
                />
                <span
                    class="truncate text-xs"
                    :class="step.number <= current ? 'font-medium text-navy-700' : 'text-gray-400'"
                >{{ step.short }}</span>
            </span>
        </li>
    </ol>
</template>
