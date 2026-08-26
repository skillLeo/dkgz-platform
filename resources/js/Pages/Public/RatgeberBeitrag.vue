<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Clock } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

/**
 * One article.
 *
 * A single column at reading width, because that is the entire job of this
 * page. The offer to arrange an assessor comes after the article rather than
 * beside it — somebody who came here from a search wanted an answer, and
 * putting the sales box next to the first paragraph says the answer was bait.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    post: { type: Object, required: true },
    more: { type: Array, default: () => [] },
})

const t = (section, field, fallback = '') => props.content?.[section]?.[field] || fallback

const title = computed(() => props.post.meta_title || `${props.post.title} | DKGZ`)
const description = computed(() => props.post.meta_description || props.post.excerpt)

/**
 * What Google shows as an article rather than as a page.
 *
 * Written out here rather than in the layout because only this page is one.
 */
const schema = computed(() => JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: props.post.title,
    description: props.post.excerpt,
    datePublished: props.post.published_iso,
    image: props.post.cover_url ? [props.post.cover_url] : undefined,
    author: { '@type': 'Organization', name: props.post.author || 'DKGZ' },
    publisher: { '@type': 'Organization', name: 'Deutsche KFZ-Gutachterzentrale' },
    mainEntityOfPage: `https://dkgz.de${props.post.url}`,
}))
</script>

<template>
    <Head>
        <title>{{ title }}</title>
        <meta name="description" :content="description">
        <meta property="og:type" content="article">
        <meta property="og:title" :content="post.title">
        <meta property="og:description" :content="description">
        <meta v-if="post.cover_url" property="og:image" :content="post.cover_url">
        <link rel="canonical" :href="`https://dkgz.de${post.url}`">
        <component :is="'script'" type="application/ld+json">{{ schema }}</component>
    </Head>

    <!--
        No bar pinned to the bottom of the screen here. An article is read, and
        a button hovering over the last two lines of every paragraph is in the
        way of the one thing the page is for — the offer to arrange an assessor
        already sits at the end of the piece, where somebody has finished.
    -->
    <PublicLayout :sticky-cta="false">
        <article>
            <header class="border-b border-gray-200 bg-gray-50">
                <div class="mx-auto w-full max-w-(--container-prose) px-4 py-14 md:px-6 md:py-16">
                    <nav class="flex flex-wrap items-center gap-2 pb-6 text-sm text-gray-600" aria-label="Brotkrumen">
                        <Link href="/ratgeber" class="hover:text-navy-700">Ratgeber</Link>
                        <span aria-hidden="true">·</span>
                        <span class="text-gray-800">{{ post.category ?? 'Beitrag' }}</span>
                    </nav>

                    <h1 class="hyphens-auto break-words text-h2 font-bold leading-tight text-navy-700 sm:text-h1" lang="de">
                        {{ post.title }}
                    </h1>

                    <p class="flex flex-wrap items-center gap-x-2.5 gap-y-1 pt-5 text-sm text-gray-600">
                        <span>{{ post.published_at }}</span>
                        <span aria-hidden="true">·</span>
                        <span class="flex items-center gap-1.5">
                            <Clock :size="14" :stroke-width="1.5" class="shrink-0" aria-hidden="true" />
                            {{ post.reading_minutes }} Min. Lesezeit
                        </span>
                        <template v-if="post.author">
                            <span aria-hidden="true">·</span>
                            <span>{{ post.author }}</span>
                        </template>
                    </p>
                </div>
            </header>

            <div class="mx-auto w-full max-w-(--container-prose) px-4 py-12 md:px-6">
                <img
                    v-if="post.cover_url"
                    :src="post.cover_url"
                    :alt="post.cover_alt || post.title"
                    class="mb-10 aspect-16/9 w-full rounded-card border border-gray-200 object-cover"
                >

                <p v-if="post.excerpt" class="measure text-lead leading-relaxed text-gray-800">{{ post.excerpt }}</p>

                <!--
                    The body is written in the admin panel by the office and
                    rendered as it was typed. It is not visitor input, and there
                    is no path by which it could be.
                -->
                <div v-if="post.body" class="artikel pt-8" v-html="post.body" />
            </div>
        </article>

        <section class="border-y border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-prose) px-4 py-12 text-center md:px-6">
                <h2 class="text-h3 font-semibold text-navy-700">
                    {{ t('cta', 'ueberschrift', 'Sie brauchen ein Gutachten?') }}
                </h2>
                <p class="pt-3 text-base leading-normal text-gray-600">
                    {{ t('cta', 'text', 'Wir vermitteln Ihnen einen geprüften Kfz-Sachverständigen in Ihrer Region. Kostenfrei und unverbindlich.') }}
                </p>
                <BaseButton href="/anfrage" size="cta" class="mt-6">
                    {{ t('cta', 'button', 'Jetzt Gutachter anfragen') }}
                </BaseButton>
            </div>
        </section>

        <!-- Something to read next, so an article is not a dead end. -->
        <section v-if="more.length" class="mx-auto w-full max-w-(--container-shell) px-4 py-14 md:px-6">
            <h2 class="text-h3 font-semibold text-navy-700">
                {{ t('weiter', 'ueberschrift', 'Weitere Beiträge') }}
            </h2>

            <div class="grid grid-cols-1 gap-x-8 gap-y-8 pt-8 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="entry in more" :key="entry.slug" :href="entry.url" class="group flex flex-col">
                    <p class="flex flex-wrap items-center gap-x-2 text-eyebrow font-semibold uppercase text-gray-600">
                        <span v-if="entry.category" style="color: var(--dkgz-accent)">{{ entry.category }}</span>
                        <span v-if="entry.category" aria-hidden="true">·</span>
                        <span>{{ entry.published_at }}</span>
                    </p>
                    <h3 class="hyphens-auto break-words pt-2 text-lead font-semibold leading-snug text-navy-700 group-hover:text-navy-500" lang="de">
                        {{ entry.title }}
                    </h3>
                    <p class="pt-2 text-base leading-normal text-gray-600">{{ entry.excerpt }}</p>
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
/*
 * An article is longer than a legal page and has more shapes in it — lists,
 * subheadings, a pulled quote. The rhythm is the same as the legal pages so the
 * two do not read as different websites, with the extra elements set to match.
 */
.artikel :deep(h2) {
    font-size: var(--text-h3);
    line-height: 1.3;
    font-weight: 600;
    letter-spacing: -0.012em;
    color: var(--color-navy-700);
    padding: 2.5rem 0 0.75rem;
}

.artikel :deep(h3) {
    font-size: var(--text-lead);
    line-height: 1.4;
    font-weight: 600;
    color: var(--color-navy-700);
    padding: 1.75rem 0 0.5rem;
}

.artikel :deep(h2:first-child),
.artikel :deep(h3:first-child) {
    padding-top: 0;
}

.artikel :deep(p),
.artikel :deep(li) {
    max-width: 68ch;
    font-size: var(--text-base);
    line-height: 1.75;
    color: var(--color-gray-800);
}

.artikel :deep(p) {
    padding-bottom: 1rem;
}

.artikel :deep(ul),
.artikel :deep(ol) {
    padding: 0 0 1rem 1.5rem;
}

.artikel :deep(ul) { list-style: disc; }
.artikel :deep(ol) { list-style: decimal; }
.artikel :deep(li) { padding-bottom: 0.375rem; }

.artikel :deep(a) {
    color: var(--color-navy-700);
    border-bottom: 1px solid var(--color-gray-300);
}

.artikel :deep(a:hover) {
    border-bottom-color: var(--color-navy-700);
}

.artikel :deep(strong) {
    font-weight: 600;
    color: var(--color-navy-700);
}

/* A quote is the one place the accent earns its keep in running text. */
.artikel :deep(blockquote) {
    border-left: 2px solid var(--dkgz-accent);
    margin: 1.5rem 0;
    padding: 0.25rem 0 0.25rem 1.25rem;
}

.artikel :deep(blockquote p) {
    font-size: var(--text-lead);
    line-height: 1.6;
    color: var(--color-navy-700);
    padding-bottom: 0;
}

.artikel :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: var(--radius-card, 3px);
    border: 1px solid var(--color-gray-200);
    margin: 1.5rem 0;
}

/* Wide content scrolls inside itself rather than taking the page with it. */
.artikel :deep(table) {
    display: block;
    overflow-x: auto;
    width: 100%;
    margin: 1.5rem 0;
}
</style>
