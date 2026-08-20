<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

const props = defineProps({
    isPlaceholder: { type: Boolean, default: false },
    page: { type: Object, required: true },
})

const form = useForm({
    title_de: props.page.title_de,
    body_de: props.page.body_de ?? '',
    meta_title: props.page.meta_title ?? '',
    meta_description: props.page.meta_description ?? '',
    is_published: props.page.is_published,
})
</script>

<template>
    <Head :title="page.title_de" />

    <AdminLayout :title="page.title_de" back-href="/admin/seiten">
        <!--
            Legal text carries obligations. The seeded drafts are a starting
            point, not advice, and this says so until someone has replaced them.
        -->
        <div v-if="isPlaceholder" class="mb-6 rounded-card border border-warning bg-warning/5 p-4" role="status">
            <p class="text-base font-medium text-warning">Platzhaltertext</p>
            <p class="measure pt-1 text-sm leading-normal text-gray-800">
                Dieser Text ist ein Platzhalter und muss durch rechtlich geprüfte Inhalte ersetzt werden. Der Hinweis
                verschwindet, sobald Sie die Seite gespeichert haben.
            </p>
        </div>

        <PageHeader :title="page.title_de" :eyebrow="`/${page.slug}`" />

        <form class="border border-gray-200 bg-white p-6" novalidate @submit.prevent="form.post(`/admin/seiten/${page.slug}`, { preserveScroll: true })">
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" class="mb-6" />

            <div class="flex flex-col gap-5">
                <BaseInput v-model="form.title_de" label="Titel" :error="form.errors.title_de" required />
                <BaseTextarea
                    v-model="form.body_de"
                    label="Inhalt"
                    hint="HTML. Überschriften als <h2>, Absätze als <p>. Zeilenlänge im Frontend auf 68 Zeichen begrenzt."
                    :rows="20"
                    :error="form.errors.body_de"
                    required
                />
                <BaseInput v-model="form.meta_title" label="Seitentitel für Suchmaschinen" :error="form.errors.meta_title" optional />
                <BaseTextarea v-model="form.meta_description" label="Beschreibung für Suchmaschinen" :rows="2" :error="form.errors.meta_description" optional />
                <BaseToggle v-model="form.is_published" label="Veröffentlicht" on-label="Sichtbar" off-label="Entwurf" />
            </div>

            <BaseButton type="submit" class="mt-6" :loading="form.processing">Seite speichern</BaseButton>
        </form>
    </AdminLayout>
</template>
