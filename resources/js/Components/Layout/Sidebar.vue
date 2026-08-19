<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import DkgzMark from './DkgzMark.vue'

/**
 * The desktop shell above 768px. Active rows carry the inset navy marker from
 * Foundations (`inset 2px 0 0`), which is the one inset shadow in the system.
 */
defineProps({
    sections: { type: Array, required: true },
})

const page = usePage()

const isActive = (item) => {
    const current = page.url.split('?')[0]

    return item.exact ? current === item.href : current.startsWith(item.href)
}
</script>

<template>
    <aside class="hidden w-64 shrink-0 flex-col border-r border-gray-200 bg-white md:flex">
        <div class="flex h-18 shrink-0 items-center border-b border-gray-200 px-5">
            <DkgzMark size="sm" :with-subtitle="false" />
        </div>

        <nav class="min-h-0 flex-1 overflow-y-auto py-4" aria-label="Bereichsnavigation">
            <div v-for="section in sections" :key="section.label ?? 'haupt'" class="pb-5">
                <p v-if="section.label" class="px-5 pb-2 text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-400">
                    {{ section.label }}
                </p>
                <ul>
                    <li v-for="item in section.items" :key="item.href">
                        <Link
                            :href="item.href"
                            class="flex items-center justify-between gap-3 px-5 py-2.5 text-sm transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                            :class="isActive(item)
                                ? 'bg-navy-100 font-medium text-navy-700 shadow-[inset_2px_0_0_var(--color-navy-700)]'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-navy-700'"
                            :aria-current="isActive(item) ? 'page' : undefined"
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                <component :is="item.icon" v-if="item.icon" :size="18" :stroke-width="1.5" class="shrink-0" aria-hidden="true" />
                                <span class="truncate">{{ item.label }}</span>
                            </span>
                            <span
                                v-if="item.badge"
                                class="shrink-0 rounded-xs bg-navy-700 px-1.5 font-mono text-[11px] tabular-nums text-white"
                            >{{ item.badge }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="shrink-0 border-t border-gray-200 p-4">
            <slot name="footer" />
        </div>
    </aside>
</template>
