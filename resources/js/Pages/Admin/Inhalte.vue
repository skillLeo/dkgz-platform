<script setup>
import { reactive } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import ContentImageField from '../../Components/Domain/ContentImageField.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

/**
 * The editor mirrors the visual order of the page: sections in the order they
 * appear, fields in the order they are read.
 */
const props = defineProps({
    pageKey: { type: String, required: true },
    pages: { type: Object, default: () => ({}) },
    sections: { type: Object, default: () => ({}) },
    canEdit: { type: Boolean, default: false },
})

const blocks = []
Object.values(props.sections).forEach((fields) => {
    fields.forEach((field) => blocks.push({ id: field.id, value: field.value ?? '' }))
})

const form = useForm({ blocks })

const valueFor = (id) => form.blocks.find((b) => b.id === id)
</script>

<template>
    <Head title="Seiteninhalte" />

    <AdminLayout title="Seiteninhalte">
        <PageHeader title="Seiteninhalte" description="Jeder Text der öffentlichen Seiten. Änderungen sind sofort sichtbar." />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[220px_minmax(0,1fr)]">
            <nav aria-label="Seiten" class="lg:sticky lg:top-6 lg:self-start">
                <ul class="flex flex-wrap gap-2 border-l border-gray-200 lg:flex-col lg:gap-1">
                    <li v-for="(label, key) in pages" :key="key">
                        <Link
                            :href="`/admin/inhalte/${key}`"
                            class="block py-1.5 pl-4 text-sm"
                            :class="key === pageKey ? '-ml-px border-l-2 border-navy-700 font-medium text-navy-700' : 'text-gray-600 hover:text-navy-700'"
                        >{{ label }}</Link>
                    </li>
                </ul>
            </nav>

            <form class="min-w-0" @submit.prevent="form.post(`/admin/inhalte/${pageKey}`, { preserveScroll: true })">
                <div class="flex flex-col gap-6">
                    <section v-for="(fields, sectionKey) in sections" :key="sectionKey" class="border border-gray-200 bg-white p-6">
                        <SectionLabel :text="sectionKey" tone="muted" />
                        <div class="flex flex-col gap-5 pt-5">
                            <template v-for="field in fields" :key="field.id">
                                <ContentImageField
                                    v-if="field.type === 'image'"
                                    :block="field"
                                    :disabled="!canEdit"
                                />

                                <BaseTextarea
                                    v-else-if="field.type === 'richtext' || (field.value ?? '').length > 120"
                                    v-model="valueFor(field.id).value"
                                    :label="field.label"
                                    :rows="4"
                                    :disabled="!canEdit"
                                />

                                <BaseInput
                                    v-else
                                    v-model="valueFor(field.id).value"
                                    :label="field.label"
                                    :disabled="!canEdit"
                                />
                            </template>
                        </div>
                    </section>
                </div>

                <BaseButton v-if="canEdit" type="submit" class="mt-6" :loading="form.processing">Inhalte speichern</BaseButton>
            </form>
        </div>
    </AdminLayout>
</template>
