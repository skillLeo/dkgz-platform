<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Bell, ChevronDown, Search } from 'lucide-vue-next'

/**
 * The 64px portal head from DKGZ Sachverständigen-Portal: page title, a search
 * on reference or place, the notification bell, the availability switch, and
 * the account initials. Divided by hairlines, not by spacing alone.
 */
const props = defineProps({
    title: { type: String, default: '' },
    initials: { type: String, default: '' },
    notificationCount: { type: Number, default: 0 },
    notificationsHref: { type: String, default: null },
    searchHref: { type: String, default: null },
    searchValue: { type: String, default: '' },
    available: { type: Boolean, default: null },
})

const emit = defineEmits(['update:available'])

const term = ref(props.searchValue)

watch(() => props.searchValue, (value) => { term.value = value })

const submitSearch = () => {
    router.get(props.searchHref, { suche: term.value || undefined }, {
        preserveState: true,
        replace: true,
    })
}
</script>

<template>
    <header class="hidden h-16 shrink-0 items-center justify-between gap-6 border-b border-gray-200 bg-white px-6 md:flex">
        <p class="min-w-0 truncate text-lead font-semibold text-navy-700">{{ title }}</p>

        <div class="flex shrink-0 items-center gap-5">
            <slot name="actions" />

            <form v-if="searchHref" class="relative" role="search" @submit.prevent="submitSearch">
                <Search
                    :size="16"
                    :stroke-width="1.5"
                    class="pointer-events-none absolute left-2.5 top-2 text-gray-400"
                    aria-hidden="true"
                />
                <input
                    v-model="term"
                    type="search"
                    class="h-8 w-60 rounded-sm border border-gray-300 pl-8 pr-2.5 text-sm text-gray-800 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) placeholder:text-gray-400 focus:border-navy-700 focus:outline-none"
                    placeholder="Referenz oder Ort"
                    aria-label="Nach Referenz oder Ort suchen"
                >
            </form>

            <Link
                v-if="notificationsHref"
                :href="notificationsHref"
                class="relative grid h-8 w-8 place-items-center text-gray-600 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:text-navy-700"
                aria-label="Benachrichtigungen"
            >
                <Bell :size="20" :stroke-width="1.5" aria-hidden="true" />
                <span
                    v-if="notificationCount"
                    class="absolute right-0 top-0 grid h-4 min-w-4 place-items-center rounded-full bg-navy-500 px-1 font-mono text-badge tabular-nums text-white"
                >{{ notificationCount > 99 ? '99+' : notificationCount }}</span>
            </Link>

            <template v-if="available !== null">
                <span class="h-6 w-px bg-gray-200" aria-hidden="true" />
                <button
                    type="button"
                    class="flex items-center gap-2.5"
                    role="switch"
                    :aria-checked="available"
                    @click="emit('update:available', !available)"
                >
                    <span
                        class="flex h-5 w-9 shrink-0 items-center rounded-full p-0.5 transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                        :class="available ? 'justify-end bg-navy-700' : 'justify-start bg-gray-300'"
                        aria-hidden="true"
                    ><span class="h-4 w-4 rounded-full bg-white" /></span>
                    <span class="whitespace-nowrap text-sm font-medium text-gray-800">
                        {{ available ? 'Verfügbar' : 'Nicht verfügbar' }}
                    </span>
                </button>
            </template>

            <span class="h-6 w-px bg-gray-200" aria-hidden="true" />

            <Link href="/portal/profil" class="flex items-center gap-2.5" aria-label="Profil öffnen">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-navy-100 text-meta font-semibold text-navy-700">
                    {{ initials }}
                </span>
                <ChevronDown :size="16" :stroke-width="1.5" class="text-gray-600" aria-hidden="true" />
            </Link>
        </div>
    </header>
</template>
