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
    /**
     * Nothing to decide — the panel is showing something, not asking.
     *
     * The dialog already handles the escape key, the focus, the backdrop and the
     * sheet it becomes on a phone. An explanation needs all of that and none of
     * the choice, so it borrows the panel and loses the second button.
     */
    dismissOnly: false,
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
        state.dismissOnly = options.dismissOnly ?? false
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
