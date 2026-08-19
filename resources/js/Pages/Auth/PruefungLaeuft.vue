<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Check } from 'lucide-vue-next'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({ assessor: { type: Object, default: null } })

const { dateTime } = useGermanFormat()
const logout = useForm({})

/**
 * The one looping animation in the product: a single pulse on the node that is
 * currently in progress. Everything before it is settled, everything after is
 * waiting.
 */
const steps = computed(() => [
    { label: 'Registrierung eingegangen', done: true, active: false },
    { label: 'Nachweise hinterlegt', done: Boolean(props.assessor?.has_document), active: false },
    { label: 'Prüfung durch DKGZ', done: false, active: true },
    { label: 'Freigabe und Zugang', done: false, active: false },
])
</script>

<template>
    <Head title="Prüfung läuft" />

    <AuthLayout
        eyebrow="Registrierung"
        title="Ihre Registrierung wird geprüft"
        description="Wir prüfen Ihre Angaben und Nachweise. Sobald Ihr Zugang freigegeben ist, erhalten Sie eine E-Mail."
        panel-title="Ihre Unterlagen liegen uns vor."
        panel-text="Die Prüfung erfolgt manuell. In der Regel melden wir uns innerhalb von zwei Werktagen."
    >
        <p v-if="assessor?.company_name" class="pb-6 text-base text-gray-800">
            {{ assessor.company_name }}
            <span v-if="assessor.submitted_at" class="block pt-1 font-mono text-xs tabular-nums text-gray-400">
                Eingegangen am {{ dateTime(assessor.submitted_at) }}
            </span>
        </p>

        <ol class="border-l border-gray-200">
            <li
                v-for="step in steps"
                :key="step.label"
                class="relative flex items-start gap-3 py-3 pl-6"
            >
                <span
                    class="absolute -left-(--spacing-dot-inset) top-4 grid h-2.5 w-2.5 place-items-center rounded-full"
                    :class="step.done ? 'bg-navy-700' : step.active ? 'bg-accent' : 'bg-gray-300'"
                    :style="step.active ? 'animation: dkgz-pulse 2s ease-in-out infinite' : undefined"
                    aria-hidden="true"
                />
                <Check v-if="step.done" :size="16" :stroke-width="1.5" class="mt-0.5 shrink-0 text-navy-700" aria-hidden="true" />
                <span
                    class="text-base"
                    :class="step.done ? 'text-gray-800' : step.active ? 'font-medium text-navy-700' : 'text-gray-400'"
                >{{ step.label }}</span>
            </li>
        </ol>

        <div
            v-if="assessor && (!assessor.has_service_areas || !assessor.has_service_types)"
            class="mt-6 rounded-sm border border-warning bg-warning/5 p-4"
        >
            <p class="text-sm leading-normal text-gray-800">
                Ergänzen Sie schon jetzt Einsatzgebiet und Leistungen — dann können wir Ihnen unmittelbar nach der
                Freigabe passende Anfragen zuleiten.
            </p>
        </div>

        <BaseButton variant="ghost" block class="mt-6" @click="logout.post('/abmelden')">Abmelden</BaseButton>
    </AuthLayout>
</template>
