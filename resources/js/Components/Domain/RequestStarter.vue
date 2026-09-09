<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { ChevronRight, FileText } from 'lucide-vue-next'
import ServiceIcon from './ServiceIcon.vue'

/**
 * The homepage's opening question: which assessment.
 *
 * Two choices and nothing else. Almost everybody arriving wants the assessment
 * after a crash, so that one leads and goes straight to the contact step. The
 * rest do not belong in a hero — seven assessments with descriptions is a page
 * of reading beside a headline, a strapline and a row of faces — so the second
 * choice hands over to the request page, which has room to list them all and
 * explain each one.
 *
 * Which assessment leads is not written here. It is whichever the operator has
 * put first under Leistungsarten, so it follows the order they already control
 * and survives a rename — the icon map was keyed by slug once and broke exactly
 * that way.
 */
const props = defineProps({
    serviceTypes: { type: Array, default: () => [] },
    /** The heading above the choices — what this is for, before anything is asked. */
    title: { type: String, default: 'Jetzt Gutachter anfragen' },
    /** The line under the choices. */
    hint: { type: String, default: '' },
    otherLabel: { type: String, default: 'Weitere Gutachten' },
    /** Where a choice goes once it is made. */
    action: { type: String, default: '/anfrage' },
})

/**
 * One class list for both choices.
 *
 * Written once and bound twice rather than pasted twice: the two have to be
 * indistinguishable, and two copies of a long class list drift apart the first
 * time somebody edits one of them.
 */
const ROW = 'flex items-center gap-4 rounded-card border border-gray-300 bg-white p-4 text-left'
    + ' transition-colors duration-(--duration-hover) ease-(--ease-dkgz)'
    + ' hover:border-navy-700 hover:bg-navy-100/60'
    + ' focus-visible:outline-2 focus-visible:outline-navy-500 focus-visible:outline-offset-2'
    + ' disabled:opacity-60'

const NAME = 'min-w-0 flex-1 hyphens-auto text-base font-semibold leading-snug text-navy-700'

const leading = computed(() => props.serviceTypes[0] ?? null)
const others = computed(() => props.serviceTypes.slice(1))

const starting = ref(false)

/** Straight past the question, because pressing this answered it. */
const startLeading = () => {
    if (! leading.value || starting.value) return

    starting.value = true

    router.get(props.action, { leistung: leading.value.slug }, { preserveScroll: false })
}

/** To the request page with nothing chosen, where every assessment is listed. */
const browseAll = () => {
    if (starting.value) return

    starting.value = true

    router.get(props.action, {}, { preserveScroll: false })
}
</script>

<template>
    <div>
        <h2 class="text-lead font-semibold leading-snug text-navy-700">{{ title }}</h2>

        <!-- Side by side where there is room, stacked on a phone. -->
        <div class="grid grid-cols-1 gap-3 pt-4 sm:grid-cols-2">
            <button
                v-if="leading"
                type="button"
                :class="ROW"
                :disabled="starting"
                @click="startLeading"
            >
                <ServiceIcon :service="leading" :size="28" :stroke-width="1.5" class="shrink-0 text-navy-700" />
                <span :class="NAME">{{ leading.name_de }}</span>
                <ChevronRight :size="20" :stroke-width="1.75" class="shrink-0 text-navy-700" aria-hidden="true" />
            </button>

            <button
                v-if="others.length"
                type="button"
                :class="ROW"
                :disabled="starting"
                @click="browseAll"
            >
                <FileText :size="28" :stroke-width="1.5" class="shrink-0 text-navy-700" aria-hidden="true" />
                <span :class="NAME">{{ otherLabel }}</span>
                <ChevronRight :size="20" :stroke-width="1.75" class="shrink-0 text-navy-700" aria-hidden="true" />
            </button>
        </div>

        <p v-if="hint" class="pt-4 text-sm text-gray-600">{{ hint }}</p>
    </div>
</template>
