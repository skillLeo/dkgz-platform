<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { Image as ImageIcon, Trash2, Upload } from 'lucide-vue-next'
import BaseButton from '../Base/BaseButton.vue'
import FieldError from '../Feedback/FieldError.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

/**
 * The image control in the content editor: what is stored, and the three things
 * an editor needs to do with it — put one there, put a different one there,
 * take it away.
 *
 * Removing an image is not destructive to the page. The slot falls back to the
 * briefed placeholder, so the layout never collapses and it stays obvious which
 * picture is still missing.
 */
const props = defineProps({
    block: { type: Object, required: true },
    disabled: { type: Boolean, default: false },
})

const { confirm } = useConfirm()

const input = ref(null)
const dragging = ref(false)
const form = useForm({ image: null })

const pick = (file) => {
    if (!file || props.disabled) return

    form.image = file
    form.post(`/admin/inhalte-bild/${props.block.id}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset('image'),
    })
}

const onDrop = (event) => {
    dragging.value = false
    pick(event.dataTransfer?.files?.[0])
}

const remove = async () => {
    const ok = await confirm({
        title: 'Bild entfernen?',
        message: `„${props.block.label}“ wird gelöscht. Die Seite zeigt an dieser Stelle wieder den Platzhalter, bis ein neues Bild hinterlegt wird.`,
        confirmLabel: 'Entfernen',
        tone: 'danger',
    })

    if (ok) {
        router.delete(`/admin/inhalte-bild/${props.block.id}`, { preserveScroll: true })
    }
}
</script>

<template>
    <div>
        <p class="pb-2 text-sm font-medium text-gray-800">{{ block.label }}</p>

        <!-- Stored image: shown at a readable size with what it actually is -->
        <div v-if="block.preview_url" class="flex flex-wrap items-start gap-4 rounded-sm border border-gray-200 bg-white p-3">
            <img
                :src="block.preview_url"
                :alt="`Vorschau: ${block.label}`"
                class="h-28 w-auto max-w-full rounded-xs border border-gray-200 object-cover"
            >
            <div class="min-w-0 flex-1">
                <p class="truncate font-mono text-meta text-gray-800">{{ block.image?.name ?? 'Bild' }}</p>
                <p class="font-mono text-meta text-gray-400">
                    <template v-if="block.image?.dimensions">{{ block.image.dimensions }} · </template>
                    {{ block.image?.size_label ?? '' }}
                </p>
                <div class="flex flex-wrap gap-2 pt-3">
                    <BaseButton
                        variant="secondary"
                        size="small"
                        :disabled="disabled || form.processing"
                        @click="input?.click()"
                    >Bild ersetzen</BaseButton>
                    <BaseButton
                        variant="ghost"
                        size="small"
                        :disabled="disabled"
                        @click="remove"
                    >
                        <Trash2 :size="14" :stroke-width="1.5" aria-hidden="true" />
                        Entfernen
                    </BaseButton>
                </div>
            </div>
        </div>

        <!-- Empty: the same dashed frame the public page shows -->
        <button
            v-else
            type="button"
            :disabled="disabled || form.processing"
            class="flex min-h-32 w-full flex-col items-center justify-center gap-2 rounded-sm border border-dashed px-4 py-6 text-center transition-colors duration-(--duration-hover) ease-(--ease-dkgz) disabled:cursor-not-allowed"
            :class="dragging ? 'border-navy-700 bg-navy-100' : 'border-gray-300 bg-white hover:border-gray-400'"
            @click="input?.click()"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <ImageIcon :size="24" :stroke-width="1.5" class="text-gray-400" aria-hidden="true" />
            <span class="text-base text-gray-800">
                Bild hierher ziehen oder
                <span class="font-medium text-navy-700 underline underline-offset-2">auswählen</span>
            </span>
            <span class="font-mono text-eyebrow text-gray-400">JPG, PNG oder WebP · max. 4 MB</span>
        </button>

        <p v-if="block.help" class="pt-2 text-xs leading-normal text-gray-600">{{ block.help }}</p>

        <p v-if="form.processing" class="flex items-center gap-2 pt-2 text-sm text-gray-600">
            <Upload :size="14" :stroke-width="1.5" aria-hidden="true" />
            Wird hochgeladen…
        </p>

        <FieldError :message="form.errors.image" />

        <input
            ref="input"
            type="file"
            accept=".jpg,.jpeg,.png,.webp"
            class="sr-only"
            @change="pick($event.target.files?.[0])"
        >
    </div>
</template>
