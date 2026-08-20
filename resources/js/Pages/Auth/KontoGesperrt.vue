<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

/**
 * Auth Screens 09. A suspension is not a dead end: running assignments stay
 * open and must still be completed, so the screen says so and offers the one
 * action that lifts the block.
 */
defineProps({
    reason: { type: String, default: null },
    suspendedAt: { type: String, default: null },
    partnerId: { type: String, default: null },
})

const page = usePage()
const { date } = useGermanFormat()
const logout = useForm({})
</script>

<template>
    <Head title="Konto gesperrt" />

    <AuthLayout
        eyebrow="Zugang"
        title="Ihr Zugang ist derzeit gesperrt."
        description="Ihr Partnerkonto wurde vorübergehend gesperrt. Laufende Aufträge bleiben bestehen und sind weiterhin abzuschließen."
        panel-title="Zugang vorübergehend gesperrt."
        panel-text="Zur Klärung wenden Sie sich bitte an die Administration."
    >
        <div v-if="reason" class="rounded-sm border border-danger bg-danger/4 p-4">
            <SectionLabel text="Grund der Sperre" tone="muted" :with-rule="false" />
            <p class="pt-2 text-base leading-normal text-gray-800">{{ reason }}</p>
        </div>

        <p v-if="suspendedAt || partnerId" class="pt-3 font-mono text-xs tabular-nums text-gray-400">
            <template v-if="suspendedAt">Gesperrt seit {{ date(suspendedAt) }}</template>
            <template v-if="suspendedAt && partnerId"> · </template>
            <template v-if="partnerId">{{ partnerId }}</template>
        </p>

        <BaseButton href="/kontakt" block class="mt-6">Nachweis einreichen</BaseButton>

        <p v-if="page.props.app?.phone" class="pt-4 text-sm text-gray-600">
            Rückfragen:
            <a :href="`tel:${page.props.app.phone.replace(/\s/g, '')}`" class="font-mono text-navy-700">{{ page.props.app.phone }}</a>
        </p>

        <BaseButton variant="ghost" block class="mt-4" @click="logout.post('/abmelden')">Abmelden</BaseButton>
    </AuthLayout>
</template>
