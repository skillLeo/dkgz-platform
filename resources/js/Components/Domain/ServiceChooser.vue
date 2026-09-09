<script setup>
import { ArrowRight, Info } from 'lucide-vue-next'
import ServiceIcon from './ServiceIcon.vue'
import BaseButton from '../Base/BaseButton.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

/**
 * Every assessment, on the first step of the request.
 *
 * Chosen and then confirmed, rather than chosen by being pressed. The homepage
 * asks a two-way question where pressing is the answer; this is a list of seven
 * where somebody is comparing, and a list that acts the instant you touch a row
 * punishes reading. The button stays inert until something is chosen, so the
 * page always shows where it is going.
 *
 * The description opens in the shared dialog rather than inside the row. Opened
 * in place it pushed its own box taller, and a grid stretches every box in a row
 * to match — so asking what one assessment meant left an empty tall box beside
 * it. The dialog already handles the escape key, the backdrop and the sheet it
 * becomes on a phone.
 *
 * A real radio group underneath: the whole row is the label, so the target is
 * the row and not a 16px circle, and a keyboard still gets arrow keys and a
 * group that announces itself.
 */
defineProps({
    serviceTypes: { type: Array, default: () => [] },
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: 'Welches Gutachten benötigen Sie?' },
    ctaLabel: { type: String, default: 'Weiter' },
    infoLabel: { type: String, default: 'Was ist das?' },
    submitting: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'confirm'])

const { confirm } = useConfirm()

/**
 * The longer explanation, not the description.
 *
 * `info_de` is written for somebody deciding which of seven assessments they
 * need; the description is written for the services page. The server falls back
 * to the description where nobody has written the longer one yet.
 */
const explain = (type) => confirm({
    title: type.name_de,
    message: type.info_de ?? type.description_de,
    confirmLabel: 'Verstanden',
    dismissOnly: true,
})
</script>

<template>
    <form novalidate @submit.prevent="emit('confirm')">
        <fieldset>
            <legend class="sr-only">{{ label }}</legend>

            <!--
                Two across where there is room. Seven names in one column is a
                tall stack that pushes the button off the screen on a desktop,
                which is the one place there is width to spare.
            -->
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <!--
                    The box is the flex row itself, so the label stretches to the
                    height the grid gives it and centres its own content. Wrapped
                    in an extra div the row sat at the top, and a one-line name
                    beside a two-line one looked as though it had slipped.
                -->
                <div
                    v-for="type in serviceTypes"
                    :key="type.id"
                    class="flex rounded-card border bg-white transition-colors duration-(--duration-hover) ease-(--ease-dkgz) has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-navy-500 has-[:focus-visible]:outline-offset-2"
                    :class="String(modelValue) === String(type.id)
                        ? 'border-navy-700 bg-navy-100/50'
                        : 'border-gray-300 hover:border-navy-700'"
                >
                    <!--
                        The label carries the whole row, so the target is the row
                        rather than the circle. The radio itself is present and
                        focusable, just not drawn.
                    -->
                    <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-4 p-4">
                        <input
                            class="sr-only"
                            type="radio"
                            name="service_type"
                            :value="String(type.id)"
                            :checked="String(modelValue) === String(type.id)"
                            @change="emit('update:modelValue', String(type.id))"
                        >
                        <ServiceIcon :service="type" :size="28" :stroke-width="1.5" class="shrink-0 text-navy-700" />
                        <span class="min-w-0 flex-1 hyphens-auto text-base font-semibold leading-snug text-navy-700">
                            {{ type.name_de }}
                        </span>
                    </label>

                    <button
                        v-if="type.info_de || type.description_de"
                        type="button"
                        class="mr-3 grid h-9 w-9 shrink-0 self-center place-items-center rounded-full border border-gray-300 text-gray-600 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) hover:border-navy-700 hover:text-navy-700 focus-visible:outline-2 focus-visible:outline-navy-500 focus-visible:outline-offset-2"
                        :aria-label="`${infoLabel} ${type.name_de}`"
                        @click="explain(type)"
                    >
                        <Info :size="17" :stroke-width="1.75" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </fieldset>

        <!--
            On screen from the start and inert until something is chosen. Growing
            a button once the list is answered leaves the page with no visible
            destination while somebody is still deciding whether to bother.
        -->
        <BaseButton
            type="submit"
            size="cta"
            block
            class="mt-6"
            :disabled="! modelValue"
            :loading="submitting"
        >
            {{ ctaLabel }}
            <ArrowRight :size="18" :stroke-width="1.75" aria-hidden="true" />
        </BaseButton>
    </form>
</template>
