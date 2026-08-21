<script setup>
import { computed, ref, watch } from 'vue'
import { X } from 'lucide-vue-next'
import BaseButton from '../Base/BaseButton.vue'
import BaseCheckbox from '../Base/BaseCheckbox.vue'

/**
 * Completion: one confirmation that the report is finished.
 *
 * The partner used to have to type what they had charged their own customer and
 * tick that both documents were uploaded. Neither is DKGZ's business — the
 * gutachten goes from the partner to their customer directly, and what DKGZ is
 * owed was fixed when the job was accepted. All that is left is to say the work
 * is done, and to show the fee that confirmation triggers.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    reference: { type: String, required: true },
    dkgzFeeLabel: { type: String, default: null },
    processing: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'submit'])

const confirmed = ref(false)

watch(() => props.open, (isOpen) => {
    if (isOpen) confirmed.value = false
})

const canSubmit = computed(() => confirmed.value && !props.processing)
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 grid place-items-center bg-navy-900/32 p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="abschluss-titel"
            @keydown.esc="emit('close')"
        >
            <form
                class="w-full max-w-sheet rounded-card bg-white shadow-3"
                @submit.prevent="emit('submit')"
            >
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-5">
                    <div>
                        <h2 id="abschluss-titel" class="text-h3 font-semibold text-navy-700">Gutachten fertiggestellt</h2>
                        <p class="font-mono text-sm text-gray-600">{{ reference }}</p>
                    </div>
                    <button
                        type="button"
                        class="grid h-8 w-8 place-items-center text-gray-600 hover:text-navy-700"
                        aria-label="Schließen"
                        @click="emit('close')"
                    >
                        <X :size="20" :stroke-width="1.5" aria-hidden="true" />
                    </button>
                </div>

                <div class="p-6">
                    <p class="text-sm leading-normal text-gray-800">
                        Damit gilt der Auftrag als erledigt. Ihr Gutachten und Ihre Rechnung senden Sie
                        direkt an Ihren Kunden beziehungsweise dessen Versicherung — hier ist dafür
                        nichts hochzuladen.
                    </p>

                    <!--
                        Nothing is calculated here. The amount owed was fixed when
                        the assignment was accepted; showing it again is a
                        confirmation, not a computation.
                    -->
                    <div class="mt-5 flex items-baseline justify-between gap-4 border-t border-gray-200 pt-4">
                        <span class="text-sm text-gray-600">DKGZ-Gebühr für diesen Auftrag</span>
                        <span class="whitespace-nowrap font-mono text-code font-medium tabular-nums text-navy-700">
                            {{ dkgzFeeLabel ?? '—' }}
                        </span>
                    </div>

                    <div class="mt-5 border-t border-gray-200 pt-5">
                        <BaseCheckbox v-model="confirmed">
                            Ich bestätige, dass das Gutachten fertiggestellt ist.
                        </BaseCheckbox>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <BaseButton variant="secondary" size="compact" @click="emit('close')">Abbrechen</BaseButton>
                    <BaseButton type="submit" size="compact" :disabled="!canSubmit" :loading="processing">
                        Auftrag abschließen
                    </BaseButton>
                </div>
            </form>
        </div>
    </Teleport>
</template>
