import { onMounted, onUnmounted, ref } from 'vue'

/**
 * In-portal notifications, per BUILD_SPEC: no websockets on shared hosting, so
 * a lightweight endpoint is polled every 45 seconds. Polling pauses while the
 * tab is hidden so a backgrounded tab costs the server nothing.
 */
export function usePolling(url, { interval = 45000, immediate = true } = {}) {
    const count = ref(0)
    const items = ref([])
    const polling = ref(false)
    const failures = ref(0)

    let timer = null

    const fetchOnce = async () => {
        if (document.hidden || polling.value) return

        polling.value = true

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })

            if (!response.ok) throw new Error(String(response.status))

            const data = await response.json()
            count.value = data.count ?? 0
            items.value = data.items ?? []
            failures.value = 0
        } catch {
            // Back off after repeated failures rather than hammering a host
            // that is already struggling.
            failures.value += 1
        } finally {
            polling.value = false
        }
    }

    const schedule = () => {
        stop()
        const backoff = Math.min(failures.value, 4)
        timer = setTimeout(async () => {
            await fetchOnce()
            schedule()
        }, interval * (backoff ? 2 ** backoff : 1))
    }

    const stop = () => {
        if (timer) clearTimeout(timer)
        timer = null
    }

    const onVisibility = () => {
        if (!document.hidden) fetchOnce()
    }

    onMounted(() => {
        if (immediate) fetchOnce()
        schedule()
        document.addEventListener('visibilitychange', onVisibility)
    })

    onUnmounted(() => {
        stop()
        document.removeEventListener('visibilitychange', onVisibility)
    })

    return { count, items, refresh: fetchOnce, stop }
}
