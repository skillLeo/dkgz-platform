<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { Trash2, Upload } from 'lucide-vue-next'
import BaseButton from '../Base/BaseButton.vue'
import FieldError from '../Feedback/FieldError.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

/**
 * The assessor's portrait, with a live preview before it is sent.
 *
 * The customer sees this alongside the contact details they receive on
 * acceptance, so it is worth showing the partner exactly what will be used —
 * square, centre-cropped — rather than letting them discover the crop later.
 */
const props = defineProps({
    photoUrl: { type: String, default: null },
    initials: { type: String, default: '' },
})

const { confirm } = useConfirm()

const input = ref(null)
const preview = ref(null)
const form = useForm({ photo: null })

const pick = (file) => {
    if (!file) return

    preview.value = URL.createObjectURL(file)
    form.photo = file
    form.post('/portal/profil/bild', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { form.reset('photo'); preview.value = null },
        onError: () => { preview.value = null },
    })
}

const remove = async () => {
    const ok = await confirm({
        title: 'Profilbild entfernen?',
        message: 'Kundinnen und Kunden sehen dann wieder Ihre Initialen.',
        confirmLabel: 'Entfernen',
        tone: 'danger',
    })

    if (ok) router.delete('/portal/profil/bild', { preserveScroll: true })
}
</script>

<template>
    <div>
        <p class="pb-2 text-sm font-medium text-gray-800">Profilbild</p>

        <div class="flex flex-wrap items-center gap-5">
            <span class="shrink-0">
                <img
                    v-if="preview || photoUrl"
                    :src="preview ?? photoUrl"
                    alt="Ihr Profilbild"
                    class="h-20 w-20 rounded-full border border-gray-200 object-cover"
                >
                <span
                    v-else
                    class="grid h-20 w-20 place-items-center rounded-full bg-navy-100 text-h4 font-semibold text-navy-700"
                    aria-hidden="true"
                >{{ initials || '–' }}</span>
            </span>

            <div class="min-w-0">
                <p class="measure text-sm leading-normal text-gray-600">
                    Wird Kundinnen und Kunden zusammen mit Ihren Kontaktdaten angezeigt, sobald Sie einen Auftrag
                    annehmen. Quadratisch, JPG, PNG oder WebP, höchstens 4 MB.
                </p>

                <div class="flex flex-wrap gap-2 pt-3">
                    <BaseButton
                        variant="secondary"
                        size="small"
                        :disabled="form.processing"
                        @click="input?.click()"
                    >
                        <Upload :size="14" :stroke-width="1.5" aria-hidden="true" />
                        {{ photoUrl ? 'Bild ersetzen' : 'Bild auswählen' }}
                    </BaseButton>
                    <BaseButton v-if="photoUrl" variant="ghost" size="small" @click="remove">
                        <Trash2 :size="14" :stroke-width="1.5" aria-hidden="true" />
                        Entfernen
                    </BaseButton>
                </div>

                <p v-if="form.processing" class="pt-2 text-sm text-gray-600">Wird hochgeladen…</p>
                <FieldError :message="form.errors.photo" />
            </div>
        </div>

        <input
            ref="input"
            type="file"
            accept=".jpg,.jpeg,.png,.webp"
            autocomplete="off"
            class="sr-only"
            @change="pick($event.target.files?.[0])"
        >
    </div>
</template>
