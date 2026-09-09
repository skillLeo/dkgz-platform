<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { MapPin } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import EmptyState from '../../Components/Feedback/EmptyState.vue'

/**
 * The partner directory.
 *
 * Two jobs. It is the only part of the site that names the firms themselves,
 * which is a hundred and thirty pages of material nothing else here provides.
 * And it gives somebody who has been recommended a particular assessor a way to
 * reach them through DKGZ rather than around it — which only works because the
 * pages carry no telephone number and no e-mail address.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    assessors: { type: Object, default: () => ({ data: [], links: [] }) },
    serviceTypes: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    total: { type: Number, default: 0 },
})

const t = (section, field, fallback = '') => props.content?.[section]?.[field] || fallback

const rows = computed(() => props.assessors.data ?? [])

/**
 * The paginator's links, made usable.
 *
 * Laravel hands over labels as raw HTML — "&laquo; Previous" — which is neither
 * German nor something to inject into the page. Entries with no URL are the
 * disabled ends and the "..." gap; neither is worth a dead button, so they go.
 */
const pages = computed(() => (props.assessors.links ?? [])
    .filter((link) => link.url)
    .map((link, index) => {
        const raw = String(link.label).replace(/<[^>]*>/g, '').trim()
        const isPrev = /pagination\.previous|previous|zurück|«/i.test(raw)
        const isNext = /pagination\.next|next|weiter|»/i.test(raw)

        return {
            key: `${index}-${link.url}`,
            url: link.url,
            active: Boolean(link.active),
            label: isPrev ? 'Zurück' : isNext ? 'Weiter' : raw,
        }
    }))

const apply = (changes) => router.get('/sachverstaendige', {
    ...props.filters,
    ...changes,
}, { preserveScroll: true, replace: true })
</script>

<template>
    <Head>
        <title>{{ t('kopf', 'meta_titel', 'Kfz-Sachverständige im DKGZ-Netz | DKGZ') }}</title>
        <meta name="description" :content="t('kopf', 'meta_text', 'Geprüfte Kfz-Sachverständige aus ganz Deutschland. Anfrage direkt über DKGZ — kostenfrei und unverbindlich.')">
        <link rel="canonical" href="https://dkgz.de/sachverstaendige">
    </Head>

    <!--
        No bar pinned to the bottom of the screen. The page already carries its
        own call to action, aimed at this partner rather than at the general
        queue, and a second one hovering over it would send somebody somewhere
        they did not choose.
    -->
    <PublicLayout :sticky-cta="false">
        <section class="border-b border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 md:py-20">
                <SectionLabel :text="t('kopf', 'eyebrow', 'Partnernetz')" />
                <h1 class="pt-6 text-h2 font-bold text-navy-700 sm:text-h1">
                    {{ t('kopf', 'ueberschrift', 'Geprüfte Kfz-Sachverständige') }}
                </h1>
                <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">
                    {{ t('kopf', 'text', 'Jeder Partner in diesem Verzeichnis wurde vor der Freigabe geprüft. Die Anfrage läuft über DKGZ und ist für Sie kostenfrei.') }}
                </p>
            </div>
        </section>

        <div class="mx-auto w-full max-w-(--container-shell) px-4 py-12 md:px-6">
            <!--
                Service only. The postal-region row asked somebody to know which
                of ten numbered zones their town is in, which is a question
                about the postal system rather than about finding an assessor.
            -->
            <div class="flex flex-col gap-4 pb-10">
                <div v-if="serviceTypes.length" class="flex flex-wrap items-center gap-2">
                    <span class="pr-1 text-eyebrow font-semibold uppercase text-gray-600">Leistung</span>
                    <button
                        type="button"
                        class="rounded-sm border px-3 py-1.5 text-sm transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                        :class="! filters.leistung ? 'border-navy-700 bg-navy-700 text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-400'"
                        @click="apply({ leistung: null })"
                    >Alle</button>
                    <button
                        v-for="type in serviceTypes"
                        :key="type.slug"
                        type="button"
                        class="rounded-sm border px-3 py-1.5 text-sm transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                        :class="filters.leistung === type.slug ? 'border-navy-700 bg-navy-700 text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-400'"
                        @click="apply({ leistung: type.slug })"
                    >{{ type.name_de }}</button>
                </div>
            </div>

            <EmptyState
                v-if="! rows.length"
                :title="t('kopf', 'leer', 'Für diese Auswahl ist derzeit kein Sachverständiger gelistet.')"
                :icon="MapPin"
            />

            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="assessor in rows"
                    :key="assessor.slug"
                    :href="assessor.url"
                    class="flex flex-col rounded-card border border-gray-200 bg-white p-6 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:border-navy-700"
                >
                    <div class="flex items-center gap-3.5">
                        <img
                            v-if="assessor.photo_url"
                            :src="assessor.photo_url"
                            :alt="assessor.name"
                            class="h-12 w-12 shrink-0 rounded-full border border-gray-200 object-cover"
                            loading="lazy"
                        >
                        <span
                            v-else
                            class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-navy-100 font-semibold text-navy-700"
                            aria-hidden="true"
                        >{{ assessor.initials }}</span>

                        <span class="min-w-0">
                            <span class="block truncate text-base font-medium text-navy-700">{{ assessor.name }}</span>
                            <span v-if="assessor.region" class="block truncate text-sm text-gray-600">{{ assessor.region }}</span>
                        </span>
                    </div>

                    <ul v-if="assessor.services.length" class="flex flex-wrap gap-1.5 pt-4">
                        <li
                            v-for="service in assessor.services.slice(0, 3)"
                            :key="service"
                            class="rounded-sm border border-gray-200 px-2 py-0.5 text-xs text-gray-600"
                        >{{ service }}</li>
                        <li v-if="assessor.services.length > 3" class="px-1 py-0.5 text-xs text-gray-400">
                            +{{ assessor.services.length - 3 }}
                        </li>
                    </ul>

                    <span class="pt-5 text-base font-medium text-navy-700">Profil ansehen →</span>
                </Link>
            </div>

            <!--
                Pagination, so a hundred and seventy partners are not one
                endless page.

                Inertia links rather than plain anchors: an <a href> inside this
                app throws away the whole page and rebuilds it, which on a slow
                connection reads as the button having broken. The labels are
                rebuilt too — the paginator hands over "&laquo; Previous" as raw
                HTML, which is neither German nor safe to inject.
            -->
            <nav v-if="pages.length > 1" class="flex flex-wrap items-center justify-center gap-1.5 pt-12" aria-label="Seiten">
                <Link
                    v-for="entry in pages"
                    :key="entry.key"
                    :href="entry.url"
                    preserve-scroll
                    class="rounded-sm border px-3 py-1.5 text-sm transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                    :class="entry.active
                        ? 'border-navy-700 bg-navy-700 text-white'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-400'"
                    :aria-current="entry.active ? 'page' : undefined"
                >{{ entry.label }}</Link>
            </nav>
        </div>
    </PublicLayout>
</template>
