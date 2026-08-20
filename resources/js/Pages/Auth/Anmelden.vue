<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BasePasswordInput from '../../Components/Base/BasePasswordInput.vue'
import BaseCheckbox from '../../Components/Base/BaseCheckbox.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import AuthNotice from '../../Components/Feedback/AuthNotice.vue'

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

// The throttle message is the one the form request raises after five attempts;
// it gets the calmer warning tone rather than the error one.
const throttled = computed(() => (form.errors.email ?? '').startsWith('Bitte warten Sie'))
</script>

<template>
    <Head :title="admin ? 'Anmeldung Administration' : 'Anmeldung'" />

    <AuthLayout
        :variant="admin ? 'admin' : 'partner'"
        :eyebrow="admin ? 'Administration' : 'Partnerportal'"
        title="Anmeldung"
        :description="admin ? '' : 'Melden Sie sich mit Ihrer hinterlegten E-Mail-Adresse an.'"
    >
        <form novalidate @submit.prevent="submit">
            <div v-if="status" class="mb-6 rounded-sm border border-navy-700 bg-navy-100 p-4">
                <p class="text-sm text-gray-800">{{ status }}</p>
            </div>

            <AuthNotice v-if="throttled" tone="warning" title="Zu viele Versuche" class="mb-6">
                {{ form.errors.email }}
            </AuthNotice>

            <AuthNotice v-else-if="form.errors.email" tone="error" title="Anmeldung nicht möglich" class="mb-6">
                {{ form.errors.email }}
            </AuthNotice>

            <div class="flex flex-col gap-5">
                <BaseInput
                    id="email"
                    v-model="form.email"
                    label="E-Mail-Adresse"
                    type="email"
                    :placeholder="admin ? 'vorname.nachname@dkgz.de' : 'name@buero.de'"
                    autocomplete="username"
                    hint="Die im Portal hinterlegte Adresse."
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
            <Link href="/registrieren" class="font-medium text-navy-700 hover:text-navy-500">Registrierung starten</Link>
        </template>
    </AuthLayout>
</template>
