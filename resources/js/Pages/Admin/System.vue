<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { AlertTriangle, Check, X } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import StatCard from '../../Components/Data/StatCard.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

defineProps({
    health: { type: Object, required: true },
    queue: { type: Object, required: true },
    emails: { type: Object, required: true },
    storage: { type: Object, default: () => ({}) },
})

const { fileSize, dateTime } = useGermanFormat()
const retry = useForm({})
</script>

<template>
    <Head title="System" />

    <AdminLayout title="System">
        <PageHeader title="System" description="Der Betriebszustand der Plattform." />

        <div class="grid grid-cols-1 gap-4 pb-6 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Warteschlange" :value="queue.pending" :tone="queue.pending > 50 ? 'warning' : 'default'" hint="Offene Hintergrundaufträge" />
            <StatCard label="Fehlgeschlagen" :value="queue.failed" :tone="queue.failed ? 'danger' : 'default'" hint="Hintergrundaufträge" />
            <StatCard label="E-Mails heute" :value="emails.sent_today" hint="Erfolgreich versendet" />
            <StatCard label="E-Mail-Fehler" :value="emails.failed" :tone="emails.failed ? 'danger' : 'default'" />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="border border-gray-200 bg-white p-5">
                <SectionLabel text="Umgebung" tone="muted" />
                <dl class="pt-4">
                    <div v-for="(value, key) in {
                        'PHP': health.php_version,
                        'Laravel': health.laravel_version,
                        'Umgebung': health.environment,
                        'Datenbank': health.database,
                        'Warteschlange': health.queue,
                        'Cache': health.cache,
                        'Sitzungen': health.session,
                    }" :key="key" class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2.5 last:border-b-0">
                        <dt class="text-sm text-gray-600">{{ key }}</dt>
                        <dd class="font-mono text-sm text-gray-800">{{ value }}</dd>
                    </div>
                </dl>
            </section>

            <section class="border border-gray-200 bg-white p-5">
                <SectionLabel text="Prüfungen" tone="muted" />
                <ul class="pt-4">
                    <li v-for="check in [
                        { label: 'Öffentliche Dateien erreichbar', ok: health.storage_link, hint: 'storage:link oder Ersatzroute' },
                        { label: 'SMTP hinterlegt', ok: health.smtp_configured, hint: 'Sonst greift der Mailer aus der Serverkonfiguration' },
                        { label: 'Debug-Modus aus', ok: !health.debug, hint: 'Im Livebetrieb muss er aus sein' },
                        { label: 'Wartungsmodus aus', ok: !health.maintenance, hint: 'Die öffentliche Seite ist erreichbar' },
                    ]" :key="check.label" class="flex items-start gap-3 border-b border-gray-100 py-2.5 last:border-b-0">
                        <Check v-if="check.ok" :size="16" :stroke-width="1.5" class="mt-0.5 shrink-0 text-success" aria-hidden="true" />
                        <AlertTriangle v-else :size="16" :stroke-width="1.5" class="mt-0.5 shrink-0 text-warning" aria-hidden="true" />
                        <div class="min-w-0">
                            <p class="text-sm text-gray-800">{{ check.label }}</p>
                            <p class="text-xs text-gray-400">{{ check.hint }}</p>
                        </div>
                    </li>
                </ul>
                <p v-if="storage.free_bytes" class="pt-4 font-mono text-xs text-gray-400">
                    Freier Speicher: {{ fileSize(storage.free_bytes) }} von {{ fileSize(storage.total_bytes) }}
                </p>
            </section>
        </div>

        <section v-if="emails.recent_failures.length" class="mt-6 border border-danger bg-white">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 p-5">
                <SectionLabel text="Fehlgeschlagene E-Mails" tone="muted" />
                <BaseButton v-if="queue.failed" variant="secondary" size="compact" :loading="retry.processing" @click="retry.post('/admin/system/jobs-wiederholen')">
                    Aufträge erneut versuchen
                </BaseButton>
            </div>
            <ul>
                <li v-for="failure in emails.recent_failures" :key="failure.id" class="border-b border-gray-100 p-5 last:border-b-0">
                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <span class="font-mono text-sm text-gray-800">{{ failure.recipient }}</span>
                        <span class="font-mono text-xs tabular-nums text-gray-400">{{ dateTime(failure.created_at) }}</span>
                    </div>
                    <p class="pt-1 text-xs text-gray-600">{{ failure.template_key }}</p>
                    <p class="pt-2 font-mono text-xs leading-relaxed break-words text-danger">{{ failure.error }}</p>
                </li>
            </ul>
        </section>
    </AdminLayout>
</template>
