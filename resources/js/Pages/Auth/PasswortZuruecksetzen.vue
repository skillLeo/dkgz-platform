<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BasePasswordInput from '../../Components/Base/BasePasswordInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import AuthNotice from '../../Components/Feedback/AuthNotice.vue'

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
    expired: { type: Boolean, default: false },
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const labels = { email: 'E-Mail-Adresse', password: 'Passwort' }
</script>

<template>
    <Head title="Neues Passwort vergeben" />

    <AuthLayout
        title="Neues Passwort vergeben"
        description="Wählen Sie ein Passwort, das Sie nirgendwo sonst verwenden."
        panel-title="Neues Passwort vergeben."
        panel-text="Dieser Link ist 24 Stunden gültig. Nach dem Speichern melden Sie sich mit dem neuen Passwort an."
    >
        <!-- Expired link: the design offers a new one rather than a dead end -->
        <template v-if="expired">
            <AuthNotice tone="warning" title="Link abgelaufen">
                Der Link zum Zurücksetzen war 24 Stunden gültig und ist nicht mehr verwendbar.
            </AuthNotice>

            <div class="pt-6">
                <p class="text-sm font-medium text-gray-800">Neuen Link anfordern</p>
                <p class="pt-1 text-sm leading-normal text-gray-600">
                    Wir senden Ihnen einen neuen Link an die hinterlegte Adresse.
                </p>
                <BaseButton href="/passwort-vergessen" block class="mt-4">Neuen Link anfordern</BaseButton>
            </div>
        </template>

        <form v-else novalidate @submit.prevent="form.post('/passwort-zuruecksetzen')">
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-6" />

            <div class="flex flex-col gap-5">
                <BaseInput
                    id="email"
                    v-model="form.email"
                    label="E-Mail-Adresse"
                    type="email"
                    autocomplete="username"
                    :error="form.errors.email"
                    required
                />

                <BasePasswordInput
                    id="password"
                    v-model="form.password"
                    label="Neues Passwort"
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
                    :error="form.errors.password_confirmation
                        || (form.password_confirmation && form.password !== form.password_confirmation
                            ? 'Die Passwörter stimmen nicht überein.'
                            : '')"
                    required
                />
            </div>

            <BaseButton type="submit" block class="mt-6" :loading="form.processing" loading-label="Wird gespeichert…">
                Passwort speichern
            </BaseButton>
        </form>
    </AuthLayout>
</template>
