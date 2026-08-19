import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Mirrors the permission list shared by HandleInertiaRequests.
 *
 * This only hides controls the user cannot operate. Every route and every
 * policy enforces independently on the server — hiding a button is never the
 * security control.
 */
export function usePermissions() {
    const page = usePage()

    const permissions = computed(() => page.props.auth?.permissions ?? [])
    const roles = computed(() => page.props.auth?.roles ?? [])
    const user = computed(() => page.props.auth?.user ?? null)

    const can = (permission) => {
        if (!permission) return true
        if (Array.isArray(permission)) return permission.some((p) => permissions.value.includes(p))

        return permissions.value.includes(permission)
    }

    const canAll = (list) => list.every((permission) => permissions.value.includes(permission))

    const hasRole = (role) => {
        if (Array.isArray(role)) return role.some((r) => roles.value.includes(r))

        return roles.value.includes(role)
    }

    const isAssessor = computed(() => user.value?.is_assessor === true)
    const isStaff = computed(() => user.value !== null && !isAssessor.value)

    return { permissions, roles, user, can, canAll, hasRole, isAssessor, isStaff }
}
