<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ArrowUpRight, Euro, GitBranch, ShieldCheck } from 'lucide-vue-next'
import DkgzMark from '../Components/Layout/DkgzMark.vue'
import FlagRule from '../Components/Layout/FlagRule.vue'
import SectionLabel from '../Components/Layout/SectionLabel.vue'

/**
 * Auth Shell, Teil A. Two panels at 46/54. Left is the authority — flat
 * navy-900, no image, no gradient. Right is the action — white, form column
 * capped at 420px, entering once at 360ms. Only the left headline and the
 * three facts change per screen.
 */
const props = defineProps({
    eyebrow: { type: String, default: 'Partnerportal' },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    panelTitle: { type: String, default: 'Bundesweites Netz geprüfter Kfz-Sachverständiger.' },
    panelText: { type: String, default: 'Zugang für Partner der Deutschen KFZ-Gutachterzentrale.' },
    panelFooter: { type: String, default: 'Partnerbereich · Zugang nur für freigegebene Sachverständige' },
    variant: { type: String, default: 'partner' },
    facts: {
        type: Array,
        default: () => [
            { icon: 'branch', title: 'Automatische Vermittlung', text: 'Anfragen nach PLZ, ohne Angebotsvergleich' },
            { icon: 'shield', title: 'Kein Vergleichsportal', text: 'Der erste verfügbare Partner übernimmt' },
            { icon: 'euro', title: 'Transparente Provision', text: '15 % ausschließlich auf abgeschlossene Aufträge' },
        ],
    },
})

const icons = { branch: GitBranch, shield: ShieldCheck, euro: Euro }
const isAdmin = computed(() => props.variant === 'admin')
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <div class="mx-auto grid min-h-screen w-full grid-cols-1 bg-white md:min-h-(--size-auth-shell) md:grid-cols-[minmax(340px,46fr)_minmax(360px,54fr)]">
            <!-- Authority panel -->
            <div class="relative flex min-w-0 flex-col overflow-hidden bg-navy-900 p-6 md:p-16">
                <div
                    class="pointer-events-none absolute -left-10 bottom-10 grid h-30 w-30 place-items-center rounded-full border border-white/7"
                    aria-hidden="true"
                >
                    <div class="grid h-23 w-23 place-items-center rounded-full border border-white/7">
                        <span class="text-lead font-bold tracking-plate text-white/7">DKGZ</span>
                    </div>
                </div>

                <div class="relative">
                    <DkgzMark size="lg" inverted :with-subtitle="!isAdmin" />
                </div>

                <div class="relative pt-12 md:pt-24">
                    <h2 class="measure-headline text-h4 font-semibold text-white md:text-h2">{{ panelTitle }}</h2>
                    <p v-if="panelText" class="measure-panel pt-5 text-lead leading-relaxed text-white/62">{{ panelText }}</p>
                </div>

                <div v-if="!isAdmin" class="relative hidden pt-20 md:block">
                    <div
                        v-for="(fact, index) in facts"
                        :key="fact.title"
                        class="flex gap-4 border-t border-white/10 py-5"
                        :class="index === facts.length - 1 ? 'border-b border-white/10' : ''"
                    >
                        <component
                            :is="icons[fact.icon] ?? GitBranch"
                            :size="24"
                            :stroke-width="1.5"
                            class="mt-0.5 shrink-0 text-white"
                            aria-hidden="true"
                        />
                        <div class="min-w-0">
                            <p class="text-base font-medium text-white">{{ fact.title }}</p>
                            <p class="text-sm leading-normal text-white/55">{{ fact.text }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="relative mt-auto pt-16">
                    <p class="text-eyebrow font-semibold uppercase text-white/45">Interner Zugang</p>
                </div>

                <div v-if="!isAdmin" class="relative mt-auto flex flex-wrap items-end justify-between gap-4 pt-16">
                    <p class="text-xs text-white/40">{{ panelFooter }}</p>
                    <div class="text-right">
                        <FlagRule :width="40" class="ml-auto" />
                        <p class="pt-2 text-xs text-white/40">Deutsche KFZ-Gutachterzentrale</p>
                    </div>
                </div>
            </div>

            <!-- Action panel -->
            <div
                class="relative flex min-w-0 items-center bg-white p-6 md:p-16"
                style="animation: dkgz-rise 360ms cubic-bezier(0.4,0,0.2,1) both"
            >
                <Link
                    href="/"
                    class="absolute right-6 top-6 inline-flex items-center gap-2 text-sm font-medium text-navy-700 hover:text-navy-500 md:right-8 md:top-8"
                >
                    Zur Website
                    <ArrowUpRight :size="16" :stroke-width="1.5" aria-hidden="true" />
                </Link>

                <div class="w-full max-w-(--container-form)">
                    <SectionLabel :text="eyebrow" :tone="isAdmin ? 'muted' : 'accent'" />
                    <h1 class="pt-6 text-h4 font-semibold text-navy-700 md:text-h2">{{ title }}</h1>
                    <p v-if="description" class="measure-form pt-2 text-base leading-normal text-gray-600">{{ description }}</p>

                    <div class="pt-8">
                        <slot />
                    </div>

                    <div v-if="$slots.footer" class="pt-8">
                        <div class="h-px w-full bg-gray-200" />
                        <div class="pt-5 text-center text-sm text-gray-600">
                            <slot name="footer" />
                        </div>
                    </div>

                    <p v-if="isAdmin" class="pt-4 font-mono text-xs text-gray-400">Zugriffe werden protokolliert.</p>
                </div>
            </div>
        </div>
    </div>
</template>
