<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import { usePage } from '@inertiajs/vue3'

/**
 * Auth Screens 08. The screen states the decision, gives the reason in full,
 * and offers the way back — a rejected partner can register again once the
 * missing document exists.
 */
defineProps({
    reason: { type: String, default: null },
    reference: { type: String, default: null },
    decidedAt: { type: String, default: null },
})

const page = usePage()
const { date } = useGermanFormat()
const logout = useForm({})
</script>

<template>
    <Head title="Registrierung abgelehnt" />

    <AuthLayout
        eyebrow="Registrierung"
        title="Registrierung nicht freigegeben."
        description="Ihre Registrierung wurde geprüft und konnte nicht freigegeben werden. Eine erneute Registrierung ist möglich, sobald die genannten Unterlagen vorliegen."
        panel-title="Keine Freigabe erteilt."
        panel-text="Wenn sich etwas an Ihren Nachweisen ändert, prüfen wir gern erneut."
    >
        <div v-if="reason" class="rounded-sm border border-gray-200 bg-gray-50 p-4">
            <SectionLabel text="Begründung" tone="muted" :with-rule="false" />
            <p class="pt-2 text-base leading-normal text-gray-800">{{ reason }}</p>
        </div>

        <p v-if="reference || decidedAt" class="pt-3 font-mono text-xs tabular-nums text-gray-400">
            <template v-if="reference">{{ reference }}</template>
            <template v-if="reference && decidedAt"> · </template>
            <template v-if="decidedAt">Entscheidung {{ date(decidedAt) }}</template>
        </p>

        <BaseButton href="/registrieren" block class="mt-6">Erneut registrieren</BaseButton>

        <p v-if="page.props.app?.phone" class="pt-4 text-sm text-gray-600">
            Rückfragen:
            <a :href="`tel:${page.props.app.phone.replace(/\s/g, '')}`" class="font-mono text-navy-700">{{ page.props.app.phone }}</a>
        </p>

        <BaseButton variant="ghost" block class="mt-4" @click="logout.post('/abmelden')">Abmelden</BaseButton>
    </AuthLayout>
</template>
