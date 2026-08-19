<script setup>
import { computed } from 'vue'
import { AlertCircle, AlertTriangle, Check, Info } from 'lucide-vue-next'

/**
 * The banner the auth screens put above a form: a title, a sentence, and
 * nothing else. Auth Screens shows four tones — the error on a failed sign-in,
 * the warning on a throttle or an expired link, and the confirmation after a
 * reset link goes out.
 */
const props = defineProps({
    tone: { type: String, default: 'error' },
    title: { type: String, required: true },
})

const tones = {
    error: { icon: AlertCircle, border: 'border-danger', bg: 'bg-danger/4', text: 'text-danger' },
    warning: { icon: AlertTriangle, border: 'border-warning', bg: 'bg-warning/5', text: 'text-warning' },
    success: { icon: Check, border: 'border-success', bg: 'bg-success-50', text: 'text-success' },
    info: { icon: Info, border: 'border-navy-700', bg: 'bg-navy-100', text: 'text-navy-700' },
}

const t = computed(() => tones[props.tone] ?? tones.error)
</script>

<template>
    <div class="rounded-sm border p-4" :class="[t.border, t.bg]" role="alert">
        <div class="flex items-center gap-2">
            <component :is="t.icon" :size="16" :stroke-width="1.5" class="shrink-0" :class="t.text" aria-hidden="true" />
            <span class="text-base font-semibold" :class="t.text">{{ title }}</span>
        </div>
        <p class="pt-2 text-sm leading-normal text-gray-800"><slot /></p>
    </div>
</template>
