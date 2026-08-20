<script setup>
/**
 * The eyebrow plus the 24×2px gold rule that opens every section in the design.
 */
defineProps({
    text: { type: String, required: true },
    withRule: { type: Boolean, default: true },
    tone: { type: String, default: 'accent' },
})
</script>

<template>
    <div>
        <p
            class="text-eyebrow font-semibold uppercase"
            :class="tone === 'accent' ? '' : 'text-gray-600'"
            :style="tone === 'accent' ? { color: 'var(--dkgz-accent)' } : undefined"
        >{{ text }}</p>
        <div v-if="withRule" class="rule-accent mt-2.5" aria-hidden="true" />
    </div>
</template>
