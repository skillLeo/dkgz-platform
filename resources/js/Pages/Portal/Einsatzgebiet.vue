<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { MapPin, X } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import CoverageGrid from '../../Components/Domain/CoverageGrid.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({ areas: { type: Array, default: () => [] } })

const { confirm } = useConfirm()
const form = useForm({ postal_code_from: '', postal_code_to: '', label: '' })

const heading = computed(() => `Abgedeckte Gebiete · ${props.areas.length}`)

const remove = async (area) => {
    const ok = await confirm({
        title: 'Gebiet entfernen?',
        message: `${area.range} wird aus Ihrem Einsatzgebiet gestrichen. Sie erhalten dann keine Anfragen mehr aus diesem Bereich.`,
        confirmLabel: 'Entfernen',
        tone: 'danger',
    })

    if (ok) useForm({}).delete(`/portal/einsatzgebiet/${area.id}`, { preserveScroll: true })
}
</script>

<template>
    <Head title="Einsatzgebiet" />

    <PortalLayout title="Einsatzgebiet">
        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
            <section class="rounded-card border border-gray-200 bg-white p-5">
                <h2 class="text-eyebrow font-semibold uppercase text-gray-600">{{ heading }}</h2>

                <p v-if="!areas.length" class="measure pt-3 text-sm leading-normal text-gray-600">
                    Ohne Einsatzgebiet erreichen Sie keine Anfragen. Legen Sie mindestens einen Postleitzahlenbereich
                    an.
                </p>

                <div v-else class="flex flex-wrap gap-2 py-4">
                    <span
                        v-for="area in areas"
                        :key="area.id"
                        class="inline-flex items-center gap-2 rounded-sm border border-gray-200 bg-gray-50 py-1 pl-2.5 pr-1.5 text-sm text-gray-800"
                    >
                        <span v-if="area.label">{{ area.label }} · </span>
                        <span class="font-mono tabular-nums">{{ area.from }}–{{ area.to }}</span>
                        <button
                            type="button"
                            class="grid h-5 w-5 shrink-0 place-items-center rounded-xs text-gray-400 hover:text-danger"
                            :aria-label="`${area.range} entfernen`"
                            @click="remove(area)"
                        >
                            <X :size="14" :stroke-width="1.5" aria-hidden="true" />
                        </button>
                    </span>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <CoverageGrid :areas="areas" />
                </div>
            </section>

            <form
                class="rounded-card border border-gray-200 bg-white p-5 xl:sticky xl:top-6"
                novalidate
                @submit.prevent="form.post('/portal/einsatzgebiet', { preserveScroll: true, onSuccess: () => form.reset() })"
            >
                <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Gebiet hinzufügen</h2>
                <ErrorSummary v-if="form.hasErrors" :errors="form.errors" class="mt-4" />

                <div class="flex flex-col gap-4 pt-4">
                    <BaseInput
                        v-model="form.label"
                        label="Ort"
                        placeholder="z. B. Mönchengladbach"
                        :error="form.errors.label"
                        optional
                    />
                    <div class="grid grid-cols-2 gap-3">
                        <BaseInput
                            v-model="form.postal_code_from"
                            label="PLZ von"
                            placeholder="41061"
                            inputmode="numeric"
                            maxlength="5"
                            numeric
                            :error="form.errors.postal_code_from"
                            required
                        />
                        <BaseInput
                            v-model="form.postal_code_to"
                            label="PLZ bis"
                            placeholder="41239"
                            inputmode="numeric"
                            maxlength="5"
                            numeric
                            :error="form.errors.postal_code_to"
                            required
                        />
                    </div>
                </div>

                <BaseButton type="submit" size="cta" block class="mt-5" :loading="form.processing">
                    Gebiet hinzufügen
                </BaseButton>

                <p class="pt-3 text-sm leading-normal text-gray-600">
                    Änderungen wirken sofort auf die Verteilung neuer Anfragen.
                </p>
            </form>
        </div>

        <p v-if="!areas.length" class="flex items-center gap-2.5 pt-6 text-sm text-gray-600">
            <MapPin :size="16" :stroke-width="1.5" class="shrink-0 text-gray-400" aria-hidden="true" />
            Noch kein Gebiet hinterlegt.
        </p>
    </PortalLayout>
</template>
