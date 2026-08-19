import { reactive, readonly } from 'vue'

let nextId = 1

const state = reactive({ items: [] })

/**
 * Transient confirmations for actions that do not navigate. Server-side flash
 * messages go through FlashMessage instead — this is for client-only feedback.
 */
export function useToast() {
    const push = (message, tone = 'success', timeout = 5000) => {
        const id = nextId++
        state.items.push({ id, message, tone })

        if (timeout) {
            setTimeout(() => dismiss(id), timeout)
        }

        return id
    }

    const dismiss = (id) => {
        const index = state.items.findIndex((item) => item.id === id)
        if (index !== -1) state.items.splice(index, 1)
    }

    return {
        toasts: readonly(state).items,
        toast: push,
        success: (message) => push(message, 'success'),
        error: (message) => push(message, 'error', 8000),
        info: (message) => push(message, 'info'),
        dismiss,
    }
}
