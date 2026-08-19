<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

const props = defineProps({
    template: { type: Object, required: true },
    variables: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    canTest: { type: Boolean, default: false },
    previewUrl: { type: String, required: true },
})

const form = useForm({ ...props.template })
const test = useForm({ email: '' })
const previewKey = ref(0)

const save = () => form.post(`/admin/email-vorlagen/${props.template.key}`, {
    preserveScroll: true,
    onSuccess: () => { previewKey.value += 1 },
})

// Built at runtime so the double braces never reach Vue's template parser.
const placeholder = (variable) => `${'{{'} ${variable} ${'}}'}`

const insert = (variable) => {
    form.body_html += placeholder(variable)
}
</script>

<template>
    <Head :title="template.name_de" />

    <AdminLayout :title="template.name_de" back-href="/admin/email-vorlagen">
        <PageHeader :title="template.name_de" :eyebrow="template.key" />

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_640px]">
            <div class="flex flex-col gap-6">
                <form class="border border-gray-200 bg-white p-6" novalidate @submit.prevent="save">
                    <ErrorSummary v-if="form.hasErrors" :errors="form.errors" class="mb-6" />

                    <div class="flex flex-col gap-5">
                        <BaseInput v-model="form.name_de" label="Name der Vorlage" :error="form.errors.name_de" :disabled="!canEdit" required />
                        <BaseInput
                            v-model="form.subject_de"
                            label="Betreff"
                            hint="Höchstens 60 Zeichen sind in den meisten Programmen sichtbar."
                            :error="form.errors.subject_de"
                            :disabled="!canEdit"
                            required
                        />
                        <BaseInput
                            v-model="form.preheader_de"
                            label="Vorschautext"
                            hint="Erscheint in der Nachrichtenliste hinter dem Betreff. 40 bis 90 Zeichen."
                            :error="form.errors.preheader_de"
                            :disabled="!canEdit"
                            optional
                        />
                        <BaseTextarea
                            v-model="form.body_html"
                            label="Inhalt"
                            hint="Absätze in <p>…</p>. Kopfband, Datenblock und Fußzeile kommen aus dem Baustein."
                            :rows="12"
                            :error="form.errors.body_html"
                            :disabled="!canEdit"
                            required
                        />
                        <BaseToggle v-model="form.is_active" label="Vorlage aktiv" description="Ist sie inaktiv, greift die eingebaute Ersatzvorlage." :disabled="!canEdit" on-label="Aktiv" off-label="Inaktiv" />
                    </div>

                    <BaseButton v-if="canEdit" type="submit" class="mt-6" :loading="form.processing">Vorlage speichern</BaseButton>
                </form>

                <section class="border border-gray-200 bg-white p-6">
                    <SectionLabel text="Platzhalter" tone="muted" />
                    <p class="pb-4 pt-2 text-sm text-gray-600">
                        Diese Platzhalter füllt das System beim Versand. Klicken, um sie in den Inhalt einzufügen.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="variable in variables"
                            :key="variable"
                            type="button"
                            class="rounded-sm border border-gray-200 px-2.5 py-1 font-mono text-xs text-gray-800 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:border-navy-700 hover:text-navy-700"
                            :disabled="!canEdit"
                            @click="insert(variable)"
                        >{{ placeholder(variable) }}</button>
                        <p v-if="!variables.length" class="text-sm text-gray-400">Für diese Vorlage sind keine Platzhalter hinterlegt.</p>
                    </div>
                </section>

                <form
                    v-if="canTest"
                    class="border border-gray-200 bg-white p-6"
                    novalidate
                    @submit.prevent="test.post(`/admin/email-vorlagen/${template.key}/test`, { preserveScroll: true })"
                >
                    <SectionLabel text="Testversand" tone="muted" />
                    <p class="pb-4 pt-2 text-sm text-gray-600">Sendet diese Vorlage mit Beispieldaten an eine Adresse Ihrer Wahl.</p>
                    <div class="flex flex-wrap items-end gap-3">
                        <BaseInput v-model="test.email" label="Empfänger" type="email" :error="test.errors.email" class="min-w-64 flex-1" />
                        <BaseButton type="submit" :loading="test.processing" loading-label="Wird gesendet…">Test senden</BaseButton>
                    </div>
                </form>
            </div>

            <!-- Live preview, rendered by the server in the real mail layout -->
            <div class="xl:sticky xl:top-6 xl:self-start">
                <SectionLabel text="Vorschau" tone="muted" />
                <div class="mt-3 overflow-hidden border border-gray-200 bg-gray-100">
                    <iframe
                        :key="previewKey"
                        :src="previewUrl"
                        title="Vorschau der E-Mail"
                        class="h-(--size-mail-preview) w-full border-0 bg-gray-100"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
