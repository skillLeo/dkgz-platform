<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { AlertTriangle } from 'lucide-vue-next'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import BaseInput from '../../Components/Base/BaseInput.vue'
import BaseSelect from '../../Components/Base/BaseSelect.vue'
import BaseTextarea from '../../Components/Base/BaseTextarea.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import ErrorSummary from '../../Components/Feedback/ErrorSummary.vue'
import { useConfirm } from '../../Composables/useConfirm.js'

/**
 * One message to every partner.
 *
 * Built to slow the hand down: the recipient count sits next to the button, and
 * a test send to one address comes first. Nobody unsends a mail to a hundred
 * assessors, so the screen makes the size of the action visible before it is
 * taken.
 */
const props = defineProps({
    audiences: { type: Array, default: () => [] },
    canSend: { type: Boolean, default: false },
})

const { confirm } = useConfirm()

const form = useForm({
    audience: 'approved',
    subject: '',
    body: '',
    test_email: '',
})

const labels = { subject: 'Betreff', body: 'Text', test_email: 'Testadresse' }

const options = computed(() => props.audiences
    .map((entry) => ({ value: entry.value, label: `${entry.label} (${entry.count})` })))

const recipientCount = computed(() => props.audiences
    .find((entry) => entry.value === form.audience)?.count ?? 0)

const sendTest = () => {
    if (! form.test_email) return

    form.transform((data) => ({ ...data })).post('/admin/partnermail', { preserveScroll: true })
}

const sendToAll = async () => {
    const ok = await confirm({
        title: `Nachricht an ${recipientCount.value} Partner senden?`,
        message: 'Der Versand lässt sich nicht zurücknehmen. Senden Sie sich die Nachricht '
            + 'vorher testweise zu, wenn Sie den Text noch nicht in einem echten Postfach gesehen haben.',
        confirmLabel: `An ${recipientCount.value} Partner senden`,
        tone: 'danger',
    })

    if (! ok) return

    form.transform((data) => ({ ...data, test_email: '' }))
        .post('/admin/partnermail', {
            preserveScroll: true,
            onSuccess: () => form.reset('subject', 'body', 'test_email'),
        })
}
</script>

<template>
    <Head title="Rundmail an Partner" />

    <AdminLayout title="Rundmail an Partner">
        <PageHeader title="Rundmail an Partner">
            <template #description>
                Eine Nachricht an alle Partner auf einmal. Jede Person erhält die Mail einzeln
                adressiert, versendet über eine Stunde verteilt.
            </template>
        </PageHeader>

        <form class="max-w-3xl" novalidate @submit.prevent="sendToAll">
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" :labels="labels" class="mb-6" />

            <section class="border border-gray-200 bg-white p-6">
                <div class="flex flex-col gap-5">
                    <BaseSelect
                        v-model="form.audience"
                        label="Empfänger"
                        :options="options"
                        :error="form.errors.audience"
                        required
                    />

                    <BaseInput
                        v-model="form.subject"
                        label="Betreff"
                        placeholder="Neue Funktion im Partnerportal"
                        :error="form.errors.subject"
                        required
                    />

                    <BaseTextarea
                        v-model="form.body"
                        label="Nachricht"
                        :rows="10"
                        hint="Erscheint im gewohnten DKGZ-Layout mit Anrede und Fußzeile."
                        :error="form.errors.body"
                        required
                    />
                </div>
            </section>

            <section class="mt-6 border border-gray-200 bg-white p-6">
                <p class="text-sm font-medium text-gray-800">Erst testen</p>
                <p class="measure pt-1 text-sm leading-normal text-gray-600">
                    Schicken Sie sich die Nachricht zuerst an eine eigene Adresse. So sehen Sie den
                    Text so, wie die Partner ihn bekommen.
                </p>

                <div class="flex flex-wrap items-end gap-3 pt-4">
                    <div class="min-w-0 flex-1" style="min-width: 16rem">
                        <BaseInput
                            v-model="form.test_email"
                            label="Testadresse"
                            type="email"
                            placeholder="info@dkgz.de"
                            :error="form.errors.test_email"
                            optional
                        />
                    </div>
                    <BaseButton
                        type="button"
                        variant="secondary"
                        :disabled="!form.test_email || !form.subject || !form.body || form.processing"
                        @click="sendTest"
                    >Testnachricht senden</BaseButton>
                </div>
            </section>

            <!--
                The count sits with the button rather than in a heading, so the
                size of what is about to happen is the last thing read.
            -->
            <div class="mt-6 flex flex-wrap items-center gap-4 border border-warning/40 bg-warning/8 p-5">
                <AlertTriangle :size="20" :stroke-width="1.75" class="shrink-0 text-warning" aria-hidden="true" />
                <p class="min-w-0 flex-1 text-sm leading-normal text-gray-800">
                    Diese Nachricht geht an <strong class="font-semibold">{{ recipientCount }}</strong>
                    Partner und lässt sich nicht zurücknehmen.
                </p>
                <BaseButton
                    v-if="canSend"
                    type="submit"
                    size="cta"
                    :disabled="!form.subject || !form.body || !recipientCount || form.processing"
                    :loading="form.processing"
                >An alle senden</BaseButton>
            </div>
        </form>
    </AdminLayout>
</template>
