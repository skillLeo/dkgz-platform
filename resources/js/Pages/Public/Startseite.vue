<script setup>
import { Head } from '@inertiajs/vue3'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'

defineProps({ content: { type: Object, default: () => ({}) } })
</script>

<template>
    <Head title="Kfz-Sachverständigen finden" />

    <div class="bg-white">
        <div class="mx-auto max-w-(--container-shell) px-6 py-16">
            <SectionLabel :text="content.hero?.eyebrow ?? 'Kostenlose Vermittlung'" />
            <h1 class="pt-6 text-h1 font-bold text-navy-700">{{ content.hero?.ueberschrift }}</h1>
            <p class="measure-lead pt-4 text-lead text-gray-600">{{ content.hero?.text }}</p>
        </div>
    </div>
</template>
