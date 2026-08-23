<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Phone } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BasePostalCodeInput from '../../Components/Base/BasePostalCodeInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

/**
 * A request taken over the telephone.
 *
 * The same fields as the public form, submitted through the same action, so the
 * customer receives the same confirmation and the same partners are notified.
 * Nothing here is a shortcut around the ordinary flow — it is the ordinary flow
 * with somebody else at the keyboard.
 */
const props = defineProps({
    serviceTypes: { type: Array, default: () => [] },
})

const form = useForm({
    service_type_id: '',
    postal_code: '',
    city: '',
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    vehicle_make: '',
    vehicle_model: '',
    vehicle_year: '',
    vehicle_plate: '',
    vehicle_vin: '',
    description: '',
    urgency: '',
})

const typeOptions = computed(() => props.serviceTypes
    .map((type) => ({ value: type.id, label: type.name_de })))

const urgencyOptions = [
    { value: 'normal', label: 'Normal' },
    { value: 'soon', label: 'Zeitnah' },
    { value: 'urgent', label: 'Dringend' },
]

const labels = {
    service_type_id: 'Art des Gutachtens',
    postal_code: 'Postleitzahl',
    city: 'Standort des Fahrzeugs',
    customer_name: 'Name',
    customer_phone: 'Telefon',
    customer_email: 'E-Mail',
    vehicle_make: 'Marke',
    vehicle_model: 'Modell',
}
</script>

<template>
    <Head title="Anfrage aufnehmen" />

    <AdminLayout title="Anfrage aufnehmen" back-href="/admin/anfragen">
        <PageHeader title="Anfrage aufnehmen">
            <template #description>
                Für eine Anfrage, die telefonisch eingeht. Sie durchläuft danach genau denselben
                Weg wie eine Anfrage über die Website.
            </template>
        </PageHeader>

        <form class="max-w-3xl" novalidate @submit.prevent="form.post('/admin/anfragen')">
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-6" />

            <section class="border border-gray-200 bg-white p-6">
                <SectionLabel text="Leistung und Standort" tone="muted" :with-rule="false" />

                <div class="flex flex-col gap-5 pt-5">
                    <BaseSelect
                        v-model="form.service_type_id"
                        label="Art des Gutachtens"
                        :options="typeOptions"
                        :error="form.errors.service_type_id"
                        required
                    />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-[200px_minmax(0,1fr)]">
                        <BasePostalCodeInput
                            v-model="form.postal_code"
                            v-model:city="form.city"
                            :error="form.errors.postal_code"
                            required
                        />
                        <BaseInput
                            v-model="form.city"
                            label="Standort des Fahrzeugs"
                            placeholder="Ort, Straße oder Werkstatt"
                            :error="form.errors.city"
                            required
                        />
                    </div>
                </div>
            </section>

            <section class="mt-6 border border-gray-200 bg-white p-6">
                <SectionLabel text="Fahrzeug" tone="muted" :with-rule="false" />

                <div class="flex flex-col gap-5 pt-5">
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <BaseInput v-model="form.vehicle_make" label="Marke" placeholder="VW" :error="form.errors.vehicle_make" required />
                        <BaseInput v-model="form.vehicle_model" label="Modell" placeholder="Passat B8" :error="form.errors.vehicle_model" required />
                        <BaseInput v-model="form.vehicle_year" label="Baujahr" placeholder="2019" inputmode="numeric" maxlength="4" numeric :error="form.errors.vehicle_year" />
                        <BaseInput v-model="form.vehicle_plate" label="Kennzeichen" placeholder="D-AB 1234" :error="form.errors.vehicle_plate" />
                    </div>

                    <BaseInput v-model="form.vehicle_vin" label="Fahrgestellnummer" :error="form.errors.vehicle_vin" optional />

                    <BaseTextarea
                        v-model="form.description"
                        label="Schilderung"
                        placeholder="Was ist passiert? Zwei bis drei Sätze genügen."
                        :error="form.errors.description"
                        optional
                    />
                </div>
            </section>

            <section class="mt-6 border border-gray-200 bg-white p-6">
                <SectionLabel text="Kundendaten" tone="muted" :with-rule="false" />

                <div class="flex flex-col gap-5 pt-5">
                    <BaseInput v-model="form.customer_name" label="Vor- und Nachname" placeholder="Martina Reinhardt" :error="form.errors.customer_name" required />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <BaseInput v-model="form.customer_phone" label="Telefon" placeholder="+49 179 0000000" numeric :error="form.errors.customer_phone" required />
                        <BaseInput v-model="form.customer_email" label="E-Mail" type="email" placeholder="name@beispiel.de" :error="form.errors.customer_email" required />
                    </div>

                    <BaseSelect
                        v-model="form.urgency"
                        label="Dringlichkeit"
                        :options="urgencyOptions"
                        :error="form.errors.urgency"
                        optional
                        class="max-w-80"
                    />
                </div>
            </section>

            <!--
                Consent was given on the telephone rather than by ticking a box.
                Saying so plainly is the operator's reminder that it has to have
                happened before this button is pressed.
            -->
            <p class="flex items-start gap-2.5 pt-6 text-sm leading-normal text-gray-600">
                <Phone :size="16" :stroke-width="1.5" class="mt-0.5 shrink-0 text-gray-400" aria-hidden="true" />
                <span class="measure">
                    Mit dem Anlegen bestätigen Sie, dass die anfragende Person am Telefon in die
                    Weitergabe ihrer Daten an geeignete Sachverständige eingewilligt hat. Sie erhält
                    unmittelbar dieselbe Bestätigungsmail wie bei einer Anfrage über die Website.
                </span>
            </p>

            <BaseButton type="submit" size="cta" class="mt-6" :loading="form.processing" loading-label="Wird angelegt…">
                Anfrage anlegen und vermitteln
            </BaseButton>
        </form>
    </AdminLayout>
</template>
