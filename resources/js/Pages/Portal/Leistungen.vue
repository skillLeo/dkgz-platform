<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Check } from 'lucide-vue-next'
import PortalLayout from '../../Layouts/PortalLayout.vue'
import PageHeader from '../../Components/Layout/PageHeader.vue'
import BaseButton from '../../Components/Base/BaseButton.vue'

const props = defineProps({
    serviceTypes: { type: Array, default: () => [] },
    selected: { type: Array, default: () => [] },
})

const form = useForm({ service_type_ids: [...props.selected] })

const toggle = (id) => {
    const index = form.service_type_ids.indexOf(id)
    if (index === -1) form.service_type_ids.push(id)
    else form.service_type_ids.splice(index, 1)
}
</script>

<template>
    <Head title="Leistungen" />

    <PortalLayout title="Leistungen">
        <PageHeader
            title="Leistungen"
            description="Sie erhalten nur Anfragen zu den Gutachten, die Sie hier ausgewählt haben."
        />

        <form class="max-w-2xl" @submit.prevent="form.post('/portal/leistungen', { preserveScroll: true })">
            <p v-if="form.errors.service_type_ids" class="pb-4 text-sm text-danger" role="alert">{{ form.errors.service_type_ids }}</p>

            <div class="flex flex-col gap-2">
                <label
                    v-for="type in serviceTypes"
                    :key="type.id"
                    class="flex cursor-pointer items-start gap-3 rounded-sm border bg-white p-4 transition-colors duration-(--duration-hover) ease-(--ease-dkgz)"
                    :class="form.service_type_ids.includes(type.id) ? 'border-navy-700 bg-navy-100' : 'border-gray-200 hover:border-gray-300'"
                >
                    <input type="checkbox" :checked="form.service_type_ids.includes(type.id)" class="sr-only" @change="toggle(type.id)">
                    <span
                        class="mt-0.5 grid h-[18px] w-[18px] shrink-0 place-items-center rounded-sm border"
                        :class="form.service_type_ids.includes(type.id) ? 'border-navy-700 bg-navy-700 text-white' : 'border-gray-300'"
                        aria-hidden="true"
                    >
                        <Check v-if="form.service_type_ids.includes(type.id)" :size="12" :stroke-width="1.5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-base font-medium text-gray-800">{{ type.name_de }}</span>
                        <span v-if="type.description_de" class="block pt-0.5 text-sm leading-normal text-gray-600">{{ type.description_de }}</span>
                    </span>
                </label>
            </div>

            <BaseButton type="submit" size="cta" class="mt-6" :loading="form.processing">Leistungen speichern</BaseButton>
        </form>
    </PortalLayout>
</template>
