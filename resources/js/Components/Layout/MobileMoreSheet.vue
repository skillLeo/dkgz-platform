<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ChevronRight, X } from 'lucide-vue-next'
import BaseToggle from '../Base/BaseToggle.vue'

/**
 * The "Mehr" sheet: every remaining nav item as a 48px row with a chevron,
 * plus the availability toggle and logout. Full screen, slides up in 240ms.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    items: { type: Array, default: () => [] },
    availability: { type: Boolean, default: null },
    userName: { type: String, default: '' },
})

const emit = defineEmits(['close', 'update:availability'])

const logout = useForm({})
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50 flex flex-col bg-white md:hidden" role="dialog" aria-modal="true" aria-label="Weitere Bereiche">
            <div
                class="flex h-full flex-col pt-[env(safe-area-inset-top)]"
                style="animation: dkgz-rise 240ms cubic-bezier(0.4,0,0.2,1) both"
            >
                <div class="flex h-14 shrink-0 items-center justify-between border-b border-gray-200 px-4">
                    <span class="text-base font-semibold text-navy-700">Mehr</span>
                    <button type="button" class="grid h-11 w-11 place-items-center text-gray-600" aria-label="Schließen" @click="emit('close')">
                        <X :size="20" :stroke-width="1.5" aria-hidden="true" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <div v-if="userName" class="border-b border-gray-200 px-4 py-4">
                        <p class="text-base font-medium text-navy-700">{{ userName }}</p>
                    </div>

                    <div v-if="availability !== null" class="border-b border-gray-200 px-4 py-4">
                        <BaseToggle
                            :model-value="availability"
                            label="Verfügbarkeit"
                            description="Bei „Nicht verfügbar“ erhalten Sie keine neuen Anfragen."
                            @update:model-value="emit('update:availability', $event)"
                        />
                    </div>

                    <ul>
                        <li v-for="item in items" :key="item.href">
                            <Link
                                :href="item.href"
                                class="flex min-h-12 items-center justify-between gap-3 border-b border-gray-100 px-4 py-3"
                                @click="emit('close')"
                            >
                                <span class="flex items-center gap-3">
                                    <component :is="item.icon" v-if="item.icon" :size="20" :stroke-width="1.5" class="text-gray-600" aria-hidden="true" />
                                    <span class="text-base text-gray-800">{{ item.label }}</span>
                                </span>
                                <ChevronRight :size="16" :stroke-width="1.5" class="shrink-0 text-gray-400" aria-hidden="true" />
                            </Link>
                        </li>
                    </ul>
                </div>

                <div class="shrink-0 border-t border-gray-200 p-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
                    <button
                        type="button"
                        class="h-12 w-full rounded-sm border border-gray-300 text-base font-medium text-navy-700"
                        @click="logout.post('/abmelden')"
                    >Abmelden</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
