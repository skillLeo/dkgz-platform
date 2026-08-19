<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Menu, X } from 'lucide-vue-next'
import DkgzMark from '../Components/Layout/DkgzMark.vue'
import FlagRule from '../Components/Layout/FlagRule.vue'
import BaseButton from '../Components/Base/BaseButton.vue'
import FlashMessage from '../Components/Feedback/FlashMessage.vue'

/**
 * The public shell. On mobile the header collapses to logo plus a hamburger
 * that opens a full-screen navy-900 panel, and a sticky "Anfrage starten" bar
 * appears once the visitor has scrolled past the hero.
 */
defineProps({
    stickyCta: { type: Boolean, default: true },
})

const page = usePage()
const menuOpen = ref(false)
const scrolled = ref(false)

const app = computed(() => page.props.app ?? {})

const nav = [
    { href: '/leistungen', label: 'Leistungen' },
    { href: '/ablauf', label: 'Ablauf' },
    { href: '/ueber-uns', label: 'Über uns' },
    { href: '/fuer-sachverstaendige', label: 'Für Sachverständige' },
    { href: '/kontakt', label: 'Kontakt' },
]

const onScroll = () => { scrolled.value = window.scrollY > 480 }

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white">
        <!-- Meldeband -->
        <div class="hidden h-7 items-center bg-navy-900 md:flex">
            <div class="mx-auto flex w-full max-w-(--container-shell) items-center justify-between gap-6 px-6">
                <span class="text-xs text-gray-300">Bundesweites Netz geprüfter Kfz-Sachverständiger</span>
                <span class="flex items-center gap-5">
                    <a v-if="app.phone" :href="`tel:${app.phone.replace(/\s/g, '')}`" class="font-mono text-xs tabular-nums text-white">{{ app.phone }}</a>
                    <span v-if="app.office_hours" class="text-xs text-gray-300">{{ app.office_hours }}</span>
                </span>
            </div>
        </div>

        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white">
            <div class="mx-auto flex h-18 w-full max-w-(--container-shell) items-center justify-between gap-6 px-4 md:px-6">
                <Link href="/" aria-label="Zur Startseite"><DkgzMark size="md" /></Link>

                <nav class="hidden items-center gap-7 lg:flex" aria-label="Hauptnavigation">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="text-sm text-gray-800 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:text-navy-700"
                    >{{ item.label }}</Link>
                </nav>

                <div class="flex items-center gap-3">
                    <BaseButton href="/anfrage" size="compact" class="hidden sm:inline-flex">Gutachter finden</BaseButton>
                    <button
                        type="button"
                        class="grid h-11 w-11 place-items-center text-navy-700 lg:hidden"
                        aria-label="Menü öffnen"
                        @click="menuOpen = true"
                    >
                        <Menu :size="24" :stroke-width="1.5" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </header>

        <!-- Full-screen navy panel on mobile -->
        <Teleport to="body">
            <div v-if="menuOpen" class="fixed inset-0 z-50 flex flex-col bg-navy-900 lg:hidden" role="dialog" aria-modal="true" aria-label="Menü">
                <div class="flex h-18 shrink-0 items-center justify-between px-4 pt-[env(safe-area-inset-top)]">
                    <DkgzMark size="md" inverted />
                    <button type="button" class="grid h-11 w-11 place-items-center text-white" aria-label="Menü schließen" @click="menuOpen = false">
                        <X :size="24" :stroke-width="1.5" aria-hidden="true" />
                    </button>
                </div>
                <nav class="flex min-h-0 flex-1 flex-col overflow-y-auto px-4 pt-6" aria-label="Hauptnavigation">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="border-b border-white/10 py-4 text-h4 text-white"
                        @click="menuOpen = false"
                    >{{ item.label }}</Link>
                    <Link href="/anmelden" class="border-b border-white/10 py-4 text-h4 text-white/72" @click="menuOpen = false">Partnerportal</Link>
                </nav>
                <div class="shrink-0 p-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
                    <BaseButton href="/anfrage" variant="inverted" size="cta" block @click="menuOpen = false">Gutachter finden</BaseButton>
                </div>
            </div>
        </Teleport>

        <main class="flex-1">
            <div v-if="page.props.flash?.success || page.props.flash?.error" class="mx-auto w-full max-w-(--container-shell) px-4 pt-6 md:px-6">
                <FlashMessage />
            </div>
            <slot />
        </main>

        <!-- Sticky CTA after the hero, mobile only -->
        <div
            v-if="stickyCta && scrolled"
            class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white p-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:hidden"
        >
            <BaseButton href="/anfrage" size="cta" block>Anfrage starten</BaseButton>
        </div>

        <footer class="mt-20 bg-navy-900">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-14 md:px-6">
                <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <DkgzMark size="sm" inverted />
                        <p class="measure-lead pt-4 text-sm leading-normal text-white/62">
                            DKGZ vermittelt Anfragen an selbstständige Kfz-Sachverständige. Die Begutachtung erbringt
                            der jeweils vermittelte Sachverständige.
                        </p>
                        <FlagRule :width="40" class="mt-5" />
                    </div>

                    <div>
                        <p class="text-eyebrow font-semibold uppercase tracking-[0.09em] text-white/45">Für Kunden</p>
                        <ul class="flex flex-col gap-2.5 pt-4">
                            <li><Link href="/anfrage" class="text-sm text-white/72 hover:text-white">Gutachter finden</Link></li>
                            <li><Link href="/leistungen" class="text-sm text-white/72 hover:text-white">Leistungen</Link></li>
                            <li><Link href="/ablauf" class="text-sm text-white/72 hover:text-white">Ablauf</Link></li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-eyebrow font-semibold uppercase tracking-[0.09em] text-white/45">Unternehmen</p>
                        <ul class="flex flex-col gap-2.5 pt-4">
                            <li><Link href="/ueber-uns" class="text-sm text-white/72 hover:text-white">Über DKGZ</Link></li>
                            <li><Link href="/kontakt" class="text-sm text-white/72 hover:text-white">Kontakt</Link></li>
                            <li><Link href="/fuer-sachverstaendige" class="text-sm text-white/72 hover:text-white">Für Sachverständige</Link></li>
                            <li><Link href="/anmelden" class="text-sm text-white/72 hover:text-white">Partnerportal</Link></li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-eyebrow font-semibold uppercase tracking-[0.09em] text-white/45">Rechtliches</p>
                        <ul class="flex flex-col gap-2.5 pt-4">
                            <li><Link href="/impressum" class="text-sm text-white/72 hover:text-white">Impressum</Link></li>
                            <li><Link href="/datenschutz" class="text-sm text-white/72 hover:text-white">Datenschutzerklärung</Link></li>
                            <li><Link href="/agb" class="text-sm text-white/72 hover:text-white">AGB</Link></li>
                            <li><Link href="/widerruf" class="text-sm text-white/72 hover:text-white">Widerrufsbelehrung</Link></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-6">
                    <p class="text-xs text-white/40">Deutsche KFZ-Gutachterzentrale</p>
                    <p v-if="app.phone" class="font-mono text-xs tabular-nums text-white/40">{{ app.phone }}</p>
                </div>
            </div>
        </footer>
    </div>
</template>
