<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

defineProps({ reason: { type: String, required: true } })
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
        </div>

        <p class="measure pt-6 text-base leading-normal text-gray-600">
            Wenden Sie sich an die DKGZ-Administration, um eine neue Einladung zu erhalten. Falls Sie bereits einen
            Zugang haben, melden Sie sich einfach an.
        </p>

        <BaseButton href="/anmelden" block class="mt-6">Zur Anmeldung</BaseButton>
    </AuthLayout>
</template>
