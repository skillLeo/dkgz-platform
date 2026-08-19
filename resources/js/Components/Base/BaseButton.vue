<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

/**
 * Foundations §05. Height 46px (default) / 44px (cta) / 40px (compact),
 * radius 3px, 140ms hover. A button is never disabled by invalid input —
 * only by its loading state.
 */
const props = defineProps({
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'default' },
    type: { type: String, default: 'button' },
    href: { type: String, default: null },
    loading: { type: Boolean, default: false },
    loadingLabel: { type: String, default: 'Wird geprüft…' },
    block: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
})

const component = computed(() => (props.href ? Link : 'button'))

const variantClasses = {
    primary: 'bg-navy-700 text-white border-navy-700 hover:bg-navy-500 hover:border-navy-500 active:bg-navy-800',
    secondary: 'bg-white text-navy-700 border-navy-700 hover:bg-navy-100',
    ghost: 'bg-transparent text-navy-700 border-transparent hover:bg-gray-100',
    inverted: 'bg-white text-navy-700 border-white hover:bg-gray-100',
    outlineInverted: 'bg-transparent text-white border-white/30 hover:border-white',
    danger: 'bg-danger text-white border-danger hover:bg-danger-900 hover:border-danger-900',
}

const sizeClasses = {
    default: 'h-(--spacing-control) px-6 text-base',
    cta: 'h-(--spacing-cta) px-6 text-base',
    compact: 'h-(--spacing-control-sm) px-4 text-base',
    small: 'h-8 px-3 text-sm',
}

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2.5 border rounded-sm font-medium',
    'transition-colors duration-(--duration-hover) ease-(--ease-dkgz)',
    'focus-visible:outline-2 focus-visible:outline-navy-500 focus-visible:outline-offset-2',
    sizeClasses[props.size] ?? sizeClasses.default,
    props.loading || props.disabled
        ? 'bg-gray-200 text-gray-400 border-gray-200 cursor-not-allowed hover:bg-gray-200 hover:border-gray-200'
        : (variantClasses[props.variant] ?? variantClasses.primary),
    props.block ? 'w-full' : '',
])
</script>

<template>
    <component
        :is="component"
        :href="href"
        :type="href ? undefined : type"
        :disabled="href ? undefined : (loading || disabled)"
        :aria-busy="loading ? 'true' : undefined"
        :class="classes"
    >
        <span
            v-if="loading"
            class="h-4 w-4 shrink-0 rounded-full border-[1.5px] border-white/35 border-t-white"
            style="animation: dkgz-spin 700ms linear infinite"
            aria-hidden="true"
        />
        <slot v-if="!loading" />
        <template v-else>{{ loadingLabel }}</template>
    </component>
</template>
