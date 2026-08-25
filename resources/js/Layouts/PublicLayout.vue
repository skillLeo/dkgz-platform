<script setup>
import CookieConsent from '../Components/Feedback/CookieConsent.vue'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Menu, X } from 'lucide-vue-next'
import BrandLogo from '../Components/Layout/BrandLogo.vue'
import BrandSeal from '../Components/Layout/BrandSeal.vue'
import SealMark from '../Components/Layout/SealMark.vue'
import FlagRule from '../Components/Layout/FlagRule.vue'
import BaseButton from '../Components/Base/BaseButton.vue'
import FlashMessage from '../Components/Feedback/FlashMessage.vue'

/**
 * The public shell, built from "DKGZ Homepage.dc.html".
 *
 * A 28px navy meldeband, then a sticky 72px header whose nav items are full
 * height with a 2px transparent underline that turns gold on hover. Below
 * 1024px the nav collapses to a hamburger opening a full-screen navy panel.
 */
defineProps({
    stickyCta: { type: Boolean, default: true },
})

const page = usePage()

/**
 * Where the portal link should point for whoever is reading.
 *
 * A signed-in partner used to be offered "Partner-Portal" pointing at the login
 * screen, which bounced them straight back here — so the site looked broken
 * when in fact they had never been signed out. Now it takes them where they
 * were going and says whose area it is.
 */
const portalLink = computed(() => {
    const user = page.props.auth?.user

    if (! user) return { href: '/anmelden', label: 'Partner-Portal' }

    const isAdmin = (user.roles ?? []).some((role) => ['admin', 'super_admin'].includes(role))

    return isAdmin
        ? { href: '/admin', label: 'Administration' }
        : { href: '/portal', label: 'Mein Portal' }
})
const menuOpen = ref(false)
const scrolled = ref(false)

const app = computed(() => page.props.app ?? {})
const content = computed(() => page.props.content ?? {})
const t = (section, field, fallback = '') => content.value?.[section]?.[field] ?? fallback

const nav = [
    { href: '/leistungen', label: 'Leistungen' },
    { href: '/ablauf', label: 'Ablauf' },
    { href: '/fuer-sachverstaendige', label: 'Für Sachverständige' },
    { href: '/ueber-uns', label: 'Über DKGZ' },
    { href: '/kontakt', label: 'Kontakt' },
]

const telHref = (value) => `tel:${String(value ?? '').replace(/\s/g, '')}`

const onScroll = () => { scrolled.value = window.scrollY > 480 }

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white">
        <!--
            Meldeband, 28px. The text comes from the shared props rather than
            the page's own content: it used to be read from whichever page's
            blocks happened to be loaded, so every page but the homepage fell
            back to the built-in default and the bar said something different as
            you moved around.
        -->
        <div v-if="app.announcement" class="hidden h-7 items-center bg-navy-900 md:flex">
            <div class="mx-auto flex w-full max-w-(--container-shell) items-center justify-between gap-6 px-6">
                <span class="text-xs tracking-nav text-gray-300">
                    {{ app.announcement }}
                </span>
                <div class="flex items-center gap-5">
                    <a v-if="app.phone" :href="telHref(app.phone)" class="font-mono text-xs tabular-nums text-white">{{ app.phone }}</a>
                    <span class="h-3 w-px bg-white/22" aria-hidden="true" />
                    <span v-if="app.office_hours" class="text-xs text-gray-300">{{ app.office_hours }}</span>
                    <FlagRule :width="40" class="ml-1" />
                </div>
            </div>
        </div>

        <!-- Header, 72px, sticky -->
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white">
            <div class="mx-auto flex h-18 w-full max-w-(--container-shell) items-center justify-between gap-8 px-4 md:px-6">
                <Link href="/" class="flex items-center gap-3.5" aria-label="Zur Startseite">
                    <BrandLogo height="h-10">
                    <BrandSeal :size="42">
                        <SealMark :size="42" />
                    </BrandSeal>
                    <span class="text-mark font-bold leading-none tracking-wordmark text-navy-700">DKGZ</span>
                    <!--
                        The gold rule separates the wordmark from the subtitle,
                        and the subtitle only appears from sm up — so on a phone
                        it was a stray line dangling after "DKGZ", separating it
                        from nothing.
                    -->
                    <span class="hidden h-(--spacing-mark-rule) w-px shrink-0 sm:block" style="background: var(--dkgz-accent)" aria-hidden="true" />
                    <span class="hidden text-seal font-semibold uppercase leading-[1.5] text-gray-600 sm:block">
                        Deutsche<br>KFZ-Gutachterzentrale
                    </span>
                    </BrandLogo>
                </Link>

                <nav class="hidden items-center gap-7 lg:flex" aria-label="Hauptnavigation">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="inline-flex h-18 items-center whitespace-nowrap border-b-2 border-transparent text-base font-medium text-gray-800 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:border-b-accent hover:text-navy-700"
                    >{{ item.label }}</Link>
                </nav>

                <div class="flex items-center gap-3">
                    <!--
                        Always present, and never wrapped: on a narrow screen the
                        label sheds its second word rather than the button
                        disappearing, so the one thing a visitor came to do is
                        reachable at every width. Hiding a word costs nothing;
                        hiding the button costs the request.
                    -->
                    <BaseButton href="/anfrage" size="compact" class="whitespace-nowrap">
                        <!--
                            One flex child, not two: the button lays its slot out
                            with a gap, so "Anfrage" and "starten" as separate
                            nodes were pushed apart by it and the non-breaking
                            space made a second gap on top.
                        -->
                        <span>Anfrage<span class="hidden sm:inline">&nbsp;starten</span></span>
                    </BaseButton>
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

        <!-- Full-screen navy panel below 1024px -->
        <Teleport to="body">
            <div v-if="menuOpen" class="fixed inset-0 z-50 flex flex-col bg-navy-900 lg:hidden" role="dialog" aria-modal="true" aria-label="Menü">
                <div class="flex h-18 shrink-0 items-center justify-between px-4 pt-[env(safe-area-inset-top)]">
                    <span class="flex items-center gap-3">
                        <BrandLogo inverted height="h-9">
                        <span class="text-h3 font-bold leading-none tracking-wordmark text-white">DKGZ</span>
                        <span class="h-6 w-px" style="background: var(--dkgz-accent)" aria-hidden="true" />
                        <span class="text-seal font-semibold uppercase leading-[1.5] text-white/72">Deutsche<br>KFZ-Gutachterzentrale</span>
                        </BrandLogo>
                    </span>
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
                    <Link :href="portalLink.href" class="border-b border-white/10 py-4 text-h4 text-white/72" @click="menuOpen = false">{{ portalLink.label }}</Link>
                </nav>
                <div class="shrink-0 p-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
                    <BaseButton href="/anfrage" variant="inverted" size="cta" block @click="menuOpen = false">Anfrage starten</BaseButton>
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

        <footer class="bg-navy-900">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6">
                <div class="grid grid-cols-1 gap-12 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.6fr)_repeat(3,minmax(0,1fr))]">
                    <div>
                        <div class="flex items-center gap-3">
                            <BrandLogo inverted height="h-9">
                            <span class="text-h3 font-bold leading-none tracking-wordmark text-white">DKGZ</span>
                            <span class="h-6 w-px shrink-0" style="background: var(--dkgz-accent)" aria-hidden="true" />
                            <span class="text-seal font-semibold uppercase leading-[1.5] text-white/72">Deutsche<br>KFZ-Gutachterzentrale</span>
                            </BrandLogo>
                        </div>
                        <p class="measure-brand pt-5 text-sm leading-relaxed text-white/60">
                            {{ t('fuss', 'beschreibung', 'Bundesweite Vermittlung von Kfz-Sachverständigen. Eine Anfrage, ein Ansprechpartner, geprüfte Partner in allen PLZ-Gebieten.') }}
                        </p>
                        <div v-if="app.phone" class="flex items-center gap-5 pt-5">
                            <a :href="telHref(app.phone)" class="font-mono text-sm tabular-nums text-white">{{ app.phone }}</a>
                            <span class="h-3 w-px bg-white/22" aria-hidden="true" />
                            <span class="text-sm text-white/60">{{ app.office_hours }}</span>
                        </div>
                    </div>

                    <div>
                        <p class="pb-4 text-eyebrow font-semibold uppercase text-white/45">Leistungen</p>
                        <ul class="flex flex-col gap-2.5">
                            <li v-for="item in ['Unfallgutachten', 'Haftpflichtgutachten', 'Wertgutachten', 'Oldtimergutachten']" :key="item">
                                <Link href="/leistungen" class="text-sm text-white/72 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:text-white">{{ item }}</Link>
                            </li>
                            <li><Link href="/leistungen" class="text-sm text-white/72 hover:text-white">Alle Leistungen</Link></li>
                        </ul>
                    </div>

                    <div>
                        <p class="pb-4 text-eyebrow font-semibold uppercase text-white/45">Unternehmen</p>
                        <ul class="flex flex-col gap-2.5">
                            <li><Link href="/ueber-uns" class="text-sm text-white/72 hover:text-white">Über DKGZ</Link></li>
                            <li><Link href="/ablauf" class="text-sm text-white/72 hover:text-white">Ablauf</Link></li>
                            <li><Link href="/fuer-sachverstaendige" class="text-sm text-white/72 hover:text-white">Für Sachverständige</Link></li>
                            <li><Link :href="portalLink.href" class="text-sm text-white/72 hover:text-white">{{ portalLink.label }}</Link></li>
                            <li><Link href="/kontakt" class="text-sm text-white/72 hover:text-white">Kontakt</Link></li>
                        </ul>
                    </div>

                    <div>
                        <p class="pb-4 text-eyebrow font-semibold uppercase text-white/45">Rechtliches</p>
                        <ul class="flex flex-col gap-2.5">
                            <li><Link href="/impressum" class="text-sm text-white/72 hover:text-white">Impressum</Link></li>
                            <li><Link href="/datenschutz" class="text-sm text-white/72 hover:text-white">Datenschutzerklärung</Link></li>
                            <li><Link href="/agb" class="text-sm text-white/72 hover:text-white">AGB</Link></li>
                            <li><Link href="/widerruf" class="text-sm text-white/72 hover:text-white">Vermittlungsbedingungen</Link></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-12 flex flex-wrap items-center justify-between gap-6 border-t border-white/12 pt-6">
                    <span class="text-sm text-white/45">© {{ new Date().getFullYear() }} DKGZ Deutsche KFZ-Gutachterzentrale</span>
                    <span class="measure-footer text-sm text-white/45">
                        {{ t('fuss', 'rechtshinweis', 'DKGZ ist eine Vermittlungsstelle. Das Gutachten erstellt der vermittelte Sachverständige.') }}
                    </span>
                </div>
            </div>
        </footer>
        <CookieConsent />
    </div>
</template>
