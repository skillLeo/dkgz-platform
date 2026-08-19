<script setup>
import { reactive } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import BaseCheckbox from '../../Components/Base/BaseCheckbox.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

/**
 * The permission matrix. Every permission is individually assignable, with a
 * select-all per group. super_admin and assessor are structural and cannot be
 * recomposed — the server refuses regardless of what the UI shows.
 */
const props = defineProps({
    roles: { type: Array, default: () => [] },
    groups: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
})

const { confirm } = useConfirm()

const drafts = reactive(
    Object.fromEntries(props.roles.map((role) => [role.id, useForm({ permissions: [...role.permissions] })]))
)

const toggle = (roleId, permission) => {
    const list = drafts[roleId].permissions
    const index = list.indexOf(permission)

    if (index === -1) list.push(permission)
    else list.splice(index, 1)
}

const groupPermissions = (groupKey) => Object.keys(props.groups[groupKey].permissions)

const allInGroup = (roleId, groupKey) =>
    groupPermissions(groupKey).every((p) => drafts[roleId].permissions.includes(p))

const toggleGroup = (roleId, groupKey) => {
    const permissions = groupPermissions(groupKey)
    const list = drafts[roleId].permissions

    if (allInGroup(roleId, groupKey)) {
        permissions.forEach((p) => {
            const i = list.indexOf(p)
            if (i !== -1) list.splice(i, 1)
        })
    } else {
        permissions.forEach((p) => {
            if (!list.includes(p)) list.push(p)
        })
    }
}

const save = (role) => drafts[role.id].post(`/admin/rollen/${role.id}`, { preserveScroll: true })

const remove = async (role) => {
    const ok = await confirm({
        title: `Rolle „${role.name}“ löschen?`,
        message: 'Die Rolle wird dauerhaft entfernt. Sie kann nur gelöscht werden, wenn ihr kein Benutzer mehr zugeordnet ist.',
        confirmLabel: 'Rolle löschen',
        tone: 'danger',
        requireTyped: role.name,
    })

    if (ok) useForm({}).delete(`/admin/rollen/${role.id}`, { preserveScroll: true })
}
</script>

<template>
    <Head title="Rollen" />

    <AdminLayout title="Rollen und Berechtigungen">
        <PageHeader
            title="Rollen und Berechtigungen"
            description="Eine Rolle ist ein Bündel von Berechtigungen. Jede Berechtigung ist einzeln zuweisbar."
        />

        <div class="flex flex-col gap-6">
            <section v-for="role in roles" :key="role.id" class="border border-gray-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 p-5">
                    <div class="min-w-0">
                        <h2 class="text-h4 font-semibold text-navy-700">{{ role.label }}</h2>
                        <p class="pt-1 font-mono text-xs text-gray-400">
                            {{ role.name }} · {{ role.users_count }} Benutzer · {{ role.permissions.length }} Berechtigungen
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <span v-if="role.protected" class="text-xs text-gray-400">Systemrolle, nicht änderbar</span>
                        <BaseButton v-if="role.can_edit" size="small" :loading="drafts[role.id].processing" @click="save(role)">Speichern</BaseButton>
                        <BaseButton v-if="role.can_delete" variant="ghost" size="small" @click="remove(role)">Löschen</BaseButton>
                    </div>
                </div>

                <div v-if="drafts[role.id].errors.permissions" class="border-b border-danger bg-danger/4 p-4">
                    <p class="text-sm text-danger">{{ drafts[role.id].errors.permissions }}</p>
                </div>

                <div class="grid grid-cols-1 gap-6 p-5 md:grid-cols-2 xl:grid-cols-3">
                    <fieldset v-for="(group, groupKey) in groups" :key="groupKey" :disabled="!role.can_edit">
                        <div class="flex items-center justify-between gap-3 pb-3">
                            <SectionLabel :text="group.label" tone="muted" :with-rule="false" />
                            <button
                                v-if="role.can_edit"
                                type="button"
                                class="shrink-0 text-xs font-medium text-navy-700 hover:text-navy-500"
                                @click="toggleGroup(role.id, groupKey)"
                            >{{ allInGroup(role.id, groupKey) ? 'Keine' : 'Alle' }}</button>
                        </div>
                        <div class="flex flex-col gap-2.5">
                            <BaseCheckbox
                                v-for="(label, permission) in group.permissions"
                                :key="permission"
                                :model-value="drafts[role.id].permissions.includes(permission)"
                                :disabled="!role.can_edit"
                                @update:model-value="toggle(role.id, permission)"
                            >{{ label }}</BaseCheckbox>
                        </div>
                    </fieldset>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
