<script setup>
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { AlertCircle, AlertTriangle, Check, Info, X } from 'lucide-vue-next'

const page = usePage()
const dismissed = ref(false)

const flash = computed(() => {
    const f = page.props.flash ?? {}

    if (f.success) return { tone: 'success', message: f.success }
    if (f.error) return { tone: 'error', message: f.error }
    if (f.warning) return { tone: 'warning', message: f.warning }
    if (f.info) return { tone: 'info', message: f.info }

    return null
})

watch(flash, () => { dismissed.value = false })

const tones = {
    success: { icon: Check, border: 'border-success', text: 'text-success', bg: 'bg-success-50' },
    error: { icon: AlertCircle, border: 'border-danger', text: 'text-danger', bg: 'bg-danger/4' },
    warning: { icon: AlertTriangle, border: 'border-warning', text: 'text-warning', bg: 'bg-warning/5' },
    info: { icon: Info, border: 'border-navy-700', text: 'text-navy-700', bg: 'bg-navy-100' },
}

const tone = computed(() => tones[flash.value?.tone] ?? tones.info)
</script>

<template>
    <div
        v-if="flash && !dismissed"
        class="flex items-start gap-3 rounded-sm border p-4"
        :class="[tone.border, tone.bg]"
        role="status"
        aria-live="polite"
    >
        <component :is="tone.icon" :size="16" :stroke-width="1.5" class="mt-0.5 shrink-0" :class="tone.text" aria-hidden="true" />
        <p class="flex-1 text-sm leading-normal text-gray-800">{{ flash.message }}</p>
        <button
            type="button"
            class="shrink-0 text-gray-400 hover:text-gray-600"
            aria-label="Meldung schließen"
            @click="dismissed = true"
        >
            <X :size="16" :stroke-width="1.5" aria-hidden="true" />
        </button>
    </div>
</template>
