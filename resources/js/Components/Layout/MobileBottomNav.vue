<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

/**
 * DKGZ Mobil: fixed bottom tab bar, 64px plus the safe-area inset, white with a
 * 1px gray-200 top border. The active tab is navy-700 and carries a 2px navy
 * indicator on that tab alone. 11px labels — the one place in the product where
 * type goes below 12px, per DECISIONS.md D-03.
 */
const props = defineProps({
    tabs: { type: Array, required: true },
})

const page = usePage()

const isActive = (tab) => {
    const current = page.url.split('?')[0]

    return tab.exact ? current === tab.href : current.startsWith(tab.href)
}
</script>

<template>
    <nav
        class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] md:hidden"
        aria-label="Hauptnavigation"
    >
        <ul class="flex h-16">
            <li v-for="tab in tabs" :key="tab.href" class="relative flex-1">
                <span
                    v-if="isActive(tab)"
                    class="absolute inset-x-0 top-0 h-0.5 bg-navy-700"
                    aria-hidden="true"
                />
                <Link
                    :href="tab.href"
                    class="flex h-full min-h-11 flex-col items-center justify-center gap-1"
                    :class="isActive(tab) ? 'text-navy-700' : 'text-gray-400'"
                    :aria-current="isActive(tab) ? 'page' : undefined"
                >
                    <span class="relative">
                        <component :is="tab.icon" :size="24" :stroke-width="1.5" aria-hidden="true" />
                        <span
                            v-if="tab.badge"
                            class="absolute -right-2 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-navy-700 px-1 font-mono text-badge tabular-nums text-white"
                        >{{ tab.badge > 99 ? '99+' : tab.badge }}</span>
                    </span>
                    <span class="text-tab">{{ tab.label }}</span>
                </Link>
            </li>
        </ul>
    </nav>
</template>
