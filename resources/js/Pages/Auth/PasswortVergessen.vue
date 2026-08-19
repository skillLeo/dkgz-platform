<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

defineProps({ status: { type: String, default: null } })

const form = useForm({ email: '' })
</script>

<template>
    <Head title="Passwort vergessen" />

    <AuthLayout
        title="Passwort zurücksetzen"
        description="Geben Sie Ihre E-Mail-Adresse ein. Wenn ein Konto dazu besteht, erhalten Sie einen Link zum Zurücksetzen."
        panel-title="Zugang wiederherstellen."
        panel-text="Der Link ist 60 Minuten gültig und nur einmal verwendbar."
    >
        <div v-if="status" class="mb-6 rounded-sm border border-navy-700 bg-navy-100 p-4">
            <p class="text-sm leading-normal text-gray-800">{{ status }}</p>
        </div>

        <form novalidate @submit.prevent="form.post('/passwort-vergessen')">
            <BaseInput
                id="email"
                v-model="form.email"
                label="E-Mail-Adresse"
                type="email"
                placeholder="name@buero.de"
                autocomplete="username"
                :error="form.errors.email"
                required
            />

            <BaseButton type="submit" block class="mt-6" :loading="form.processing" loading-label="Wird gesendet…">
                Link anfordern
            </BaseButton>
        </form>

        <template #footer>
            <Link href="/anmelden" class="font-medium text-navy-700 hover:text-navy-500">Zurück zur Anmeldung</Link>
        </template>
    </AuthLayout>
</template>
