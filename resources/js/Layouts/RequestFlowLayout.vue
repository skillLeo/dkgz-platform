<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ChevronLeft, X } from 'lucide-vue-next'
import DkgzMark from '../Components/Layout/DkgzMark.vue'
import FlashMessage from '../Components/Feedback/FlashMessage.vue'
import ConfirmDialog from '../Components/Feedback/ConfirmDialog.vue'
import ToastStack from '../Components/Feedback/ToastStack.vue'
import CookieConsent from '../Components/Feedback/CookieConsent.vue'
import { useConfirm } from '../Composables/useConfirm.js'

/**
 * The shell for the request itself: logo, a way back, a way out, and nothing
 * else. No navigation, no footer, no announcement bar — everything that would
 * invite the visitor to wander off mid-form is removed on purpose.
 *
 * The form is one page by design, so there is no stepper here; a numbered
 * progress indicator over a single step would be theatre. See DECISIONS.md D-17.
 */
const props = defineProps({
    title: { type: String, required: true },
    label: { type: String, default: 'Ihre Anfrage' },
    backHref: { type: String, default: null },
    /** Warn before leaving when the visitor has typed something. */
    dirty: { type: Boolean, default: false },
})

const { confirm } = useConfirm()
const leaving = ref(false)

const exit = async () => {
    if (!props.dirty) {
        router.visit('/')

        return
    }

    const ok = await confirm({
        title: 'Anfrage verwerfen?',
        message: 'Ihre Eingaben gehen dabei verloren. Die Anfrage wurde noch nicht abgesendet.',
        confirmLabel: 'Verwerfen',
        tone: 'danger',
    })

    if (ok) {
        leaving.value = true
        router.visit('/')
    }
}

/** The browser's own guard, for a closed tab rather than our exit button. */
const guard = (event) => {
    if (!props.dirty || leaving.value) return

    event.preventDefault()
    event.returnValue = ''
}

onMounted(() => window.addEventListener('beforeunload', guard))
onBeforeUnmount(() => window.removeEventListener('beforeunload', guard))
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-svh flex-col bg-gray-50">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex h-18 w-full max-w-(--container-wide) items-center gap-4 px-4 md:px-6">
                <Link
                    v-if="backHref"
                    :href="backHref"
                    class="-ml-2 grid h-11 w-11 shrink-0 place-items-center rounded-sm text-gray-600 hover:text-navy-700"
                    aria-label="Zurück"
                >
                    <ChevronLeft :size="20" :stroke-width="1.5" aria-hidden="true" />
                </Link>

                <Link href="/" class="shrink-0" aria-label="Zur Startseite">
                    <DkgzMark size="sm" :with-subtitle="false" />
                </Link>

                <span class="ml-auto hidden text-sm text-gray-600 sm:block">{{ label }}</span>

                <button
                    type="button"
                    class="-mr-2 ml-auto grid h-11 w-11 shrink-0 place-items-center rounded-sm text-gray-600 hover:text-navy-700 sm:ml-4"
                    aria-label="Anfrage abbrechen"
                    @click="exit"
                >
                    <X :size="20" :stroke-width="1.5" aria-hidden="true" />
                </button>
            </div>
        </header>

        <main class="flex-1">
            <FlashMessage class="mx-auto mt-6 w-full max-w-(--container-wide) px-4 md:px-6" />
            <slot />
        </main>

        <ConfirmDialog />
        <ToastStack />
        <CookieConsent />
    </div>
</template>
