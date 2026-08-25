<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { ChevronDown } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

/**
 * Every published question, grouped the way they were filed.
 *
 * Grouped rather than one long list: somebody arriving with a question about
 * cost should not have to read past everything about the process to find it.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    groups: { type: Object, default: () => ({}) },
})

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

const open = ref(null)

const categories = computed(() => Object.entries(props.groups)
    .map(([name, entries]) => ({ name, entries })))
</script>

<template>
    <Head>
        <title>{{ t('kopf', 'meta_titel', 'Häufige Fragen — DKGZ') }}</title>
        <meta name="description" :content="t('kopf', 'meta_text', 'Antworten auf die häufigsten Fragen zur Vermittlung von Kfz-Sachverständigen durch DKGZ.')">
    </Head>

    <PublicLayout>
        <div class="mx-auto w-full max-w-(--container-shell) px-4 py-20 md:px-6">
            <SectionLabel :text="t('kopf', 'eyebrow', 'Fragen und Antworten')" />

            <h1 class="pt-6 text-h1 font-bold text-navy-700">
                {{ t('kopf', 'ueberschrift', 'Häufige Fragen') }}
            </h1>

            <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">
                {{ t('kopf', 'text', 'Wenn Ihre Frage hier nicht beantwortet wird, rufen Sie uns gern an — wir helfen Ihnen weiter.') }}
            </p>

            <div v-if="categories.length" class="pt-12">
                <section v-for="category in categories" :key="category.name" class="pb-10 last:pb-0">
                    <h2 class="text-h3 font-semibold text-navy-700">{{ category.name }}</h2>

                    <dl class="border-t border-gray-200 pt-2 mt-4">
                        <div v-for="entry in category.entries" :key="entry.id" class="border-b border-gray-200">
                            <dt>
                                <button
                                    type="button"
                                    class="flex w-full items-start justify-between gap-4 py-5 text-left"
                                    :aria-expanded="open === entry.id"
                                    @click="open = open === entry.id ? null : entry.id"
                                >
                                    <span class="text-base font-medium text-navy-700">{{ entry.question_de }}</span>
                                    <ChevronDown
                                        :size="18"
                                        :stroke-width="1.5"
                                        class="mt-0.5 shrink-0 text-gray-600 transition-transform duration-(--duration-disclosure) ease-(--ease-dkgz)"
                                        :class="open === entry.id ? 'rotate-180' : ''"
                                        aria-hidden="true"
                                    />
                                </button>
                            </dt>
                            <dd v-if="open === entry.id" class="measure whitespace-pre-line pb-5 text-base leading-normal text-gray-600">
                                {{ entry.answer_de }}
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>

            <p v-else class="measure pt-10 text-base leading-normal text-gray-600">
                {{ t('kopf', 'leer', 'Es sind noch keine Fragen hinterlegt.') }}
            </p>

            <div class="mt-6 border border-navy-700 p-8">
                <h2 class="text-h3 font-semibold text-navy-700">
                    {{ t('abschluss', 'ueberschrift', 'Frage nicht dabei?') }}
                </h2>
                <p class="measure pt-3 text-base leading-normal text-gray-600">
                    {{ t('abschluss', 'text', 'Stellen Sie einfach Ihre Anfrage — der vermittelte Sachverständige klärt alles Weitere direkt mit Ihnen.') }}
                </p>
                <BaseButton href="/anfrage" size="cta" class="mt-6">
                    {{ t('abschluss', 'button', 'Anfrage starten') }}
                </BaseButton>
            </div>
        </div>
    </PublicLayout>
</template>
