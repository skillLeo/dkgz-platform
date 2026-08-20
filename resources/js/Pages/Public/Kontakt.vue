<script setup>
import { onMounted } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import SectionLabel from '../../Components/Layout/SectionLabel.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseCheckbox from '../../Components/Base/BaseCheckbox.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    contact: { type: Object, default: () => ({}) },
})

const t = (section, field, fallback = '') => props.content?.[section]?.[field] ?? fallback

const form = useForm({
    name: '', email: '', phone: '', reference: '', subject: '', message: '',
    privacy: false, website: '',
})

const labels = { name: 'Name', email: 'E-Mail-Adresse', subject: 'Betreff', message: 'Nachricht', privacy: 'Datenschutzerklärung' }
</script>

<template>
    <Head title="Kontakt" />

    <PublicLayout :sticky-cta="false">
        <div class="mx-auto grid w-full max-w-(--container-shell) grid-cols-1 gap-12 px-4 py-20 md:px-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
            <div class="min-w-0">
                <SectionLabel text="Kontakt" />
                <h1 class="pt-6 text-h1 font-bold text-navy-700">{{ t('kopf', 'ueberschrift', 'Kontakt') }}</h1>
                <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">{{ t('kopf', 'text') }}</p>

                <form class="mt-8 rounded-card border border-gray-200 bg-white p-6 md:p-8" novalidate @submit.prevent="form.post('/kontakt')">
                    <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-6" />

                    <div class="hidden" aria-hidden="true">
                        <label for="kontakt-website">Website</label>
                        <input id="kontakt-website" v-model="form.website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="flex flex-col gap-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <BaseInput id="name" v-model="form.name" label="Name" autocomplete="name" :error="form.errors.name" required />
                            <BaseInput id="email" v-model="form.email" label="E-Mail-Adresse" type="email" autocomplete="email" :error="form.errors.email" required />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <BaseInput id="phone" v-model="form.phone" label="Telefon" autocomplete="tel" numeric :error="form.errors.phone" optional />
                            <BaseInput id="reference" v-model="form.reference" label="Vorgangsnummer" placeholder="DKGZ26080000" mono :error="form.errors.reference" optional />
                        </div>
                        <BaseInput id="subject" v-model="form.subject" label="Betreff" :error="form.errors.subject" required />
                        <BaseTextarea id="message" v-model="form.message" label="Nachricht" :rows="6" :error="form.errors.message" required />
                        <BaseCheckbox id="privacy" v-model="form.privacy" :error="form.errors.privacy">
                            Ich habe die
                            <a href="/datenschutz" class="border-b border-navy-700 pb-0.5 text-navy-700">Datenschutzerklärung</a>
                            gelesen und stimme der Verarbeitung meiner Anfrage zu.
                        </BaseCheckbox>
                    </div>

                    <BaseButton type="submit" size="cta" class="mt-6" :loading="form.processing" loading-label="Wird gesendet…">
                        {{ t('formular', 'cta', 'Nachricht senden') }}
                    </BaseButton>
                </form>
            </div>

            <aside class="rounded-card border border-gray-200 bg-white p-6 lg:sticky lg:top-24">
                <SectionLabel text="Anschrift" tone="muted" />
                <p class="pt-4 text-base leading-relaxed text-gray-800">
                    {{ contact.company }}<br>
                    {{ contact.street }}<br>
                    {{ contact.postal_code }} {{ contact.city }}
                </p>

                <div class="mt-5 border-t border-gray-200 pt-4">
                    <SectionLabel text="Erreichbarkeit" tone="muted" :with-rule="false" />
                    <a v-if="contact.phone" :href="`tel:${contact.phone.replace(/\s/g, '')}`" class="block pt-2 font-mono text-base tabular-nums text-navy-700">{{ contact.phone }}</a>
                    <a v-if="contact.email" :href="`mailto:${contact.email}`" class="block text-base text-navy-700">{{ contact.email }}</a>
                    <p class="pt-1 text-sm text-gray-600">{{ contact.hours }}</p>
                </div>
            </aside>
        </div>
    </PublicLayout>
</template>
