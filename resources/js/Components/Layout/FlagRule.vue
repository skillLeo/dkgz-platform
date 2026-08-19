<script setup>
/**
 * The black-red-gold rule that closes the auth panel and the e-mail header.
 * 40×3px in the shell, 32×3px in e-mail.
 */
defineProps({
    width: { type: Number, default: 40 },
})
</script>

<template>
    <span class="flex h-[3px]" :style="{ width: `${width}px` }" aria-hidden="true">
        <span class="flex-1 bg-flag-black" />
        <span class="flex-1 bg-flag-red" />
        <span class="flex-1 bg-flag-gold" />
    </span>
</template>
