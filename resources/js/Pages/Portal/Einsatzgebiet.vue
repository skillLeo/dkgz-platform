<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { MapPin, Trash2 } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import EmptyState from '../../Components/Feedback/EmptyState.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

defineProps({ areas: { type: Array, default: () => [] } })

const { confirm } = useConfirm()
const form = useForm({ postal_code_from: '', postal_code_to: '', label: '' })

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
        <PageHeader
            title="Einsatzgebiet"
            description="Anfragen erreichen Sie, wenn die Postleitzahl des Fahrzeugstandorts in einem dieser Bereiche liegt."
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <section class="border border-gray-200 bg-white">
                <div class="border-b border-gray-200 p-5">
                    <SectionLabel text="Ihre Gebiete" tone="muted" />
                </div>

                <EmptyState
                    v-if="!areas.length"
                    title="Noch kein Gebiet hinterlegt"
                    description="Ohne Einsatzgebiet erreichen Sie keine Anfragen. Legen Sie mindestens einen Postleitzahlenbereich an."
                    :icon="MapPin"
                    class="border-0"
                />

                <ul v-else>
                    <li v-for="area in areas" :key="area.id" class="flex items-center justify-between gap-4 border-b border-gray-100 p-5 last:border-b-0">
                        <div class="min-w-0">
                            <p class="font-mono text-base tabular-nums text-gray-800">{{ area.from }}–{{ area.to }}</p>
                            <p v-if="area.label" class="pt-0.5 text-sm text-gray-600">{{ area.label }}</p>
                        </div>
                        <button
                            type="button"
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-sm text-gray-600 hover:text-danger"
                            aria-label="Gebiet entfernen"
                            @click="remove(area)"
                        >
                            <Trash2 :size="18" :stroke-width="1.5" aria-hidden="true" />
                        </button>
                    </li>
                </ul>
            </section>

            <form class="border border-gray-200 bg-white p-5 lg:sticky lg:top-6 lg:self-start" novalidate @submit.prevent="form.post('/portal/einsatzgebiet', { preserveScroll: true, onSuccess: () => form.reset() })">
                <SectionLabel text="Gebiet hinzufügen" tone="muted" />
                <ErrorSummary v-if="form.hasErrors" :errors="form.errors" class="mt-4" />

                <div class="flex flex-col gap-4 pt-4">
                    <div class="grid grid-cols-2 gap-3">
                        <BaseInput v-model="form.postal_code_from" label="von" placeholder="40000" inputmode="numeric" maxlength="5" numeric :error="form.errors.postal_code_from" required />
                        <BaseInput v-model="form.postal_code_to" label="bis" placeholder="40999" inputmode="numeric" maxlength="5" numeric :error="form.errors.postal_code_to" required />
                    </div>
                    <BaseInput v-model="form.label" label="Bezeichnung" placeholder="Düsseldorf und Umgebung" :error="form.errors.label" optional />
                </div>

                <BaseButton type="submit" size="cta" block class="mt-5" :loading="form.processing">Gebiet hinzufügen</BaseButton>
            </form>
        </div>
    </PortalLayout>
</template>
