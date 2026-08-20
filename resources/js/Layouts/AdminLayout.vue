<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
    Bell, Building2, Euro, FileText, FileType, Home, Inbox, LayoutGrid, Mail, Settings,
    ShieldCheck, Sliders, User, UserPlus, Users, Wrench,
} from 'lucide-vue-next'
import Sidebar from '../Components/Layout/Sidebar.vue'
import TopBar from '../Components/Layout/TopBar.vue'
import MobileBottomNav from '../Components/Layout/MobileBottomNav.vue'
import MobileTopBar from '../Components/Layout/MobileTopBar.vue'
import MobileMoreSheet from '../Components/Layout/MobileMoreSheet.vue'
import FlashMessage from '../Components/Feedback/FlashMessage.vue'
import ConfirmDialog from '../Components/Feedback/ConfirmDialog.vue'
import ToastStack from '../Components/Feedback/ToastStack.vue'
import { usePermissions } from '../Composables/usePermissions.js'

const props = defineProps({
    title: { type: String, required: true },
    backHref: { type: String, default: null },
    pendingAssessors: { type: Number, default: 0 },
})

const page = usePage()
const { can } = usePermissions()
const moreOpen = ref(false)

const initials = computed(() => (page.props.auth?.user?.name ?? '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join(''))

const tabs = computed(() => [
    { href: '/admin', label: 'Start', icon: Home, exact: true },
    { href: '/admin/anfragen', label: 'Anfragen', icon: Inbox },
    { href: '/admin/auftraege', label: 'Aufträge', icon: FileText },
    { href: '/admin/sachverstaendige', label: 'Partner', icon: Building2, badge: props.pendingAssessors },
    { href: '#mehr', label: 'Mehr', icon: LayoutGrid },
])

// Every entry is filtered by permission, so a support user never sees a link
// that would 403. The server enforces the same rules independently.
const moreItems = computed(() => [
    can('commissions.view') ? { href: '/admin/provisionen', label: 'Provisionen', icon: Euro } : null,
    can('invitations.view') ? { href: '/admin/einladungen', label: 'Einladungen', icon: UserPlus } : null,
    can('servicetypes.manage') ? { href: '/admin/leistungsarten', label: 'Leistungsarten', icon: Wrench } : null,
    can('content.view') ? { href: '/admin/inhalte', label: 'Inhalte', icon: FileType } : null,
    can('pages.manage') ? { href: '/admin/seiten', label: 'Seiten', icon: FileText } : null,
    can('faqs.manage') ? { href: '/admin/faq', label: 'FAQ', icon: Bell } : null,
    can('emails.view') ? { href: '/admin/email-vorlagen', label: 'E-Mail-Vorlagen', icon: Mail } : null,
    can('branding.edit') ? { href: '/admin/branding', label: 'Erscheinungsbild', icon: Sliders } : null,
    can('integrations.manage') ? { href: '/admin/integrationen', label: 'Integrationen', icon: Settings } : null,
    can('settings.view') ? { href: '/admin/einstellungen', label: 'Einstellungen', icon: Settings } : null,
    can('users.view') ? { href: '/admin/benutzer', label: 'Benutzer', icon: Users } : null,
    can('roles.view') ? { href: '/admin/rollen', label: 'Rollen', icon: ShieldCheck } : null,
    can('logs.view') ? { href: '/admin/protokoll', label: 'Protokoll', icon: FileType } : null,
    can('settings.view') ? { href: '/admin/system', label: 'System', icon: Sliders } : null,
    { href: '/admin/profil', label: 'Mein Konto', icon: User },
].filter(Boolean))

const sections = computed(() => [
    {
        items: [
            { href: '/admin', label: 'Übersicht', icon: Home, exact: true },
            can('requests.view') ? { href: '/admin/anfragen', label: 'Anfragen', icon: Inbox } : null,
            can('assignments.view') ? { href: '/admin/auftraege', label: 'Aufträge', icon: FileText } : null,
            can('assessors.view') ? { href: '/admin/sachverstaendige', label: 'Sachverständige', icon: Building2, badge: props.pendingAssessors || null } : null,
            can('invitations.view') ? { href: '/admin/einladungen', label: 'Einladungen', icon: UserPlus } : null,
            can('commissions.view') ? { href: '/admin/provisionen', label: 'Provisionen', icon: Euro } : null,
        ].filter(Boolean),
    },
    {
        label: 'Inhalte',
        items: [
            can('content.view') ? { href: '/admin/inhalte', label: 'Seiteninhalte', icon: FileType } : null,
            can('pages.manage') ? { href: '/admin/seiten', label: 'Rechtliche Seiten', icon: FileText } : null,
            can('faqs.manage') ? { href: '/admin/faq', label: 'FAQ', icon: Bell } : null,
            can('emails.view') ? { href: '/admin/email-vorlagen', label: 'E-Mail-Vorlagen', icon: Mail } : null,
        ].filter(Boolean),
    },
    {
        label: 'Verwaltung',
        items: [
            can('servicetypes.manage') ? { href: '/admin/leistungsarten', label: 'Leistungsarten', icon: Wrench } : null,
            can('branding.edit') ? { href: '/admin/branding', label: 'Erscheinungsbild', icon: Sliders } : null,
            can('integrations.manage') ? { href: '/admin/integrationen', label: 'Integrationen', icon: Settings } : null,
            can('settings.view') ? { href: '/admin/einstellungen', label: 'Einstellungen', icon: Settings } : null,
            can('users.view') ? { href: '/admin/benutzer', label: 'Benutzer', icon: Users } : null,
            can('roles.view') ? { href: '/admin/rollen', label: 'Rollen', icon: ShieldCheck } : null,
            can('logs.view') ? { href: '/admin/protokoll', label: 'Protokoll', icon: FileType } : null,
            can('settings.view') ? { href: '/admin/system', label: 'System', icon: Sliders } : null,
            { href: '/admin/profil', label: 'Mein Konto', icon: User },
        ].filter(Boolean),
    },
].filter((section) => section.items.length))

const onTab = (event) => {
    if (event.target.closest('a')?.getAttribute('href') === '#mehr') {
        event.preventDefault()
        moreOpen.value = true
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <MobileTopBar :title="title" :back-href="backHref" show-search />

        <div class="flex min-h-screen">
            <Sidebar
                :groups="sections"
                subtitle="Administration"
                :ident-value="page.props.auth?.user?.name ?? null"
                ident-label="Angemeldet als"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <TopBar :title="title" :initials="initials" />

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
            :user-name="page.props.auth?.user?.name ?? ''"
            @close="moreOpen = false"
        />

        <ConfirmDialog />
        <ToastStack />
    </div>
</template>
