<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Check, Copy, RefreshCw } from 'lucide-vue-next'
import BaseButton from '../Base/BaseButton.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

/**
 * What the sending domain's DNS actually says, and what it should say.
 *
 * Mail from a domain without SPF is filed as spam by most large German
 * providers — which here means a partner never learns a request came in, while
 * everything inside the application reports "sent". The failure is only visible
 * from outside, so this panel looks outward and states the gap plainly.
 */
const props = defineProps({
    check: { type: Object, default: null },
    presets: { type: Array, default: () => [] },
})

const emit = defineEmits(['apply-preset'])

const recheck = useForm({})
const copied = ref(null)

const copy = async (value, key) => {
    try {
        await navigator.clipboard.writeText(value)
        copied.value = key
        setTimeout(() => { copied.value = null }, 2000)
    } catch {
        copied.value = null
    }
}

const tone = {
    ok: { dot: 'bg-success', text: 'text-success', label: 'Vorhanden' },
    missing: { dot: 'bg-danger', text: 'text-danger', label: 'Fehlt' },
    problem: { dot: 'bg-warning', text: 'text-warning', label: 'Prüfen' },
}
</script>

<template>
    <section class="rounded-card border border-gray-200 bg-white">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 px-5 py-4">
            <div class="min-w-0">
                <h2 class="text-lead font-semibold text-navy-700">E-Mail-Zustellbarkeit</h2>
                <p class="pt-1 text-sm text-gray-600">
                    <template v-if="check?.domain">
                        Geprüfte Absenderdomain <span class="font-mono text-gray-800">{{ check.domain }}</span>
                    </template>
                    <template v-else>Es ist keine Absenderadresse hinterlegt.</template>
                </p>
            </div>
            <BaseButton
                variant="secondary"
                size="compact"
                :loading="recheck.processing"
                @click="recheck.post('/admin/e-mail-zustellbarkeit/pruefen', { preserveScroll: true })"
            >
                <RefreshCw :size="14" :stroke-width="1.5" aria-hidden="true" />
                Erneut prüfen
            </BaseButton>
        </div>

        <p v-if="check && !check.available" class="border-b border-gray-200 bg-warning/5 px-5 py-3 text-sm text-warning">
            {{ check.message }}
        </p>

        <!--
            The checked domain is the one mail is sent FROM, which is not always
            the one the site runs on. Saying so prevents an operator from fixing
            DNS on the wrong domain and wondering why nothing changed.
        -->
        <div
            v-if="check?.domain && !check.domain_matches_app"
            class="border-b border-gray-200 bg-warning/5 px-5 py-4"
        >
            <p class="text-base font-medium text-warning">Absenderdomain weicht von der Website ab</p>
            <p class="measure pt-1 text-sm leading-normal text-gray-800">
                Diese Seite läuft unter <span class="font-mono">{{ check.app_domain ?? 'unbekannt' }}</span>,
                versendet aber als <span class="font-mono">{{ check.from_address }}</span>. Geprüft werden deshalb
                die DNS-Einträge von <span class="font-mono">{{ check.domain }}</span> — nicht die der Website.
                Das ist zulässig, wenn es so gewollt ist; tragen Sie die Einträge dann bei
                <span class="font-mono">{{ check.domain }}</span> ein. Soll von der Website-Domain gesendet werden,
                ändern Sie zuerst die Absenderadresse unter E-Mail.
            </p>
        </div>

        <!-- The one warning that matters most, stated first and loudly. -->
        <div
            v-if="(check?.records ?? []).some((r) => r.name === 'SPF' && r.state === 'missing')"
            class="border-b border-gray-200 bg-danger/5 px-5 py-4"
        >
            <p class="text-base font-medium text-danger">Diese Domain hat keinen SPF-Eintrag</p>
            <p class="measure pt-1 text-sm leading-normal text-gray-800">
                E-Mails von <span class="font-mono">{{ check?.domain }}</span> werden bei vielen Anbietern als Spam
                eingestuft oder ganz verworfen. Solange das so ist, erfahren Partner nichts von neuen Anfragen und
                Kunden nichts vom Ergebnis — obwohl die Plattform den Versand als erfolgreich meldet.
            </p>
        </div>

        <ul>
            <li
                v-for="record in check?.records ?? []"
                :key="record.name"
                class="border-b border-gray-100 px-5 py-4 last:border-b-0"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span class="flex items-center gap-2.5">
                        <span class="block h-1.5 w-1.5 shrink-0 rounded-full" :class="tone[record.state].dot" aria-hidden="true" />
                        <span class="text-base font-medium text-gray-800">{{ record.name }}</span>
                        <span class="text-sm" :class="tone[record.state].text">{{ tone[record.state].label }}</span>
                    </span>
                    <span class="font-mono text-meta text-gray-400">{{ record.type }}</span>
                </div>

                <p class="measure pt-2 text-sm leading-normal text-gray-600">{{ record.explanation }}</p>

                <dl class="pt-3">
                    <dt class="text-eyebrow font-semibold uppercase text-gray-600">Name / Host</dt>
                    <dd class="flex items-start gap-2 pt-1">
                        <code class="min-w-0 flex-1 break-all rounded-xs border border-gray-200 bg-gray-50 px-2 py-1 font-mono text-xs text-gray-800">{{ record.host }}</code>
                        <button
                            type="button"
                            class="grid h-7 w-7 shrink-0 place-items-center rounded-xs text-gray-600 hover:text-navy-700"
                            :aria-label="`${record.name}-Host kopieren`"
                            @click="copy(record.host, `${record.name}-host`)"
                        >
                            <Check v-if="copied === `${record.name}-host`" :size="14" :stroke-width="2" class="text-success" aria-hidden="true" />
                            <Copy v-else :size="14" :stroke-width="1.5" aria-hidden="true" />
                        </button>
                    </dd>

                    <dt class="pt-3 text-eyebrow font-semibold uppercase text-gray-600">
                        {{ record.state === 'ok' ? 'Aktueller Wert' : 'Empfohlener Wert' }}
                    </dt>
                    <dd class="flex items-start gap-2 pt-1">
                        <code class="min-w-0 flex-1 break-all rounded-xs border border-gray-200 bg-gray-50 px-2 py-1 font-mono text-xs text-gray-800">{{ record.value }}</code>
                        <button
                            type="button"
                            class="grid h-7 w-7 shrink-0 place-items-center rounded-xs text-gray-600 hover:text-navy-700"
                            :aria-label="`${record.name}-Wert kopieren`"
                            @click="copy(record.value, `${record.name}-value`)"
                        >
                            <Check v-if="copied === `${record.name}-value`" :size="14" :stroke-width="2" class="text-success" aria-hidden="true" />
                            <Copy v-else :size="14" :stroke-width="1.5" aria-hidden="true" />
                        </button>
                    </dd>
                </dl>
            </li>
        </ul>

        <div v-if="presets.length" class="border-t border-gray-200 px-5 py-4">
            <p class="text-eyebrow font-semibold uppercase text-gray-600">Anbieter übernehmen</p>
            <p class="measure pt-1 text-sm leading-normal text-gray-600">
                Setzt Server, Port und Verschlüsselung. Benutzername und Passwort tragen Sie danach selbst ein.
            </p>
            <div class="flex flex-wrap gap-2 pt-3">
                <BaseButton
                    v-for="preset in presets"
                    :key="preset.id"
                    variant="secondary"
                    size="small"
                    @click="emit('apply-preset', preset)"
                >{{ preset.label }}</BaseButton>
            </div>
        </div>
    </section>
</template>
