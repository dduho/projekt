<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">{{ t('checklist') }}</h3>
                <p class="text-sm text-gray-500">{{ completedCount }} / {{ items.length }} {{ t('tasks_completed') }}</p>
            </div>
        </div>

        <!-- Progress Bar -->
        <div v-if="items.length > 0" class="w-full bg-gray-200 rounded-full h-2">
            <div
                class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                :style="{ width: completionPercentage + '%' }"
            ></div>
        </div>

        <!-- Add Item Form -->
        <div class="flex gap-2">
            <input
                v-model="newItemTitle"
                type="text"
                :placeholder="t('add_new_task')"
                @keyup.enter="addItem"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <button
                @click="addItem"
                :disabled="!newItemTitle.trim() || loading"
                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 transition-colors"
            >
                {{ t('add') }}
            </button>
        </div>

        <!-- Items List -->
        <div v-if="items.length > 0" class="space-y-2">
            <draggable
                v-model="items"
                :options="{ animation: 200, ghostClass: 'opacity-50' }"
                @change="reorderItems"
                class="space-y-2"
            >
                <div
                    v-for="item in items"
                    :key="item.id"
                    class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group"
                >
                    <!-- Checkbox -->
                    <input
                        type="checkbox"
                        :checked="item.completed"
                        @change="toggleItem(item)"
                        class="w-5 h-5 text-blue-500 rounded cursor-pointer"
                    />

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <p :class="['text-sm font-medium', item.completed ? 'line-through text-gray-400' : 'text-gray-900']">
                            {{ item.title }}
                        </p>
                        <p v-if="item.description" class="text-xs text-gray-500 line-clamp-1">
                            {{ item.description }}
                        </p>
                    </div>

                    <!-- Delete Button -->
                    <button
                        @click="deleteItem(item)"
                        :disabled="loading"
                        class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-red-500 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>

                    <!-- Drag Handle -->
                    <div class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 cursor-move">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 5a2 2 0 11-4 0 2 2 0 014 0zM4 13a2 2 0 11-4 0 2 2 0 014 0zM4 21a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path d="M12 5a2 2 0 11-4 0 2 2 0 014 0zM12 13a2 2 0 11-4 0 2 2 0 014 0zM12 21a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </draggable>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8">
            <p class="text-gray-500">{{ t('no_checklist_items') }}</p>
        </div>

        <!-- Loading Toast -->
        <Transition>
            <div v-if="loading" class="fixed bottom-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg">
                {{ t('saving') }}...
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import draggable from 'vuedraggable'
import axios from 'axios'

const props = defineProps({
    projectId: {
        type: [Number, String],
        required: true,
    },
    initialItems: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const t = (key, replacements = {}) => {
    const translation = page.props.translations?.[key] || key
    let result = translation

    Object.entries(replacements).forEach(([key, value]) => {
        result = result.replace(`:${key}`, value)
    })

    return result
}

const items = ref(props.initialItems)
const newItemTitle = ref('')
const loading = ref(false)

const completedCount = computed(() => items.value.filter(item => item.completed).length)
const completionPercentage = computed(() => {
    return items.value.length > 0 ? Math.round((completedCount.value / items.value.length) * 100) : 0
})

const addItem = async () => {
    if (!newItemTitle.value.trim()) return

    loading.value = true
    try {
        const response = await axios.post(
            `/api/projects/${props.projectId}/checklist-items`,
            { title: newItemTitle.value }
        )

        items.value.push(response.data.data)
        newItemTitle.value = ''
    } catch (error) {
        console.error('Error adding checklist item:', error)
    } finally {
        loading.value = false
    }
}

const toggleItem = async (item) => {
    loading.value = true
    try {
        const response = await axios.patch(
            `/api/checklist-items/${item.id}`,
            { completed: !item.completed }
        )

        Object.assign(item, response.data.data)
    } catch (error) {
        console.error('Error updating checklist item:', error)
    } finally {
        loading.value = false
    }
}

const deleteItem = async (item) => {
    if (!confirm(t('confirm_delete'))) return

    loading.value = true
    try {
        await axios.delete(`/api/checklist-items/${item.id}`)
        items.value = items.value.filter(i => i.id !== item.id)
    } catch (error) {
        console.error('Error deleting checklist item:', error)
    } finally {
        loading.value = false
    }
}

const reorderItems = async () => {
    loading.value = true
    try {
        await axios.post(
            `/api/projects/${props.projectId}/checklist-items/reorder`,
            {
                items: items.value.map((item, index) => ({
                    id: item.id,
                    order: index + 1,
                })),
            }
        )
    } catch (error) {
        console.error('Error reordering items:', error)
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.line-clamp-1 {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
