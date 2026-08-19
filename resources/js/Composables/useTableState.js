import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Keeps sort, filters and pagination in the URL so a table view is shareable
 * and survives a reload. Everything is server-side.
 */
export function useTableState(initial = {}, { only = [] } = {}) {
    const params = new URLSearchParams(window.location.search)

    const state = reactive({
        suche: params.get('suche') ?? initial.suche ?? '',
        sort: params.get('sort') ?? initial.sort ?? null,
        direction: params.get('direction') ?? initial.direction ?? 'desc',
        ...Object.fromEntries(
            Object.entries(initial.filters ?? {}).map(([key, value]) => [key, params.get(key) ?? value])
        ),
    })

    const query = () =>
        Object.fromEntries(Object.entries(state).filter(([, value]) => value !== null && value !== '' && value !== undefined))

    const push = () => {
        router.get(window.location.pathname, query(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only,
        })
    }

    const sortBy = (key) => {
        state.direction = state.sort === key && state.direction === 'asc' ? 'desc' : 'asc'
        state.sort = key
        push()
    }

    const reset = () => {
        Object.keys(state).forEach((key) => {
            state[key] = key === 'direction' ? 'desc' : null
        })
        push()
    }

    let debounce = null
    watch(() => state.suche, () => {
        if (debounce) clearTimeout(debounce)
        debounce = setTimeout(push, 300)
    })

    return { state, query, push, sortBy, reset }
}
