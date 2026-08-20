<script setup>
import { watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { X } from 'lucide-vue-next'

/**
 * The mobile navigation for the portal and admin: a full-height panel opened
 * from the header, listing every destination as an ordinary row.
 *
 * Replaces the fixed bottom tab bar. A tab bar suits four or five destinations;
 * both of these shells carry a dozen or more, which meant most of them lived
 * behind a "Mehr" sheet and were effectively hidden.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    groups: { type: Array, required: true },
})

const emit = defineEmits(['close'])

const page = usePage()

const isActive = (item) => {
    const current = page.url.split('?')[0]

    return item.exact ? current === item.href : current.startsWith(item.href)
}

// A tap that navigates should also close the panel behind it.
watch(() => page.url, () => emit('close'))

watch(() => props.open, (open) => {
    if (typeof document === 'undefined') return

    document.body.style.overflow = open ? 'hidden' : ''
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-(--duration-panel) ease-(--ease-dkgz)"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-(--duration-panel) ease-(--ease-dkgz)"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-50 md:hidden" role="dialog" aria-modal="true" aria-label="Navigation">
                <button
                    type="button"
                    class="absolute inset-0 h-full w-full bg-navy-900/32"
                    aria-label="Menü schließen"
                    @click="emit('close')"
                />

                <nav class="absolute inset-y-0 right-0 flex w-full max-w-90 flex-col bg-navy-900">
                    <div class="flex h-14 shrink-0 items-center justify-between border-b border-white/10 px-4">
                        <span class="text-eyebrow font-semibold uppercase text-white/60">Navigation</span>
                        <button
                            type="button"
                            class="-mr-2 grid h-11 w-11 place-items-center text-white/72 hover:text-white"
                            aria-label="Menü schließen"
                            @click="emit('close')"
                        >
                            <X :size="20" :stroke-width="1.5" aria-hidden="true" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto py-2">
                        <template v-for="(group, index) in groups" :key="index">
                            <span
                                v-if="index > 0 && !group.label"
                                class="my-2 mx-4 block h-px bg-white/10"
                                aria-hidden="true"
                            />
                            <p
                                v-if="group.label"
                                class="px-4 pb-1 pt-4 text-eyebrow font-semibold uppercase text-white/45"
                            >{{ group.label }}</p>
                            <ul>
                                <li v-for="item in group.items" :key="item.href">
                                    <Link
                                        :href="item.href"
                                        class="flex min-h-12 items-center gap-3 border-l-2 px-4 text-base"
                                        :class="isActive(item)
                                            ? 'border-l-accent bg-navy-700 text-white'
                                            : 'border-l-transparent text-white/72'"
                                        :aria-current="isActive(item) ? 'page' : undefined"
                                        @click="emit('close')"
                                    >
                                        <component :is="item.icon" :size="20" :stroke-width="1.5" class="shrink-0" aria-hidden="true" />
                                        <span class="truncate">{{ item.label }}</span>
                                        <span
                                            v-if="item.badge"
                                            class="ml-auto shrink-0 rounded-sm bg-navy-500 px-1.5 py-px font-mono text-meta tabular-nums text-white"
                                        >{{ item.badge }}</span>
                                    </Link>
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div class="shrink-0 border-t border-white/10 p-4">
                        <slot name="footer" />
                    </div>
                </nav>
            </div>
        </Transition>
    </Teleport>
</template>
