<script setup>
import { computed, ref, watch } from 'vue'
import { X } from 'lucide-vue-next'
import BaseButton from '../Base/BaseButton.vue'
import BaseCheckbox from '../Base/BaseCheckbox.vue'
import FieldError from '../Feedback/FieldError.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

/**
 * Completion, as the design draws it: a 560px dialog with the fee entered once
 * and the split shown live beneath it. The partner sees the commission before
 * confirming, not after — the figure is the whole point of the step.
 *
 * No payment is taken here or anywhere else; this only records what was
 * charged so the monthly commission statement can be drawn up.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    reference: { type: String, required: true },
    ratePercent: { type: Number, required: true },
    modelValue: { type: [Number, null], default: null },
    error: { type: String, default: '' },
    processing: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'close', 'submit'])

const { money, parseMoney, amount, percent } = useGermanFormat()

const raw = ref(props.modelValue === null ? '' : amount(props.modelValue))
const confirmed = ref(false)

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        raw.value = props.modelValue === null ? '' : amount(props.modelValue)
        confirmed.value = false
    }
})

const cents = computed(() => parseMoney(raw.value))
const commissionCents = computed(() => (cents.value === null
    ? null
    : Math.round((cents.value * props.ratePercent) / 100)))
const shareCents = computed(() => (cents.value === null || commissionCents.value === null
    ? null
    : cents.value - commissionCents.value))

const onInput = (event) => {
    raw.value = event.target.value
    emit('update:modelValue', parseMoney(raw.value))
}

const canSubmit = computed(() => confirmed.value && cents.value !== null && !props.processing)
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
                        <h2 id="abschluss-titel" class="text-h3 font-semibold text-navy-700">Auftrag abschließen</h2>
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
                    <label for="honorar" class="block pb-2 text-sm font-medium text-gray-800">
                        Tatsächlich berechnetes Honorar (netto) <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <div class="flex h-12 items-center gap-2 rounded-sm border border-navy-700 px-3.5">
                        <input
                            id="honorar"
                            :value="raw"
                            type="text"
                            inputmode="decimal"
                            required
                            class="h-full min-w-0 flex-1 border-none bg-transparent p-0 text-right font-mono text-code tabular-nums text-navy-700 focus:outline-none focus:ring-0"
                            @input="onInput"
                        >
                        <span class="font-mono text-code text-gray-600" aria-hidden="true">€</span>
                    </div>
                    <FieldError :message="error" />

                    <div class="mt-5 flex items-baseline justify-between gap-4 border-t border-gray-200 pt-4">
                        <span class="text-sm text-gray-600">DKGZ-Vermittlungsprovision {{ percent(ratePercent) }}</span>
                        <span class="whitespace-nowrap font-mono text-code font-medium tabular-nums text-navy-700">
                            {{ commissionCents === null ? '—' : money(commissionCents) }}
                        </span>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 pt-2">
                        <span class="text-sm text-gray-600">Verbleibt bei Ihnen</span>
                        <span class="whitespace-nowrap font-mono text-base tabular-nums text-gray-600">
                            {{ shareCents === null ? '—' : money(shareCents) }}
                        </span>
                    </div>

                    <div class="mt-5 border-t border-gray-200 pt-5">
                        <BaseCheckbox v-model="confirmed">
                            Ich bestätige, dass die hochgeladenen Unterlagen dem angegebenen Honorar entsprechen.
                        </BaseCheckbox>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <BaseButton variant="secondary" size="compact" @click="emit('close')">Abbrechen</BaseButton>
                    <BaseButton type="submit" size="compact" :disabled="!canSubmit" :loading="processing">
                        Abschluss bestätigen
                    </BaseButton>
                </div>
            </form>
        </div>
    </Teleport>
</template>
