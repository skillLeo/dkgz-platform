<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { MessageSquareQuote, Plus, Star, Trash2 } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import EmptyState from '../../Components/Feedback/EmptyState.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

/**
 * Customer quotes for the homepage.
 *
 * Every other reassurance on that page is DKGZ talking about DKGZ. This is the
 * one place somebody else does the talking, and it only works while it is true
 * — a fabricated review is illegal here under the UWG, quite apart from being
 * the first thing a reader smells.
 */
const props = defineProps({
    testimonials: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
})

const { confirm } = useConfirm()

const editing = ref(null)
const creating = ref(false)

const RATINGS = [
    { value: '', label: 'Keine Sterne zeigen' },
    { value: '5', label: '5 Sterne' },
    { value: '4', label: '4 Sterne' },
    { value: '3', label: '3 Sterne' },
]

const blank = () => ({
    name: '', location: '', quote: '', rating: '5', is_published: true, photo: null,
})

const form = useForm(blank())

const labels = { name: 'Name', quote: 'Zitat', photo: 'Foto' }

const startCreate = () => {
    editing.value = null
    creating.value = true
    form.defaults(blank())
    form.reset()
    form.clearErrors()
}

const startEdit = (row) => {
    creating.value = false
    editing.value = row.id
    form.clearErrors()

    Object.assign(form, {
        name: row.name,
        location: row.location ?? '',
        quote: row.quote,
        rating: row.rating ? String(row.rating) : '',
        is_published: row.is_published,
        photo: null,
    })
}

const cancel = () => { creating.value = false; editing.value = null }

const submit = () => {
    const options = { preserveScroll: true, forceFormData: true, onSuccess: cancel }

    creating.value
        ? form.post('/admin/kundenstimmen', options)
        : form.post(`/admin/kundenstimmen/${editing.value}`, options)
}

const remove = async (row) => {
    const ok = await confirm({
        title: `Kundenstimme von ${row.name} löschen?`,
        message: 'Sie verschwindet damit von der Startseite.',
        confirmLabel: 'Löschen',
        tone: 'danger',
    })

    if (ok) useForm({}).delete(`/admin/kundenstimmen/${row.id}`, { preserveScroll: true })
}
</script>

<template>
    <Head title="Kundenstimmen" />

    <AdminLayout title="Kundenstimmen">
        <PageHeader title="Kundenstimmen">
            <template #description>
                Erscheinen auf der Startseite. Nur echte Rückmeldungen mit dem Einverständnis der
                Person — erfundene Bewertungen sind in Deutschland wettbewerbswidrig.
            </template>
            <template v-if="canEdit" #actions>
                <BaseButton size="compact" @click="startCreate">
                    <Plus :size="16" :stroke-width="1.75" aria-hidden="true" />
                    Kundenstimme anlegen
                </BaseButton>
            </template>
        </PageHeader>

        <form
            v-if="creating || editing !== null"
            class="mb-6 border border-gray-200 bg-white p-6"
            novalidate
            @submit.prevent="submit"
        >
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-5" />

            <div class="flex flex-col gap-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput v-model="form.name" label="Name" placeholder="Martina R." hint="So, wie die Person genannt werden möchte." :error="form.errors.name" required />
                    <BaseInput v-model="form.location" label="Ort" placeholder="Düsseldorf" hint="Macht „bundesweit“ glaubhaft." :error="form.errors.location" optional />
                </div>

                <BaseTextarea
                    v-model="form.quote"
                    label="Zitat"
                    :rows="4"
                    hint="Zwei bis drei Sätze wirken am besten. Wörtlich, nicht geglättet."
                    :error="form.errors.quote"
                    required
                />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[220px_minmax(0,1fr)]">
                    <BaseSelect v-model="form.rating" label="Bewertung" :options="RATINGS" placeholder="Keine Sterne zeigen" empty-allowed :error="form.errors.rating" optional />

                    <div>
                        <p class="pb-2 text-sm font-medium text-gray-800">Foto <span class="font-normal text-gray-400">optional</span></p>
                        <input
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp"
                            class="w-full text-sm text-gray-600 file:mr-3 file:rounded-sm file:border file:border-gray-300 file:bg-white file:px-3 file:py-2 file:text-sm file:text-navy-700"
                            @input="form.photo = $event.target.files[0] ?? null"
                        >
                        <p class="pt-1.5 text-xs text-gray-400">Ohne Foto werden die Initialen gezeigt.</p>
                        <p v-if="form.errors.photo" class="pt-1 text-xs text-danger">{{ form.errors.photo }}</p>
                    </div>
                </div>

                <BaseToggle v-model="form.is_published" label="Auf der Startseite zeigen" on-label="Sichtbar" off-label="Verborgen" />
            </div>

            <div class="flex gap-3 pt-6">
                <BaseButton type="submit" size="compact" :loading="form.processing">
                    {{ creating ? 'Anlegen' : 'Speichern' }}
                </BaseButton>
                <BaseButton type="button" variant="ghost" size="compact" @click="cancel">Abbrechen</BaseButton>
            </div>
        </form>

        <EmptyState v-if="! testimonials.length" title="Noch keine Kundenstimmen" :icon="MessageSquareQuote" />

        <div v-else class="flex flex-col gap-3">
            <section v-for="row in testimonials" :key="row.id" class="border border-gray-200 bg-white">
                <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                    <div class="flex min-w-0 gap-4">
                        <img v-if="row.photo_url" :src="row.photo_url" alt="" class="h-12 w-12 shrink-0 rounded-full border border-gray-200 object-cover">
                        <span v-else class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-navy-100 text-sm font-semibold text-navy-700" aria-hidden="true">{{ row.initials }}</span>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h2 class="text-base font-medium text-navy-700">{{ row.name }}</h2>
                                <span v-if="row.location" class="text-sm text-gray-600">{{ row.location }}</span>
                                <StatusDot :status="row.is_published ? 'approved' : 'closed'" :label="row.is_published ? 'Sichtbar' : 'Verborgen'" />
                            </div>
                            <p v-if="row.rating" class="flex gap-0.5 pt-1.5" :aria-label="`${row.rating} von 5 Sternen`">
                                <Star v-for="n in row.rating" :key="n" :size="13" :stroke-width="0" class="fill-current" style="color: var(--dkgz-accent)" aria-hidden="true" />
                            </p>
                            <p class="measure pt-2 text-sm leading-normal text-gray-600">{{ row.quote }}</p>
                        </div>
                    </div>

                    <div v-if="canEdit" class="flex shrink-0 gap-3">
                        <BaseButton variant="secondary" size="small" @click="startEdit(row)">Bearbeiten</BaseButton>
                        <BaseButton variant="ghost" size="small" @click="remove(row)">
                            <Trash2 :size="14" :stroke-width="1.5" aria-hidden="true" />
                            Löschen
                        </BaseButton>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
