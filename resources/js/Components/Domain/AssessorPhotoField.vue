<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { Trash2 } from 'lucide-vue-next'
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

const saved = ref(false)

const pick = (event) => {
    const file = event.target.files?.[0]

    if (! file) return

    preview.value = URL.createObjectURL(file)
    saved.value = false
    form.photo = file

    form.post('/portal/profil/bild', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('photo')
            preview.value = null
            saved.value = true
        },
        onError: () => { preview.value = null },
        // Always clear the input: without this, picking the same file again
        // fires no change event and the second attempt looks like a dead button.
        onFinish: () => { if (input.value) input.value.value = '' },
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
                    annehmen. Quadratisch, JPG, PNG oder WebP, höchstens 12 MB.
                </p>

                <div class="flex flex-wrap items-center gap-3 pt-3">
                    <input
                        ref="input"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                        autocomplete="off"
                        :disabled="form.processing"
                        class="block max-w-full text-sm text-gray-600 file:mr-3 file:cursor-pointer file:rounded-sm file:border file:border-navy-700 file:bg-white file:px-3 file:py-2 file:text-sm file:text-navy-700 hover:file:bg-navy-100"
                        :aria-label="photoUrl ? 'Profilbild ersetzen' : 'Profilbild auswählen'"
                        @change="pick"
                    >
                    <BaseButton v-if="photoUrl" variant="ghost" size="small" @click="remove">
                        <Trash2 :size="14" :stroke-width="1.5" aria-hidden="true" />
                        Entfernen
                    </BaseButton>
                </div>

                <p v-if="form.processing" class="pt-2 text-sm text-gray-600" aria-live="polite">
                    Wird hochgeladen…
                </p>
                <p v-else-if="saved" class="pt-2 text-sm text-success" aria-live="polite">
                    Gespeichert.
                </p>
                <FieldError :message="form.errors.photo" />
            </div>
        </div>
    </div>
</template>
