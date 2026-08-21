<script setup>
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import AssessorPhotoField from '../../Components/Domain/AssessorPhotoField.vue'
import TabBar from '../../Components/Data/TabBar.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
import BasePasswordInput from '../../Components/Base/BasePasswordInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

const props = defineProps({
    email: { type: String, default: '' },
    photo: { type: Object, default: () => ({}) },
    company: { type: Object, default: () => ({}) },
    bank: { type: Object, default: null },
    collectsBankDetails: { type: Boolean, default: false },
    notifications: { type: Object, default: () => ({}) },
})

const tabs = computed(() => [
    { value: 'firma', label: 'Profil & Firma' },
    props.collectsBankDetails ? { value: 'bank', label: 'Bankverbindung' } : null,
    { value: 'passwort', label: 'Passwort' },
    { value: 'benachrichtigungen', label: 'Benachrichtigungen' },
].filter(Boolean))

const current = ref('firma')

const companyForm = useForm({ ...props.company })
const bankForm = useForm({ ...(props.bank ?? {}) })
const notifyForm = useForm({ ...props.notifications })
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' })
</script>

<template>
    <Head title="Einstellungen" />

    <PortalLayout title="Einstellungen">
        <TabBar :tabs="tabs" :current="current" @select="current = $event" />

        <div class="max-w-3xl pt-6">
            <section
                v-if="current === 'firma'"
                class="mb-5 rounded-card border border-gray-200 bg-white p-5"
            >
                <AssessorPhotoField :photo-url="photo.url" :initials="photo.initials" />
            </section>

            <form
                v-if="current === 'firma'"
                class="rounded-card border border-gray-200 bg-white p-5"
                novalidate
                @submit.prevent="companyForm.post('/portal/einstellungen/firma', { preserveScroll: true })"
            >
                <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Firmendaten</h2>
                <ErrorSummary v-if="companyForm.hasErrors" :errors="companyForm.errors" class="mt-4" />

                <div class="grid grid-cols-1 gap-5 pt-4 sm:grid-cols-2">
                    <BaseInput v-model="companyForm.company_name" label="Firmenname" :error="companyForm.errors.company_name" required />
                    <BaseInput v-model="companyForm.vat_id" label="USt-IdNr." placeholder="DE123456789" :error="companyForm.errors.vat_id" optional />
                    <BaseInput v-model="companyForm.phone" label="Telefon" type="tel" :error="companyForm.errors.phone" required />
                    <BaseInput :model-value="email" label="E-Mail" disabled hint="Änderung nur über den Support." />
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-6">
                    <BaseButton type="submit" size="cta" :loading="companyForm.processing">Änderungen speichern</BaseButton>
                    <BaseButton variant="ghost" size="cta" @click="companyForm.reset()">Verwerfen</BaseButton>
                </div>
            </form>

            <form
                v-else-if="current === 'bank' && collectsBankDetails"
                class="rounded-card border border-gray-200 bg-white p-5"
                novalidate
                @submit.prevent="bankForm.post('/portal/einstellungen/bankverbindung', { preserveScroll: true })"
            >
                <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Bankverbindung</h2>
                <p class="measure pt-2 text-sm leading-normal text-gray-600">
                    Diese Angaben dienen ausschließlich der Abrechnung der monatlichen Vermittlungsprovision per
                    Überweisung. Es wird zu keinem Zeitpunkt ein Betrag von diesem Konto eingezogen, und die
                    Plattform wickelt keinerlei Zahlungen ab. Die Angabe ist freiwillig.
                </p>
                <ErrorSummary v-if="bankForm.hasErrors" :errors="bankForm.errors" class="mt-4" />

                <div class="flex flex-col gap-5 pt-4">
                    <BaseInput v-model="bankForm.bank_account_holder" label="Kontoinhaber" :error="bankForm.errors.bank_account_holder" optional />
                    <BaseInput v-model="bankForm.bank_iban" label="IBAN" placeholder="DE89 3704 0044 0532 0130 00" :error="bankForm.errors.bank_iban" optional />
                    <BaseInput v-model="bankForm.bank_bic" label="BIC" placeholder="COBADEFFXXX" :error="bankForm.errors.bank_bic" optional />
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-6">
                    <BaseButton type="submit" size="cta" :loading="bankForm.processing">Änderungen speichern</BaseButton>
                    <BaseButton variant="ghost" size="cta" @click="bankForm.reset()">Verwerfen</BaseButton>
                </div>
            </form>

            <form
                v-else-if="current === 'passwort'"
                class="rounded-card border border-gray-200 bg-white p-5"
                novalidate
                @submit.prevent="passwordForm.post('/portal/einstellungen/passwort', { preserveScroll: true, onSuccess: () => passwordForm.reset() })"
            >
                <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Passwort ändern</h2>
                <ErrorSummary v-if="passwordForm.hasErrors" :errors="passwordForm.errors" class="mt-4" />

                <div class="flex flex-col gap-5 pt-4">
                    <BasePasswordInput v-model="passwordForm.current_password" label="Aktuelles Passwort" autocomplete="current-password" :error="passwordForm.errors.current_password" required />
                    <BasePasswordInput v-model="passwordForm.password" label="Neues Passwort" autocomplete="new-password" :error="passwordForm.errors.password" show-meter show-checklist required />
                    <BasePasswordInput v-model="passwordForm.password_confirmation" label="Neues Passwort wiederholen" autocomplete="new-password" :error="passwordForm.errors.password_confirmation" required />
                </div>

                <BaseButton type="submit" size="cta" class="mt-6" :loading="passwordForm.processing">Passwort ändern</BaseButton>
            </form>

            <form
                v-else
                class="rounded-card border border-gray-200 bg-white p-5"
                novalidate
                @submit.prevent="notifyForm.post('/portal/einstellungen/benachrichtigungen', { preserveScroll: true })"
            >
                <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Benachrichtigungen</h2>
                <ErrorSummary v-if="notifyForm.hasErrors" :errors="notifyForm.errors" class="mt-4" />

                <div class="pt-2">
                    <div class="border-b border-gray-100 py-3.5">
                        <BaseToggle
                            v-model="notifyForm.notify_new_request"
                            label="Neue Anfrage im Einsatzgebiet"
                            :show-state="false"
                        />
                    </div>
                    <div class="py-3.5">
                        <BaseToggle
                            v-model="notifyForm.notify_commission_statement"
                            label="Monatliche Provisionsabrechnung"
                            :show-state="false"
                        />
                    </div>
                </div>

                <p class="measure pt-2 text-sm leading-normal text-gray-600">
                    Anfragen erscheinen unabhängig davon immer im Portal — diese Einstellungen betreffen nur den
                    E-Mail-Versand.
                </p>

                <div class="flex flex-wrap items-center gap-3 pt-6">
                    <BaseButton type="submit" size="cta" :loading="notifyForm.processing">Änderungen speichern</BaseButton>
                    <BaseButton variant="ghost" size="cta" @click="notifyForm.reset()">Verwerfen</BaseButton>
                </div>
            </form>
        </div>
    </PortalLayout>
</template>
