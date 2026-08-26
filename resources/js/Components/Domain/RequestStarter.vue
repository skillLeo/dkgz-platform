<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowRight, FileText, ShieldCheck } from 'lucide-vue-next'
import BaseSelect from '../Base/BaseSelect.vue'
import BaseButton from '../Base/BaseButton.vue'

/**
 * The first and only thing anybody is asked before the form: which assessment.
 *
 * One dropdown. Choosing explains what that service is, and that is the whole
 * of it — the postal code moved to the second step to sit with the contact
 * details, so the opening ask is a single question with a single answer rather
 * than a form pretending to be one.
 *
 * The button is on screen from the start, inert until the dropdown has an
 * answer. Revealing it only once it did meant the box had no visible
 * destination while somebody was deciding whether to bother, which is exactly
 * the moment their eye needs somewhere to land.
 *
 * It sits in the homepage hero and again on /anfrage, from the same file, so
 * somebody arriving at the request page from an advert meets the same question
 * as somebody who started at the top.
 */
const props = defineProps({
    serviceTypes: { type: Array, default: () => [] },
    /** Preselected when the visitor arrives with a service already in mind. */
    initialService: { type: [String, Number], default: '' },
    /** The card's own heading — what this box is for, before anything is asked. */
    title: { type: String, default: 'Jetzt Gutachter anfragen' },
    ctaLabel: { type: String, default: 'Weiter' },
    /** The line under the button. */
    hint: { type: String, default: '' },
    serviceLabel: { type: String, default: 'Welche Gutachtenart benötigen Sie?' },
    serviceHint: {
        type: String,
        default: 'Wählen Sie die passende Leistung aus, damit wir den richtigen Sachverständigen für Sie finden.',
    },
    /** Where the button goes once a service is chosen. */
    action: { type: String, default: '/anfrage' },
})

const emit = defineEmits(['start'])

const serviceId = ref(String(props.initialService ?? ''))
const starting = ref(false)

const options = computed(() => props.serviceTypes.map((type) => ({
    value: String(type.id),
    label: type.name_de,
})))

const service = computed(() => props.serviceTypes.find((type) => String(type.id) === serviceId.value) ?? null)

const ready = computed(() => service.value !== null)

watch(serviceId, () => { starting.value = false })

const start = () => {
    if (! ready.value) return

    starting.value = true

    emit('start', { service_type_id: serviceId.value })

    if (! props.action) return

    router.get(props.action, { leistung: service.value.slug }, { preserveScroll: false })
}
</script>

<template>
    <form class="rounded-card border border-gray-200 bg-white p-5 shadow-(--shadow-1) sm:p-6" novalidate @submit.prevent="start">
        <!--
            The box says what it is before it asks anything. Without a heading
            it read as a stray dropdown in the middle of the page rather than
            the thing the page is for.
        -->
        <div class="flex items-center gap-3 pb-5">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-navy-700 text-white" aria-hidden="true">
                <FileText :size="18" :stroke-width="1.75" />
            </span>
            <h2 class="text-lead font-semibold leading-snug text-navy-700">{{ title }}</h2>
        </div>

        <BaseSelect
            v-model="serviceId"
            :label="serviceLabel"
            :options="options"
            placeholder="Bitte auswählen"
        />

        <!--
            The service explains itself where it was chosen, so the next
            question arrives with a reason attached rather than as one more
            thing to fill in.
        -->
        <p v-if="! ready && serviceHint" class="flex gap-2 pt-2.5 text-sm leading-normal text-gray-600">
            <ShieldCheck :size="15" :stroke-width="1.5" class="mt-0.5 shrink-0 text-gray-400" aria-hidden="true" />
            <span>{{ serviceHint }}</span>
        </p>

        <p
            v-else-if="service?.description_de"
            class="flex gap-2 pt-3 text-sm leading-normal text-gray-600"
            style="animation: dkgz-enter 260ms cubic-bezier(0.4,0,0.2,1) both"
        >
            <ShieldCheck :size="15" :stroke-width="1.5" class="mt-0.5 shrink-0" style="color: var(--dkgz-accent)" aria-hidden="true" />
            <span>{{ service.description_de }}</span>
        </p>

        <!--
            Always on screen, and inert until the questions above it have been
            answered. Appearing only once they were meant the box had no visible
            destination while somebody was deciding — which is the moment their
            eye needs somewhere to land.
        -->
        <BaseButton
            type="submit"
            size="cta"
            block
            class="mt-6"
            :disabled="! ready"
            :loading="starting"
        >
            {{ ctaLabel }}
            <ArrowRight :size="18" :stroke-width="1.75" aria-hidden="true" />
        </BaseButton>

        <p v-if="hint" class="pt-3 text-center text-sm text-gray-600">{{ hint }}</p>
    </form>
</template>
