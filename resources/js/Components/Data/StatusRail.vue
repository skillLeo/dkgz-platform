<script setup>
/**
 * The vertical progress rail from the portal workspace: a 20px gutter of discs
 * joined by a hairline, with the label and its stamp alongside. A step that has
 * happened carries a filled navy disc; one still ahead carries a hollow ring,
 * and its text drops to grey so the eye can see how far the order has come.
 */
defineProps({
    steps: { type: Array, required: true },
})
</script>

<template>
    <ol class="grid grid-cols-[20px_minmax(0,1fr)] gap-3">
        <template v-for="(step, index) in steps" :key="step.label">
            <li class="flex flex-col items-center" aria-hidden="true">
                <span
                    class="mt-1.5 block h-2.5 w-2.5 rounded-full"
                    :class="step.done ? 'bg-navy-700' : 'border border-gray-300 bg-white'"
                />
                <span
                    v-if="index < steps.length - 1"
                    class="block min-h-8 w-px flex-1 bg-gray-200"
                />
            </li>
            <li :class="index < steps.length - 1 ? 'pb-4' : ''">
                <p class="text-sm" :class="step.done ? 'font-medium text-gray-800' : 'text-gray-400'">
                    {{ step.label }}
                </p>
                <p class="font-mono text-meta" :class="step.done ? 'text-gray-400' : 'text-gray-300'">
                    {{ step.stamp }}
                </p>
            </li>
        </template>
    </ol>
</template>
