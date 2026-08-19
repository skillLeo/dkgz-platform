<script setup>
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

/**
 * One page for 404, 419, 500 and 503, in the design language rather than the
 * framework's default. Copy comes from content_blocks so it stays editable.
 */
const props = defineProps({
    status: { type: Number, required: true },
    content: { type: Object, default: () => ({}) },
})

const page = usePage()

const section = computed(() => props.content?.[String(props.status)] ?? {})

const titles = {
    404: 'Diese Seite existiert nicht.',
    419: 'Ihre Sitzung ist abgelaufen.',
    500: 'Es ist ein Fehler aufgetreten.',
    503: 'Wartungsarbeiten',
}

const heading = computed(() => section.value.ueberschrift ?? titles[props.status] ?? 'Es ist ein Fehler aufgetreten.')
const body = computed(() => section.value.text ?? '')
</script>

<template>
    <Head :title="`Fehler ${status}`" />

    <PublicLayout :sticky-cta="false">
        <div class="mx-auto w-full max-w-[680px] px-4 py-24 md:px-6 md:py-32">
            <p class="font-mono text-sm tabular-nums text-gray-400">Fehler {{ status }}</p>
            <h1 class="pt-4 text-h1 font-bold text-navy-700">{{ heading }}</h1>
            <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">{{ body }}</p>

            <div class="flex flex-wrap items-center gap-6 pt-8">
                <BaseButton href="/" size="cta">Zur Startseite</BaseButton>
                <span v-if="page.props.app?.phone" class="text-sm text-gray-600">
                    oder telefonisch
                    <a :href="`tel:${page.props.app.phone.replace(/\s/g, '')}`" class="font-mono text-navy-700">{{ page.props.app.phone }}</a>
                </span>
            </div>
        </div>
    </PublicLayout>
</template>
