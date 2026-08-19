<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Search, SlidersHorizontal, X } from 'lucide-vue-next'
import BaseButton from '../Base/BaseButton.vue'
import { useBreakpoint } from '../../Composables/useBreakpoint.js'

/**
 * Inline on desktop; below 768px the same controls open in a bottom sheet with
 * an explicit "Anwenden", per BUILD_SPEC Part D.
 */
const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
    searchPlaceholder: { type: String, default: 'Suchen' },
    only: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const { isMobile } = useBreakpoint()

const sheetOpen = ref(false)
const draft = ref({ ...props.modelValue })

watch(() => props.modelValue, (value) => { draft.value = { ...value } })

let debounce = null

const push = (filters) => {
    emit('update:modelValue', filters)

    router.get(window.location.pathname, filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: props.only,
    })
}

const onSearch = (value) => {
    draft.value = { ...draft.value, suche: value, page: undefined }

    if (debounce) clearTimeout(debounce)
    debounce = setTimeout(() => push(draft.value), 300)
}

const apply = () => {
    sheetOpen.value = false
    push({ ...draft.value, page: undefined })
}

const reset = () => {
    draft.value = {}
    sheetOpen.value = false
    push({})
}

const activeCount = () =>
    Object.entries(props.modelValue).filter(([key, value]) => key !== 'suche' && key !== 'page' && value).length
</script>

<template>
    <div class="border-b border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <div class="relative min-w-0 flex-1">
                <Search :size="16" :stroke-width="1.5" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                <input
                    :value="modelValue.suche ?? ''"
                    type="search"
                    :placeholder="searchPlaceholder"
                    :aria-label="searchPlaceholder"
                    class="h-(--spacing-control-sm) w-full rounded-sm border border-gray-300 bg-white pl-10 pr-3.5 text-base text-gray-800 outline-none transition-colors duration-(--duration-focus) ease-(--ease-dkgz) placeholder:text-gray-400 hover:border-gray-400 focus:border-navy-700 focus:outline-2 focus:outline-navy-500 focus:outline-offset-2"
                    @input="onSearch($event.target.value)"
                >
            </div>

            <button
                v-if="isMobile"
                type="button"
                class="flex h-(--spacing-control-sm) shrink-0 items-center gap-2 rounded-sm border border-gray-300 px-3.5 text-sm font-medium text-navy-700"
                @click="sheetOpen = true"
            >
                <SlidersHorizontal :size="16" :stroke-width="1.5" aria-hidden="true" />
                Filter
                <span v-if="activeCount()" class="font-mono tabular-nums">{{ activeCount() }}</span>
            </button>

            <div v-else class="flex shrink-0 items-center gap-3">
                <slot name="filters" :draft="draft" :apply="push" />
                <button
                    v-if="activeCount()"
                    type="button"
                    class="text-sm font-medium text-navy-700 hover:text-navy-500"
                    @click="reset"
                >Zurücksetzen</button>
            </div>
        </div>
    </div>

    <!-- Mobile filter sheet -->
    <Teleport to="body">
        <div v-if="sheetOpen && isMobile" class="fixed inset-0 z-50 flex flex-col justify-end">
            <button
                type="button"
                class="absolute inset-0 bg-navy-900/40"
                aria-label="Filter schließen"
                @click="sheetOpen = false"
            />
            <div
                class="relative max-h-[80vh] overflow-y-auto rounded-t-card bg-white pb-[env(safe-area-inset-bottom)]"
                style="animation: dkgz-rise 240ms cubic-bezier(0.4,0,0.2,1) both"
                role="dialog"
                aria-modal="true"
                aria-label="Filter"
            >
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4">
                    <span class="text-base font-semibold text-navy-700">Filter</span>
                    <button type="button" class="text-gray-400" aria-label="Schließen" @click="sheetOpen = false">
                        <X :size="20" :stroke-width="1.5" aria-hidden="true" />
                    </button>
                </div>

                <div class="flex flex-col gap-5 p-4">
                    <slot name="filters" :draft="draft" :apply="() => {}" />
                </div>

                <div class="sticky bottom-0 flex gap-3 border-t border-gray-200 bg-white p-4">
                    <BaseButton variant="secondary" size="cta" block @click="reset">Zurücksetzen</BaseButton>
                    <BaseButton size="cta" block @click="apply">Anwenden</BaseButton>
                </div>
            </div>
        </div>
    </Teleport>
</template>
