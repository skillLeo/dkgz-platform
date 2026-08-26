<script setup>
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { BookOpen, ExternalLink, Plus, Trash2 } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import EmptyState from '../../Components/Feedback/EmptyState.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

/**
 * The Ratgeber, from the office's side.
 *
 * Writing an article should be a title, a text and a tick. Everything a search
 * engine wants — the address, the description, the date — is worked out unless
 * somebody deliberately says otherwise, and the fields that do that are folded
 * away, because a form demanding eight answers before anything can be published
 * is a form nobody publishes from.
 */
const props = defineProps({
    posts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
})

const { confirm } = useConfirm()

const editing = ref(null)
const creating = ref(false)
const advanced = ref(false)

const blank = () => ({
    title: '',
    category: '',
    excerpt: '',
    body: '',
    cover: null,
    cover_alt: '',
    author: '',
    meta_title: '',
    meta_description: '',
    is_published: false,
    published_at: '',
})

const form = useForm(blank())

const labels = {
    title: 'Titel',
    body: 'Text',
    excerpt: 'Anrisstext',
    meta_description: 'Meta-Beschreibung',
    cover: 'Bild',
}

const categoryOptions = computed(() => props.categories.map((name) => ({ value: name, label: name })))

const published = computed(() => props.posts.filter((post) => post.is_published && ! post.is_scheduled).length)

const current = computed(() => props.posts.find((post) => post.id === editing.value) ?? null)

const startCreate = () => {
    editing.value = null
    creating.value = true
    advanced.value = false
    form.defaults(blank())
    form.reset()
    form.clearErrors()
}

const startEdit = (post) => {
    creating.value = false
    editing.value = post.id
    advanced.value = false
    form.clearErrors()

    Object.assign(form, {
        title: post.title,
        category: post.category ?? '',
        excerpt: post.excerpt ?? '',
        body: post.body ?? '',
        cover: null,
        cover_alt: post.cover_alt ?? '',
        author: post.author ?? '',
        meta_title: post.meta_title ?? '',
        meta_description: post.meta_description ?? '',
        is_published: post.is_published,
        published_at: post.published_at ?? '',
    })
}

const cancel = () => {
    creating.value = false
    editing.value = null
}

const submit = () => {
    const options = { preserveScroll: true, forceFormData: true, onSuccess: cancel }

    creating.value
        ? form.post('/admin/ratgeber', options)
        : form.post(`/admin/ratgeber/${editing.value}`, options)
}

const removeCover = async () => {
    const ok = await confirm({
        title: 'Bild entfernen?',
        message: 'Der Beitrag bleibt bestehen, nur das Bild wird gelöscht.',
        confirmLabel: 'Entfernen',
        tone: 'danger',
    })

    if (ok) useForm({}).delete(`/admin/ratgeber/${editing.value}/bild`, { preserveScroll: true })
}

const remove = async (post) => {
    const ok = await confirm({
        title: `„${post.title}“ löschen?`,
        message: 'Die Seite ist danach nicht mehr erreichbar. Wenn sie bei Google indexiert ist, '
            + 'liefert sie künftig einen Fehler.',
        confirmLabel: 'Löschen',
        tone: 'danger',
    })

    if (ok) useForm({}).delete(`/admin/ratgeber/${post.id}`, { preserveScroll: true })
}

/** What the row says about where an article stands. */
const state = (post) => {
    if (post.is_scheduled) return { status: 'pending', label: `Geplant für ${post.published_at}` }
    if (post.is_published) return { status: 'approved', label: 'Veröffentlicht' }

    return { status: 'closed', label: 'Entwurf' }
}
</script>

<template>
    <Head title="Ratgeber" />

    <AdminLayout title="Ratgeber">
        <PageHeader title="Ratgeber">
            <template #description>
                Beiträge für dkgz.de/ratgeber. Die Adresse baut sich aus dem Titel — Sie tragen
                keinen Link ein. Ein Beitrag mit einem Datum in der Zukunft erscheint erst dann.
            </template>
            <template v-if="canEdit" #actions>
                <BaseButton size="compact" @click="startCreate">
                    <Plus :size="16" :stroke-width="1.75" aria-hidden="true" />
                    Beitrag schreiben
                </BaseButton>
            </template>
        </PageHeader>

        <p class="pb-5 text-sm text-gray-600">
            <span class="font-mono tabular-nums text-navy-700">{{ posts.length }}</span> Beiträge ·
            <span class="font-mono tabular-nums text-navy-700">{{ published }}</span> veröffentlicht
        </p>

        <!-- ── The form, for a new article or an existing one ── -->
        <form
            v-if="creating || editing !== null"
            class="mb-6 border border-gray-200 bg-white p-6"
            novalidate
            @submit.prevent="submit"
        >
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-5" />

            <div class="flex flex-col gap-5">
                <BaseInput
                    v-model="form.title"
                    label="Titel"
                    hint="Wird zur Überschrift und zur Adresse der Seite."
                    :error="form.errors.title"
                    required
                />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseSelect
                        v-model="form.category"
                        label="Kategorie"
                        :options="categoryOptions"
                        placeholder="Ohne Kategorie"
                        empty-allowed
                        :error="form.errors.category"
                        optional
                    />
                    <BaseInput
                        v-model="form.author"
                        label="Autor"
                        placeholder="DKGZ-Redaktion"
                        :error="form.errors.author"
                        optional
                    />
                </div>

                <BaseTextarea
                    v-model="form.excerpt"
                    label="Anrisstext"
                    hint="Die zwei Zeilen in der Übersicht und bei Google. Ohne Angabe wird der Anfang des Textes genommen."
                    :error="form.errors.excerpt"
                    optional
                />

                <BaseTextarea
                    v-model="form.body"
                    label="Text"
                    :rows="16"
                    hint="HTML ist erlaubt: <h2> für Zwischenüberschriften, <p> für Absätze, <ul><li> für Listen."
                    :error="form.errors.body"
                />

                <!--
                    The picture, with its own alt text beside it. Uploading
                    re-encodes it and strips the EXIF block, so an article
                    photograph never publishes where it was taken.
                -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                    <div>
                        <p class="pb-2 text-sm font-medium text-gray-800">Bild <span class="font-normal text-gray-400">optional</span></p>
                        <input
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp"
                            class="w-full text-sm text-gray-600 file:mr-3 file:rounded-sm file:border file:border-gray-300 file:bg-white file:px-3 file:py-2 file:text-sm file:text-navy-700"
                            @input="form.cover = $event.target.files[0] ?? null"
                        >
                        <p v-if="form.errors.cover" class="pt-1.5 text-xs text-danger">{{ form.errors.cover }}</p>

                        <div v-if="current?.cover_url" class="flex items-center gap-3 pt-3">
                            <img :src="current.cover_url" alt="" class="h-14 w-20 rounded-sm border border-gray-200 object-cover">
                            <button
                                type="button"
                                class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-danger"
                                @click="removeCover"
                            >
                                <Trash2 :size="14" :stroke-width="1.5" aria-hidden="true" />
                                Entfernen
                            </button>
                        </div>
                    </div>

                    <BaseInput
                        v-model="form.cover_alt"
                        label="Bildbeschreibung"
                        hint="Für Screenreader und für Google."
                        :error="form.errors.cover_alt"
                        optional
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[200px_minmax(0,1fr)] sm:items-end">
                    <BaseInput
                        v-model="form.published_at"
                        label="Datum"
                        type="date"
                        hint="Leer heißt: heute."
                        :error="form.errors.published_at"
                        optional
                    />
                    <BaseToggle
                        v-model="form.is_published"
                        label="Veröffentlicht"
                        on-label="Sichtbar"
                        off-label="Entwurf"
                    />
                </div>

                <!--
                    Folded away, because the page works without either: the
                    title and the anrisstext already answer Google. This is for
                    the one article somebody wants to word differently there.
                -->
                <div class="border-t border-gray-200 pt-5">
                    <button
                        type="button"
                        class="text-sm text-gray-600 underline underline-offset-2 hover:text-navy-700"
                        @click="advanced = ! advanced"
                    >{{ advanced ? 'Google-Angaben ausblenden' : 'Google-Angaben anpassen' }}</button>

                    <div v-if="advanced" class="flex flex-col gap-4 pt-4">
                        <BaseInput
                            v-model="form.meta_title"
                            label="Seitentitel für Google"
                            hint="Ohne Angabe wird der Titel des Beitrags genommen."
                            :error="form.errors.meta_title"
                            optional
                        />
                        <BaseTextarea
                            v-model="form.meta_description"
                            label="Beschreibung für Google"
                            hint="Ohne Angabe wird der Anrisstext genommen."
                            :error="form.errors.meta_description"
                            optional
                        />
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-6">
                <BaseButton type="submit" size="compact" :loading="form.processing">
                    {{ creating ? 'Anlegen' : 'Speichern' }}
                </BaseButton>
                <BaseButton type="button" variant="ghost" size="compact" @click="cancel">Abbrechen</BaseButton>
            </div>
        </form>

        <EmptyState v-if="! posts.length" title="Noch keine Beiträge" :icon="BookOpen" />

        <div v-else class="flex flex-col gap-3">
            <section v-for="post in posts" :key="post.id" class="border border-gray-200 bg-white">
                <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                    <div class="flex min-w-0 gap-4">
                        <img
                            v-if="post.cover_url"
                            :src="post.cover_url"
                            alt=""
                            class="hidden h-16 w-24 shrink-0 rounded-sm border border-gray-200 object-cover sm:block"
                        >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-base font-medium text-navy-700">{{ post.title }}</h2>
                                <StatusDot v-bind="state(post)" />
                            </div>
                            <p v-if="post.excerpt" class="measure pt-1.5 text-sm leading-normal text-gray-600">
                                {{ post.excerpt }}
                            </p>
                            <p class="pt-2 font-mono text-xs text-gray-400">
                                {{ post.category ?? 'ohne Kategorie' }} · {{ post.published_at ?? 'ohne Datum' }} ·
                                {{ post.reading_minutes }} Min. · /ratgeber/{{ post.slug }}
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <a
                            v-if="post.is_published && ! post.is_scheduled"
                            :href="post.url"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-navy-700"
                        >
                            <ExternalLink :size="14" :stroke-width="1.5" aria-hidden="true" />
                            Ansehen
                        </a>
                        <BaseButton v-if="canEdit" variant="secondary" size="small" @click="startEdit(post)">Bearbeiten</BaseButton>
                        <BaseButton v-if="canEdit" variant="ghost" size="small" @click="remove(post)">Löschen</BaseButton>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
