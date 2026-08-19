<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Users } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import BasePasswordInput from '../../Components/Base/BasePasswordInput.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    roles: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
})

const { dateTime } = useGermanFormat()
const createOpen = ref(false)

const form = useForm({
    first_name: '', last_name: '', email: '',
    password: '', password_confirmation: '', role: '',
})

const columns = [
    { key: 'name', label: 'Name', cardRole: 'primary' },
    { key: 'email', label: 'E-Mail', cardRole: 'meta' },
    { key: 'roles', label: 'Rolle', cardRole: 'meta' },
    { key: 'is_active', label: 'Status', cardRole: 'status' },
    { key: 'last_login_at', label: 'Letzte Anmeldung', mono: true },
]

const roleOptions = props.roles.map((r) => ({ value: r, label: r }))
</script>

<template>
    <Head title="Benutzer" />

    <AdminLayout title="Benutzer">
        <PageHeader title="Benutzer" description="Konten der Administration. Sachverständige werden unter Partner verwaltet.">
            <template #actions>
                <BaseButton v-if="can.create" size="compact" @click="createOpen = !createOpen">Benutzer anlegen</BaseButton>
            </template>
        </PageHeader>

        <form v-if="createOpen" class="mb-6 border border-gray-200 bg-white p-6" novalidate @submit.prevent="form.post('/admin/benutzer', { preserveScroll: true, onSuccess: () => { createOpen = false; form.reset() } })">
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" class="mb-5" />
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <BaseInput v-model="form.first_name" label="Vorname" :error="form.errors.first_name" required />
                <BaseInput v-model="form.last_name" label="Nachname" :error="form.errors.last_name" required />
                <BaseInput v-model="form.email" label="E-Mail-Adresse" type="email" :error="form.errors.email" required />
                <BaseSelect v-model="form.role" label="Rolle" :options="roleOptions" :error="form.errors.role" required />
                <BasePasswordInput v-model="form.password" label="Passwort" autocomplete="new-password" :error="form.errors.password" show-meter show-checklist required />
                <BasePasswordInput v-model="form.password_confirmation" label="Passwort wiederholen" autocomplete="new-password" :error="form.errors.password_confirmation" required />
            </div>
            <div class="flex gap-3 pt-5">
                <BaseButton type="submit" size="compact" :loading="form.processing">Anlegen</BaseButton>
                <BaseButton type="button" variant="ghost" size="compact" @click="createOpen = false">Abbrechen</BaseButton>
            </div>
        </form>

        <DataTable :columns="columns" :rows="users.data" :meta="users" empty-title="Keine Benutzer" :empty-icon="Users">
            <template #cell-roles="{ row }">
                <span class="text-sm text-gray-800">{{ row.roles.join(', ') }}</span>
            </template>
            <template #cell-is_active="{ row }">
                <StatusDot :status="row.is_active ? 'approved' : 'closed'" :label="row.is_active ? 'Aktiv' : 'Deaktiviert'" />
            </template>
            <template #cell-last_login_at="{ row }">
                <span class="font-mono text-sm tabular-nums text-gray-600">{{ row.last_login_at ? dateTime(row.last_login_at) : 'nie' }}</span>
            </template>
        </DataTable>
    </AdminLayout>
</template>
