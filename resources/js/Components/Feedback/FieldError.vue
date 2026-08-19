<script setup>
import { AlertCircle } from 'lucide-vue-next'

/**
 * Auth Shell §Teil B: the error colour appears only on the border and in this
 * message. The label stays gray-800 so the field never shouts.
 */
defineProps({
    message: { type: String, required: true },
    id: { type: String, default: null },
})
</script>

<template>
    <div :id="id" class="flex items-start gap-1.5 pt-1.5" role="alert">
        <AlertCircle :size="14" :stroke-width="1.5" class="mt-[3px] shrink-0 text-danger" aria-hidden="true" />
        <span class="text-xs leading-normal text-danger">{{ message }}</span>
    </div>
</template>
