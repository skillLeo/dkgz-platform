<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Wrench } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseToggle from '../../Components/Base/BaseToggle.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import EmptyState from '../../Components/Feedback/EmptyState.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({ serviceTypes: { type: Array, default: () => [] } })

const { confirm } = useConfirm()
const createOpen = ref(false)
const editing = ref(null)

const create = useForm({ name_de: '', description_de: '', icon: '', is_active: true })
const edit = useForm({ name_de: '', description_de: '', icon: '', is_active: true })

const startEdit = (type) => {
    editing.value = type.id
    edit.name_de = type.name_de
    edit.description_de = type.description_de ?? ''
    edit.icon = type.icon ?? ''
    edit.is_active = type.is_active
}

const remove = async (type) => {
    const ok = await confirm({
        title: `„${type.name_de}“ löschen?`,
        message: 'Eine Leistungsart lässt sich nur löschen, solange keine Anfrage sie verwendet — sonst verlöre die Historie ihre Bedeutung.',
        confirmLabel: 'Löschen',
        tone: 'danger',
    })

    if (ok) useForm({}).delete(`/admin/leistungsarten/${type.id}`, { preserveScroll: true })
}
</script>

<template>
    <Head title="Leistungsarten" />

    <AdminLayout title="Leistungsarten">
        <PageHeader title="Leistungsarten" description="Welche Gutachten über die Plattform angefragt werden können.">
            <template #actions>
                <BaseButton size="compact" @click="createOpen = !createOpen">Leistungsart anlegen</BaseButton>
            </template>
        </PageHeader>

        <form v-if="createOpen" class="mb-6 border border-gray-200 bg-white p-6" novalidate @submit.prevent="create.post('/admin/leistungsarten', { preserveScroll: true, onSuccess: () => { createOpen = false; create.reset() } })">
            <ErrorSummary v-if="create.hasErrors" :errors="create.errors" class="mb-5" />
            <div class="flex flex-col gap-5">
                <BaseInput v-model="create.name_de" label="Name" :error="create.errors.name_de" required />
                <BaseTextarea v-model="create.description_de" label="Beschreibung" hint="Erscheint auf der öffentlichen Leistungsseite." :error="create.errors.description_de" optional />
                <BaseInput v-model="create.icon" label="Symbol" hint="Name eines lucide-Symbols, z. B. file-text." optional />
            </div>
            <div class="flex gap-3 pt-5">
                <BaseButton type="submit" size="compact" :loading="create.processing">Anlegen</BaseButton>
                <BaseButton type="button" variant="ghost" size="compact" @click="createOpen = false">Abbrechen</BaseButton>
            </div>
        </form>

        <EmptyState v-if="!serviceTypes.length" title="Keine Leistungsarten" :icon="Wrench" />

        <div v-else class="flex flex-col gap-3">
            <section v-for="type in serviceTypes" :key="type.id" class="border border-gray-200 bg-white">
                <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-base font-medium text-navy-700">{{ type.name_de }}</h2>
                            <StatusDot :status="type.is_active ? 'approved' : 'closed'" :label="type.is_active ? 'Aktiv' : 'Inaktiv'" />
                        </div>
                        <p v-if="type.description_de" class="measure pt-1.5 text-sm leading-normal text-gray-600">{{ type.description_de }}</p>
                        <p class="pt-2 font-mono text-xs text-gray-400">
                            {{ type.slug }} · {{ type.requests_count }} Anfragen · {{ type.assessors_count }} Partner
                        </p>
                    </div>
                    <div class="flex shrink-0 gap-3">
                        <BaseButton variant="secondary" size="small" @click="startEdit(type)">Bearbeiten</BaseButton>
                        <BaseButton v-if="type.can_delete" variant="ghost" size="small" @click="remove(type)">Löschen</BaseButton>
                    </div>
                </div>

                <form v-if="editing === type.id" class="border-t border-gray-200 p-5" novalidate @submit.prevent="edit.post(`/admin/leistungsarten/${type.id}`, { preserveScroll: true, onSuccess: () => (editing = null) })">
                    <div class="flex flex-col gap-5">
                        <BaseInput v-model="edit.name_de" label="Name" :error="edit.errors.name_de" required />
                        <BaseTextarea v-model="edit.description_de" label="Beschreibung" :error="edit.errors.description_de" optional />
                        <BaseInput v-model="edit.icon" label="Symbol" optional />
                        <BaseToggle v-model="edit.is_active" label="Aktiv" description="Inaktive Leistungsarten erscheinen nicht im Anfrageformular." on-label="Aktiv" off-label="Inaktiv" />
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
