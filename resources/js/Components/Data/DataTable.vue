<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { ArrowDown, ArrowUp, ChevronRight } from 'lucide-vue-next'
import TablePagination from './TablePagination.vue'
import TableSkeleton from './TableSkeleton.vue'
import EmptyState from '../Feedback/EmptyState.vue'
import BaseButton from '../Base/BaseButton.vue'
import { useBreakpoint } from '../../Composables/useBreakpoint.js'

/**
 * The one table in the product. Server-side sorting, filtering and pagination;
 * under 768px every row becomes a stacked card, per BUILD_SPEC Part D.
 *
 * Columns: { key, label, align?, mono?, width?, sortable?, cardRole? }
 * cardRole marks what a column becomes on mobile:
 *   'primary'  the bold identifier
 *   'status'   the dot in the top right
 *   'meta'     a label/value row (first two or three only)
 *   omitted    hidden on mobile
 */
const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
    sort: { type: String, default: null },
    direction: { type: String, default: 'desc' },
    rowHref: { type: Function, default: null },
    loading: { type: Boolean, default: false },
    emptyTitle: { type: String, default: 'Keine Einträge' },
    emptyDescription: { type: String, default: '' },
    /**
     * The filters currently narrowing the list. When these are set and nothing
     * came back, the empty state says so and offers a way out — otherwise a
     * filtered-to-nothing table reads as "there is no data", which sends people
     * looking for a bug that is not there.
     */
    activeFilters: { type: Object, default: () => ({}) },
    resetHref: { type: String, default: null },
    emptyIcon: { type: [Object, Function], default: null },
    only: { type: Array, default: () => [] },
})

const { isMobile } = useBreakpoint()

const primary = computed(() => props.columns.find((c) => c.cardRole === 'primary') ?? props.columns[0])
const statusColumn = computed(() => props.columns.find((c) => c.cardRole === 'status'))
const metaColumns = computed(() => props.columns.filter((c) => c.cardRole === 'meta').slice(0, 3))

const applySort = (column) => {
    if (!column.sortable) return

    const nextDirection = props.sort === column.key && props.direction === 'asc' ? 'desc' : 'asc'

    router.get(
        window.location.pathname,
        { ...currentQuery(), sort: column.key, direction: nextDirection },
        { preserveState: true, preserveScroll: true, replace: true, only: props.only }
    )
}

const currentQuery = () => Object.fromEntries(new URLSearchParams(window.location.search))

const alignClass = (column) => ({
    right: 'text-right',
    center: 'text-center',
}[column.align] ?? 'text-left')
</script>

<template>
    <div class="border border-gray-200 bg-white">
        <slot name="toolbar" />

        <TableSkeleton v-if="loading" :columns="columns.length" />

        <EmptyState
            v-else-if="!rows.length && hasFilters"
            title="Kein Ergebnis"
            :description="filteredDescription"
            class="border-0"
        >
            <BaseButton v-if="resetHref" :href="resetHref" variant="secondary" size="compact">
                Filter zurücksetzen
            </BaseButton>
        </EmptyState>

        <EmptyState
            v-else-if="!rows.length"
            :title="emptyTitle"
            :description="emptyDescription"
            :icon="emptyIcon"
            class="border-0"
        >
            <slot name="empty-action" />
        </EmptyState>

        <!-- Desktop: a real table, square corners per Foundations -->
        <div v-else-if="!isMobile" class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            class="px-4 py-3 text-eyebrow font-semibold uppercase text-gray-600"
                            :class="alignClass(column)"
                            :style="column.width ? { width: column.width } : undefined"
                            :aria-sort="sort === column.key ? (direction === 'asc' ? 'ascending' : 'descending') : undefined"
                        >
                            <button
                                v-if="column.sortable"
                                type="button"
                                class="inline-flex items-center gap-1.5 hover:text-navy-700"
                                @click="applySort(column)"
                            >
                                {{ column.label }}
                                <ArrowUp v-if="sort === column.key && direction === 'asc'" :size="12" :stroke-width="1.5" aria-hidden="true" />
                                <ArrowDown v-else-if="sort === column.key" :size="12" :stroke-width="1.5" aria-hidden="true" />
                            </button>
                            <template v-else>{{ column.label }}</template>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, index) in rows"
                        :key="row.id ?? index"
                        class="border-b border-gray-100 transition-colors duration-(--duration-hover) ease-(--ease-dkgz) last:border-b-0 hover:bg-gray-50"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="px-4 py-3.5 text-sm text-gray-800"
                            :class="[alignClass(column), column.mono ? 'font-mono tabular-nums' : '']"
                        >
                            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                                <Link
                                    v-if="rowHref && column === primary"
                                    :href="rowHref(row)"
                                    class="font-medium text-navy-700 hover:text-navy-500"
                                >{{ row[column.key] }}</Link>
                                <template v-else>{{ row[column.key] ?? '—' }}</template>
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Below 768px: stacked cards, tap the card to open the detail -->
        <div v-else>
            <component
                :is="rowHref ? Link : 'div'"
                v-for="(row, index) in rows"
                :key="row.id ?? index"
                :href="rowHref ? rowHref(row) : undefined"
                class="flex items-start gap-3 border-b border-gray-100 px-4 py-4 last:border-b-0"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <span class="min-w-0 truncate text-base font-medium text-navy-700" :class="primary?.mono ? 'font-mono tabular-nums' : ''">
                            <slot :name="`cell-${primary.key}`" :row="row" :value="row[primary.key]">{{ row[primary.key] }}</slot>
                        </span>
                        <span v-if="statusColumn" class="shrink-0">
                            <slot :name="`cell-${statusColumn.key}`" :row="row" :value="row[statusColumn.key]">{{ row[statusColumn.key] }}</slot>
                        </span>
                    </div>

                    <dl class="pt-2">
                        <div
                            v-for="column in metaColumns"
                            :key="column.key"
                            class="flex items-baseline justify-between gap-3 py-0.5"
                        >
                            <dt class="shrink-0 text-xs text-gray-600">{{ column.label }}</dt>
                            <dd class="min-w-0 truncate text-right text-sm text-gray-800" :class="column.mono ? 'font-mono tabular-nums' : ''">
                                <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">{{ row[column.key] ?? '—' }}</slot>
                            </dd>
                        </div>
                    </dl>
                </div>

                <ChevronRight v-if="rowHref" :size="16" :stroke-width="1.5" class="mt-1 shrink-0 text-gray-400" aria-hidden="true" />
            </component>
        </div>

        <TablePagination v-if="meta && rows.length" :meta="meta" />
    </div>
</template>
