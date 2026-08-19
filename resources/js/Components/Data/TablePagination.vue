<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
    meta: { type: Object, required: true },
})

const from = computed(() => props.meta.from ?? 0)
const to = computed(() => props.meta.to ?? 0)
const total = computed(() => props.meta.total ?? 0)

const previous = computed(() => props.meta.links?.find((l) => l.label?.includes('Previous'))?.url ?? null)
const next = computed(() => props.meta.links?.find((l) => l.label?.includes('Next'))?.url ?? null)

// Numeric pages only, and only a window around the current one.
const pages = computed(() =>
    (props.meta.links ?? []).filter((link) => /^\d+$/.test(link.label))
)
</script>

<template>
    <div
        v-if="total > 0"
        class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 px-4 py-3"
    >
        <p class="font-mono text-xs tabular-nums text-gray-600">
            {{ from }}–{{ to }} von {{ total }}
        </p>

        <nav v-if="pages.length > 1" class="flex items-center gap-1" aria-label="Seitennummerierung">
            <component
                :is="previous ? Link : 'span'"
                :href="previous"
                preserve-scroll
                class="grid h-8 w-8 place-items-center rounded-sm border border-gray-200"
                :class="previous ? 'text-navy-700 hover:border-gray-300' : 'cursor-not-allowed text-gray-300'"
                :aria-label="'Vorherige Seite'"
            >
                <ChevronLeft :size="16" :stroke-width="1.5" aria-hidden="true" />
            </component>

            <component
                :is="page.url ? Link : 'span'"
                v-for="page in pages"
                :key="page.label"
                :href="page.url"
                preserve-scroll
                class="grid h-8 min-w-8 place-items-center rounded-sm border px-2 font-mono text-xs tabular-nums"
                :class="page.active
                    ? 'border-navy-700 bg-navy-700 text-white'
                    : 'border-gray-200 text-gray-800 hover:border-gray-300'"
                :aria-current="page.active ? 'page' : undefined"
            >{{ page.label }}</component>

            <component
                :is="next ? Link : 'span'"
                :href="next"
                preserve-scroll
                class="grid h-8 w-8 place-items-center rounded-sm border border-gray-200"
                :class="next ? 'text-navy-700 hover:border-gray-300' : 'cursor-not-allowed text-gray-300'"
                :aria-label="'Nächste Seite'"
            >
                <ChevronRight :size="16" :stroke-width="1.5" aria-hidden="true" />
            </component>
        </nav>
    </div>
</template>
