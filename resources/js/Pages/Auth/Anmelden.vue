<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BasePasswordInput from '../../Components/Base/BasePasswordInput.vue'
import BaseCheckbox from '../../Components/Base/BaseCheckbox.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

const props = defineProps({
    admin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: true },
    status: { type: String, default: null },
})

const form = useForm({ email: '', password: '', remember: false })

const submit = () => {
    form.post(props.admin ? '/admin/anmelden' : '/anmelden', {
        onFinish: () => form.reset('password'),
    })
}

const labels = { email: 'E-Mail-Adresse', password: 'Passwort' }
const title = computed(() => 'Anmeldung')
</script>

<template>
    <Head :title="admin ? 'Anmeldung Administration' : 'Anmeldung'" />

    <AuthLayout
        :variant="admin ? 'admin' : 'partner'"
        :eyebrow="admin ? 'Administration' : 'Partnerportal'"
        :title="title"
        :description="admin ? '' : 'Melden Sie sich mit Ihren Zugangsdaten an.'"
    >
        <form novalidate @submit.prevent="submit">
            <div v-if="status" class="mb-6 rounded-sm border border-navy-700 bg-navy-100 p-4">
                <p class="text-sm text-gray-800">{{ status }}</p>
            </div>

            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-6" />

            <div class="flex flex-col gap-5">
                <BaseInput
                    id="email"
                    v-model="form.email"
                    label="E-Mail-Adresse"
                    type="email"
                    :placeholder="admin ? 'vorname.nachname@dkgz.de' : 'name@buero.de'"
                    autocomplete="username"
                    :error="form.errors.email"
                    required
                />

                <BasePasswordInput
                    id="password"
                    v-model="form.password"
                    label="Passwort"
                    autocomplete="current-password"
                    :error="form.errors.password"
                    required
                />

                <div class="flex items-center justify-between gap-4">
                    <BaseCheckbox v-model="form.remember">Angemeldet bleiben</BaseCheckbox>
                    <Link href="/passwort-vergessen" class="shrink-0 text-sm font-medium text-navy-700 hover:text-navy-500">
                        Passwort vergessen?
                    </Link>
                </div>
            </div>

            <BaseButton type="submit" block class="mt-6" :loading="form.processing">Anmelden</BaseButton>
        </form>

        <template v-if="!admin && canRegister" #footer>
            Noch kein Partner?
            <Link href="/registrieren" class="font-medium text-navy-700 hover:text-navy-500">Jetzt registrieren</Link>
        </template>
    </AuthLayout>
</template>
