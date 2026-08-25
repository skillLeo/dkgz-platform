<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ServiceIcon from '../../Components/Domain/ServiceIcon.vue'

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    serviceTypes: { type: Array, default: () => [] },
})

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback
</script>

<template>
    <Head title="Leistungen" />

    <PublicLayout>
        <div class="mx-auto w-full max-w-(--container-shell) px-4 py-20 md:px-6">
            <SectionLabel text="Leistungen" />
            <h1 class="pt-6 text-h1 font-bold text-navy-700">
                {{ t('kopf', 'ueberschrift', 'Welches Gutachten benötigen Sie?') }}
            </h1>
            <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">
                {{ t('kopf', 'text', 'Wir vermitteln für jede dieser Leistungen einen geprüften Sachverständigen aus Ihrer Region. Die Anfrage ist in jedem Fall kostenfrei.') }}
            </p>

            <div class="grid grid-cols-1 gap-4 pt-12 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="type in serviceTypes"
                    :key="type.id"
                    :href="`/leistungen/${type.slug}`"
                    class="flex flex-col border border-gray-200 bg-white p-6 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:border-navy-700"
                >
                    <ServiceIcon :service="type" class="text-navy-700" />
                    <h2 class="pt-4 text-h4 font-semibold text-navy-700">{{ type.name_de }}</h2>
                    <p class="flex-1 pt-2 text-base leading-normal text-gray-600">{{ type.description_de }}</p>
                    <span class="pt-5 text-base font-medium text-navy-700">{{ t('liste', 'link', 'Gutachter finden') }} →</span>
                </Link>
            </div>

            <div class="mt-16 border border-navy-700 p-8">
                <h2 class="text-h2 font-semibold text-navy-700">
                    {{ t('hilfe', 'ueberschrift', 'Nicht sicher, was Sie brauchen?') }}
                </h2>
                <p class="measure pt-3 text-base leading-normal text-gray-600">
                    {{ t('hilfe', 'text', 'Beschreiben Sie in der Anfrage kurz, was passiert ist. Der vermittelte Sachverständige ordnet den Fall ein und sagt Ihnen, welches Gutachten in Ihrem Fall das richtige ist.') }}
                </p>
                <BaseButton href="/anfrage" size="cta" class="mt-6">
                    {{ t('hilfe', 'button', 'Anfrage starten') }}
                </BaseButton>
            </div>
        </div>
    </PublicLayout>
</template>
