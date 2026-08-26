<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ChevronLeft, ShieldCheck, X } from 'lucide-vue-next'
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
 * The step indicator goes in the progress slot when there is more than one
 * step to be in; a page that is the whole request does not get one, because a
 * progress bar over a single step is theatre. See DECISIONS.md D-17.
 */
const props = defineProps({
    title: { type: String, required: true },
    /** The reassurance beside the mark in the header. */
    label: { type: String, default: 'Kostenfrei & unverbindlich' },
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

                <!--
                    The seal, not just the word. This header is the only thing
                    on screen while somebody types their telephone number into a
                    site they reached from an advert ten seconds ago, so it is
                    where the mark has to be doing its work.
                -->
                <Link href="/" class="shrink-0" aria-label="Zur Startseite">
                    <DkgzMark size="sm" :with-subtitle="false" />
                </Link>

                <!--
                    A reassurance rather than a way out. Somebody is about to
                    type their telephone number into a site they met ten seconds
                    ago; the useful thing to put in front of them is why that is
                    safe, not an arrow inviting them to leave. Going back a step
                    is offered beside the answer it would change, which is where
                    somebody looks for it.
                -->
                <span class="ml-auto flex shrink-0 items-center gap-2 text-sm text-gray-600">
                    <ShieldCheck :size="22" :stroke-width="1.75" class="shrink-0" style="color: var(--dkgz-accent)" aria-hidden="true" />
                    <span class="hidden sm:inline">{{ label }}</span>
                </span>

                <button
                    type="button"
                    class="-mr-2 ml-3 grid h-11 w-11 shrink-0 place-items-center rounded-sm text-gray-600 hover:text-navy-700 sm:ml-4"
                    aria-label="Anfrage abbrechen"
                    @click="exit"
                >
                    <X :size="20" :stroke-width="1.5" aria-hidden="true" />
                </button>
            </div>
        </header>

        <!--
            A band under the header for the step indicator. It belongs to the
            shell rather than the form card: it describes where the visitor is
            in the whole process, and reads as a property of the page rather
            than of one box on it.
        -->
        <div v-if="$slots.progress" class="border-b border-gray-200 bg-white">
            <div class="mx-auto w-full max-w-(--container-wide) px-4 py-4 md:px-6">
                <slot name="progress" />
            </div>
        </div>

        <main class="flex-1">
            <FlashMessage class="mx-auto mt-6 w-full max-w-(--container-wide) px-4 md:px-6" />
            <slot />
        </main>

        <ConfirmDialog />
        <ToastStack />
        <CookieConsent />
    </div>
</template>
