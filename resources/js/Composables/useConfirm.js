import { reactive, readonly } from 'vue'

/**
 * One shared dialog instance, mounted once per layout. Irreversible actions ask
 * the operator to type a confirmation word before the button becomes active.
 */
const state = reactive({
    open: false,
    title: '',
    message: '',
    confirmLabel: 'Bestätigen',
    cancelLabel: 'Abbrechen',
    tone: 'default',
    requireTyped: null,
    resolve: null,
})

export function useConfirm() {
    const confirm = (options = {}) => {
        state.title = options.title ?? 'Sind Sie sicher?'
        state.message = options.message ?? ''
        state.confirmLabel = options.confirmLabel ?? 'Bestätigen'
        state.cancelLabel = options.cancelLabel ?? 'Abbrechen'
        state.tone = options.tone ?? 'default'
        state.requireTyped = options.requireTyped ?? null
        state.open = true

        return new Promise((resolve) => {
            state.resolve = resolve
        })
    }

    const settle = (result) => {
        state.open = false
        state.resolve?.(result)
        state.resolve = null
    }

    return {
        state: readonly(state),
        confirm,
        accept: () => settle(true),
        cancel: () => settle(false),
    }
}
