<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BasePasswordInput from '../../Components/Base/BasePasswordInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

const props = defineProps({
    profile: { type: Object, required: true },
    mustChangePassword: { type: Boolean, default: false },
})

const details = useForm({
    first_name: props.profile.first_name,
    last_name: props.profile.last_name,
    phone: props.profile.phone,
})

const password = useForm({ current_password: '', password: '', password_confirmation: '' })
</script>

<template>
    <Head title="Mein Konto" />

    <AdminLayout title="Mein Konto">
        <div
            v-if="mustChangePassword"
            class="mb-6 max-w-3xl rounded-card border border-warning bg-warning/5 p-4"
            role="status"
        >
            <p class="text-base font-medium text-warning">Bitte vergeben Sie ein eigenes Passwort</p>
            <p class="measure pt-1 text-sm leading-normal text-gray-800">
                Ihr aktuelles Passwort wurde von jemand anderem gesetzt. Solange es unverändert bleibt, ist es kein
                Geheimnis, das nur Sie kennen. Erst danach stehen Ihnen die übrigen Bereiche wieder offen.
            </p>
        </div>

        <div class="grid max-w-4xl grid-cols-1 items-start gap-6 lg:grid-cols-2">
            <form
                class="rounded-card border border-gray-200 bg-white p-5"
                novalidate
                @submit.prevent="details.post('/admin/profil', { preserveScroll: true })"
            >
                <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Angaben</h2>
                <ErrorSummary v-if="details.hasErrors" :errors="details.errors" class="mt-4" />

                <div class="flex flex-col gap-5 pt-4">
                    <BaseInput v-model="details.first_name" label="Vorname" :error="details.errors.first_name" required />
                    <BaseInput v-model="details.last_name" label="Nachname" :error="details.errors.last_name" required />
                    <BaseInput v-model="details.phone" label="Telefon" type="tel" :error="details.errors.phone" optional />
                    <BaseInput :model-value="profile.email" label="E-Mail" disabled hint="Änderung nur durch die Systemverwaltung." />
                </div>

                <p class="pt-4 text-sm text-gray-600">
                    Rolle: <span class="text-gray-800">{{ profile.roles.join(', ') }}</span>
                </p>

                <BaseButton type="submit" size="cta" class="mt-5" :loading="details.processing">
                    Änderungen speichern
                </BaseButton>
            </form>

            <form
                class="rounded-card border border-gray-200 bg-white p-5"
                novalidate
                @submit.prevent="password.post('/admin/profil/passwort', { preserveScroll: true, onSuccess: () => password.reset() })"
            >
                <h2 class="text-eyebrow font-semibold uppercase text-gray-600">Passwort ändern</h2>
                <ErrorSummary v-if="password.hasErrors" :errors="password.errors" class="mt-4" />

                <div class="flex flex-col gap-5 pt-4">
                    <BasePasswordInput
                        v-model="password.current_password"
                        label="Aktuelles Passwort"
                        autocomplete="current-password"
                        :error="password.errors.current_password"
                        required
                    />
                    <BasePasswordInput
                        v-model="password.password"
                        label="Neues Passwort"
                        autocomplete="new-password"
                        :error="password.errors.password"
                        show-meter
                        show-checklist
                        required
                    />
                    <BasePasswordInput
                        v-model="password.password_confirmation"
                        label="Neues Passwort wiederholen"
                        autocomplete="new-password"
                        :error="password.errors.password_confirmation"
                        required
                    />
                </div>

                <BaseButton type="submit" size="cta" class="mt-5" :loading="password.processing">
                    Passwort ändern
                </BaseButton>
            </form>
        </div>
    </AdminLayout>
</template>
