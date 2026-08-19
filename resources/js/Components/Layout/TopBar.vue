<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Bell, LogOut } from 'lucide-vue-next'

defineProps({
    title: { type: String, default: '' },
    userName: { type: String, default: '' },
    notificationCount: { type: Number, default: 0 },
    notificationsHref: { type: String, default: null },
})

const logout = useForm({})
</script>

<template>
    <header class="hidden h-18 shrink-0 items-center justify-between gap-6 border-b border-gray-200 bg-white px-6 md:flex">
        <p class="min-w-0 truncate text-h4 font-semibold text-navy-700">{{ title }}</p>

        <div class="flex shrink-0 items-center gap-4">
            <slot name="actions" />

            <Link
                v-if="notificationsHref"
                :href="notificationsHref"
                class="relative grid h-10 w-10 place-items-center rounded-sm text-gray-600 hover:bg-gray-50 hover:text-navy-700"
                aria-label="Benachrichtigungen"
            >
                <Bell :size="20" :stroke-width="1.5" aria-hidden="true" />
                <span
                    v-if="notificationCount"
                    class="absolute right-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-navy-700 px-1 font-mono text-[10px] tabular-nums text-white"
                >{{ notificationCount > 99 ? '99+' : notificationCount }}</span>
            </Link>

            <span class="h-6 w-px bg-gray-200" aria-hidden="true" />

            <span class="text-sm text-gray-800">{{ userName }}</span>

            <button
                type="button"
                class="grid h-10 w-10 place-items-center rounded-sm text-gray-600 hover:bg-gray-50 hover:text-navy-700"
                aria-label="Abmelden"
                @click="logout.post('/abmelden')"
            >
                <LogOut :size="18" :stroke-width="1.5" aria-hidden="true" />
            </button>
        </div>
    </header>
</template>
