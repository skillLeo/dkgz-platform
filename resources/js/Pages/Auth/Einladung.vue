<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Lock } from 'lucide-vue-next'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BasePasswordInput from '../../Components/Base/BasePasswordInput.vue'
import BasePostalCodeInput from '../../Components/Base/BasePostalCodeInput.vue'
import BaseCheckbox from '../../Components/Base/BaseCheckbox.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    invitation: { type: Object, required: true },
    token: { type: String, required: true },
    commissionRate: { type: Number, default: 15 },
    serviceTypes: { type: Array, default: () => [] },
})

const { date, percent } = useGermanFormat()

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    password: '',
    password_confirmation: '',
    company_name: '',
    street: '',
    house_number: '',
    postal_code: '',
    city: '',
    terms: false,
    privacy: false,
})

const labels = {
    first_name: 'Vorname',
    last_name: 'Nachname',
    phone: 'Telefonnummer',
    password: 'Passwort',
    company_name: 'Firmenname',
    street: 'Straße',
    house_number: 'Hausnummer',
    postal_code: 'Postleitzahl',
    city: 'Ort',
    terms: 'AGB',
    privacy: 'Datenschutzerklärung',
}
</script>

<template>
    <Head title="Einladung annehmen" />

    <AuthLayout
        eyebrow="Einladung"
        title="Zugang einrichten"
        description="Sie wurden in das DKGZ-Partnernetz eingeladen. Richten Sie jetzt Ihren Zugang ein."
        panel-title="Sie wurden in das DKGZ-Partnernetz eingeladen."
        panel-text="Nach dem Einrichten hinterlegen Sie Einsatzgebiet und Leistungen — danach erhalten Sie passende Anfragen."
    >
        <div v-if="invitation.message" class="mb-6 rounded-sm border border-gray-200 border-l-(length:--spacing-quote-rule) border-l-navy-700 bg-gray-50 p-4">
            <p class="text-base leading-relaxed text-gray-800">{{ invitation.message }}</p>
            <p v-if="invitation.invited_by" class="pt-2.5 font-mono text-xs text-gray-400">
                {{ invitation.invited_by }} · DKGZ Administration
            </p>
        </div>

        <dl class="mb-6 border-t border-gray-200">
            <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                <dt class="text-sm text-gray-600">Vermittlungsprovision</dt>
                <dd class="text-sm text-gray-800">{{ percent(commissionRate) }} auf abgeschlossene Aufträge</dd>
            </div>
            <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5">
                <dt class="text-sm text-gray-600">Grundgebühr</dt>
                <dd class="text-sm text-gray-800">keine</dd>
            </div>
            <div class="flex items-baseline justify-between gap-4 py-2.5">
                <dt class="text-sm text-gray-600">Einladung gültig bis</dt>
                <dd class="font-mono text-sm tabular-nums text-gray-800">{{ date(invitation.expires_at) }}</dd>
            </div>
        </dl>

        <form novalidate @submit.prevent="form.post(`/einladung/${token}`)">
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-6" />

            <div class="flex flex-col gap-5">
                <div>
                    <div class="flex items-baseline justify-between gap-4 pb-2">
                        <span class="text-sm font-medium text-gray-800">E-Mail-Adresse</span>
                        <span class="text-sm text-gray-400">gesperrt</span>
                    </div>
                    <div class="relative">
                        <div class="flex h-(--spacing-control) items-center overflow-hidden truncate rounded-sm bg-gray-50 pl-3.5 pr-10 text-base text-gray-600">
                            {{ invitation.email }}
                        </div>
                        <Lock :size="16" :stroke-width="1.5" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput id="first_name" v-model="form.first_name" label="Vorname" :error="form.errors.first_name" autocomplete="given-name" required />
                    <BaseInput id="last_name" v-model="form.last_name" label="Nachname" :error="form.errors.last_name" autocomplete="family-name" required />
                </div>

                <BaseInput id="phone" v-model="form.phone" label="Telefonnummer" :error="form.errors.phone" placeholder="+49 179 0000000" autocomplete="tel" numeric required />

                <BaseInput id="company_name" v-model="form.company_name" label="Firmenname" :error="form.errors.company_name" autocomplete="organization" required />

                <div class="grid grid-cols-[minmax(0,1fr)_120px] gap-4">
                    <BaseInput id="street" v-model="form.street" label="Straße" :error="form.errors.street" autocomplete="address-line1" required />
                    <BaseInput id="house_number" v-model="form.house_number" label="Hausnummer" :error="form.errors.house_number" required />
                </div>

                <div class="grid grid-cols-[160px_minmax(0,1fr)] gap-4">
                    <BasePostalCodeInput
                        id="postal_code"
                        v-model="form.postal_code"
                        v-model:city="form.city"
                        :error="form.errors.postal_code"
                        required
                    />
                    <BaseInput id="city" v-model="form.city" label="Ort" :error="form.errors.city" autocomplete="address-level2" required />
                </div>

                <BasePasswordInput
                    id="password"
                    v-model="form.password"
                    label="Passwort festlegen"
                    autocomplete="new-password"
                    :error="form.errors.password"
                    show-meter
                    show-checklist
                    required
                />

                <BasePasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    label="Passwort wiederholen"
                    autocomplete="new-password"
                    :error="form.errors.password_confirmation"
                    required
                />

                <BaseCheckbox id="terms" v-model="form.terms" :error="form.errors.terms">
                    Ich akzeptiere die
                    <a href="/agb" class="border-b border-navy-700 pb-0.5 text-navy-700">Allgemeinen Geschäftsbedingungen</a>
                    und die Provisionsvereinbarung von {{ percent(commissionRate) }} auf abgeschlossene Aufträge.
                </BaseCheckbox>

                <BaseCheckbox id="privacy" v-model="form.privacy" :error="form.errors.privacy">
                    Ich habe die
                    <a href="/datenschutz" class="border-b border-navy-700 pb-0.5 text-navy-700">Datenschutzerklärung</a>
                    gelesen und stimme der Verarbeitung meiner Daten zu.
                </BaseCheckbox>
            </div>

            <BaseButton type="submit" block class="mt-6" :loading="form.processing" loading-label="Wird eingerichtet…">
                Einladung annehmen und fortfahren
            </BaseButton>
        </form>
    </AuthLayout>
</template>
