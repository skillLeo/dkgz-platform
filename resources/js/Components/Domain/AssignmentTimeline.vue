<script setup>
import { Check } from 'lucide-vue-next'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

/**
 * The vertical status timeline, fed by assignment_status_events. Append-only,
 * so every entry is settled — there is no in-progress node here and therefore
 * no pulse.
 */
defineProps({
    events: { type: Array, default: () => [] },
})

const { dateTime } = useGermanFormat()

const actorLabel = (type) => ({
    assessor: 'Sachverständiger',
    admin: 'Administration',
    system: 'System',
}[type] ?? type)
</script>

<template>
    <ol v-if="events.length" class="border-l border-gray-200">
        <li v-for="(event, index) in events" :key="index" class="relative py-3 pl-6">
            <span class="absolute -left-[5px] top-[18px] grid h-2.5 w-2.5 place-items-center rounded-full bg-navy-700" aria-hidden="true" />
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <span class="text-base text-gray-800">{{ event.label ?? event.to_status }}</span>
                <span class="font-mono text-xs tabular-nums text-gray-400">{{ dateTime(event.created_at) }}</span>
            </div>
            <p class="pt-0.5 text-xs text-gray-600">
                {{ actorLabel(event.actor_type) }}<template v-if="event.actor"> · {{ event.actor }}</template>
            </p>
            <p v-if="event.note" class="measure pt-1 text-sm leading-normal text-gray-800">{{ event.note }}</p>
        </li>
    </ol>
    <p v-else class="text-sm text-gray-400">Noch keine Ereignisse.</p>
</template>
