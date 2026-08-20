<script setup>
import AssessorPhotoField from '../../Components/Domain/AssessorPhotoField.vue'
import { Head, useForm } from '@inertiajs/vue3'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BasePostalCodeInput from '../../Components/Base/BasePostalCodeInput.vue'
import BaseVatInput from '../../Components/Base/BaseVatInput.vue'
import BaseDatePicker from '../../Components/Base/BaseDatePicker.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

const props = defineProps({ profile: { type: Object, required: true } })

const form = useForm({ ...props.profile })

const labels = {
    first_name: 'Vorname', last_name: 'Nachname', phone: 'Telefonnummer',
    company_name: 'Firmenname', street: 'Straße', house_number: 'Hausnummer',
    postal_code: 'Postleitzahl', city: 'Ort', vat_id: 'USt-IdNr.',
    certification_number: 'Zertifizierungsnummer',
}
</script>

<template>
    <Head title="Profil" />

    <PortalLayout title="Profil">
        <section class="mb-6 max-w-3xl rounded-card border border-gray-200 bg-white p-5">
            <AssessorPhotoField :photo-url="profile.photo_url" :initials="profile.initials" />
        </section>

        <PageHeader title="Profil" description="Ihre Angaben im Partnernetz." />

        <form class="max-w-3xl" novalidate @submit.prevent="form.post('/portal/profil', { preserveScroll: true })">
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-6" />

            <section class="border border-gray-200 bg-white p-6">
                <SectionLabel text="Ansprechpartner" tone="muted" />
                <div class="flex flex-col gap-5 pt-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <BaseInput v-model="form.first_name" label="Vorname" :error="form.errors.first_name" required />
                        <BaseInput v-model="form.last_name" label="Nachname" :error="form.errors.last_name" required />
                    </div>
                    <BaseInput v-model="form.phone" label="Telefonnummer" numeric :error="form.errors.phone" required />
                    <BaseInput :model-value="profile.email" label="E-Mail-Adresse" disabled hint="Die E-Mail-Adresse ändert die Administration." />
                </div>
            </section>

            <section class="mt-6 border border-gray-200 bg-white p-6">
                <SectionLabel text="Unternehmen" tone="muted" />
                <div class="flex flex-col gap-5 pt-5">
                    <BaseInput v-model="form.company_name" label="Firmenname" :error="form.errors.company_name" required />
                    <div class="grid grid-cols-[minmax(0,1fr)_120px] gap-4">
                        <BaseInput v-model="form.street" label="Straße" :error="form.errors.street" required />
                        <BaseInput v-model="form.house_number" label="Hausnummer" :error="form.errors.house_number" required />
                    </div>
                    <div class="grid grid-cols-[160px_minmax(0,1fr)] gap-4">
                        <BasePostalCodeInput v-model="form.postal_code" v-model:city="form.city" :error="form.errors.postal_code" required />
                        <BaseInput v-model="form.city" label="Ort" :error="form.errors.city" required />
                    </div>
                    <BaseVatInput v-model="form.vat_id" :error="form.errors.vat_id" />
                    <BaseInput v-model="form.website" label="Internetadresse" placeholder="www.buero.de" :error="form.errors.website" optional />
                </div>
            </section>

            <section class="mt-6 border border-gray-200 bg-white p-6">
                <SectionLabel text="Qualifikation" tone="muted" />
                <div class="flex flex-col gap-5 pt-5">
                    <BaseInput :model-value="profile.certification_body" label="Zertifizierungsstelle" disabled hint="Eine Änderung der Stelle prüft die Administration." />
                    <BaseInput v-model="form.certification_number" label="Zertifizierungsnummer" mono :error="form.errors.certification_number" required />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <BaseDatePicker v-model="form.certification_valid_until" label="Gültig bis" :error="form.errors.certification_valid_until" optional />
                        <BaseInput v-model="form.years_experience" label="Berufserfahrung in Jahren" inputmode="numeric" numeric :error="form.errors.years_experience" optional />
                    </div>
                    <p class="text-sm text-gray-600">
                        Qualifikationsnachweis: <span :class="profile.has_document ? 'text-success' : 'text-warning'">{{ profile.has_document ? 'hinterlegt' : 'fehlt' }}</span>
                    </p>
                </div>
            </section>

            <BaseButton type="submit" size="cta" class="mt-6" :loading="form.processing">Profil speichern</BaseButton>
        </form>
    </PortalLayout>
</template>
