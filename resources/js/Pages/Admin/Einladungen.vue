<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { UserPlus } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import DataTable from '../../Components/Data/DataTable.vue'
import StatusDot from '../../Components/Data/StatusDot.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import { useConfirm } from '../../Composables/useConfirm.js'

const props = defineProps({
    invitations: { type: Object, required: true },
    can: { type: Object, default: () => ({}) },
    enabled: { type: Boolean, default: true },
})

const { date } = useGermanFormat()
const { confirm } = useConfirm()

const createOpen = ref(false)
const form = useForm({ email: '', role: 'assessor', message: '' })

const columns = [
    { key: 'email', label: 'E-Mail', mono: true, cardRole: 'primary' },
    { key: 'status', label: 'Status', cardRole: 'status' },
    { key: 'invited_by', label: 'Eingeladen von', cardRole: 'meta' },
    { key: 'expires_at', label: 'Gültig bis', mono: true, cardRole: 'meta' },
    { key: 'aktionen', label: '', align: 'right' },
]

const resend = (id) => useForm({}).post(`/admin/einladungen/${id}/erneut`, { preserveScroll: true })

const revoke = async (row) => {
    const ok = await confirm({
        title: 'Einladung zurückziehen?',
        message: `Der Link an ${row.email} wird sofort ungültig.`,
        confirmLabel: 'Zurückziehen',
        tone: 'danger',
    })

    if (ok) useForm({}).delete(`/admin/einladungen/${row.id}`, { preserveScroll: true })
}
</script>

<template>
    <Head title="Einladungen" />

    <AdminLayout title="Einladungen">
        <PageHeader title="Einladungen" description="Ein eingeladener Partner ist bereits geprüft und startet freigegeben.">
            <template #actions>
                <BaseButton v-if="can.create && enabled" size="compact" @click="createOpen = !createOpen">Einladung senden</BaseButton>
            </template>
        </PageHeader>

        <div v-if="!enabled" class="mb-6 border border-warning bg-warning/5 p-4">
            <p class="text-sm text-gray-800">Einladungen sind derzeit über die Funktionseinstellungen deaktiviert.</p>
        </div>

        <form v-if="createOpen" class="mb-6 border border-gray-200 bg-white p-6" novalidate @submit.prevent="form.post('/admin/einladungen', { preserveScroll: true, onSuccess: () => { createOpen = false; form.reset() } })">
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" class="mb-5" />
            <div class="flex flex-col gap-5">
                <BaseInput v-model="form.email" label="E-Mail-Adresse" type="email" :error="form.errors.email" required />
                <BaseTextarea v-model="form.message" label="Persönliche Nachricht" hint="Erscheint als Zitat in der Einladungsmail." :error="form.errors.message" optional />
            </div>
            <div class="flex gap-3 pt-5">
                <BaseButton type="submit" size="compact" :loading="form.processing">Einladung senden</BaseButton>
                <BaseButton type="button" variant="ghost" size="compact" @click="createOpen = false">Abbrechen</BaseButton>
            </div>
        </form>

        <DataTable :columns="columns" :rows="invitations.data" :meta="invitations" empty-title="Keine Einladungen" :empty-icon="UserPlus">
            <template #cell-status="{ row }">
                <StatusDot :status="row.status === 'accepted' ? 'approved' : row.status === 'expired' ? 'closed' : 'pending'" :label="row.status_label" />
            </template>
            <template #cell-expires_at="{ row }">
                <span class="font-mono text-sm tabular-nums text-gray-600">{{ date(row.expires_at) }}</span>
            </template>
            <template #cell-aktionen="{ row }">
                <span v-if="row.status === 'pending'" class="flex justify-end gap-3">
                    <button type="button" class="text-sm font-medium text-navy-700 hover:text-navy-500" @click.stop.prevent="resend(row.id)">Erneut senden</button>
                    <button type="button" class="text-sm font-medium text-gray-600 hover:text-danger" @click.stop.prevent="revoke(row)">Zurückziehen</button>
                </span>
            </template>
        </DataTable>
    </AdminLayout>
</template>
