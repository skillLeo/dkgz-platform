<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

defineProps({ reason: { type: String, default: null } })

const logout = useForm({})
</script>

<template>
    <Head title="Registrierung abgelehnt" />

    <AuthLayout
        eyebrow="Registrierung"
        title="Ihre Registrierung wurde nicht freigegeben"
        description="Wir konnten Ihren Zugang auf Grundlage der eingereichten Angaben nicht freigeben."
        panel-title="Keine Freigabe erteilt."
        panel-text="Wenn sich etwas an Ihren Nachweisen ändert, prüfen wir gern erneut."
    >
        <div v-if="reason" class="rounded-sm border border-gray-200 bg-gray-50 p-4">
            <p class="text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">Begründung</p>
            <p class="pt-2 text-base leading-normal text-gray-800">{{ reason }}</p>
        </div>

        <p class="measure pt-6 text-base leading-normal text-gray-600">
            Wenn Sie die genannten Punkte ergänzen können, wenden Sie sich an die Administration. Wir prüfen Ihre
            Unterlagen dann noch einmal.
        </p>

        <BaseButton variant="ghost" block class="mt-6" @click="logout.post('/abmelden')">Abmelden</BaseButton>
    </AuthLayout>
</template>
