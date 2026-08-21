<script setup>
import { computed, watch } from 'vue'
import { AlertTriangle, X } from 'lucide-vue-next'
import BaseButton from '../Base/BaseButton.vue'
import BaseSelect from '../Base/BaseSelect.vue'
import BaseTextarea from '../Base/BaseTextarea.vue'

/**
 * "Nein" — the job did not come about.
 *
 * A reason is required rather than optional, and the warning is stated here
 * rather than buried in the terms, because this is the exact moment where the
 * temptation exists: an assessor who has the customer's number can settle the
 * job privately and report that it fell through. Saying plainly what that costs
 * is fairer than discovering it afterwards, and it is the only warning the
 * person who needs it will actually read.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    reference: { type: String, required: true },
    reasons: { type: Array, default: () => [] },
    reason: { type: String, default: '' },
    note: { type: String, default: '' },
    error: { type: String, default: '' },
    processing: { type: Boolean, default: false },
})

const emit = defineEmits(['update:reason', 'update:note', 'close', 'submit'])

const canSubmit = computed(() => props.reason !== '' && ! props.processing)

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        emit('update:reason', '')
        emit('update:note', '')
    }
})
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-navy-900/32 p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="nicht-zustande-titel"
            @keydown.esc="emit('close')"
        >
            <form
                class="w-full max-w-sheet rounded-card bg-white shadow-3"
                @submit.prevent="emit('submit')"
            >
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-5">
                    <div class="min-w-0">
                        <h2 id="nicht-zustande-titel" class="text-h3 font-semibold text-navy-700">
                            Auftrag nicht zustande gekommen
                        </h2>
                        <p class="font-mono text-sm text-gray-600">{{ reference }}</p>
                    </div>
                    <button
                        type="button"
                        class="grid h-8 w-8 shrink-0 place-items-center text-gray-600 hover:text-navy-700"
                        aria-label="Schließen"
                        @click="emit('close')"
                    >
                        <X :size="20" :stroke-width="1.5" aria-hidden="true" />
                    </button>
                </div>

                <div class="p-6">
                    <p class="text-sm leading-normal text-gray-800">
                        Die Anfrage geht damit zurück in die Vermittlung, damit der Kunde weiterhin
                        einen Sachverständigen erhält. Es wird keine DKGZ-Gebühr berechnet.
                    </p>

                    <div class="pt-5">
                        <BaseSelect
                            :model-value="reason"
                            label="Warum ist der Auftrag nicht zustande gekommen?"
                            :options="reasons"
                            placeholder="Bitte wählen"
                            required
                            :error="error"
                            @update:model-value="emit('update:reason', $event)"
                        />
                    </div>

                    <div class="pt-4">
                        <BaseTextarea
                            :model-value="note"
                            label="Anmerkung"
                            :rows="3"
                            optional
                            hint="Hilft uns, die Vermittlung zu verbessern."
                            @update:model-value="emit('update:note', $event)"
                        />
                    </div>

                    <!--
                        Stated at the moment of temptation, not in the terms.
                    -->
                    <div class="mt-5 flex gap-3 border border-warning/40 bg-warning/8 p-4">
                        <AlertTriangle :size="18" :stroke-width="1.75" class="mt-0.5 shrink-0 text-warning" aria-hidden="true" />
                        <p class="text-sm leading-normal text-gray-800">
                            <strong class="font-semibold">Wichtiger Hinweis:</strong>
                            Eine direkte Vereinbarung mit dem Kunden an DKGZ vorbei — also die Durchführung
                            des Auftrags ohne Bestätigung über die Plattform — führt zum sofortigen
                            Ausschluss aus dem Partnernetz.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <BaseButton variant="secondary" size="compact" @click="emit('close')">Abbrechen</BaseButton>
                    <BaseButton type="submit" variant="danger" size="compact" :disabled="!canSubmit" :loading="processing">
                        Nicht zustande gekommen
                    </BaseButton>
                </div>
            </form>
        </div>
    </Teleport>
</template>
