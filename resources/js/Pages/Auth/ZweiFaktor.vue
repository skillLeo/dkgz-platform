<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseCodeInput from '../../Components/Base/BaseCodeInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

const form = useForm({ code: '' })

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
        panel-text="Der Code wechselt alle 30 Sekunden."
    >
        <form novalidate @submit.prevent="submit">
            <BaseCodeInput
                v-model="form.code"
                label="Bestätigungscode"
                :error="form.errors.code"
                @complete="submit"
            />

            <BaseButton type="submit" block class="mt-6" :loading="form.processing">Bestätigen</BaseButton>
        </form>
    </AuthLayout>
</template>
