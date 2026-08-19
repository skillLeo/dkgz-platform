import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Per-step autosave for the four-step registration. Saves on a debounce and
 * reports "Automatisch gespeichert · 14:32" the way the design specifies —
 * 12.5px mono, gray-400.
 */
export function useAutosave(source, url, { delay = 1500, method = 'post' } = {}) {
    const saving = ref(false)
    const savedAt = ref(null)
    const failed = ref(false)

    let timer = null
    let firstRun = true

    const save = () => {
        saving.value = true
        failed.value = false

        router[method](url, typeof source === 'function' ? source() : source.value, {
            preserveScroll: true,
            preserveState: true,
            only: [],
            onSuccess: () => { savedAt.value = new Date() },
            onError: () => { failed.value = true },
            onFinish: () => { saving.value = false },
        })
    }

    watch(
        typeof source === 'function' ? source : source,
        () => {
            // Never fire on the initial hydration of the form.
            if (firstRun) {
                firstRun = false

                return
            }

            if (timer) clearTimeout(timer)
            timer = setTimeout(save, delay)
        },
        { deep: true }
    )

    return { saving, savedAt, failed, saveNow: save }
}
