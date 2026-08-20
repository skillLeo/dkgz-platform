<script setup>
/**
 * The underlined tab row from the portal settings screen. The active tab keeps
 * a 2px navy rule; inactive tabs reserve the same space in transparent so the
 * row does not shift by two pixels when the selection moves.
 */
defineProps({
    tabs: { type: Array, required: true },
    current: { type: String, required: true },
})

defineEmits(['select'])
</script>

<template>
    <div class="flex gap-6 overflow-x-auto border-b border-gray-200" role="tablist">
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            role="tab"
            :aria-selected="tab.value === current"
            class="-mb-px whitespace-nowrap border-b-2 pb-3 text-sm transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
            :class="tab.value === current
                ? 'border-b-navy-700 font-medium text-navy-700'
                : 'border-b-transparent text-gray-600 hover:text-navy-700'"
            @click="$emit('select', tab.value)"
        >{{ tab.label }}</button>
    </div>
</template>
