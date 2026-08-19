<script setup>
defineProps({
    rows: { type: Number, default: 8 },
    columns: { type: Number, default: 5 },
})
</script>

<template>
    <div aria-hidden="true">
        <div
            v-for="row in rows"
            :key="row"
            class="grid gap-4 border-b border-gray-100 px-4 py-3.5"
            :style="{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }"
        >
            <span
                v-for="column in columns"
                :key="column"
                class="block h-4 bg-gray-100"
                :class="column === 1 ? 'w-3/4' : 'w-1/2'"
            />
        </div>
    </div>
</template>
