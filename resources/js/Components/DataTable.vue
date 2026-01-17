<template>
    <div class="glass-card">
        <!-- Header with Search and Actions -->
        <div class="card-header">
            <div class="flex-1">
                <h3 v-if="title" class="card-title">{{ title }}</h3>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Search -->
                <div v-if="searchable" class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Rechercher..."
                        class="input-glass w-64 pl-10"
                    />
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                </div>
                
                <!-- Slot for custom actions -->
                <slot name="actions" />
            </div>
        </div>

        <!-- Filters (optional) -->
        <div v-if="$slots.filters" class="mb-6 pb-4 border-b border-white/10">
            <slot name="filters" />
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="table-glass">
                <thead>
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            :class="column.headerClass"
                            @click="column.sortable ? handleSort(column.key) : null"
                            class="cursor-pointer"
                        >
                            <div class="flex items-center gap-2">
                                {{ column.label }}
                                <component
                                    v-if="column.sortable && sortColumn === column.key"
                                    :is="sortDirection === 'asc' ? ArrowUp : ArrowDown"
                                    class="w-4 h-4"
                                />
                            </div>
                        </th>
                        <th v-if="$slots.actions" class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="text-center py-8">
                            <div class="spinner w-8 h-8 mx-auto" />
                        </td>
                    </tr>
                    <tr v-else-if="paginatedData.length === 0">
                        <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="text-center py-8">
                            <p class="text-gray-400">{{ emptyMessage }}</p>
                        </td>
                    </tr>
                    <tr v-else v-for="(row, index) in paginatedData" :key="row[rowKey] || index">
                        <td v-for="column in columns" :key="column.key" :class="column.cellClass">
                            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                                {{ formatCell(row[column.key], column) }}
                            </slot>
                        </td>
                        <td v-if="$slots.actions" class="text-right">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="paginate && !loading && totalPages > 1" class="flex items-center justify-between mt-6 pt-4 border-t border-white/10">
            <div class="text-sm text-gray-400">
                Affichage {{ (currentPage - 1) * perPage + 1 }} à {{ Math.min(currentPage * perPage, filteredData.length) }} sur {{ filteredData.length }} résultats
            </div>
            
            <div class="flex items-center gap-2">
                <button
                    @click="goToPage(currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="btn btn-ghost btn-sm"
                >
                    <ChevronLeft class="w-4 h-4" />
                </button>
                
                <button
                    v-for="page in visiblePages"
                    :key="page"
                    @click="goToPage(page)"
                    class="btn btn-sm"
                    :class="page === currentPage ? 'btn-primary' : 'btn-ghost'"
                >
                    {{ page }}
                </button>
                
                <button
                    @click="goToPage(currentPage + 1)"
                    :disabled="currentPage === totalPages"
                    class="btn btn-ghost btn-sm"
                >
                    <ChevronRight class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Search, ArrowUp, ArrowDown, ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    columns: {
        type: Array,
        required: true
    },
    data: {
        type: Array,
        required: true
    },
    rowKey: {
        type: String,
        default: 'id'
    },
    searchable: {
        type: Boolean,
        default: true
    },
    paginate: {
        type: Boolean,
        default: true
    },
    perPage: {
        type: Number,
        default: 15
    },
    loading: {
        type: Boolean,
        default: false
    },
    emptyMessage: {
        type: String,
        default: 'Aucune donnée disponible'
    }
});

const emit = defineEmits(['row-click']);

const searchQuery = ref('');
const sortColumn = ref('');
const sortDirection = ref('asc');
const currentPage = ref(1);

// Filtered data based on search
const filteredData = computed(() => {
    if (!searchQuery.value) return props.data;
    
    const query = searchQuery.value.toLowerCase();
    return props.data.filter(row => {
        return props.columns.some(column => {
            const value = row[column.key];
            return value && value.toString().toLowerCase().includes(query);
        });
    });
});

// Sorted data
const sortedData = computed(() => {
    if (!sortColumn.value) return filteredData.value;
    
    return [...filteredData.value].sort((a, b) => {
        const aVal = a[sortColumn.value];
        const bVal = b[sortColumn.value];
        
        if (aVal === bVal) return 0;
        
        const comparison = aVal > bVal ? 1 : -1;
        return sortDirection.value === 'asc' ? comparison : -comparison;
    });
});

// Paginated data
const totalPages = computed(() => {
    if (!props.paginate) return 1;
    return Math.ceil(sortedData.value.length / props.perPage);
});

const paginatedData = computed(() => {
    if (!props.paginate) return sortedData.value;
    
    const start = (currentPage.value - 1) * props.perPage;
    const end = start + props.perPage;
    return sortedData.value.slice(start, end);
});

const visiblePages = computed(() => {
    const pages = [];
    const maxVisible = 5;
    const halfVisible = Math.floor(maxVisible / 2);
    
    let start = Math.max(1, currentPage.value - halfVisible);
    let end = Math.min(totalPages.value, start + maxVisible - 1);
    
    if (end - start < maxVisible - 1) {
        start = Math.max(1, end - maxVisible + 1);
    }
    
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    
    return pages;
});

const handleSort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const formatCell = (value, column) => {
    if (column.format) {
        return column.format(value);
    }
    return value;
};
</script>
