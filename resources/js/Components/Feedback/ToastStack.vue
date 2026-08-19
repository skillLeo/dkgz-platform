<script setup>
import { AlertCircle, Check, Info, X } from 'lucide-vue-next'
import { useToast } from '../../Composables/useToast.js'

const { toasts, dismiss } = useToast()

const tones = {
    success: { icon: Check, border: 'border-success', text: 'text-success' },
    error: { icon: AlertCircle, border: 'border-danger', text: 'text-danger' },
    info: { icon: Info, border: 'border-navy-700', text: 'text-navy-700' },
}
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-4 bottom-4 z-100 flex flex-col gap-2 md:inset-x-auto md:right-6 md:bottom-6 md:w-96"
            role="region"
            aria-live="polite"
        >
            <div
                v-for="item in toasts"
                :key="item.id"
                class="pointer-events-auto flex items-start gap-3 rounded-sm border-l-2 border border-gray-200 bg-white p-4 shadow-(--shadow-2)"
                :class="(tones[item.tone] ?? tones.info).border"
                style="animation: dkgz-rise 180ms cubic-bezier(0.4,0,0.2,1) both"
            >
                <component
                    :is="(tones[item.tone] ?? tones.info).icon"
                    :size="16"
                    :stroke-width="1.5"
                    class="mt-0.5 shrink-0"
                    :class="(tones[item.tone] ?? tones.info).text"
                    aria-hidden="true"
                />
                <p class="flex-1 text-sm leading-normal text-gray-800">{{ item.message }}</p>
                <button type="button" class="shrink-0 text-gray-400 hover:text-gray-600" aria-label="Schließen" @click="dismiss(item.id)">
                    <X :size="16" :stroke-width="1.5" aria-hidden="true" />
                </button>
            </div>
        </div>
    </Teleport>
</template>
