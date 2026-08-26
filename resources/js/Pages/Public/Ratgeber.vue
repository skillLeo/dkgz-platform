<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Clock } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import EmptyState from '../../Components/Feedback/EmptyState.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

/**
 * The Ratgeber index.
 *
 * Every other page here answers somebody who has already decided they need an
 * assessor. These articles are for the person a week earlier, still working out
 * whether they need one at all — so the listing is built for skimming: a title,
 * what it is about, how long it takes and the first two lines, and nothing that
 * has to be read in order.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    posts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
})

const t = (section, field, fallback = '') => props.content?.[section]?.[field] || fallback

const active = ref(null)

const shown = computed(() => (active.value === null
    ? props.posts
    : props.posts.filter((post) => post.category === active.value)))

/** The newest piece gets the width; the rest are cards. */
const lead = computed(() => (active.value === null ? shown.value[0] ?? null : null))
const rest = computed(() => (lead.value ? shown.value.slice(1) : shown.value))
</script>

<template>
    <Head>
        <title>{{ t('kopf', 'meta_titel', 'Ratgeber — Kfz-Gutachten verstehen | DKGZ') }}</title>
        <meta name="description" :content="t('kopf', 'meta_text', 'Antworten auf die Fragen, die sich nach einem Unfall zuerst stellen — von der Deutschen Kfz-Gutachterzentrale.')">
        <link rel="canonical" href="https://dkgz.de/ratgeber">
    </Head>

    <PublicLayout>
        <section class="border-b border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 md:py-20">
                <SectionLabel :text="t('kopf', 'eyebrow', 'Ratgeber')" />
                <h1 class="hyphens-auto break-words pt-6 text-h2 font-bold text-navy-700 sm:text-h1" lang="de">
                    {{ t('kopf', 'ueberschrift', 'Gutachten verstehen') }}
                </h1>
                <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">
                    {{ t('kopf', 'text', 'Was nach einem Unfall zu tun ist, wer das Gutachten bezahlt und worauf es dabei ankommt — verständlich erklärt.') }}
                </p>
            </div>
        </section>

        <div class="mx-auto w-full max-w-(--container-shell) px-4 py-14 md:px-6 md:py-16">
            <!--
                Only the headings something is actually filed under, so the row
                never offers a filter that comes back empty.
            -->
            <div v-if="categories.length > 1" class="flex flex-wrap gap-2 pb-10">
                <button
                    type="button"
                    class="rounded-sm border px-3.5 py-2 text-sm transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                    :class="active === null
                        ? 'border-navy-700 bg-navy-700 text-white'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-400'"
                    @click="active = null"
                >Alle</button>
                <button
                    v-for="category in categories"
                    :key="category"
                    type="button"
                    class="rounded-sm border px-3.5 py-2 text-sm transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                    :class="active === category
                        ? 'border-navy-700 bg-navy-700 text-white'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-400'"
                    @click="active = category"
                >{{ category }}</button>
            </div>

            <EmptyState
                v-if="! shown.length"
                :title="t('kopf', 'leer', 'Hier erscheinen in Kürze die ersten Beiträge.')"
                :icon="Clock"
            />

            <template v-else>
                <!-- The newest piece, given the width. -->
                <Link
                    v-if="lead"
                    :href="lead.url"
                    class="group grid grid-cols-1 gap-8 border-b border-gray-200 pb-12 lg:grid-cols-[minmax(0,5fr)_minmax(0,4fr)] lg:items-center"
                >
                    <div class="min-w-0 order-2 lg:order-1">
                        <p class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-eyebrow font-semibold uppercase text-gray-600">
                            <span v-if="lead.category" style="color: var(--dkgz-accent)">{{ lead.category }}</span>
                            <span v-if="lead.category" aria-hidden="true">·</span>
                            <span>{{ lead.published_at }}</span>
                        </p>
                        <h2 class="hyphens-auto break-words pt-3 text-h2 font-bold leading-tight text-navy-700 group-hover:text-navy-500" lang="de">
                            {{ lead.title }}
                        </h2>
                        <p class="measure pt-3 text-lead leading-relaxed text-gray-600">{{ lead.excerpt }}</p>
                        <p class="flex items-center gap-2 pt-4 text-sm text-gray-400">
                            <Clock :size="14" :stroke-width="1.5" class="shrink-0" aria-hidden="true" />
                            {{ lead.reading_minutes }} Min. Lesezeit
                            <template v-if="lead.author"> · {{ lead.author }}</template>
                        </p>
                    </div>

                    <div v-if="lead.cover_url" class="order-1 overflow-hidden rounded-card border border-gray-200 lg:order-2">
                        <img
                            :src="lead.cover_url"
                            :alt="lead.title"
                            class="aspect-16/10 w-full object-cover transition-transform duration-(--duration-disclosure) ease-(--ease-dkgz) group-hover:scale-[1.02]"
                            loading="lazy"
                        >
                    </div>
                </Link>

                <div class="grid grid-cols-1 gap-x-8 gap-y-10 pt-12 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="post in rest"
                        :key="post.slug"
                        :href="post.url"
                        class="group flex flex-col"
                    >
                        <div v-if="post.cover_url" class="overflow-hidden rounded-card border border-gray-200">
                            <img
                                :src="post.cover_url"
                                :alt="post.title"
                                class="aspect-16/10 w-full object-cover transition-transform duration-(--duration-disclosure) ease-(--ease-dkgz) group-hover:scale-[1.02]"
                                loading="lazy"
                            >
                        </div>

                        <p class="flex flex-wrap items-center gap-x-2 gap-y-1 pt-4 text-eyebrow font-semibold uppercase text-gray-600">
                            <span v-if="post.category" style="color: var(--dkgz-accent)">{{ post.category }}</span>
                            <span v-if="post.category" aria-hidden="true">·</span>
                            <span>{{ post.published_at }}</span>
                        </p>
                        <h2 class="hyphens-auto break-words pt-2 text-h4 font-semibold leading-snug text-navy-700 group-hover:text-navy-500" lang="de">
                            {{ post.title }}
                        </h2>
                        <p class="pt-2 text-base leading-normal text-gray-600">{{ post.excerpt }}</p>
                        <p class="flex items-center gap-2 pt-3 text-sm text-gray-400">
                            <Clock :size="14" :stroke-width="1.5" class="shrink-0" aria-hidden="true" />
                            {{ post.reading_minutes }} Min. Lesezeit
                        </p>
                    </Link>
                </div>
            </template>
        </div>

        <section class="border-t border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-14 text-center md:px-6">
                <h2 class="text-h3 font-semibold text-navy-700">
                    {{ t('cta', 'ueberschrift', 'Sie brauchen ein Gutachten?') }}
                </h2>
                <p class="measure-lead mx-auto pt-3 text-base leading-normal text-gray-600">
                    {{ t('cta', 'text', 'Wir vermitteln Ihnen einen geprüften Kfz-Sachverständigen in Ihrer Region. Kostenfrei und unverbindlich.') }}
                </p>
                <BaseButton href="/anfrage" size="cta" class="mt-6">
                    {{ t('cta', 'button', 'Jetzt Gutachter anfragen') }}
                </BaseButton>
            </div>
        </section>
    </PublicLayout>
</template>
