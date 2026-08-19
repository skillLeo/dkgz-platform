<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

defineProps({
    page: { type: Object, required: true },
    navigation: { type: Array, default: () => [] },
})

const { date } = useGermanFormat()
</script>

<template>
    <Head :title="page.meta_title ?? page.title">
        <meta v-if="page.meta_description" name="description" :content="page.meta_description">
    </Head>

    <PublicLayout :sticky-cta="false">
        <div class="mx-auto grid w-full max-w-(--container-wide) grid-cols-1 gap-16 px-4 py-20 md:px-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:items-start">
            <nav aria-label="Rechtliche Seiten" class="lg:sticky lg:top-24">
                <p class="pb-4 text-eyebrow font-semibold uppercase tracking-[0.09em] text-gray-600">Rechtliches</p>
                <ul class="flex flex-col gap-2.5 border-l border-gray-200">
                    <li v-for="item in navigation" :key="item.slug">
                        <Link
                            :href="`/${item.slug}`"
                            class="block py-0.5 pl-3.5 text-sm"
                            :class="item.slug === page.slug ? '-ml-px border-l-2 border-navy-700 font-medium text-navy-700' : 'text-gray-600 hover:text-navy-700'"
                        >{{ item.title }}</Link>
                    </li>
                </ul>
            </nav>

            <article class="min-w-0">
                <h1 class="text-h1 font-bold text-navy-700">{{ page.title }}</h1>
                <p class="pt-2 font-mono text-sm text-gray-400">Stand: {{ date(page.updated_at) }}</p>

                <!-- Long-form measure: 68 characters, 1.75 leading, per the design -->
                <div class="legal pt-12" v-html="page.body"></div>
            </article>
        </div>
    </PublicLayout>
</template>

<style scoped>
.legal :deep(h2) {
    font-size: var(--text-h3);
    line-height: 1.3;
    font-weight: 600;
    letter-spacing: -0.012em;
    color: var(--color-navy-700);
    padding: 2rem 0 0.75rem;
}

.legal :deep(h2:first-child) {
    padding-top: 0;
}

.legal :deep(p) {
    max-width: 68ch;
    font-size: var(--text-base);
    line-height: 1.75;
    color: var(--color-gray-800);
}

.legal :deep(a) {
    color: var(--color-navy-700);
    border-bottom: 1px solid var(--color-gray-300);
}
</style>
