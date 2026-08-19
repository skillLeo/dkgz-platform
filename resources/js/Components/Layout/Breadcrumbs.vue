<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'

defineProps({
    items: { type: Array, default: () => [] },
})
</script>

<template>
    <nav v-if="items.length" aria-label="Brotkrumennavigation" class="pb-4">
        <ol class="flex flex-wrap items-center gap-1.5">
            <li v-for="(item, index) in items" :key="index" class="flex items-center gap-1.5">
                <Link
                    v-if="item.href && index < items.length - 1"
                    :href="item.href"
                    class="text-sm text-gray-600 hover:text-navy-700"
                >{{ item.label }}</Link>
                <span v-else class="text-sm text-gray-800" aria-current="page">{{ item.label }}</span>
                <ChevronRight
                    v-if="index < items.length - 1"
                    :size="14"
                    :stroke-width="1.5"
                    class="text-gray-400"
                    aria-hidden="true"
                />
            </li>
        </ol>
    </nav>
</template>
