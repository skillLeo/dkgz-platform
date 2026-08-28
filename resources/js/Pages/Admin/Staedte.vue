<script setup>
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ExternalLink, Plus } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseCheckbox from '../../Components/Base/BaseCheckbox.vue'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

/**
 * The cities that have their own pages.
 *
 * Kept short on purpose: a page for every service in every place would be
 * thousands of near-identical pages, which search engines treat as thin content
 * and rank accordingly. Ten to twenty real cities, each saying something true,
 * is worth more than all of them — so the screen shows how many pages each city
 * publishes rather than hiding the number.
 */
const props = defineProps({
    cities: { type: Array, default: () => [] },
    serviceTypes: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
})

const { confirm } = useConfirm()

const editing = ref(null)
const creating = ref(false)

const blank = () => ({
    name: '',
    state: '',
    postal_code: '',
    headline: '',
    intro: '',
    body: '',
    faqs: [],
    meta_title: '',
    meta_description: '',
    is_active: false,
    service_type_ids: [],
})

const form = useForm(blank())

const labels = {
    name: 'Name',
    postal_code: 'Postleitzahl',
    meta_description: 'Meta-Beschreibung',
    body: 'Ortstext',
}

const totalPages = computed(() => props.cities.reduce((sum, city) => sum + city.page_count, 0))

const startCreate = () => {
    editing.value = null
    creating.value = true
    form.defaults(blank())
    form.reset()
    form.clearErrors()
}

const startEdit = (city) => {
    creating.value = false
    editing.value = city.id
    form.clearErrors()

    Object.assign(form, {
        name: city.name,
        state: city.state ?? '',
        postal_code: city.postal_code ?? '',
        headline: city.headline ?? '',
        intro: city.intro ?? '',
        body: city.body ?? '',
        faqs: (city.faqs ?? []).map((entry) => ({ ...entry })),
        meta_title: city.meta_title ?? '',
        meta_description: city.meta_description ?? '',
        is_active: city.is_active,
        service_type_ids: [...city.service_type_ids],
    })
}

const submit = () => {
    const done = () => { creating.value = false; editing.value = null }

    creating.value
        ? form.post('/admin/staedte', { preserveScroll: true, onSuccess: done })
        : form.post(`/admin/staedte/${editing.value}`, { preserveScroll: true, onSuccess: done })
}

const toggleService = (id) => {
    form.service_type_ids = form.service_type_ids.includes(id)
        ? form.service_type_ids.filter((value) => value !== id)
        : [...form.service_type_ids, id]
}

const remove = async (city) => {
    const ok = await confirm({
        title: `${city.name} entfernen?`,
        message: `Die ${city.page_count} Seiten dieser Stadt sind danach nicht mehr erreichbar. `
            + 'Wenn sie bei Google indexiert sind, liefern sie künftig einen Fehler.',
        confirmLabel: 'Entfernen',
        tone: 'danger',
    })

    if (ok) useForm({}).delete(`/admin/staedte/${city.id}`, { preserveScroll: true })
}
</script>

<template>
    <Head title="Städte" />

    <AdminLayout title="Städte">
        <PageHeader title="Städte">
            <template #description>
                Für jede Stadt entsteht eine Übersichtsseite und je eine Seite pro angehakter
                Leistung. Die Adressen bauen sich aus den Namen — Sie tragen keine Links ein.
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-center justify-between gap-4 pb-5">
            <p class="text-sm text-gray-600">
                <span class="font-mono tabular-nums text-navy-700">{{ cities.length }}</span> Städte ·
                <span class="font-mono tabular-nums text-navy-700">{{ totalPages }}</span> veröffentlichte Seiten
            </p>

            <BaseButton v-if="canEdit" size="compact" @click="startCreate">
                <Plus :size="16" :stroke-width="1.75" aria-hidden="true" />
                Stadt anlegen
            </BaseButton>
        </div>

        <!-- The form, shown for a new city or the one being edited. -->
        <section v-if="creating || editing" class="mb-6 border border-navy-700 bg-white p-6">
            <SectionLabel :text="creating ? 'Neue Stadt' : 'Stadt bearbeiten'" tone="muted" :with-rule="false" />

            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mt-4" />

            <div class="flex flex-col gap-5 pt-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <BaseInput v-model="form.name" label="Stadt" placeholder="Düsseldorf" :error="form.errors.name" required />
                    <BaseInput v-model="form.state" label="Bundesland" placeholder="Nordrhein-Westfalen" :error="form.errors.state" optional />
                    <BaseInput
                        v-model="form.postal_code"
                        label="Postleitzahl"
                        placeholder="40213"
                        inputmode="numeric"
                        maxlength="5"
                        numeric
                        hint="Für die Anzahl der Partner auf der Seite."
                        :error="form.errors.postal_code"
                        optional
                    />
                </div>

                <BaseInput
                    v-model="form.headline"
                    label="Überschrift"
                    :placeholder="`Kfz-Gutachter in ${form.name || 'Düsseldorf'}`"
                    hint="Leer lassen für die automatische Überschrift."
                    :error="form.errors.headline"
                    optional
                />

                <BaseTextarea
                    v-model="form.intro"
                    label="Einleitungstext"
                    :rows="3"
                    hint="Ein bis zwei Sätze über diese Stadt. Je konkreter, desto besser findet Google die Seite."
                    :error="form.errors.intro"
                    optional
                />

                <!--
                    The part that cannot be templated, which is the part that
                    earns the ranking. Every city page carries the same three
                    steps, notes and questions underneath; this is where
                    something true about this particular place goes.
                -->
                <BaseTextarea
                    v-model="form.body"
                    label="Ortstext"
                    :rows="10"
                    hint="Der ausführliche Text zu dieser Stadt. HTML ist erlaubt: <p> für Absätze, <h3> für Zwischenüberschriften, <ul><li> für Listen. Leer lassen, um den allgemeinen Text zu verwenden."
                    :error="form.errors.body"
                    optional
                />

                <!--
                    Questions about this city specifically. They are shown
                    before the three every city page carries, not instead of
                    them: a page with no answers ranks like a page with nothing.
                -->
                <div>
                    <div class="flex items-baseline justify-between gap-4 pb-2">
                        <p class="text-sm font-medium text-gray-800">Eigene Fragen <span class="font-normal text-gray-400">optional</span></p>
                        <button type="button" class="text-sm text-navy-700 underline underline-offset-2" @click="form.faqs.push({ frage: '', antwort: '' })">Frage hinzufügen</button>
                    </div>

                    <div v-for="(entry, index) in form.faqs" :key="index" class="flex flex-col gap-2 border-t border-gray-200 py-3">
                        <BaseInput v-model="entry.frage" label="Frage" :error="form.errors[`faqs.${index}.frage`]" />
                        <BaseTextarea v-model="entry.antwort" label="Antwort" :rows="3" :error="form.errors[`faqs.${index}.antwort`]" />
                        <button type="button" class="self-start text-sm text-gray-600 hover:text-danger" @click="form.faqs.splice(index, 1)">Entfernen</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput v-model="form.meta_title" label="Meta-Titel" hint="Leer lassen für den automatischen Titel." :error="form.errors.meta_title" optional />
                    <BaseInput v-model="form.meta_description" label="Meta-Beschreibung" hint="Höchstens 320 Zeichen." :error="form.errors.meta_description" optional />
                </div>

                <div>
                    <p class="pb-3 text-sm font-medium text-gray-800">Leistungen in dieser Stadt</p>
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        <BaseCheckbox
                            v-for="type in serviceTypes"
                            :key="type.id"
                            :model-value="form.service_type_ids.includes(type.id)"
                            @update:model-value="toggleService(type.id)"
                        >{{ type.name_de }}</BaseCheckbox>
                    </div>
                    <p class="pt-2 text-sm text-gray-600">
                        Jede angehakte Leistung bekommt eine eigene Seite unter dieser Stadt.
                    </p>
                </div>

                <BaseToggle
                    v-model="form.is_active"
                    label="Seiten veröffentlicht"
                    description="Ausgeschaltet bleiben die Texte erhalten, die Seiten sind aber nicht erreichbar."
                    on-label="Öffentlich"
                    off-label="Verborgen"
                />
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-6">
                <BaseButton :loading="form.processing" @click="submit">
                    {{ creating ? 'Stadt anlegen' : 'Speichern' }}
                </BaseButton>
                <BaseButton variant="secondary" @click="creating = false; editing = null">Abbrechen</BaseButton>
            </div>
        </section>

        <div v-if="!cities.length" class="border border-gray-200 bg-white p-10 text-center">
            <p class="text-base text-gray-800">Noch keine Städte angelegt.</p>
            <p class="measure mx-auto pt-1 text-sm text-gray-600">
                Beginnen Sie mit zehn bis fünfzehn großen Städten. Wenige Seiten mit echtem Inhalt
                werden besser gefunden als viele, die sich gleichen.
            </p>
        </div>

        <div v-else class="overflow-x-auto border border-gray-200 bg-white">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Stadt</th>
                        <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Adresse</th>
                        <th scope="col" class="px-5 py-3 text-right text-eyebrow font-semibold uppercase text-gray-600">Seiten</th>
                        <th scope="col" class="px-5 py-3 text-left text-eyebrow font-semibold uppercase text-gray-600">Status</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Aktionen</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="city in cities" :key="city.id" class="border-b border-gray-100 last:border-b-0">
                        <td class="px-5 py-3.5">
                            <span class="block text-sm font-medium text-gray-800">{{ city.name }}</span>
                            <span v-if="city.state" class="block text-xs text-gray-600">{{ city.state }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <a
                                :href="city.url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1.5 font-mono text-sm text-navy-700 hover:text-navy-500"
                            >
                                {{ city.url }}
                                <ExternalLink :size="13" :stroke-width="1.5" aria-hidden="true" />
                            </a>
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-sm tabular-nums text-gray-800">
                            {{ city.page_count }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="text-sm" :class="city.is_active ? 'text-success' : 'text-gray-500'">
                                {{ city.is_active ? 'Öffentlich' : 'Verborgen' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right">
                            <template v-if="canEdit">
                                <button type="button" class="text-sm text-navy-700 hover:text-navy-500" @click="startEdit(city)">Bearbeiten</button>
                                <button type="button" class="pl-4 text-sm text-gray-600 hover:text-danger" @click="remove(city)">Entfernen</button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
