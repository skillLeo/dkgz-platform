<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BasePasswordInput from '../../Components/Base/BasePasswordInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

defineProps({ email: { type: String, default: '' } })

const form = useForm({ current_password: '', password: '', password_confirmation: '' })
</script>

<template>
    <Head title="Einstellungen" />

    <PortalLayout title="Einstellungen">
        <PageHeader title="Einstellungen" />

        <form class="max-w-xl border border-gray-200 bg-white p-6" novalidate @submit.prevent="form.post('/portal/einstellungen/passwort', { preserveScroll: true, onSuccess: () => form.reset() })">
            <SectionLabel text="Passwort ändern" tone="muted" />
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" class="mt-5" />

            <div class="flex flex-col gap-5 pt-5">
                <BasePasswordInput v-model="form.current_password" label="Aktuelles Passwort" autocomplete="current-password" :error="form.errors.current_password" required />
                <BasePasswordInput v-model="form.password" label="Neues Passwort" autocomplete="new-password" :error="form.errors.password" show-meter show-checklist required />
                <BasePasswordInput v-model="form.password_confirmation" label="Neues Passwort wiederholen" autocomplete="new-password" :error="form.errors.password_confirmation" required />
            </div>

            <BaseButton type="submit" size="cta" class="mt-6" :loading="form.processing">Passwort ändern</BaseButton>
        </form>
    </PortalLayout>
</template>
