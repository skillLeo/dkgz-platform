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
    mailLog: { type: Array, default: () => [] },
})

const { fileSize, dateTime } = useGermanFormat()
const retry = useForm({})
const drain = useForm({})

const tone = {
    sent: 'text-success',
    queued: 'text-gray-600',
    failed: 'text-danger',
}
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

        <!--
            The last twenty attempts, whatever their outcome. A panel that shows
            only failures cannot distinguish "nothing was ever sent" from "all
            fine", and on shared hosting the first case is the common one.
        -->
        <section class="mt-6 rounded-card border border-gray-200 bg-white">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 px-5 py-4">
                <div class="min-w-0">
                    <h2 class="text-lead font-semibold text-navy-700">Mail-Zustellung</h2>
                    <p class="pt-1 text-sm text-gray-600">
                        Postausgang
                        <span class="font-mono text-gray-800">{{ health.mail_host || 'nicht konfiguriert' }}</span>
                        · Absender <span class="font-mono text-gray-800">{{ health.mail_from }}</span>
                    </p>
                </div>
                <BaseButton
                    variant="secondary"
                    size="compact"
                    :loading="drain.processing"
                    @click="drain.post('/admin/system/warteschlange', { preserveScroll: true })"
                >Warteschlange jetzt leeren</BaseButton>
            </div>

            <div v-if="!health.smtp_configured" class="border-b border-gray-200 bg-danger/5 px-5 py-4">
                <p class="text-base font-medium text-danger">Kein Postausgangsserver hinterlegt</p>
                <p class="measure pt-1 text-sm leading-normal text-gray-800">
                    Ohne SMTP-Server verlässt keine E-Mail die Plattform. Die Aufträge sammeln sich in der
                    Warteschlange, bis ein Server eingetragen ist.
                </p>
            </div>

            <div v-else-if="!health.scheduler_last_run" class="border-b border-gray-200 bg-warning/5 px-5 py-4">
                <p class="text-base font-medium text-warning">Der Zeitplaner hat noch nie ausgeführt</p>
                <p class="measure pt-1 text-sm leading-normal text-gray-800">
                    Es wurde noch keine E-Mail versendet. Das deutet darauf hin, dass der Cron-Eintrag auf dem
                    Server fehlt — ohne ihn läuft keine der geplanten Aufgaben.
                </p>
            </div>

            <p v-if="!mailLog.length" class="px-5 py-10 text-center text-sm text-gray-600">
                Es wurde noch kein Versand protokolliert.
            </p>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-175">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-5 py-2.5 text-left text-eyebrow font-semibold uppercase text-gray-600">Vorlage</th>
                            <th class="px-3 py-2.5 text-left text-eyebrow font-semibold uppercase text-gray-600">Empfänger</th>
                            <th class="px-3 py-2.5 text-left text-eyebrow font-semibold uppercase text-gray-600">Status</th>
                            <th class="px-5 py-2.5 text-left text-eyebrow font-semibold uppercase text-gray-600">Zeitpunkt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in mailLog" :key="row.id" class="border-b border-gray-100 last:border-b-0">
                            <td class="px-5 py-3 font-mono text-meta text-gray-800">{{ row.template_key }}</td>
                            <td class="px-3 py-3 text-sm text-gray-800">{{ row.recipient }}</td>
                            <td class="px-3 py-3 text-sm" :class="tone[row.status] ?? 'text-gray-600'">
                                {{ row.status }}
                                <span v-if="row.error" class="block max-w-100 truncate text-xs text-danger" :title="row.error">
                                    {{ row.error }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 font-mono text-meta tabular-nums text-gray-600">
                                {{ dateTime(row.sent_at ?? row.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminLayout>
</template>
