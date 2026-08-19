<script setup>
import { Head } from '@inertiajs/vue3'
import { Check } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

const props = defineProps({ content: { type: Object, default: () => ({}) } })

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback
</script>

<template>
    <Head title="Vielen Dank" />

    <PublicLayout :sticky-cta="false">
        <div class="mx-auto w-full max-w-[680px] px-4 py-24 md:px-6">
            <div class="grid h-14 w-14 place-items-center rounded-full border-2 border-navy-700 text-navy-700">
                <Check :size="26" :stroke-width="1.5" aria-hidden="true" />
            </div>
            <h1 class="pt-8 text-h1 font-bold text-navy-700">{{ t('danke', 'ueberschrift', 'Vielen Dank für Ihre Rückmeldung.') }}</h1>
            <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">{{ t('danke', 'text') }}</p>
            <BaseButton href="/" variant="secondary" size="compact" class="mt-8">Zur Startseite</BaseButton>
        </div>
    </PublicLayout>
</template>
