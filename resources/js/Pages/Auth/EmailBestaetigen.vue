<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

defineProps({
    email: { type: String, default: '' },
    status: { type: String, default: null },
})

const resend = useForm({})
const logout = useForm({})
</script>

<template>
    <Head title="E-Mail bestätigen" />

    <AuthLayout
        title="Bitte bestätigen Sie Ihre E-Mail-Adresse"
        panel-title="Noch ein Schritt."
        panel-text="Die Bestätigung stellt sicher, dass Benachrichtigungen zu Anfragen Sie auch erreichen."
    >
        <p class="text-base leading-normal text-gray-600">
            Wir haben einen Bestätigungslink an
            <span class="font-mono text-gray-800">{{ email }}</span>
            geschickt. Öffnen Sie den Link, um fortzufahren.
        </p>

        <div v-if="status" class="mt-6 rounded-sm border border-navy-700 bg-navy-100 p-4">
            <p class="text-sm text-gray-800">{{ status }}</p>
        </div>

        <div class="flex flex-col gap-3 pt-6">
            <BaseButton block :loading="resend.processing" loading-label="Wird gesendet…" @click="resend.post('/email-bestaetigen/erneut')">
                Bestätigungslink erneut senden
            </BaseButton>
            <BaseButton variant="ghost" block @click="logout.post('/abmelden')">Abmelden</BaseButton>
        </div>
    </AuthLayout>
</template>
