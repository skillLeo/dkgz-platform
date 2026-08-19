<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({ areas: { type: Array, default: () => [] } })
</script>

<template>
    <Head title="Einsatzgebiet" />

    <div class="min-h-screen bg-gray-100 p-6">
        <div class="mx-auto max-w-(--container-shell)">
            <h1 class="text-h2 font-semibold text-navy-700">Einsatzgebiet</h1>
            <ul class="pt-6">
                <li v-for="area in areas" :key="area.id" class="border-b border-gray-200 py-3 font-mono text-sm tabular-nums text-gray-800">
                    {{ area.range }}
                </li>
            </ul>
        </div>
    </div>
</template>
