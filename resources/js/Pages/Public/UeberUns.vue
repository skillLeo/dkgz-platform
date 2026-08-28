<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

const props = defineProps({ content: { type: Object, default: () => ({}) } })

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

/** The three points, skipping any the operator has emptied. */
const punkte = computed(() => [1, 2, 3]
    .map((n) => ({ title: t('punkte', `titel_${n}`), text: t('punkte', `text_${n}`) }))
    .filter((item) => item.title || item.text))
</script>

<template>
    <Head title="Über uns" />

    <PublicLayout>
        <!-- The building, then a fuller account of who DKGZ is. -->
        <section class="border-b border-gray-200 bg-white">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 lg:py-24">
                <figure v-if="t('haus', 'bild')" class="pb-14">
                    <img
                        :src="t('haus', 'bild')"
                        :alt="t('haus', 'bildunterschrift', 'Geschäftsstelle der DKGZ')"
                        class="aspect-3/2 w-full max-w-160 rounded-card border border-gray-200 object-cover"
                    >
                    <figcaption class="pt-3 text-sm text-gray-600">
                        {{ t('haus', 'bildunterschrift') }}
                    </figcaption>
                </figure>

                <h2 class="text-h2 font-semibold text-navy-700">
                    {{ t('profil', 'ueberschrift', 'Wer hinter DKGZ steht') }}
                </h2>

                <div class="grid gap-x-12 gap-y-6 pt-8 md:grid-cols-2">
                    <p
                        v-for="key in ['absatz_1', 'absatz_2', 'absatz_3', 'absatz_4']"
                        v-show="t('profil', key)"
                        :key="key"
                        class="measure text-base leading-relaxed text-gray-800"
                    >{{ t('profil', key) }}</p>
                </div>
            </div>
        </section>

        <div class="mx-auto w-full max-w-(--container-shell) px-4 py-20 md:px-6">
            <SectionLabel :text="t('kopf', 'eyebrow', 'Über DKGZ')" />
            <h1 class="pt-6 text-h1 font-bold text-navy-700">{{ t('kopf', 'ueberschrift') }}</h1>
            <p class="measure pt-6 text-lead leading-relaxed text-gray-800">{{ t('kopf', 'text') }}</p>

            <!--
                Three points about what DKGZ is not, which is the thing most
                often misunderstood about it. They were typed into the template,
                so the one part of the page making the sharpest claims was the
                one part nobody could correct.
            -->
            <div v-if="punkte.length" class="grid grid-cols-1 gap-8 pt-16 md:grid-cols-3">
                <div v-for="item in punkte" :key="item.title" class="border-t border-gray-200 pt-5">
                    <h2 class="text-h4 font-semibold text-navy-700">{{ item.title }}</h2>
                    <p class="pt-2 text-base leading-normal text-gray-600">{{ item.text }}</p>
                </div>
            </div>

            <div v-if="t('partner', 'ueberschrift')" class="mt-16 border border-navy-700 p-8">
                <h2 class="text-h2 font-semibold text-navy-700">{{ t('partner', 'ueberschrift') }}</h2>
                <p v-if="t('partner', 'text')" class="measure pt-3 text-base leading-normal text-gray-600">
                    {{ t('partner', 'text') }}
                </p>
                <BaseButton href="/fuer-sachverstaendige" size="cta" class="mt-6">
                    {{ t('partner', 'button', 'Zum Partnerbereich') }}
                </BaseButton>
            </div>
        </div>
    </PublicLayout>
</template>
