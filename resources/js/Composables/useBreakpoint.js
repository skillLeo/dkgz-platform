import { onMounted, onUnmounted, ref, computed } from 'vue'

/**
 * The single source of truth for the 768px shell switch: below it the portal
 * and admin render the native app shell, above it the sidebar shell.
 */
const MOBILE_MAX = 767

export function useBreakpoint() {
    const width = ref(typeof window === 'undefined' ? 1280 : window.innerWidth)

    const update = () => {
        width.value = window.innerWidth
    }

    onMounted(() => {
        update()
        window.addEventListener('resize', update, { passive: true })
    })

    onUnmounted(() => {
        window.removeEventListener('resize', update)
    })

    const isMobile = computed(() => width.value <= MOBILE_MAX)
    const isTablet = computed(() => width.value >= 768 && width.value < 1024)
    const isDesktop = computed(() => width.value >= 1024)

    return { width, isMobile, isTablet, isDesktop }
}
