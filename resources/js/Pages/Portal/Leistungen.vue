<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import BaseCheckbox from '../../Components/Base/BaseCheckbox.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

const props = defineProps({
    serviceTypes: { type: Array, default: () => [] },
    selected: { type: Array, default: () => [] },
    demandWindowDays: { type: Number, default: 90 },
})

const form = useForm({ service_type_ids: [...props.selected] })

const toggle = (id, on) => {
    form.service_type_ids = on
        ? [...form.service_type_ids, id]
        : form.service_type_ids.filter((value) => value !== id)
}
</script>

<template>
    <Head title="Leistungen" />

    <PortalLayout title="Leistungen">
        <form
            class="max-w-3xl rounded-card border border-gray-200 bg-white p-5"
            novalidate
            @submit.prevent="form.post('/portal/leistungen', { preserveScroll: true })"
        >
            <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Angebotene Leistungen</h2>
            <p class="measure pt-2 text-sm leading-normal text-gray-600">
                Sie erhalten nur Anfragen zu den hier ausgewählten Gutachtenarten.
            </p>

            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" class="mt-4" />

            <ul class="pt-4">
                <li
                    v-for="type in serviceTypes"
                    :key="type.id"
                    class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 py-3.5 last:border-b-0"
                >
                    <BaseCheckbox
                        :model-value="form.service_type_ids.includes(type.id)"
                        @update:model-value="toggle(type.id, $event)"
                    >
                        <span class="text-base text-gray-800">{{ type.name_de }}</span>
                    </BaseCheckbox>
                    <span class="shrink-0 font-mono text-meta tabular-nums text-gray-400">
                        {{ type.demand }} {{ type.demand === 1 ? 'Anfrage' : 'Anfragen' }} / {{ demandWindowDays }} Tage
                    </span>
                </li>
            </ul>

            <BaseButton type="submit" size="cta" class="mt-5" :loading="form.processing">Auswahl speichern</BaseButton>

            <p class="pt-3 text-sm leading-normal text-gray-600">
                Die Zahlen zeigen, wie viele Anfragen dieser Art in Ihrem Einsatzgebiet eingegangen sind — unabhängig
                davon, ob Sie die Leistung derzeit anbieten.
            </p>
        </form>
    </PortalLayout>
</template>
