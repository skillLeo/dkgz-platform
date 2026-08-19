<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BasePasswordInput from '../../Components/Base/BasePasswordInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
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
        panel-text="Nach dem Speichern melden Sie sich mit dem neuen Passwort an."
    >
        <form novalidate @submit.prevent="form.post('/passwort-zuruecksetzen')">
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
                    :error="form.errors.password_confirmation"
                    required
                />
            </div>

            <BaseButton type="submit" block class="mt-6" :loading="form.processing" loading-label="Wird gespeichert…">
                Passwort speichern
            </BaseButton>
        </form>
    </AuthLayout>
</template>
