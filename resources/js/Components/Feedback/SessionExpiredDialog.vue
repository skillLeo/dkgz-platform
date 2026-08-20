<script setup>
import { Clock } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import BaseButton from '../Base/BaseButton.vue'

/**
 * Auth Screens 11. The only surface in the product allowed a blur: the
 * application stays visible behind the dialog at 40 % opacity and 4px blur, so
 * the operator can see their work is still there while being told to sign in
 * again.
 */
defineProps({
    open: { type: Boolean, default: false },
    loginUrl: { type: String, default: '/anmelden' },
})
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-100">
            <!-- The application behind, blurred and dimmed -->
            <div class="absolute inset-0 bg-navy-900/32 backdrop-blur-(--blur-dialog)" aria-hidden="true" />

            <div class="absolute inset-0 grid place-items-center p-5">
                <div
                    class="w-full max-w-(--container-dialog) rounded-card bg-white p-8 shadow-(--shadow-3)"
                    role="alertdialog"
                    aria-modal="true"
                    aria-labelledby="sitzung-titel"
                >
                    <span class="grid h-11 w-11 place-items-center rounded-full border-(length:--spacing-hairline) border-navy-700 text-navy-700" aria-hidden="true">
                        <Clock :size="24" :stroke-width="1.5" />
                    </span>

                    <h2 id="sitzung-titel" class="pt-5 text-h3 font-semibold text-navy-700">Ihre Sitzung ist abgelaufen</h2>
                    <p class="pt-2 text-base leading-relaxed text-gray-600">
                        Aus Sicherheitsgründen wurden Sie nach 30 Minuten ohne Aktivität abgemeldet.
                    </p>

                    <BaseButton block class="mt-6" @click="router.visit(loginUrl)">Erneut anmelden</BaseButton>
                    <p class="pt-3 text-center">
                        <a href="/" class="text-sm font-medium text-navy-700 hover:text-navy-500">Zur Website</a>
                    </p>
                </div>
            </div>
        </div>
    </Teleport>
</template>
