<template>
    <div class="space-y-4">
        <!-- Header -->
        <div>
            <h3 class="text-lg font-bold text-gray-900">{{ t('attachments') }}</h3>
            <p class="text-sm text-gray-500">{{ t('max_file_size') }}: 50MB</p>
        </div>

        <!-- Drop Zone -->
        <div
            @drop.prevent="handleDrop"
            @dragover.prevent="isDragover = true"
            @dragleave.prevent="isDragover = false"
            :class="[
                'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                isDragover ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
            ]"
        >
            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0 0V8m0 4H8m4 0h4M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>

            <p class="text-gray-700 font-medium mb-2">{{ t('drag_drop_files') }}</p>
            <p class="text-sm text-gray-500 mb-4">{{ t('or') }}</p>

            <label>
                <input
                    type="file"
                    multiple
                    @change="handleFileSelect"
                    :disabled="uploading"
                    class="hidden"
                />
                <button
                    type="button"
                    :disabled="uploading"
                    @click="$event.target.parentElement.querySelector('input').click()"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 transition-colors"
                >
                    {{ t('browse_files') }}
                </button>
            </label>
        </div>

        <!-- Uploads in Progress -->
        <div v-if="uploadingFiles.length > 0" class="space-y-3">
            <div v-for="file in uploadingFiles" :key="file.id" class="space-y-2">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium text-gray-900">{{ file.name }}</p>
                    <p class="text-xs text-gray-500">{{ file.progress }}%</p>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div
                        class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                        :style="{ width: file.progress + '%' }"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Attachments List -->
        <div v-if="attachments.length > 0" class="space-y-2">
            <div
                v-for="attachment in attachments"
                :key="attachment.id"
                :class="[
                    'flex items-center justify-between p-3 rounded-lg transition-all duration-300 group',
                    attachment.isNew 
                        ? 'bg-blue-50 border-2 border-blue-300 hover:bg-blue-100' 
                        : 'bg-gray-50 hover:bg-gray-100'
                ]"
            >
                <!-- File Info -->
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ attachment.original_name }}</p>
                            <span v-if="attachment.isNew" class="text-xs bg-blue-500 text-white px-2 py-1 rounded-full font-semibold">{{ t('New') }}</span>
                        </div>
                        <p class="text-xs text-gray-500">{{ formatFileSize(attachment.file_size) }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                        @click="downloadFile(attachment)"
                        :disabled="downloading === attachment.id"
                        class="p-1 text-gray-400 hover:text-blue-500 transition-colors"
                        :title="t('download')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0 0V8m0 4H8m4 0h4M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <button
                        @click="deleteFile(attachment)"
                        :disabled="deleting === attachment.id"
                        class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                        :title="t('delete')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!uploading" class="text-center py-8">
            <p class="text-gray-500">{{ t('no_attachments') }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, defineProps } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { useNotificationStore } from '@/stores/notification'

const props = defineProps({
    projectId: {
        type: [Number, String],
        required: true,
    },
    initialAttachments: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const notificationStore = useNotificationStore()
const t = (key) => {
    return page.props.translations?.[key] || key
}

const attachments = ref(props.initialAttachments)
const isDragover = ref(false)
const uploading = ref(false)
const downloading = ref(null)
const deleting = ref(null)
const uploadingFiles = ref([])

const handleDrop = async (event) => {
    isDragover.value = false
    const files = event.dataTransfer.files
    await uploadFiles(files)
}

const handleFileSelect = async (event) => {
    const files = event.target.files
    await uploadFiles(files)
    event.target.value = '' // Reset input
}

const uploadFiles = async (files) => {
    if (files.length === 0) return

    uploading.value = true

    for (const file of files) {
        const fileId = Math.random()
        const uploadingFile = { id: fileId, name: file.name, progress: 0 }
        uploadingFiles.value.push(uploadingFile)

        try {
            const formData = new FormData()
            formData.append('file', file)

            const response = await axios.post(
                `/api/projects/${props.projectId}/attachments`,
                formData,
                {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    onUploadProgress: (e) => {
                        if (e.total) {
                            uploadingFile.progress = Math.round((e.loaded / e.total) * 100)
                        }
                    },
                }
            )

            attachments.value.push({
                ...response.data.data,
                isNew: true
            })
            
            // Remove 'isNew' flag after a short delay
            setTimeout(() => {
                const newAttachment = attachments.value.find(a => a.id === response.data.data.id)
                if (newAttachment) {
                    newAttachment.isNew = false
                }
            }, 3000)
            
            notificationStore.success(t('File uploaded successfully!'))
        } catch (error) {
            console.error('Error uploading file:', error)
            notificationStore.error(t('Error uploading file'))
        } finally {
            uploadingFiles.value = uploadingFiles.value.filter(f => f.id !== fileId)
        }
    }

    uploading.value = false
}

const downloadFile = async (attachment) => {
    downloading.value = attachment.id
    try {
        const response = await axios.get(
            `/api/attachments/${attachment.id}/download`,
            { responseType: 'blob' }
        )

        const url = window.URL.createObjectURL(response.data)
        const link = document.createElement('a')
        link.href = url
        link.download = attachment.original_name
        link.click()
        window.URL.revokeObjectURL(url)
    } catch (error) {
        console.error('Error downloading file:', error)
    } finally {
        downloading.value = null
    }
}

const deleteFile = async (attachment) => {
    if (!confirm(t('confirm_delete'))) return

    deleting.value = attachment.id
    try {
        await axios.delete(`/api/attachments/${attachment.id}`)
        attachments.value = attachments.value.filter(a => a.id !== attachment.id)
        notificationStore.success(t('File deleted successfully!'))
    } catch (error) {
        console.error('Error deleting file:', error)
        notificationStore.error(error.response?.data?.message || t('Error deleting file'))
    } finally {
        deleting.value = null
    }
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}
</script>
