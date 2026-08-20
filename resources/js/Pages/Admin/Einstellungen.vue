<script setup>
import { computed, reactive, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { AlertTriangle, Lock } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import MailDeliverabilityPanel from '../../Components/Domain/MailDeliverabilityPanel.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

/**
 * One screen drives every settings group. Secrets are never rendered back: the
 * field shows whether a value is stored and an empty submission leaves it alone.
 */
const props = defineProps({
    group: { type: String, required: true },
    groups: { type: Object, default: () => ({}) },
    settings: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    smtpConfigured: { type: Boolean, default: false },
    brandingTokens: { type: Object, default: () => ({}) },
    deliverability: { type: Object, default: null },
    mailPresets: { type: Array, default: () => [] },
    mailTransportReady: { type: Boolean, default: true },
})

const initial = {}
props.settings.forEach((setting) => {
    initial[setting.field] = setting.is_secret ? '' : (setting.value ?? (setting.type === 'boolean' ? false : ''))
})

const form = useForm(initial)
const testMail = useForm({ email: '' })

/** Fills host, port and encryption; credentials stay the operator's to enter. */
const applyPreset = (preset) => {
    if ('integrations.smtp_host' in form) form['integrations.smtp_host'] = preset.host
    if ('integrations.smtp_port' in form) form['integrations.smtp_port'] = String(preset.port)
    if ('integrations.smtp_encryption' in form) form['integrations.smtp_encryption'] = preset.encryption
}

const submit = () => form.post(`/admin/einstellungen/${props.group}`, {
    preserveScroll: true,
    forceFormData: props.settings.some((s) => s.type === 'file'),
})
</script>

<template>
    <Head :title="groups[group] ?? 'Einstellungen'" />

    <AdminLayout :title="groups[group] ?? 'Einstellungen'">
        <PageHeader :title="groups[group] ?? 'Einstellungen'" />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[220px_minmax(0,1fr)]">
            <nav aria-label="Einstellungsbereiche" class="lg:sticky lg:top-6 lg:self-start">
                <ul class="flex flex-wrap gap-2 border-l border-gray-200 lg:flex-col lg:gap-1">
                    <li v-for="(label, key) in groups" :key="key">
                        <Link
                            :href="`/admin/einstellungen/${key}`"
                            class="block py-1.5 pl-4 text-sm"
                            :class="key === group
                                ? '-ml-px border-l-2 border-navy-700 font-medium text-navy-700'
                                : 'text-gray-600 hover:text-navy-700'"
                        >{{ label }}</Link>
                    </li>
                </ul>
            </nav>

            <div class="min-w-0">
                <div v-if="group === 'integrations' && !smtpConfigured" class="mb-6 flex items-start gap-3 border border-warning bg-warning/5 p-4">
                    <AlertTriangle :size="18" :stroke-width="1.5" class="mt-0.5 shrink-0 text-warning" aria-hidden="true" />
                    <p class="text-sm leading-normal text-gray-800">
                        Es ist kein SMTP-Server hinterlegt. Ausgehende E-Mails laufen derzeit über den in der
                        Serverkonfiguration eingetragenen Mailer.
                    </p>
                </div>

                <form class="border border-gray-200 bg-white p-6" novalidate @submit.prevent="submit">
                    <ErrorSummary v-if="form.hasErrors" :errors="form.errors" class="mb-6" />

                    <div class="flex flex-col gap-5">
                        <template v-for="setting in settings" :key="setting.key">
                            <BaseToggle
                                v-if="setting.type === 'boolean'"
                                v-model="form[setting.field]"
                                :label="setting.label"
                                :description="setting.help"
                                on-label="Aktiv"
                                off-label="Inaktiv"
                                :disabled="!canEdit"
                            />

                            <BaseTextarea
                                v-else-if="setting.type === 'text'"
                                v-model="form[setting.field]"
                                :label="setting.label"
                                :hint="setting.help"
                                :error="form.errors[setting.field]"
                                :disabled="!canEdit"
                            />

                            <div v-else-if="setting.type === 'file'">
                                <label class="block pb-2 text-sm font-medium text-gray-800">{{ setting.label }}</label>
                                <img v-if="setting.preview_url" :src="setting.preview_url" :alt="setting.label" class="mb-3 h-12 w-auto">
                                <input
                                    type="file"
                                    accept="image/png,image/jpeg,image/svg+xml,image/webp,image/x-icon"
                                    :disabled="!canEdit"
                                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-sm file:border file:border-gray-300 file:bg-white file:px-3 file:py-2 file:text-sm file:text-navy-700"
                                    @change="form[setting.field] = $event.target.files[0]"
                                >
                                <p v-if="setting.help" class="pt-1.5 text-xs text-gray-400">{{ setting.help }}</p>
                            </div>

                            <div v-else-if="setting.is_secret">
                                <BaseInput
                                    v-model="form[setting.field]"
                                    :label="setting.label"
                                    type="password"
                                    :placeholder="setting.has_value ? '•••••••• (gespeichert)' : 'Nicht gesetzt'"
                                    :hint="setting.help ?? 'Leer lassen, um den gespeicherten Wert zu behalten.'"
                                    :error="form.errors[setting.field]"
                                    :disabled="!canEdit"
                                    autocomplete="new-password"
                                />
                            </div>

                            <div v-else-if="setting.key.startsWith('branding.color_')" class="flex items-end gap-3">
                                <BaseInput
                                    v-model="form[setting.field]"
                                    :label="setting.label"
                                    :hint="setting.help"
                                    :error="form.errors[setting.field]"
                                    :disabled="!canEdit"
                                    mono
                                    class="flex-1"
                                />
                                <span
                                    class="mb-1 h-11 w-11 shrink-0 rounded-sm border border-gray-300"
                                    :style="{ background: form[setting.field] }"
                                    aria-hidden="true"
                                />
                            </div>

                            <BaseInput
                                v-else
                                v-model="form[setting.field]"
                                :label="setting.label"
                                :hint="setting.help"
                                :error="form.errors[setting.field]"
                                :disabled="!canEdit"
                                :numeric="setting.type === 'integer' || setting.type === 'decimal'"
                            />
                        </template>
                    </div>

                    <BaseButton v-if="canEdit" type="submit" class="mt-6" :loading="form.processing">Einstellungen speichern</BaseButton>
                </form>

                <MailDeliverabilityPanel
                    v-if="deliverability"
                    :transport-ready="mailTransportReady"
                    :check="deliverability"
                    :presets="mailPresets"
                    class="mt-6"
                    @apply-preset="applyPreset"
                />

                <!-- Testmail: sends a real message and prints the real SMTP error -->
                <form
                    v-if="group === 'integrations'"
                    class="mt-6 border border-gray-200 bg-white p-6"
                    novalidate
                    @submit.prevent="testMail.post('/admin/integrationen/smtp-test', { preserveScroll: true })"
                >
                    <h2 class="text-h4 font-semibold text-navy-700">Testmail senden</h2>
                    <p class="measure pt-2 text-sm leading-normal text-gray-600">
                        Sendet sofort eine echte Nachricht über die oben hinterlegten Zugangsdaten. Schlägt der Versand
                        fehl, erscheint hier die Fehlermeldung des Servers im Klartext.
                    </p>

                    <div v-if="testMail.errors.smtp" class="mt-4 border border-danger bg-danger/4 p-4">
                        <p class="text-sm font-semibold text-danger">Der Versand ist fehlgeschlagen</p>
                        <p class="pt-2 font-mono text-xs leading-relaxed break-words text-gray-800">{{ testMail.errors.smtp }}</p>
                    </div>

                    <div class="flex flex-wrap items-end gap-3 pt-4">
                        <BaseInput
                            v-model="testMail.email"
                            label="Empfänger"
                            type="email"
                            placeholder="name@beispiel.de"
                            :error="testMail.errors.email"
                            class="min-w-64 flex-1"
                        />
                        <BaseButton type="submit" :loading="testMail.processing" loading-label="Wird gesendet…">Testmail senden</BaseButton>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
