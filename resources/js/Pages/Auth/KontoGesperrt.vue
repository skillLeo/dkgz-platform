<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

defineProps({
    reason: { type: String, default: null },
    suspendedAt: { type: String, default: null },
})

const { dateTime } = useGermanFormat()
const logout = useForm({})
</script>

<template>
    <Head title="Konto gesperrt" />

    <AuthLayout
        eyebrow="Zugang"
        title="Ihr Zugang ist gesperrt"
        description="Sie erhalten vorerst keine weiteren Anfragen aus Ihrem Einsatzgebiet."
        panel-title="Zugang vorübergehend gesperrt."
        panel-text="Zur Klärung wenden Sie sich bitte an die Administration."
    >
        <div v-if="reason" class="rounded-sm border border-danger bg-danger/4 p-4">
            <p class="text-eyebrow font-semibold uppercase tracking-[0.09em] text-danger">Begründung</p>
            <p class="pt-2 text-base leading-normal text-gray-800">{{ reason }}</p>
            <p v-if="suspendedAt" class="pt-2 font-mono text-xs tabular-nums text-gray-400">
                Gesperrt am {{ dateTime(suspendedAt) }}
            </p>
        </div>

        <BaseButton variant="ghost" block class="mt-6" @click="logout.post('/abmelden')">Abmelden</BaseButton>
    </AuthLayout>
</template>
