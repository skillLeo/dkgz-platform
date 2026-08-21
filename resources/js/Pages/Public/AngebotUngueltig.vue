<script setup>
import { Head } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

/**
 * An offer link that matches nothing.
 *
 * Says the link is unusable without saying whether it ever existed — a page
 * that distinguished the two would let anyone test tokens.
 */
defineProps({
    reason: { type: String, default: 'unbekannt' },
})
</script>

<template>
    <PublicLayout>
        <Head title="Link nicht gültig" />

        <div class="mx-auto w-full max-w-(--container-shell) px-4 py-16 md:px-6 md:py-24">
            <div class="mx-auto max-w-lg">
                <p class="text-eyebrow font-semibold uppercase text-gray-600">Auftragsangebot</p>
                <h1 class="text-balance pt-2 text-h1 font-bold leading-tight text-navy-700">
                    Dieser Link ist nicht mehr gültig.
                </h1>
                <p class="measure pt-4 leading-relaxed text-gray-800">
                    Möglicherweise wurde der Auftrag inzwischen vergeben oder der Link ist abgelaufen.
                    Wenn Sie als Sachverständiger mit uns arbeiten möchten, können Sie sich jederzeit
                    registrieren.
                </p>
                <div class="flex flex-col gap-3 pt-8 sm:flex-row">
                    <BaseButton size="cta" href="/registrieren" class="w-full sm:w-auto">
                        Als Partner registrieren
                    </BaseButton>
                    <BaseButton variant="secondary" size="cta" href="/" class="w-full sm:w-auto">
                        Zur Startseite
                    </BaseButton>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
