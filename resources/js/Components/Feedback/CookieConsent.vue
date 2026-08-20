<script setup>
import { onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BaseButton from '../Base/BaseButton.vue'

/**
 * Appears only when an analytics ID is configured.
 *
 * Essential cookies — session and CSRF — need no consent under the TDDDG, and a
 * banner asking permission for something that requires none trains people to
 * dismiss banners without reading them. So there is no banner at all until the
 * operator actually adds a tracker.
 */
const KEY = 'dkgz-cookie-consent'

const page = usePage()
const visible = ref(false)

onMounted(() => {
    if (!page.props.app?.analytics_configured) return

    try {
        visible.value = window.localStorage.getItem(KEY) === null
    } catch {
        // Storage blocked: show it, but it cannot be remembered.
        visible.value = true
    }
})

const decide = (accepted) => {
    try {
        window.localStorage.setItem(KEY, accepted ? 'accepted' : 'declined')
    } catch {
        // Nothing to do — the choice simply is not remembered.
    }

    visible.value = false

    if (accepted) {
        window.dispatchEvent(new CustomEvent('dkgz:analytics-consent'))
    }
}
</script>

<template>
    <div
        v-if="visible"
        class="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white p-4 shadow-2 md:p-5"
        role="dialog"
        aria-labelledby="cookie-titel"
        aria-describedby="cookie-text"
    >
        <div class="mx-auto flex max-w-(--container-wide) flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
                <p id="cookie-titel" class="text-base font-medium text-navy-700">Statistik-Cookies</p>
                <p id="cookie-text" class="measure pt-1 text-sm leading-normal text-gray-600">
                    Wir möchten anonym messen, wie diese Seite genutzt wird. Für den Betrieb notwendige Cookies
                    setzen wir ohnehin — dafür ist keine Zustimmung nötig. Sie können jederzeit widersprechen.
                    <a href="/datenschutz" class="font-medium text-navy-700 underline underline-offset-2">Datenschutzerklärung</a>
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-3">
                <BaseButton variant="secondary" size="compact" @click="decide(false)">Nur notwendige</BaseButton>
                <BaseButton size="compact" @click="decide(true)">Einverstanden</BaseButton>
            </div>
        </div>
    </div>
</template>
