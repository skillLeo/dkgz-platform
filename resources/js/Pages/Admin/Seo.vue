<script setup>
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { AlertTriangle, Search } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

/**
 * Every public page and what a search engine will make of it.
 *
 * The wording is not copied here. Each title and description is read from
 * wherever it actually lives — a content block, a city's own column, an
 * article's — and an edit is written straight back there, so there is never a
 * second version to disagree with the first.
 *
 * What this adds over editing those one at a time is the view: everything in
 * one list, with the pages missing a description or running past what Google
 * will show marked as such, and a preview of the result.
 */
const props = defineProps({
    pages: { type: Array, default: () => [] },
    limits: { type: Object, default: () => ({ title: 60, description: 155 }) },
    canEdit: { type: Boolean, default: false },
})

const editing = ref(null)
const filter = ref('')
const onlyIssues = ref(false)

const form = useForm({
    path: '', source: '', reference: '', title: '', description: '', indexed: true,
})

const shown = computed(() => props.pages.filter((page) => {
    if (onlyIssues.value && ! page.issues.length) return false
    if (! filter.value) return true

    const needle = filter.value.toLowerCase()

    return page.label.toLowerCase().includes(needle) || page.path.toLowerCase().includes(needle)
}))

const groups = computed(() => {
    const out = new Map()

    for (const page of shown.value) {
        // Read once. Reading it twice trusts the two reads to return the same
        // key, which is one assumption more than this needs to make.
        const group = page.group ?? 'Seiten'

        if (! out.has(group)) out.set(group, [])
        out.get(group).push(page)
    }

    return out
})

const withIssues = computed(() => props.pages.filter((page) => page.issues.length).length)

const start = (page) => {
    editing.value = page.path
    form.clearErrors()
    Object.assign(form, {
        path: page.path,
        source: page.source,
        reference: page.reference,
        title: page.title ?? '',
        description: page.description ?? '',
        indexed: page.indexed,
    })
}

const submit = () => form.post('/admin/seo', {
    preserveScroll: true,
    onSuccess: () => { editing.value = null },
})

/** How close a string is to the length Google will cut it at. */
const meter = (value, limit) => {
    const length = (value ?? '').length

    if (length === 0) return { length, tone: 'text-danger' }
    if (length > limit) return { length, tone: 'text-danger' }
    if (length > limit * 0.85) return { length, tone: 'text-warning' }

    return { length, tone: 'text-success' }
}
</script>

<template>
    <Head title="SEO" />

    <AdminLayout title="SEO">
        <PageHeader title="SEO">
            <template #description>
                Seitentitel, Beschreibung und Indexierung für alle öffentlichen Seiten. Die Texte
                werden dort gespeichert, wo sie ohnehin stehen — es gibt keine zweite Fassung.
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-center gap-4 pb-5">
            <p class="text-sm text-gray-600">
                <span class="font-mono tabular-nums text-navy-700">{{ pages.length }}</span> Seiten ·
                <span class="font-mono tabular-nums" :class="withIssues ? 'text-warning' : 'text-navy-700'">{{ withIssues }}</span> mit Hinweisen
            </p>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input v-model="onlyIssues" type="checkbox" class="h-4 w-4 rounded-sm border-gray-300">
                Nur Seiten mit Hinweisen
            </label>

            <div class="ml-auto w-full max-w-72">
                <BaseInput v-model="filter" label="Suchen" placeholder="Seite oder Adresse" :icon="Search" />
            </div>
        </div>

        <div v-for="[group, rows] in groups" :key="group" class="pb-8">
            <p class="pb-3 text-eyebrow font-semibold uppercase text-gray-600">{{ group }}</p>

            <div class="flex flex-col gap-2">
                <section v-for="page in rows" :key="page.path" class="border border-gray-200 bg-white">
                    <div class="flex flex-wrap items-start justify-between gap-4 p-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h2 class="text-base font-medium text-navy-700">{{ page.label }}</h2>
                                <span v-if="! page.indexed" class="rounded-sm bg-gray-100 px-2 py-0.5 text-xs text-gray-600">noindex</span>
                                <span v-if="page.shared" class="rounded-sm border border-gray-200 px-2 py-0.5 text-xs text-gray-500">gemeinsame Vorlage</span>
                            </div>

                            <p class="pt-1 font-mono text-xs text-gray-400">{{ page.path }}</p>

                            <p v-if="page.issues.length" class="flex flex-wrap items-center gap-2 pt-2 text-xs text-warning">
                                <AlertTriangle :size="13" :stroke-width="1.75" class="shrink-0" aria-hidden="true" />
                                {{ page.issues.join(' · ') }}
                            </p>
                        </div>

                        <BaseButton v-if="canEdit" variant="secondary" size="small" @click="start(page)">Bearbeiten</BaseButton>
                    </div>

                    <form v-if="editing === page.path" class="border-t border-gray-200 p-5" novalidate @submit.prevent="submit">
                        <!--
                            The result, roughly as Google shows it. Nobody can
                            judge a meta description by its character count, and
                            everybody can judge it by looking at it.
                        -->
                        <div class="mb-5 rounded-card border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs text-gray-600">dkgz.de{{ page.path }}</p>
                            <p class="truncate pt-1 text-lead text-[#1a0dab]">{{ form.title || page.label }}</p>
                            <p class="measure pt-1 text-sm leading-normal text-gray-600">
                                {{ form.description || 'Keine Beschreibung hinterlegt.' }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-4">
                            <div>
                                <BaseInput v-model="form.title" label="Seitentitel" :error="form.errors.title" />
                                <p class="pt-1.5 text-xs" :class="meter(form.title, limits.title).tone">
                                    {{ meter(form.title, limits.title).length }} / {{ limits.title }} Zeichen
                                </p>
                            </div>

                            <div>
                                <BaseTextarea v-model="form.description" label="Beschreibung" :rows="3" :error="form.errors.description" />
                                <p class="pt-1.5 text-xs" :class="meter(form.description, limits.description).tone">
                                    {{ meter(form.description, limits.description).length }} / {{ limits.description }} Zeichen
                                </p>
                            </div>

                            <BaseToggle
                                v-model="form.indexed"
                                label="Von Google indexieren"
                                on-label="Indexiert"
                                off-label="noindex"
                            />
                        </div>

                        <p v-if="page.shared" class="pt-4 text-xs leading-normal text-gray-600">
                            Diese Seite nutzt eine gemeinsame Vorlage. Änderungen an Titel und
                            Beschreibung gelten für alle Seiten dieser Gruppe.
                        </p>

                        <div class="flex gap-3 pt-5">
                            <BaseButton type="submit" size="compact" :loading="form.processing">Speichern</BaseButton>
                            <BaseButton type="button" variant="ghost" size="compact" @click="editing = null">Abbrechen</BaseButton>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
