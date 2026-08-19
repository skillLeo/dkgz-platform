<script setup>
import { Link, router } from '@inertiajs/vue3'
import { Bell, ChevronLeft, Search } from 'lucide-vue-next'

/**
 * DKGZ Mobil: fixed 56px app bar plus the safe-area inset. On a detail screen a
 * back chevron takes the title's left position; there is never both a hamburger
 * and a back arrow.
 */
defineProps({
    title: { type: String, required: true },
    backHref: { type: String, default: null },
    showSearch: { type: Boolean, default: false },
    notificationCount: { type: Number, default: 0 },
    notificationsHref: { type: String, default: null },
})

const goBack = (href) => {
    if (href) router.visit(href)
    else window.history.back()
}
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-40 border-b border-gray-200 bg-white pt-[env(safe-area-inset-top)] md:hidden"
    >
        <div class="flex h-14 items-center gap-2 px-2">
            <button
                v-if="backHref !== null"
                type="button"
                class="grid h-11 w-11 shrink-0 place-items-center text-navy-700"
                aria-label="Zurück"
                @click="goBack(backHref)"
            >
                <ChevronLeft :size="24" :stroke-width="1.5" aria-hidden="true" />
            </button>

            <h1 class="min-w-0 flex-1 truncate text-base font-semibold text-navy-700" :class="backHref === null ? 'pl-2' : ''">
                {{ title }}
            </h1>

            <button
                v-if="showSearch"
                type="button"
                class="grid h-11 w-11 shrink-0 place-items-center text-gray-600"
                aria-label="Suchen"
            >
                <Search :size="20" :stroke-width="1.5" aria-hidden="true" />
            </button>

            <Link
                v-if="notificationsHref"
                :href="notificationsHref"
                class="relative grid h-11 w-11 shrink-0 place-items-center text-gray-600"
                aria-label="Benachrichtigungen"
            >
                <Bell :size="20" :stroke-width="1.5" aria-hidden="true" />
                <span
                    v-if="notificationCount"
                    class="absolute right-1.5 top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-navy-700 px-1 font-mono text-[10px] tabular-nums text-white"
                >{{ notificationCount > 99 ? '99+' : notificationCount }}</span>
            </Link>
        </div>
    </header>
</template>
