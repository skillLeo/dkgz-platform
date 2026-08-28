<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { BadgeCheck, Check, MapPin } from 'lucide-vue-next'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

/**
 * One partner's public page.
 *
 * Everything a customer needs to decide, and no way to act on that decision
 * except through DKGZ: no telephone number, no e-mail address, no street. A
 * partner is listed on the understanding that work arrives through the
 * platform, and a page carrying their number would quietly end that.
 *
 * So the button is the whole of the call to action, and it opens the shortest
 * form on the site — the assessor is already chosen, so there is nothing to
 * match on and nothing to ask beyond who to call about what.
 */
const props = defineProps({
    content: { type: Object, default: () => ({}) },
    assessor: { type: Object, required: true },
    requestServiceTypes: { type: Array, default: () => [] },
})

const t = (section, field, fallback = '') => props.content?.[section]?.[field] || fallback

const asking = ref(false)

const form = useForm({
    service_type_id: props.requestServiceTypes.length === 1
        ? String(props.requestServiceTypes[0].id)
        : '',
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    requested_assessor_id: null,
    website: '',
    rendered_at: 0,
})

const options = computed(() => props.requestServiceTypes.map((type) => ({
    value: String(type.id),
    label: type.name_de,
})))

const labels = {
    service_type_id: 'Art des Gutachtens',
    customer_name: 'Vor- und Nachname',
    customer_phone: 'Telefon',
    customer_email: 'E-Mail',
}

const REQUIRED = {
    service_type_id: 'Bitte wählen Sie die Art des Gutachtens.',
    customer_name: 'Bitte geben Sie Ihren Namen an.',
    customer_email: 'Bitte geben Sie eine E-Mail-Adresse an.',
    customer_phone: 'Bitte geben Sie eine Telefonnummer an.',
}

const open = () => {
    asking.value = true
    form.rendered_at = Date.now()
}

const submit = () => {
    const missing = Object.keys(REQUIRED).filter((field) => String(form[field] ?? '').trim() === '')

    if (missing.length) {
        form.setError(Object.fromEntries(missing.map((f) => [f, REQUIRED[f]])))

        return
    }

    form.clearErrors()
    form.transform((data) => ({ ...data, requested_assessor_id: props.assessor.id }))
    form.post('/anfrage')
}
</script>

<template>
    <Head>
        <title>{{ `${assessor.name} — Kfz-Sachverständiger${assessor.city ? ` in ${assessor.city}` : ''} | DKGZ` }}</title>
        <meta
            name="description"
            :content="`${assessor.name}, geprüfter Kfz-Sachverständiger im DKGZ-Netz${assessor.city ? ` in ${assessor.city}` : ''}. Anfrage kostenfrei und unverbindlich über DKGZ.`"
        >
        <link rel="canonical" :href="`https://dkgz.de${assessor.url}`">
    </Head>

    <PublicLayout>
        <section class="border-b border-gray-200 bg-gray-50">
            <div class="mx-auto w-full max-w-(--container-shell) px-4 py-12 md:px-6 md:py-16">
                <nav class="flex flex-wrap items-center gap-2 pb-8 text-sm text-gray-600" aria-label="Brotkrumen">
                    <Link href="/sachverstaendige" class="hover:text-navy-700">Sachverständige</Link>
                    <span aria-hidden="true">·</span>
                    <span class="text-gray-800">{{ assessor.name }}</span>
                </nav>

                <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                    <img
                        v-if="assessor.photo_url"
                        :src="assessor.photo_url"
                        :alt="assessor.name"
                        class="h-24 w-24 shrink-0 rounded-full border border-gray-200 object-cover"
                    >
                    <span
                        v-else
                        class="grid h-24 w-24 shrink-0 place-items-center rounded-full bg-navy-100 text-h3 font-semibold text-navy-700"
                        aria-hidden="true"
                    >{{ assessor.initials }}</span>

                    <div class="min-w-0">
                        <h1 class="hyphens-auto break-words text-h2 font-bold text-navy-700" lang="de">
                            {{ assessor.name }}
                        </h1>
                        <p class="flex flex-wrap items-center gap-x-4 gap-y-1 pt-3 text-base text-gray-600">
                            <span v-if="assessor.region" class="flex items-center gap-2">
                                <MapPin :size="16" :stroke-width="1.5" class="shrink-0 text-navy-700" aria-hidden="true" />
                                {{ assessor.region }}
                            </span>
                            <span class="flex items-center gap-2">
                                <BadgeCheck :size="16" :stroke-width="1.5" class="shrink-0" style="color: var(--dkgz-accent)" aria-hidden="true" />
                                Geprüfter DKGZ-Partner
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 gap-12 px-4 py-14 md:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,380px)] lg:items-start">
            <div class="min-w-0">
                <section v-if="assessor.public_profile">
                    <h2 class="text-h3 font-semibold text-navy-700">
                        {{ t('profil', 'ueberschrift', 'Über diesen Sachverständigen') }}
                    </h2>
                    <p class="measure whitespace-pre-line pt-4 text-base leading-relaxed text-gray-800">
                        {{ assessor.public_profile }}
                    </p>
                </section>

                <section v-if="assessor.services.length" :class="assessor.public_profile ? 'pt-12' : ''">
                    <h2 class="text-h3 font-semibold text-navy-700">
                        {{ t('profil', 'leistungen', 'Angebotene Leistungen') }}
                    </h2>
                    <ul class="grid grid-cols-1 gap-4 pt-6 sm:grid-cols-2">
                        <li
                            v-for="service in assessor.services"
                            :key="service.url"
                            class="rounded-card border border-gray-200 bg-white p-5"
                        >
                            <Link :href="service.url" class="text-base font-medium text-navy-700 hover:text-navy-500">
                                {{ service.name }}
                            </Link>
                            <p v-if="service.description" class="pt-1.5 text-sm leading-normal text-gray-600">
                                {{ service.description }}
                            </p>
                        </li>
                    </ul>
                </section>

                <section v-if="assessor.certification_body || assessor.years_experience" class="pt-12">
                    <h2 class="text-h3 font-semibold text-navy-700">
                        {{ t('profil', 'qualifikation', 'Qualifikation') }}
                    </h2>
                    <dl class="flex flex-col gap-3 pt-5 text-base">
                        <div v-if="assessor.certification_body" class="flex flex-wrap gap-x-3">
                            <dt class="text-gray-600">Zertifizierung</dt>
                            <dd class="font-medium text-navy-700">{{ assessor.certification_body }}</dd>
                        </div>
                        <div v-if="assessor.years_experience" class="flex flex-wrap gap-x-3">
                            <dt class="text-gray-600">Berufserfahrung</dt>
                            <dd class="font-medium text-navy-700">{{ assessor.years_experience }} Jahre</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <!--
                The only way to act on the page. No telephone number and no
                e-mail address anywhere on it: a listed partner is asked for
                work through the platform and reached no other way.
            -->
            <aside class="rounded-card border border-navy-700 p-6 lg:sticky lg:top-24">
                <template v-if="! asking">
                    <h2 class="text-h4 font-semibold text-navy-700">
                        {{ t('profil', 'cta_titel', 'Diesen Sachverständigen anfragen') }}
                    </h2>
                    <p class="pt-2.5 text-sm leading-normal text-gray-600">
                        {{ t('profil', 'cta_text', 'Ihre Anfrage geht ausschließlich an diesen Sachverständigen — nicht an andere Partner.') }}
                    </p>
                    <BaseButton size="cta" block class="mt-5" @click="open">
                        {{ t('profil', 'cta', 'Gutachter anfragen') }}
                    </BaseButton>
                    <p class="flex items-start gap-2 pt-4 text-sm leading-normal text-gray-600">
                        <Check :size="15" :stroke-width="2" class="mt-0.5 shrink-0 text-success" aria-hidden="true" />
                        Kostenfrei und unverbindlich
                    </p>
                </template>

                <form v-else novalidate @submit.prevent="submit">
                    <h2 class="text-h4 font-semibold text-navy-700">
                        {{ t('profil', 'formular_titel', 'Anfrage an') }} {{ assessor.name }}
                    </h2>

                    <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mt-4" />

                    <!-- Honeypot: visually and programmatically hidden -->
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" v-model="form.website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="flex flex-col gap-4 pt-5">
                        <BaseSelect
                            v-model="form.service_type_id"
                            label="Art des Gutachtens"
                            :options="options"
                            placeholder="Bitte auswählen"
                            :error="form.errors.service_type_id"
                            required
                        />
                        <BaseInput
                            v-model="form.customer_name"
                            label="Ihr Name"
                            placeholder="Vor- und Nachname"
                            autocomplete="name"
                            :error="form.errors.customer_name"
                            required
                        />
                        <BaseInput
                            v-model="form.customer_email"
                            label="E-Mail-Adresse"
                            type="email"
                            placeholder="E-Mail eingeben"
                            autocomplete="email"
                            :error="form.errors.customer_email"
                            required
                        />
                        <BaseInput
                            v-model="form.customer_phone"
                            label="Telefonnummer"
                            placeholder="Telefonnummer eingeben"
                            autocomplete="tel"
                            numeric
                            :error="form.errors.customer_phone"
                            required
                        />
                    </div>

                    <BaseButton
                        type="submit"
                        size="cta"
                        block
                        class="mt-6"
                        :loading="form.processing"
                        loading-label="Wird gesendet…"
                    >{{ t('profil', 'absenden', 'Kostenfrei anfragen') }}</BaseButton>

                    <p class="pt-4 text-sm leading-normal text-gray-600">
                        {{ t('profil', 'datenschutzhinweis', 'Mit dem Absenden willigen Sie ein, dass DKGZ Ihre Angaben an diesen Sachverständigen weitergibt.') }}
                        <a href="/datenschutz" class="border-b border-navy-700 pb-0.5 text-navy-700">Datenschutzerklärung</a>
                    </p>
                </form>
            </aside>
        </div>
    </PublicLayout>
</template>
