<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    reason: { type: String, required: true },
    sentAt: { type: String, default: null },
    validDays: { type: Number, default: 14 },
})

const { date } = useGermanFormat()
</script>

<template>
    <Head title="Einladung ungültig" />

    <AuthLayout
        eyebrow="Einladung"
        title="Diese Einladung ist nicht mehr gültig"
        panel-title="Einladung abgelaufen."
        panel-text="Eine neue Einladung fordern Sie bei der Administration an."
    >
        <div class="rounded-sm border border-gray-200 bg-gray-50 p-4">
            <p class="text-base leading-normal text-gray-800">{{ reason }}</p>
            <p v-if="sentAt" class="pt-2 font-mono text-xs tabular-nums text-gray-400">
                Diese Einladung war {{ validDays }} Tage gültig. Sie wurde am {{ date(sentAt) }} versendet.
            </p>
        </div>

        <p class="measure pt-6 text-base leading-normal text-gray-600">
            Bitte wenden Sie sich an die DKGZ-Administration, um eine neue Einladung zu erhalten. Falls Sie bereits
            einen Zugang haben, melden Sie sich einfach an.
        </p>

        <BaseButton href="/anmelden" block class="mt-6">Zur Anmeldung</BaseButton>
    </AuthLayout>
</template>
