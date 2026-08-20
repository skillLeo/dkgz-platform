<script setup>
import { computed, onUnmounted, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import AuthNotice from '../../Components/Feedback/AuthNotice.vue'

/**
 * Auth Screens 02. Two states on one screen: the form, and — once a link has
 * gone out — the confirmation with a resend countdown, so the visitor is never
 * left wondering whether anything happened.
 */
const props = defineProps({
    status: { type: String, default: null },
})

const form = useForm({ email: '' })

const sentTo = ref(null)
const cooldown = ref(0)
let timer = null

const startCooldown = (seconds = 60) => {
    cooldown.value = seconds
    if (timer) clearInterval(timer)
    timer = setInterval(() => {
        cooldown.value -= 1
        if (cooldown.value <= 0) clearInterval(timer)
    }, 1000)
}

onUnmounted(() => { if (timer) clearInterval(timer) })

const countdown = computed(() => {
    const m = String(Math.floor(cooldown.value / 60)).padStart(2, '0')
    const s = String(cooldown.value % 60).padStart(2, '0')

    return `${m}:${s}`
})

const submit = () => form.post('/passwort-vergessen', {
    preserveScroll: true,
    onSuccess: () => {
        sentTo.value = form.email
        startCooldown(60)
    },
})
</script>

<template>
    <Head title="Passwort vergessen" />

    <AuthLayout
        title="Passwort zurücksetzen"
        description="Geben Sie Ihre E-Mail-Adresse ein. Wenn ein Konto dazu besteht, erhalten Sie einen Link zum Zurücksetzen."
        panel-title="Zugang wiederherstellen."
        panel-text="Der Link ist 24 Stunden gültig und nur einmal verwendbar."
    >
        <!-- Sent state -->
        <template v-if="sentTo">
            <AuthNotice tone="success" title="E-Mail versendet">
                Wir haben einen Link zum Zurücksetzen an
                <span class="font-mono text-gray-800">{{ sentTo }}</span>
                gesendet. Der Link ist 24 Stunden gültig.
            </AuthNotice>

            <div class="pt-6">
                <p class="text-sm font-medium text-gray-800">Keine E-Mail erhalten?</p>
                <p class="pt-1 text-sm leading-normal text-gray-600">
                    Sehen Sie im Spam-Ordner nach. Sie können den Link erneut anfordern.
                </p>

                <p v-if="cooldown > 0" class="pt-3 font-mono text-xs tabular-nums text-gray-400">
                    Erneut senden möglich in {{ countdown }}
                </p>
                <BaseButton
                    v-else
                    variant="secondary"
                    block
                    class="mt-4"
                    :loading="form.processing"
                    @click="submit"
                >Erneut senden</BaseButton>
            </div>
        </template>

        <!-- Form state -->
        <form v-else novalidate @submit.prevent="submit">
            <AuthNotice v-if="status && !sentTo" tone="info" title="Hinweis" class="mb-6">{{ status }}</AuthNotice>

            <BaseInput
                id="email"
                v-model="form.email"
                label="E-Mail-Adresse"
                type="email"
                placeholder="name@buero.de"
                autocomplete="username"
                hint="Die im Portal hinterlegte Adresse."
                :error="form.errors.email"
                required
            />

            <BaseButton type="submit" block class="mt-6" :loading="form.processing" loading-label="Wird gesendet…">
                Link anfordern
            </BaseButton>
        </form>

        <template #footer>
            <Link href="/anmelden" class="font-medium text-navy-700 hover:text-navy-500">Zurück zur Anmeldung</Link>
        </template>
    </AuthLayout>
</template>
