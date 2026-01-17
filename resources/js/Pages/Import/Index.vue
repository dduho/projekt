<template>
  <AppLayout title="Import Excel">
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-white">Import de Donnees</h1>
          <p class="text-white/60 mt-1">Importez vos projets, risques et demandes de changement depuis un fichier Excel</p>
        </div>
        <GlassButton
          variant="secondary"
          :icon="Download"
          @click="downloadTemplate"
        >
          Telecharger le template
        </GlassButton>
      </div>

      <!-- Upload Zone -->
      <GlassCard>
        <template #header>
          <div class="flex items-center gap-3">
            <FileSpreadsheet class="w-5 h-5 text-emerald-400" />
            <h2 class="text-lg font-semibold text-white">1. Selectionner un fichier</h2>
          </div>
        </template>

        <div
          class="border-2 border-dashed border-white/30 rounded-xl p-8 text-center transition-colors"
          :class="{
            'border-emerald-400/50 bg-emerald-400/10': isDragging,
            'hover:border-white/50': !isDragging
          }"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleDrop"
        >
          <Upload class="w-12 h-12 text-white/40 mx-auto mb-4" />

          <div v-if="!selectedFile">
            <p class="text-white/80 mb-2">Glissez-deposez votre fichier Excel ici</p>
            <p class="text-white/50 text-sm mb-4">ou</p>
            <label class="cursor-pointer">
              <GlassButton variant="primary" as="span">
                Parcourir les fichiers
              </GlassButton>
              <input
                type="file"
                class="hidden"
                accept=".xlsx,.xls"
                @change="handleFileSelect"
              />
            </label>
            <p class="text-white/40 text-xs mt-4">Formats acceptes: .xlsx, .xls (max 10MB)</p>
          </div>

          <div v-else class="flex items-center justify-center gap-4">
            <div class="flex items-center gap-3 px-4 py-2 bg-white/10 rounded-lg">
              <FileSpreadsheet class="w-8 h-8 text-emerald-400" />
              <div class="text-left">
                <p class="text-white font-medium">{{ selectedFile.name }}</p>
                <p class="text-white/50 text-sm">{{ formatFileSize(selectedFile.size) }}</p>
              </div>
            </div>
            <GlassButton variant="ghost" size="sm" @click="clearFile">
              <X class="w-4 h-4" />
            </GlassButton>
          </div>
        </div>
      </GlassCard>

      <!-- Validation Results -->
      <GlassCard v-if="validation">
        <template #header>
          <div class="flex items-center gap-3">
            <CheckCircle v-if="validation.valid" class="w-5 h-5 text-emerald-400" />
            <AlertCircle v-else class="w-5 h-5 text-red-400" />
            <h2 class="text-lg font-semibold text-white">2. Validation du fichier</h2>
          </div>
        </template>

        <div class="space-y-4">
          <!-- Sheets Status -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div
              v-for="(info, sheetName) in validation.sheets"
              :key="sheetName"
              class="p-3 rounded-lg"
              :class="info.exists ? 'bg-emerald-400/10 border border-emerald-400/30' : 'bg-red-400/10 border border-red-400/30'"
            >
              <div class="flex items-center gap-2 mb-1">
                <CheckCircle v-if="info.exists" class="w-4 h-4 text-emerald-400" />
                <X v-else class="w-4 h-4 text-red-400" />
                <span class="text-white text-sm font-medium truncate">{{ sheetName }}</span>
              </div>
              <p class="text-white/60 text-xs">
                {{ info.exists ? `${info.rows} lignes` : 'Non trouve' }}
              </p>
            </div>
          </div>

          <!-- Missing Sheets Warning -->
          <div v-if="validation.missing_sheets?.length" class="p-4 bg-amber-400/10 border border-amber-400/30 rounded-lg">
            <div class="flex items-start gap-3">
              <AlertTriangle class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-amber-300 font-medium">Feuilles manquantes</p>
                <p class="text-white/60 text-sm mt-1">
                  Les feuilles suivantes n'ont pas ete trouvees: {{ validation.missing_sheets.join(', ') }}
                </p>
              </div>
            </div>
          </div>

          <!-- Validation Success -->
          <div v-if="validation.valid" class="p-4 bg-emerald-400/10 border border-emerald-400/30 rounded-lg">
            <div class="flex items-center gap-3">
              <CheckCircle class="w-5 h-5 text-emerald-400" />
              <p class="text-emerald-300">Fichier valide et pret pour l'import!</p>
            </div>
          </div>
        </div>
      </GlassCard>

      <!-- Preview -->
      <GlassCard v-if="preview && validation?.valid">
        <template #header>
          <div class="flex items-center gap-3">
            <Eye class="w-5 h-5 text-blue-400" />
            <h2 class="text-lg font-semibold text-white">3. Apercu des donnees</h2>
          </div>
        </template>

        <div class="space-y-6">
          <div v-for="(data, sheetName) in preview" :key="sheetName">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-white font-medium">{{ sheetName }}</h3>
              <span class="text-white/50 text-sm">{{ data.total_rows }} lignes</span>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr>
                    <th
                      v-for="(header, i) in data.headers.slice(0, 6)"
                      :key="i"
                      class="px-3 py-2 text-left text-white/60 bg-white/5 border-b border-white/10 font-medium"
                    >
                      {{ header || '-' }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, i) in data.sample" :key="i">
                    <td
                      v-for="(cell, j) in row.slice(0, 6)"
                      :key="j"
                      class="px-3 py-2 text-white/80 border-b border-white/5 truncate max-w-[150px]"
                    >
                      {{ cell || '-' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </GlassCard>

      <!-- Import Button -->
      <div v-if="validation?.valid && filePath" class="flex justify-end gap-4">
        <GlassButton variant="secondary" @click="clearFile">
          Annuler
        </GlassButton>
        <GlassButton
          variant="primary"
          :icon="Upload"
          :loading="importing"
          @click="startImport"
        >
          Lancer l'import
        </GlassButton>
      </div>

      <!-- Import Results -->
      <GlassCard v-if="importResult">
        <template #header>
          <div class="flex items-center gap-3">
            <CheckCircle v-if="importResult.success" class="w-5 h-5 text-emerald-400" />
            <AlertCircle v-else class="w-5 h-5 text-red-400" />
            <h2 class="text-lg font-semibold text-white">Resultat de l'import</h2>
          </div>
        </template>

        <div class="space-y-4">
          <!-- Stats -->
          <div class="grid grid-cols-3 gap-4">
            <div class="text-center p-4 bg-white/5 rounded-lg">
              <p class="text-2xl font-bold text-white">
                {{ importResult.stats?.projects?.created || 0 }}
              </p>
              <p class="text-white/60 text-sm">Projets crees</p>
              <p class="text-emerald-400 text-xs mt-1">
                +{{ importResult.stats?.projects?.updated || 0 }} mis a jour
              </p>
            </div>
            <div class="text-center p-4 bg-white/5 rounded-lg">
              <p class="text-2xl font-bold text-white">
                {{ importResult.stats?.risks?.created || 0 }}
              </p>
              <p class="text-white/60 text-sm">Risques importes</p>
            </div>
            <div class="text-center p-4 bg-white/5 rounded-lg">
              <p class="text-2xl font-bold text-white">
                {{ importResult.stats?.changes?.created || 0 }}
              </p>
              <p class="text-white/60 text-sm">Changements importes</p>
            </div>
          </div>

          <!-- Errors -->
          <div v-if="importResult.errors?.length" class="p-4 bg-red-400/10 border border-red-400/30 rounded-lg">
            <div class="flex items-start gap-3">
              <AlertTriangle class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-red-300 font-medium">Erreurs rencontrees ({{ importResult.errors.length }})</p>
                <ul class="text-white/60 text-sm mt-2 space-y-1 max-h-40 overflow-y-auto">
                  <li v-for="(error, i) in importResult.errors" :key="i">{{ error }}</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Success Actions -->
          <div v-if="importResult.success" class="flex justify-end gap-3">
            <GlassButton variant="secondary" @click="resetImport">
              Nouvel import
            </GlassButton>
            <GlassButton variant="primary" :href="route('projects.index')">
              Voir les projets
            </GlassButton>
          </div>
        </div>
      </GlassCard>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import {
  Upload,
  Download,
  FileSpreadsheet,
  CheckCircle,
  AlertCircle,
  AlertTriangle,
  X,
  Eye,
} from 'lucide-vue-next';
import axios from 'axios';

const page = usePage();

const selectedFile = ref(null);
const filePath = ref(null);
const validation = ref(null);
const preview = ref(null);
const importing = ref(false);
const isDragging = ref(false);

// Get flash messages from Inertia
const importResult = computed(() => {
  const flash = page.props.flash || {};
  if (flash.import_stats) {
    return {
      success: !!flash.success,
      stats: flash.import_stats,
      errors: flash.import_errors || [],
    };
  }
  return null;
});

const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    processFile(file);
  }
};

const handleDrop = (event) => {
  isDragging.value = false;
  const file = event.dataTransfer.files[0];
  if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls'))) {
    processFile(file);
  }
};

const processFile = async (file) => {
  selectedFile.value = file;
  validation.value = null;
  preview.value = null;
  filePath.value = null;

  const formData = new FormData();
  formData.append('file', file);

  try {
    // Validate file
    const validateResponse = await axios.post('/import/validate', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    validation.value = validateResponse.data.validation;
    filePath.value = validateResponse.data.file_path;

    // If valid, get preview
    if (validateResponse.data.validation.valid) {
      const previewResponse = await axios.post('/import/preview', {
        file_path: validateResponse.data.file_path,
      });
      preview.value = previewResponse.data.preview;
    }
  } catch (error) {
    console.error('Validation error:', error);
    validation.value = {
      valid: false,
      error: error.response?.data?.message || 'Erreur lors de la validation',
    };
  }
};

const clearFile = () => {
  selectedFile.value = null;
  validation.value = null;
  preview.value = null;
  filePath.value = null;
};

const startImport = () => {
  if (!filePath.value) return;

  importing.value = true;

  router.post('/import', {
    file_path: filePath.value,
  }, {
    onFinish: () => {
      importing.value = false;
      clearFile();
    },
  });
};

const resetImport = () => {
  clearFile();
  router.reload({ only: ['flash'] });
};

const downloadTemplate = () => {
  window.location.href = '/import/template';
};

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const route = (name) => {
  const routes = {
    'projects.index': '/projects',
  };
  return routes[name] || '/';
};
</script>
