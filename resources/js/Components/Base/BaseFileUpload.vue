<script setup>
import { computed, ref } from 'vue'
import { FileText, Upload, X } from 'lucide-vue-next'
import { useGermanFormat } from '../../Composables/useGermanFormat.js'
import FieldError from '../Feedback/FieldError.vue'

/**
 * Auth Shell §Teil B, three states: empty (112px dashed), uploading (64px row
 * with a 2px navy progress rule), filled (64px row with a remove action).
 */
const props = defineProps({
    modelValue: { type: [Array, Object, null], default: null },
    label: { type: String, required: true },
    accept: { type: String, default: '.pdf,.jpg,.jpeg,.png' },
    acceptLabel: { type: String, default: 'PDF, JPG oder PNG · max. 10 MB' },
    multiple: { type: Boolean, default: false },
    maxFiles: { type: Number, default: 1 },
    maxSizeMb: { type: Number, default: 10 },
    error: { type: String, default: '' },
    progress: { type: Number, default: null },
    existing: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'remove-existing'])

const { fileSize, dateTime } = useGermanFormat()

const fileNoun = computed(() => (props.multiple ? 'Dateien' : 'PDF'))

const input = ref(null)
const dragging = ref(false)
const localError = ref('')

const files = computed(() => {
    if (!props.modelValue) return []

    return Array.isArray(props.modelValue) ? props.modelValue : [props.modelValue]
})

const hasContent = computed(() => files.value.length > 0 || props.existing.length > 0)

const validate = (incoming) => {
    const limit = props.maxSizeMb * 1000 * 1000
    const tooBig = incoming.find((file) => file.size > limit)

    if (tooBig) {
        localError.value = `„${tooBig.name}“ ist größer als ${props.maxSizeMb} MB.`

        return false
    }

    if (incoming.length + props.existing.length > props.maxFiles) {
        localError.value = `Es sind höchstens ${props.maxFiles} Dateien möglich.`

        return false
    }

    localError.value = ''

    return true
}

const accept = (fileList) => {
    const incoming = Array.from(fileList)
    if (!incoming.length || !validate(incoming)) return

    emit('update:modelValue', props.multiple ? incoming : incoming[0])
}

const onDrop = (event) => {
    dragging.value = false
    if (props.disabled) return
    accept(event.dataTransfer.files)
}

const removeFile = (index) => {
    if (!props.multiple) {
        emit('update:modelValue', null)

        return
    }

    const next = files.value.slice()
    next.splice(index, 1)
    emit('update:modelValue', next)
}

const shownError = computed(() => props.error || localError.value)
</script>

<template>
    <div>
        <label class="block pb-2 text-sm font-medium text-gray-800">
            {{ label }}<span v-if="required" class="text-danger" aria-hidden="true"> *</span>
        </label>

        <div v-if="!hasContent">
            <button
                type="button"
                :disabled="disabled"
                class="flex h-28 w-full flex-col items-center justify-center gap-2 rounded-sm border border-dashed px-4 text-center transition-colors duration-(--duration-hover) ease-(--ease-dkgz) disabled:cursor-not-allowed"
                :class="dragging
                    ? 'border-navy-700 bg-navy-100'
                    : shownError ? 'border-danger bg-danger/3' : 'border-gray-300 bg-white hover:border-gray-400'"
                @click="input?.click()"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
            >
                <Upload :size="24" :stroke-width="1.5" class="text-gray-600" aria-hidden="true" />
                <span class="text-base text-gray-800">
                    {{ fileNoun }} hierher ziehen oder
                    <span class="font-medium text-navy-700 underline underline-offset-2">auswählen</span>
                </span>
                <span class="font-mono text-eyebrow text-gray-400">{{ acceptLabel }}</span>
            </button>
        </div>

        <div v-else class="flex flex-col gap-2">
            <div
                v-for="(doc, index) in existing"
                :key="`vorhanden-${doc.id ?? index}`"
                class="flex h-16 items-center gap-3 rounded-sm border border-gray-200 bg-white px-4"
            >
                <FileText :size="20" :stroke-width="1.5" class="shrink-0 text-navy-700" aria-hidden="true" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-base font-medium text-gray-800">{{ doc.original_name }}</p>
                    <p class="truncate font-mono text-xs tabular-nums text-gray-400">
                        {{ fileSize(doc.size_bytes) }}<template v-if="doc.uploaded_at"> · {{ dateTime(doc.uploaded_at) }}</template>
                    </p>
                </div>
                <button
                    type="button"
                    class="shrink-0 whitespace-nowrap text-sm font-medium text-navy-700 hover:text-navy-500"
                    @click="emit('remove-existing', doc)"
                >
                    Entfernen
                </button>
            </div>

            <div
                v-for="(file, index) in files"
                :key="`neu-${index}`"
                class="relative flex h-16 items-center gap-3 overflow-hidden rounded-sm border border-gray-200 bg-white px-4"
            >
                <FileText :size="20" :stroke-width="1.5" class="shrink-0 text-navy-700" aria-hidden="true" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-base font-medium text-gray-800">{{ file.name }}</p>
                    <p class="font-mono text-xs tabular-nums text-gray-400">
                        {{ fileSize(file.size) }}<template v-if="progress !== null"> · {{ progress }} %</template>
                    </p>
                </div>
                <button
                    v-if="progress === null"
                    type="button"
                    class="shrink-0 text-gray-400 hover:text-gray-600"
                    aria-label="Datei entfernen"
                    @click="removeFile(index)"
                >
                    <X :size="16" :stroke-width="1.5" aria-hidden="true" />
                </button>
                <span
                    v-if="progress !== null"
                    class="absolute bottom-0 left-0 h-0.5 bg-navy-700 transition-[width] duration-(--duration-hover) ease-(--ease-dkgz)"
                    :style="{ width: `${progress}%` }"
                    aria-hidden="true"
                />
            </div>

            <button
                v-if="multiple && files.length + existing.length < maxFiles"
                type="button"
                class="w-fit text-sm font-medium text-navy-700 hover:text-navy-500"
                @click="input?.click()"
            >
                Weitere Datei hinzufügen
            </button>
        </div>

        <input
            ref="input"
            type="file"
            class="sr-only"
            :accept="accept"
            :multiple="multiple"
            :disabled="disabled"
            @change="accept($event.target.files)"
        >

        <FieldError v-if="shownError" :message="shownError" />
    </div>
</template>
