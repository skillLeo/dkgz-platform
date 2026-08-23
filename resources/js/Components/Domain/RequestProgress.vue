<script setup>
import { Check } from 'lucide-vue-next'

/**
 * Where the visitor is in the request, and how much is left.
 *
 * A single long form asks somebody to judge, before they start, whether it is
 * worth their time. Three named steps with "1 von 3" answer that question up
 * front, and the completed steps stay clickable so going back to correct
 * something never means starting again.
 */
defineProps({
    steps: { type: Array, required: true },
    current: { type: Number, required: true },
    furthest: { type: Number, default: 1 },
})

const emit = defineEmits(['go'])
</script>

<template>
    <div>
        <div class="flex items-baseline justify-between gap-4 pb-3">
            <p class="text-eyebrow font-semibold uppercase text-gray-600">
                {{ steps[current - 1]?.label }}
            </p>
            <p class="font-mono text-sm tabular-nums text-gray-600">
                Schritt {{ current }} von {{ steps.length }}
            </p>
        </div>

        <ol class="flex gap-2">
            <li v-for="step in steps" :key="step.number" class="min-w-0 flex-1">
                <component
                    :is="step.number <= furthest ? 'button' : 'div'"
                    :type="step.number <= furthest ? 'button' : undefined"
                    class="block w-full text-left"
                    :class="step.number <= furthest && step.number !== current ? 'cursor-pointer' : ''"
                    :aria-current="step.number === current ? 'step' : undefined"
                    @click="step.number <= furthest && emit('go', step.number)"
                >
                    <span
                        class="block h-1 rounded-full transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                        :class="step.number <= current ? 'bg-navy-700' : 'bg-gray-200'"
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
                </component>
            </li>
        </ol>
    </div>
</template>
