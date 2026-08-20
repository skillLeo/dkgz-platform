<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseCodeInput from '../../Components/Base/BaseCodeInput.vue'
import BaseCheckbox from '../../Components/Base/BaseCheckbox.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

const form = useForm({ code: '', trust_device: false })

const submit = () => form.post('/admin/zwei-faktor', { onFinish: () => form.reset('code') })
</script>

<template>
    <Head title="Zwei-Faktor-Bestätigung" />

    <AuthLayout
        variant="admin"
        eyebrow="Administration"
        title="Bestätigungscode eingeben"
        description="Öffnen Sie Ihre Authenticator-App und geben Sie den aktuellen sechsstelligen Code ein."
        panel-title="Zusätzliche Bestätigung."
        panel-text="Sechsstelliger Code, zehn Minuten gültig."
    >
        <form novalidate @submit.prevent="submit">
            <BaseCodeInput
                v-model="form.code"
                label="Bestätigungscode"
                :error="form.errors.code"
                @complete="submit"
            />

            <div class="pt-5">
                <BaseCheckbox v-model="form.trust_device">Diesem Gerät für 30 Tage vertrauen.</BaseCheckbox>
            </div>

            <BaseButton type="submit" block class="mt-6" :loading="form.processing">Bestätigen</BaseButton>

            <div class="pt-4 text-center">
                <p class="text-sm text-gray-600">Code nicht erhalten?</p>
                <p class="pt-1 text-sm leading-normal text-gray-600">
                    Der Code steht in Ihrer Authenticator-App und wechselt alle 30 Sekunden.
                </p>
            </div>
        </form>
    </AuthLayout>
</template>
