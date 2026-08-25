<script setup>
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { HelpCircle } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import EmptyState from '../../Components/Feedback/EmptyState.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({
    categories: { type: Array, default: () => [] }, faqs: { type: Array, default: () => [] } })

const { confirm } = useConfirm()
const createOpen = ref(false)
const editing = ref(null)

const create = useForm({ question_de: '', answer_de: '', category: '', is_published: true })
const edit = useForm({ question_de: '', answer_de: '', category: '', is_published: true })
const order = useForm({ order: [] })

const categoryOptions = computed(() => props.categories.map((name) => ({ value: name, label: name })))

const startEdit = (faq) => {
    editing.value = faq.id
    edit.question_de = faq.question_de
    edit.answer_de = faq.answer_de
    edit.category = faq.category ?? ''
    edit.is_published = faq.is_published
}

const move = (index, direction) => {
    const ids = props.faqs.map((f) => f.id)
    const target = index + direction

    if (target < 0 || target >= ids.length) return

    ;[ids[index], ids[target]] = [ids[target], ids[index]]
    order.order = ids
    order.post('/admin/faq/reihenfolge', { preserveScroll: true })
}

const remove = async (faq) => {
    const ok = await confirm({
        title: 'Frage löschen?',
        message: faq.question_de,
        confirmLabel: 'Löschen',
        tone: 'danger',
    })

    if (ok) useForm({}).delete(`/admin/faq/${faq.id}`, { preserveScroll: true })
}
</script>

<template>
    <Head title="FAQ" />

    <AdminLayout title="FAQ">
        <PageHeader title="Häufige Fragen" description="Erscheinen auf der Startseite und den Leistungsseiten.">
            <template #actions>
                <BaseButton size="compact" @click="createOpen = !createOpen">Frage anlegen</BaseButton>
            </template>
        </PageHeader>

        <form v-if="createOpen" class="mb-6 border border-gray-200 bg-white p-6" novalidate @submit.prevent="create.post('/admin/faq', { preserveScroll: true, onSuccess: () => { createOpen = false; create.reset() } })">
            <div class="flex flex-col gap-5">
                <BaseInput v-model="create.question_de" label="Frage" :error="create.errors.question_de" required />
                <BaseTextarea v-model="create.answer_de" label="Antwort" :error="create.errors.answer_de" required />
                <!--
                    A fixed list rather than free text: the public page renders
                    one section per distinct value, so "Kosten" and "kosten"
                    typed on different days would become two headings saying the
                    same thing.
                -->
                <BaseSelect
                    v-model="create.category"
                    label="Kategorie"
                    :options="categoryOptions"
                    placeholder="Allgemein"
                    hint="Bestimmt, unter welcher Überschrift die Frage auf der FAQ-Seite erscheint."
                    :error="create.errors.category"
                    optional
                />
            </div>
            <div class="flex gap-3 pt-5">
                <BaseButton type="submit" size="compact" :loading="create.processing">Anlegen</BaseButton>
                <BaseButton type="button" variant="ghost" size="compact" @click="createOpen = false">Abbrechen</BaseButton>
            </div>
        </form>

        <EmptyState v-if="!faqs.length" title="Keine Fragen hinterlegt" :icon="HelpCircle" />

        <div v-else class="flex flex-col gap-3">
            <section v-for="(faq, index) in faqs" :key="faq.id" class="border border-gray-200 bg-white">
                <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                    <div class="min-w-0">
                        <p class="text-base font-medium text-navy-700">{{ faq.question_de }}</p>
                        <p class="measure pt-1.5 text-sm leading-normal text-gray-600">{{ faq.answer_de }}</p>
                        <p class="pt-2 font-mono text-xs text-gray-400">
                            {{ faq.category ?? 'ohne Kategorie' }}<template v-if="!faq.is_published"> · nicht veröffentlicht</template>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" class="grid h-8 w-8 place-items-center rounded-sm border border-gray-200 text-gray-600 hover:border-gray-300 disabled:opacity-40" :disabled="index === 0" aria-label="Nach oben" @click="move(index, -1)">↑</button>
                        <button type="button" class="grid h-8 w-8 place-items-center rounded-sm border border-gray-200 text-gray-600 hover:border-gray-300 disabled:opacity-40" :disabled="index === faqs.length - 1" aria-label="Nach unten" @click="move(index, 1)">↓</button>
                        <BaseButton variant="secondary" size="small" @click="startEdit(faq)">Bearbeiten</BaseButton>
                        <BaseButton variant="ghost" size="small" @click="remove(faq)">Löschen</BaseButton>
                    </div>
                </div>

                <form v-if="editing === faq.id" class="border-t border-gray-200 p-5" novalidate @submit.prevent="edit.post(`/admin/faq/${faq.id}`, { preserveScroll: true, onSuccess: () => (editing = null) })">
                    <div class="flex flex-col gap-5">
                        <BaseInput v-model="edit.question_de" label="Frage" :error="edit.errors.question_de" required />
                        <BaseTextarea v-model="edit.answer_de" label="Antwort" :error="edit.errors.answer_de" required />
                        <BaseSelect
                            v-model="edit.category"
                            label="Kategorie"
                            :options="categoryOptions"
                            placeholder="Allgemein"
                            :error="edit.errors.category"
                            optional
                        />
                        <BaseToggle v-model="edit.is_published" label="Veröffentlicht" on-label="Sichtbar" off-label="Verborgen" />
                    </div>
                    <div class="flex gap-3 pt-5">
                        <BaseButton type="submit" size="compact" :loading="edit.processing">Speichern</BaseButton>
                        <BaseButton type="button" variant="ghost" size="compact" @click="editing = null">Abbrechen</BaseButton>
                    </div>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
