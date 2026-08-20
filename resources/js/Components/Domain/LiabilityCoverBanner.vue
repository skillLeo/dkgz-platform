<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ShieldAlert, ShieldCheck } from 'lucide-vue-next'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

/**
 * Tells a partner where their liability cover stands, before it costs them work.
 *
 * A partner who quietly stops receiving requests cannot tell a slow week from
 * being switched off. This states the position plainly and offers exactly one
 * action, and it is deliberately calm — an expiring certificate is an errand,
 * not a failure.
 */
const props = defineProps({
    cover: { type: Object, default: null },
})

const { date } = useGermanFormat()

const state = computed(() => {
    if (!props.cover?.valid_until) return null
    if (props.cover.has_lapsed) return 'lapsed'
    if (props.cover.expires_soon) return 'expiring'

    return null
})
</script>

<template>
    <div
        v-if="state"
        class="rounded-card border p-4"
        :class="state === 'lapsed' ? 'border-danger bg-danger/5' : 'border-warning bg-warning/5'"
        role="status"
    >
        <div class="flex items-start gap-3">
            <component
                :is="state === 'lapsed' ? ShieldAlert : ShieldCheck"
                :size="20"
                :stroke-width="1.5"
                class="mt-0.5 shrink-0"
                :class="state === 'lapsed' ? 'text-danger' : 'text-warning'"
                aria-hidden="true"
            />
            <div class="min-w-0">
                <p class="text-base font-medium" :class="state === 'lapsed' ? 'text-danger' : 'text-gray-800'">
                    <template v-if="state === 'lapsed'">
                        Ihr Haftpflichtnachweis ist am {{ date(cover.valid_until) }} abgelaufen
                    </template>
                    <template v-else>
                        Ihr Haftpflichtnachweis läuft am {{ date(cover.valid_until) }} ab
                    </template>
                </p>
                <p class="measure pt-1 text-sm leading-normal text-gray-600">
                    <template v-if="state === 'lapsed' && cover.blocks_matching">
                        Solange kein gültiger Nachweis vorliegt, vermitteln wir Ihnen keine neuen Anfragen.
                        Laufende Aufträge bleiben bestehen und können normal abgeschlossen werden.
                    </template>
                    <template v-else-if="state === 'lapsed'">
                        Bitte hinterlegen Sie den aktuellen Nachweis. Ihre Vermittlung läuft vorerst weiter.
                    </template>
                    <template v-else>
                        Hinterlegen Sie den neuen Nachweis rechtzeitig, damit Ihre Vermittlung ohne Unterbrechung
                        weiterläuft.
                    </template>
                </p>
                <Link
                    href="/portal/profil"
                    class="mt-3 inline-block text-sm font-medium"
                    :class="state === 'lapsed' ? 'text-danger hover:underline' : 'text-navy-700 hover:text-navy-500'"
                >Nachweis hinterlegen</Link>
            </div>
        </div>
    </div>
</template>
