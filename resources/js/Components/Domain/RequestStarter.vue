<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowRight, Check, Loader2, ShieldCheck } from 'lucide-vue-next'
import BaseSelect from '../Base/BaseSelect.vue'
import BaseInput from '../Base/BaseInput.vue'
import BaseButton from '../Base/BaseButton.vue'

/**
 * The first thing anybody is asked: what, and where.
 *
 * One card, revealed a line at a time. Choosing a service explains what that
 * service is, which is where the postal code appears; a code that resolves to a
 * town shows the town back, which is where the button appears. Nothing is on
 * screen that the visitor has not yet earned the right to be asked, so the
 * opening ask is a single dropdown rather than a form.
 *
 * It sits in the homepage hero and again on /anfrage, from the same file,
 * because somebody arriving at the request page from an advert should meet the
 * same two questions in the same order as somebody who started at the top.
 */
const props = defineProps({
    serviceTypes: { type: Array, default: () => [] },
    /** Preselected when the visitor arrives with a service already in mind. */
    initialService: { type: [String, Number], default: '' },
    initialPostalCode: { type: String, default: '' },
    ctaLabel: { type: String, default: 'Jetzt Gutachter anfragen' },
    serviceLabel: { type: String, default: 'Welche Gutachtenart benötigen Sie?' },
    postalLabel: { type: String, default: 'Postleitzahl des Fahrzeugstandorts' },
    serviceHint: {
        type: String,
        default: 'Wählen Sie die passende Leistung aus, damit wir den richtigen Sachverständigen für Sie finden.',
    },
    /** Where the button goes once both answers are in. */
    action: { type: String, default: '/anfrage' },
})

const emit = defineEmits(['start'])

const serviceId = ref(String(props.initialService ?? ''))
const postalCode = ref(String(props.initialPostalCode ?? '').replace(/\D/g, '').slice(0, 5))
const city = ref('')
const resolving = ref(false)
const unknown = ref(false)
const starting = ref(false)

const options = computed(() => props.serviceTypes.map((type) => ({
    value: String(type.id),
    label: type.name_de,
})))

const service = computed(() => props.serviceTypes.find((type) => String(type.id) === serviceId.value) ?? null)

/**
 * The three gates, in order. Each one is what makes the next line appear.
 */
const hasService = computed(() => service.value !== null)
const hasLocation = computed(() => postalCode.value.length === 5 && city.value !== '')
const ready = computed(() => hasService.value && hasLocation.value)

/**
 * The town, looked up rather than typed.
 *
 * Asking for both a postal code and a town invited them to disagree, and the
 * code is the one the matching runs on. Showing the town back is what makes the
 * code feel checked rather than merely entered.
 */
let timer = null

const lookUp = async (code) => {
    resolving.value = true
    unknown.value = false

    try {
        const response = await fetch(`/api/plz/${code}`, { headers: { Accept: 'application/json' } })
        const data = response.ok ? await response.json() : null

        if (data?.city) {
            city.value = data.city
        } else {
            city.value = ''
            unknown.value = true
        }
    } catch {
        // A lookup that cannot reach the server must not trap somebody on this
        // screen. The code goes through and the server checks it on submit.
        city.value = ''
        unknown.value = false
    } finally {
        resolving.value = false
    }
}

const onPostalInput = (value) => {
    postalCode.value = String(value).replace(/\D/g, '').slice(0, 5)
    city.value = ''
    unknown.value = false

    if (timer) clearTimeout(timer)
    if (postalCode.value.length !== 5) return

    timer = setTimeout(() => lookUp(postalCode.value), 250)
}

// A code carried in from another page is resolved without being retyped.
if (postalCode.value.length === 5) lookUp(postalCode.value)

// Changing the service leaves the location alone: it is still the same car in
// the same place, and clearing it would be busywork.
watch(serviceId, () => { starting.value = false })

const postalHint = computed(() => {
    if (resolving.value) return 'Ort wird ermittelt…'
    if (unknown.value) return 'Diese Postleitzahl konnten wir nicht zuordnen. Bitte prüfen Sie Ihre Eingabe.'

    return ''
})

const start = () => {
    if (! ready.value) return

    starting.value = true

    emit('start', {
        service_type_id: serviceId.value,
        postal_code: postalCode.value,
        city: city.value,
    })

    if (! props.action) return

    router.get(props.action, {
        leistung: service.value.slug,
        plz: postalCode.value,
    }, { preserveScroll: false })
}
</script>

<template>
    <form class="rounded-card border border-gray-200 bg-white p-5 shadow-(--shadow-1) sm:p-6" novalidate @submit.prevent="start">
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
        <p v-if="! hasService && serviceHint" class="flex gap-2 pt-2.5 text-sm leading-normal text-gray-600">
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

        <div v-if="hasService" class="pt-5" style="animation: dkgz-enter 260ms cubic-bezier(0.4,0,0.2,1) both">
            <BaseInput
                :model-value="postalCode"
                :label="postalLabel"
                :hint="postalHint"
                placeholder="40210"
                inputmode="numeric"
                autocomplete="postal-code"
                maxlength="5"
                numeric
                @update:model-value="onPostalInput"
            />

            <p v-if="resolving" class="flex items-center gap-2 pt-2.5 text-sm text-gray-600">
                <Loader2 :size="15" :stroke-width="1.75" class="shrink-0 animate-spin" aria-hidden="true" />
                Ort wird ermittelt…
            </p>

            <p
                v-else-if="city"
                class="flex items-center gap-2 pt-2.5 text-sm font-medium text-success"
                style="animation: dkgz-enter 220ms cubic-bezier(0.4,0,0.2,1) both"
            >
                <Check :size="16" :stroke-width="2" class="shrink-0" aria-hidden="true" />
                {{ postalCode }} {{ city }}
            </p>
        </div>

        <BaseButton
            v-if="ready"
            type="submit"
            size="cta"
            block
            class="mt-6"
            :loading="starting"
            style="animation: dkgz-enter 260ms cubic-bezier(0.4,0,0.2,1) both"
        >
            {{ ctaLabel }}
            <ArrowRight :size="18" :stroke-width="1.75" aria-hidden="true" />
        </BaseButton>
    </form>
</template>
