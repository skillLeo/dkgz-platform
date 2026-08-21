<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BrandLogo from './BrandLogo.vue'
import BrandSeal from './BrandSeal.vue'

/**
 * The wordmark lockup: seal ring, "DKGZ", a 1px gold rule, then the two-line
 * subtitle. Sizes come straight from the design — 26px in the auth shell,
 * 22px on the public header, 19px in e-mail, 20px on mobile.
 */
const props = defineProps({
    size: { type: String, default: 'md' },
    inverted: { type: Boolean, default: false },
    withSubtitle: { type: Boolean, default: true },
})

const page = usePage()
const branding = computed(() => page.props.branding ?? {})

const scale = {
    sm: { seal: 32, inner: 25, word: 'text-mark-sm', rule: 'h-5', sub: 'text-seal-xs tracking-rail' },
    md: { seal: 36, inner: 28, word: 'text-h3', rule: 'h-6', sub: 'text-seal-sm tracking-seal' },
    lg: { seal: 44, inner: 34, word: 'text-wordmark', rule: 'h-7', sub: 'text-seal tracking-seal' },
}

const s = computed(() => scale[props.size] ?? scale.md)
const wordColour = computed(() => (props.inverted ? 'text-white' : 'text-navy-700'))
const subColour = computed(() => (props.inverted ? 'text-white/72' : 'text-gray-600'))
const ringColour = computed(() => (props.inverted ? 'border-white/55' : 'border-navy-700'))
</script>

<template>
    <span class="flex items-center gap-2.5">
        <BrandLogo :inverted="inverted" height="h-9">
        <template #default>
            <BrandSeal :size="s.seal">
                <span
                    class="grid shrink-0 place-items-center rounded-full border"
                    :class="ringColour"
                    :style="{ width: `${s.seal}px`, height: `${s.seal}px` }"
                    aria-hidden="true"
                >
                    <span
                        class="grid place-items-center rounded-full border"
                        :style="{ width: `${s.inner}px`, height: `${s.inner}px`, borderColor: 'var(--dkgz-accent)' }"
                    >
                        <span class="text-seal-2xs font-bold" :class="wordColour">DKGZ</span>
                    </span>
                </span>
            </BrandSeal>
            <span class="font-bold leading-none tracking-wordmark" :class="[s.word, wordColour]">
                {{ branding.platform_name ?? 'DKGZ' }}
            </span>
            <template v-if="withSubtitle">
                <span class="w-px shrink-0" :class="s.rule" style="background: var(--dkgz-accent)" aria-hidden="true" />
                <span class="font-semibold uppercase leading-[1.5]" :class="[s.sub, subColour]">
                    Deutsche<br>KFZ-Gutachterzentrale
                </span>
            </template>
        </template>
        </BrandLogo>
    </span>
</template>
