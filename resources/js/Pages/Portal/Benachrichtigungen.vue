<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Bell } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'
import EmptyState from '../../Components/Feedback/EmptyState.vue'

const props = defineProps({ notifications: { type: Object, required: true } })

const markRead = useForm({})
const hasUnread = props.notifications.data.some((n) => !n.read_at)
</script>

<template>
    <Head title="Benachrichtigungen" />

    <PortalLayout title="Benachrichtigungen">
        <PageHeader title="Benachrichtigungen">
            <template #actions>
                <BaseButton v-if="hasUnread" variant="secondary" size="compact" :loading="markRead.processing" @click="markRead.post('/portal/benachrichtigungen/gelesen')">
                    Alle als gelesen markieren
                </BaseButton>
            </template>
        </PageHeader>

        <EmptyState
            v-if="!notifications.data.length"
            title="Keine Benachrichtigungen"
            description="Neue Anfragen aus Ihrem Einsatzgebiet erscheinen hier und erreichen Sie zusätzlich per E-Mail."
            :icon="Bell"
        />

        <ul v-else class="border border-gray-200 bg-white">
            <li
                v-for="notification in notifications.data"
                :key="notification.id"
                class="border-b border-gray-100 last:border-b-0"
                :class="notification.read_at ? '' : 'bg-navy-100'"
            >
                <component
                    :is="notification.data.url ? Link : 'div'"
                    :href="notification.data.url"
                    class="flex items-start justify-between gap-4 p-5"
                >
                    <div class="min-w-0">
                        <p class="text-base font-medium text-navy-700">{{ notification.data.title }}</p>
                        <p v-if="notification.data.body" class="pt-1 text-sm leading-normal text-gray-600">{{ notification.data.body }}</p>
                    </div>
                    <span class="shrink-0 font-mono text-xs tabular-nums text-gray-400">{{ notification.created_at_label }}</span>
                </component>
            </li>
        </ul>
    </PortalLayout>
</template>
