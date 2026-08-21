<script setup>
import BrandLogo from './BrandLogo.vue'
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { LogOut } from 'lucide-vue-next'

/**
 * The desktop rail from DKGZ Sachverständigen-Portal: navy-900, a 64px brand
 * head, 40px rows whose active state is a navy-700 fill plus a 2px accent
 * marker on the leading edge, and a foot carrying the partner ID and sign-out.
 * Groups are separated by a hairline, not by a heading.
 */
const props = defineProps({
    /**
     * Either an array of item arrays, or an array of { label?, items }. The
     * portal rail has no headings and separates by hairline; the admin rail
     * carries headings because it holds far more entries than an eye can group
     * by spacing alone.
     */
    groups: { type: Array, required: true },
    subtitle: { type: [Array, String], default: () => ['Partner-', 'Portal'] },
    identLabel: { type: String, default: null },
    identValue: { type: String, default: null },
})

const page = usePage()

const subtitleLines = computed(() => (Array.isArray(props.subtitle) ? props.subtitle : [props.subtitle]))

const normalised = computed(() => props.groups
    .map((group) => (Array.isArray(group) ? { label: null, items: group } : group))
    .filter((group) => group.items?.length))

const isActive = (item) => {
    const current = page.url.split('?')[0]

    return item.exact ? current === item.href : current.startsWith(item.href)
}
</script>

<template>
    <aside class="hidden w-60 shrink-0 flex-col bg-navy-900 md:flex">
        <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-white/10 px-5">
            <BrandLogo inverted height="h-8">
                <span class="text-h4 font-bold leading-none tracking-wordmark text-white">DKGZ</span>
                <span class="h-5 w-px bg-accent" aria-hidden="true" />
                <span class="text-seal-sm font-semibold uppercase leading-snug tracking-rail text-white/60">
                    <!--
                        subtitleLines, not subtitle: the prop accepts a string as
                        well as an array, and iterating the string walked it one
                        character at a time — "Administration" came out stacked
                        letter by letter down the sidebar.
                    -->
                    <template v-for="(part, i) in subtitleLines" :key="part">{{ part }}<br v-if="i < subtitleLines.length - 1" ></template>
                </span>
            </BrandLogo>
        </div>

        <nav class="min-h-0 flex-1 overflow-y-auto py-4" aria-label="Bereichsnavigation">
            <template v-for="(group, index) in normalised" :key="index">
                <span
                    v-if="index > 0 && !group.label"
                    class="my-4 mx-5 block h-px bg-white/10"
                    aria-hidden="true"
                />
                <p
                    v-if="group.label"
                    class="px-5 pb-2 pt-5 text-eyebrow font-semibold uppercase text-white/45"
                >{{ group.label }}</p>
                <ul>
                    <li v-for="item in group.items" :key="item.href">
                        <Link
                            :href="item.href"
                            class="flex h-10 items-center gap-3 border-l-2 px-5 text-base transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                            :class="isActive(item)
                                ? 'border-l-accent bg-navy-700 text-white'
                                : 'border-l-transparent text-white/72 hover:bg-navy-800 hover:text-white'"
                            :aria-current="isActive(item) ? 'page' : undefined"
                        >
                            <component :is="item.icon" :size="20" :stroke-width="1.5" class="shrink-0" aria-hidden="true" />
                            <span class="truncate">{{ item.label }}</span>
                            <span
                                v-if="item.badge"
                                class="ml-auto shrink-0 rounded-sm bg-navy-500 px-1.5 py-px font-mono text-meta leading-normal tabular-nums text-white"
                            >{{ item.badge }}</span>
                        </Link>
                    </li>
                </ul>
            </template>
        </nav>

        <div class="shrink-0 border-t border-white/10 p-5">
            <slot name="footer">
                <p v-if="identValue" class="font-mono text-meta text-white/45">
                    {{ identLabel }}<br >{{ identValue }}
                </p>
            </slot>
            <button
                type="button"
                class="flex items-center gap-2.5 pt-3 text-sm text-white/60 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:text-white"
                @click="page.props.auth && $inertia.post('/abmelden')"
            >
                <LogOut :size="18" :stroke-width="1.5" aria-hidden="true" />
                Abmelden
            </button>
        </div>
    </aside>
</template>
