<script setup>
import { computed } from 'vue'

/**
 * Foundations §08. A 6px dot plus a word — never colour alone, so the status is
 * still readable without colour vision.
 */
const props = defineProps({
    status: { type: String, required: true },
    label: { type: String, default: '' },
    pulse: { type: Boolean, default: false },
})

const tones = {
    // requests
    new: { dot: 'bg-gray-400', text: 'text-gray-600', label: 'Neu' },
    matched: { dot: 'bg-navy-500', text: 'text-navy-700', label: 'Vermittelt' },
    assigned: { dot: 'bg-navy-700', text: 'text-navy-700', label: 'Vergeben' },
    completed: { dot: 'bg-success', text: 'text-success', label: 'Abgeschlossen' },
    cancelled: { dot: 'bg-gray-400', text: 'text-gray-600', label: 'Storniert' },
    expired: { dot: 'bg-warning', text: 'text-warning', label: 'Frist abgelaufen' },
    unanswered: { dot: 'bg-warning', text: 'text-warning', label: 'Ohne Rückmeldung' },
    // matches
    pending: { dot: 'bg-warning', text: 'text-warning', label: 'Offen' },
    accepted: { dot: 'bg-success', text: 'text-success', label: 'Angenommen' },
    declined: { dot: 'bg-danger', text: 'text-danger', label: 'Abgelehnt' },
    closed: { dot: 'bg-gray-400', text: 'text-gray-600', label: 'Geschlossen' },
    // assignments
    in_progress: { dot: 'bg-navy-500', text: 'text-navy-700', label: 'In Bearbeitung' },
    documents_uploaded: { dot: 'bg-navy-500', text: 'text-navy-700', label: 'Unterlagen hochgeladen' },
    // commissions
    open: { dot: 'bg-warning', text: 'text-warning', label: 'Offen' },
    invoiced: { dot: 'bg-navy-500', text: 'text-navy-700', label: 'Abgerechnet' },
    settled: { dot: 'bg-success', text: 'text-success', label: 'Bezahlt' },
    waived: { dot: 'bg-gray-400', text: 'text-gray-600', label: 'Erlassen' },
    // assessors
    approved: { dot: 'bg-success', text: 'text-success', label: 'Freigegeben' },
    rejected: { dot: 'bg-danger', text: 'text-danger', label: 'Abgelehnt' },
    suspended: { dot: 'bg-danger', text: 'text-danger', label: 'Gesperrt' },
    available: { dot: 'bg-success', text: 'text-success', label: 'Verfügbar' },
    unavailable: { dot: 'bg-gray-400', text: 'text-gray-600', label: 'Nicht verfügbar' },
}

const tone = computed(() => tones[props.status] ?? { dot: 'bg-gray-400', text: 'text-gray-600', label: props.status })
const text = computed(() => props.label || tone.value.label)
</script>

<template>
    <span class="inline-flex items-center gap-2 whitespace-nowrap">
        <span
            class="h-1.5 w-1.5 shrink-0 rounded-full"
            :class="tone.dot"
            :style="pulse ? 'animation: dkgz-pulse 2s ease-in-out infinite' : undefined"
            aria-hidden="true"
        />
        <span class="text-sm" :class="tone.text">{{ text }}</span>
    </span>
</template>
