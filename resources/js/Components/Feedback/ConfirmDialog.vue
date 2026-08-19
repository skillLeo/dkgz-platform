<script setup>
import { computed, ref, watch, nextTick } from 'vue'
import { AlertTriangle } from 'lucide-vue-next'
import BaseButton from '../Base/BaseButton.vue'
import { useConfirm } from '../../Composables/useConfirm.js'
import { useBreakpoint } from '../../Composables/useBreakpoint.js'

/**
 * Mounted once per layout. Irreversible actions require the operator to type a
 * confirmation word; below 768px the dialog becomes a full-screen sheet that
 * slides up in 240ms.
 */
const { state, accept, cancel } = useConfirm()
const { isMobile } = useBreakpoint()

const typed = ref('')
const input = ref(null)
const panel = ref(null)

const needsTyping = computed(() => Boolean(state.requireTyped))
const canConfirm = computed(() => !needsTyping.value || typed.value.trim() === state.requireTyped)

watch(() => state.open, async (open) => {
    typed.value = ''

    if (!open) return

    await nextTick()
    ;(needsTyping.value ? input.value : panel.value)?.focus()
})

const onKeydown = (event) => {
    if (event.key === 'Escape') cancel()
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="state.open"
            class="fixed inset-0 z-100 flex"
            :class="isMobile ? 'flex-col justify-end' : 'items-center justify-center p-6'"
            @keydown="onKeydown"
        >
            <button type="button" class="absolute inset-0 bg-navy-900/40" aria-label="Abbrechen" @click="cancel" />

            <div
                ref="panel"
                tabindex="-1"
                role="alertdialog"
                aria-modal="true"
                :aria-labelledby="'bestaetigung-titel'"
                class="relative w-full bg-white outline-none"
                :class="isMobile
                    ? 'max-h-(--size-sheet-max) overflow-y-auto rounded-t-card pb-[env(safe-area-inset-bottom)]'
                    : 'max-w-lg rounded-card shadow-(--shadow-3)'"
                :style="isMobile
                    ? 'animation: dkgz-rise 240ms cubic-bezier(0.4,0,0.2,1) both'
                    : 'animation: dkgz-rise 180ms cubic-bezier(0.4,0,0.2,1) both'"
            >
                <div class="p-6">
                    <div class="flex items-start gap-3">
                        <AlertTriangle
                            v-if="state.tone === 'danger'"
                            :size="20"
                            :stroke-width="1.5"
                            class="mt-0.5 shrink-0 text-danger"
                            aria-hidden="true"
                        />
                        <div class="min-w-0">
                            <h2 id="bestaetigung-titel" class="text-h4 font-semibold text-navy-700">{{ state.title }}</h2>
                            <p v-if="state.message" class="pt-2 text-base leading-normal text-gray-600">{{ state.message }}</p>
                        </div>
                    </div>

                    <div v-if="needsTyping" class="pt-5">
                        <label for="bestaetigung-eingabe" class="block pb-2 text-sm font-medium text-gray-800">
                            Tippen Sie <span class="font-mono text-navy-700">{{ state.requireTyped }}</span>, um fortzufahren
                        </label>
                        <input
                            id="bestaetigung-eingabe"
                            ref="input"
                            v-model="typed"
                            type="text"
                            autocomplete="off"
                            class="h-(--spacing-control) w-full rounded-sm border border-gray-300 bg-white px-3.5 font-mono text-base text-gray-800 outline-none hover:border-gray-400 focus:border-navy-700 focus:outline-2 focus:outline-navy-500 focus:outline-offset-2"
                        >
                    </div>
                </div>

                <div class="flex gap-3 border-t border-gray-200 p-4" :class="isMobile ? '' : 'justify-end'">
                    <BaseButton variant="secondary" :block="isMobile" @click="cancel">{{ state.cancelLabel }}</BaseButton>
                    <BaseButton
                        :variant="state.tone === 'danger' ? 'danger' : 'primary'"
                        :disabled="!canConfirm"
                        :block="isMobile"
                        @click="accept"
                    >{{ state.confirmLabel }}</BaseButton>
                </div>
            </div>
        </div>
    </Teleport>
</template>
