<script setup>
import { computed } from 'vue'

/**
 * The "Anfragen pro Woche" chart from the admin dashboard: plain navy columns
 * on a shared baseline, the value above each and the ISO week below. No grid,
 * no axis line, no animation — the design treats this as a reading of volume,
 * not a chart to explore.
 */
const props = defineProps({
    weeks: { type: Array, default: () => [] },
})

const peak = computed(() => Math.max(1, ...props.weeks.map((week) => week.total)))

const heightFor = (total) => `${Math.max(2, Math.round((total / peak.value) * 100))}%`
</script>

<template>
    <div>
        <div class="flex h-40 items-end gap-1.5 sm:gap-3">
            <div v-for="week in weeks" :key="week.week" class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                <span class="font-mono text-meta tabular-nums text-gray-600">{{ week.total }}</span>
                <span
                    class="w-full rounded-xs bg-navy-700"
                    :style="{ height: heightFor(week.total) }"
                    aria-hidden="true"
                />
            </div>
        </div>

        <div class="flex gap-1.5 border-t border-gray-200 pt-2 sm:gap-3">
            <span
                v-for="week in weeks"
                :key="`kw-${week.week}`"
                class="min-w-0 flex-1 text-center font-mono text-meta tabular-nums text-gray-400"
            >{{ week.week }}</span>
        </div>

        <p class="sr-only">
            Anfragen pro Kalenderwoche:
            <template v-for="week in weeks" :key="`sr-${week.week}`">{{ week.label }}: {{ week.total }}. </template>
        </p>
    </div>
</template>
