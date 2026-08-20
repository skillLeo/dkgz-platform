<script setup>
import { Check } from 'lucide-vue-next'
import RequestFlowLayout from '../../Layouts/RequestFlowLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    request: { type: Object, required: true },
})

const page = usePage()
const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

// The design names the actual postal code in step one, not a generic phrase.
const step = (n, part) => t('ablauf', `schritt_${n}_${part}`).replace(':plz', props.request.postal_code ?? '')
</script>

<template>

    <RequestFlowLayout title="Anfrage bestätigt" label="Anfrage eingegangen">
        <div class="mx-auto w-full max-w-(--container-prose) px-4 py-20 md:px-6 md:py-24">
            <div class="grid h-14 w-14 place-items-center rounded-full border-2 border-navy-700 text-navy-700">
                <Check :size="26" :stroke-width="1.5" aria-hidden="true" />
            </div>

            <h1 class="pt-8 text-h1 font-bold text-navy-700">{{ t('kopf', 'ueberschrift') }}</h1>
            <p class="pt-4 text-lead leading-relaxed text-gray-600">{{ t('kopf', 'text') }}</p>

            <div class="mt-8 flex flex-wrap items-center justify-between gap-6 rounded-card border border-navy-700 px-6 py-5">
                <div>
                    <p class="text-eyebrow font-semibold uppercase text-gray-600">{{ t('referenz', 'label', 'Ihre Vorgangsnummer') }}</p>
                    <p class="pt-1.5 font-mono text-h3 tabular-nums text-navy-700">{{ request.reference }}</p>
                </div>
                <p class="measure-note text-sm leading-normal text-gray-600">{{ t('referenz', 'hinweis') }}</p>
            </div>

            <p class="pt-12 text-eyebrow font-semibold uppercase text-gray-600">{{ t('ablauf', 'ueberschrift', 'Wie es weitergeht') }}</p>

            <ol class="border-t border-gray-200 pt-2">
                <li v-for="n in [1, 2, 3]" :key="n" class="grid grid-cols-[32px_minmax(0,1fr)] gap-4 border-b border-gray-200 py-5">
                    <span class="pt-0.5 font-mono text-sm tabular-nums text-gray-400">0{{ n }}</span>
                    <div>
                        <h2 class="text-lead font-semibold text-navy-700">{{ step(n, 'titel') }}</h2>
                        <p class="measure pt-1 text-base leading-normal text-gray-600">{{ step(n, 'text') }}</p>
                    </div>
                </li>
            </ol>

            <div class="flex flex-wrap items-center gap-6 pt-8">
                <BaseButton href="/" variant="secondary" size="compact">Zur Startseite</BaseButton>
                <span v-if="page.props.app?.phone" class="text-sm text-gray-600">
                    Rückfragen: <a :href="`tel:${page.props.app.phone.replace(/\s/g, '')}`" class="font-mono text-navy-700">{{ page.props.app.phone }}</a>
                </span>
            </div>
        </div>
    </RequestFlowLayout>
</template>
