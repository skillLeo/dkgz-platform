<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    Bell, Euro, FileText, Home, Inbox, LayoutGrid, MapPin, Settings, ThumbsDown, User, Wrench,
} from 'lucide-vue-next'
import Sidebar from '../Components/Layout/Sidebar.vue'
import TopBar from '../Components/Layout/TopBar.vue'
import MobileBottomNav from '../Components/Layout/MobileBottomNav.vue'
import MobileTopBar from '../Components/Layout/MobileTopBar.vue'
import MobileMoreSheet from '../Components/Layout/MobileMoreSheet.vue'
import BaseToggle from '../Components/Base/BaseToggle.vue'
import FlashMessage from '../Components/Feedback/FlashMessage.vue'
import ConfirmDialog from '../Components/Feedback/ConfirmDialog.vue'
import ToastStack from '../Components/Feedback/ToastStack.vue'
import { usePolling } from '../Composables/usePolling.js'

/**
 * Below 768px this becomes the native app shell from DKGZ Mobil: a fixed 56px
 * top bar, a fixed 64px tab bar, and the "Mehr" sheet for everything else.
 * Above it, the sidebar shell.
 */
const props = defineProps({
    title: { type: String, required: true },
    backHref: { type: String, default: null },
    openRequests: { type: Number, default: 0 },
})

const page = usePage()
const moreOpen = ref(false)

const assessor = computed(() => page.props.auth?.user?.assessor ?? null)
const available = computed({
    get: () => assessor.value?.is_available ?? false,
    set: (value) => router.post('/portal/verfuegbarkeit', { is_available: value }, { preserveScroll: true }),
})

const { count: notificationCount } = usePolling('/api/notifications/poll')

const tabs = computed(() => [
    { href: '/portal', label: 'Start', icon: Home, exact: true },
    { href: '/portal/anfragen', label: 'Anfragen', icon: Inbox, badge: props.openRequests },
    { href: '/portal/auftraege', label: 'Aufträge', icon: FileText },
    { href: '/portal/provisionen', label: 'Provisionen', icon: Euro },
    { href: '#mehr', label: 'Mehr', icon: LayoutGrid },
])

const moreItems = [
    { href: '/portal/abgelehnt', label: 'Abgelehnte Anfragen', icon: ThumbsDown },
    { href: '/portal/einsatzgebiet', label: 'Einsatzgebiet', icon: MapPin },
    { href: '/portal/leistungen', label: 'Leistungen', icon: Wrench },
    { href: '/portal/benachrichtigungen', label: 'Benachrichtigungen', icon: Bell },
    { href: '/portal/profil', label: 'Profil', icon: User },
    { href: '/portal/einstellungen', label: 'Einstellungen', icon: Settings },
]

const sections = computed(() => [
    {
        items: [
            { href: '/portal', label: 'Übersicht', icon: Home, exact: true },
            { href: '/portal/anfragen', label: 'Neue Anfragen', icon: Inbox, badge: props.openRequests || null },
            { href: '/portal/auftraege', label: 'Aufträge', icon: FileText },
            { href: '/portal/abgelehnt', label: 'Abgelehnt', icon: ThumbsDown },
            { href: '/portal/provisionen', label: 'Provisionen', icon: Euro },
        ],
    },
    {
        label: 'Mein Profil',
        items: [
            { href: '/portal/einsatzgebiet', label: 'Einsatzgebiet', icon: MapPin },
            { href: '/portal/leistungen', label: 'Leistungen', icon: Wrench },
            { href: '/portal/profil', label: 'Profil', icon: User },
            { href: '/portal/einstellungen', label: 'Einstellungen', icon: Settings },
        ],
    },
])

const onTab = (event) => {
    if (event.target.closest('a')?.getAttribute('href') === '#mehr') {
        event.preventDefault()
        moreOpen.value = true
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <MobileTopBar
            :title="title"
            :back-href="backHref"
            :notification-count="notificationCount"
            notifications-href="/portal/benachrichtigungen"
        />

        <div class="flex min-h-screen">
            <Sidebar :sections="sections">
                <template #footer>
                    <BaseToggle
                        v-model="available"
                        label="Verfügbarkeit"
                        on-label="Verfügbar"
                        off-label="Nicht verfügbar"
                    />
                </template>
            </Sidebar>

            <div class="flex min-w-0 flex-1 flex-col">
                <TopBar
                    :title="title"
                    :user-name="page.props.auth?.user?.name ?? ''"
                    :notification-count="notificationCount"
                    notifications-href="/portal/benachrichtigungen"
                />

                <main
                    class="min-w-0 flex-1 px-4 pb-[calc(4rem+env(safe-area-inset-bottom)+1rem)] pt-[calc(3.5rem+env(safe-area-inset-top)+1rem)] md:px-6 md:py-6"
                >
                    <FlashMessage class="mb-5" />
                    <slot />
                </main>
            </div>
        </div>

        <div @click="onTab">
            <MobileBottomNav :tabs="tabs" />
        </div>

        <MobileMoreSheet
            :open="moreOpen"
            :items="moreItems"
            :availability="available"
            :user-name="page.props.auth?.user?.name ?? ''"
            @close="moreOpen = false"
            @update:availability="available = $event"
        />

        <ConfirmDialog />
        <ToastStack />
    </div>
</template>
