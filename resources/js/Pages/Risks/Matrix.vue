<template>
  <AppLayout page-title="Matrice des Risques" page-description="Vue matricielle">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <GlassButton 
            variant="ghost" 
            @click="$inertia.visit(route('risks.index'))"
          >
            <ArrowLeft class="w-5 h-5" />
          </GlassButton>
          <div>
            <h1 class="text-3xl font-bold text-white mb-2">Risk Matrix</h1>
            <p class="text-slate-300">Visual representation of risks by impact and likelihood</p>
          </div>
        </div>
        <GlassButton 
          variant="primary" 
          @click="$inertia.visit(route('risks.create'))"
          v-if="can('create risks')"
        >
          <Plus class="w-5 h-5 mr-2" />
          New Risk
        </GlassButton>
      </div>

      <!-- Legend -->
      <GlassCard>
        <h3 class="text-lg font-semibold text-white mb-4">Risk Severity Legend</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="flex items-center gap-3">
            <div class="w-4 h-4 rounded bg-red-500"></div>
            <div>
              <p class="text-white font-semibold">Critical</p>
              <p class="text-sm text-slate-400">Score: 16-25 (Immediate action required)</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-4 h-4 rounded bg-yellow-500"></div>
            <div>
              <p class="text-white font-semibold">High</p>
              <p class="text-sm text-slate-400">Score: 10-15 (Active management needed)</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-4 h-4 rounded bg-green-500"></div>
            <div>
              <p class="text-white font-semibold">Low to Medium</p>
              <p class="text-sm text-slate-400">Score: 1-9 (Monitor regularly)</p>
            </div>
          </div>
        </div>
      </GlassCard>

      <!-- Risk Matrix -->
      <GlassCard class="overflow-x-auto">
        <div class="min-w-[800px]">
          <!-- Matrix Grid -->
          <div class="grid grid-cols-6 gap-2">
            <!-- Top Left Empty Cell -->
            <div class="flex items-center justify-center p-4">
              <div class="text-center">
                <p class="text-slate-400 text-xs font-semibold">Impact →</p>
                <p class="text-slate-400 text-xs font-semibold">↓ Likelihood</p>
              </div>
            </div>

            <!-- Impact Headers (1-5) -->
            <div 
              v-for="impact in 5" 
              :key="`impact-${impact}`"
              class="flex items-center justify-center p-4 glass-card"
            >
              <div class="text-center">
                <p class="text-white font-bold">{{ impact }}</p>
                <p class="text-xs text-slate-400">{{ getImpactLabel(impact) }}</p>
              </div>
            </div>

            <!-- Matrix Rows (Likelihood 5 to 1) -->
            <template v-for="likelihood in [5, 4, 3, 2, 1]" :key="`row-${likelihood}`">
              <!-- Likelihood Header -->
              <div class="flex items-center justify-center p-4 glass-card">
                <div class="text-center">
                  <p class="text-white font-bold">{{ likelihood }}</p>
                  <p class="text-xs text-slate-400">{{ getLikelihoodLabel(likelihood) }}</p>
                </div>
              </div>

              <!-- Risk Cells -->
              <div 
                v-for="impact in 5" 
                :key="`cell-${likelihood}-${impact}`"
                :class="[
                  'relative p-3 rounded-lg transition-all cursor-pointer min-h-[120px]',
                  getCellColor(impact, likelihood),
                  'hover:scale-105'
                ]"
                @click="showCellRisks(impact, likelihood)"
              >
                <!-- Score Badge -->
                <div class="absolute top-2 right-2 px-2 py-1 rounded-full bg-black/30 text-xs font-bold text-white">
                  {{ impact * likelihood }}
                </div>

                <!-- Risks in this cell -->
                <div class="space-y-1 mt-6">
                  <div 
                    v-for="risk in getRisksInCell(impact, likelihood)"
                    :key="risk.id"
                    class="bg-black/30 p-2 rounded text-xs hover:bg-black/50 transition"
                    @click.stop="viewRisk(risk.id)"
                  >
                    <p class="font-semibold text-white truncate">{{ risk.title }}</p>
                    <p class="text-slate-300 text-xs truncate">{{ risk.project?.code }}</p>
                  </div>
                </div>

                <!-- Count if more risks -->
                <div 
                  v-if="getRisksInCell(impact, likelihood).length > 3"
                  class="text-xs text-center text-white/70 mt-2"
                >
                  +{{ getRisksInCell(impact, likelihood).length - 3 }} more
                </div>

                <!-- Empty state -->
                <div 
                  v-if="getRisksInCell(impact, likelihood).length === 0"
                  class="absolute inset-0 flex items-center justify-center opacity-30"
                >
                  <p class="text-xs text-slate-400">No risks</p>
                </div>
              </div>
            </template>
          </div>
        </div>
      </GlassCard>

      <!-- Summary Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <GlassCard>
          <div class="text-center">
            <p class="text-3xl font-bold text-white mb-2">{{ matrixStats.total }}</p>
            <p class="text-slate-400">Total Risks</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-3xl font-bold text-red-400 mb-2">{{ matrixStats.critical }}</p>
            <p class="text-slate-400">Critical Risks</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-3xl font-bold text-yellow-400 mb-2">{{ matrixStats.high }}</p>
            <p class="text-slate-400">High Risks</p>
          </div>
        </GlassCard>
        <GlassCard>
          <div class="text-center">
            <p class="text-3xl font-bold text-slate-400 mb-2">{{ averageScore.toFixed(1) }}</p>
            <p class="text-slate-400">Average Score</p>
          </div>
        </GlassCard>
      </div>
    </div>

    <!-- Cell Risks Modal -->
    <GlassModal v-model="showCellModal" :title="`Risks with Score ${selectedScore}`">
      <div class="space-y-3">
        <div 
          v-for="risk in selectedCellRisks"
          :key="risk.id"
          class="glass p-4 rounded-lg hover:bg-white/10 cursor-pointer transition"
          @click="viewRisk(risk.id)"
        >
          <h4 class="text-white font-semibold mb-1">{{ risk.title }}</h4>
          <p class="text-sm text-slate-300 mb-2">{{ risk.description }}</p>
          <div class="flex items-center gap-3 text-xs text-slate-400">
            <span>{{ risk.project?.code }}</span>
            <span>Impact: {{ risk.impact }}</span>
            <span>Likelihood: {{ risk.likelihood }}</span>
            <StatusBadge :status="risk.status" size="sm" />
          </div>
        </div>
      </div>
    </GlassModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassModal from '@/Components/Glass/GlassModal.vue'
import StatusBadge from '@/Components/Glass/StatusBadge.vue'
import { ArrowLeft, Plus } from 'lucide-vue-next'

const props = defineProps({
  risks: Array,
})

const showCellModal = ref(false)
const selectedCellRisks = ref([])
const selectedScore = ref(0)

const matrixStats = computed(() => {
  return {
    total: props.risks.length,
    critical: props.risks.filter(r => r.impact * r.likelihood >= 16).length,
    high: props.risks.filter(r => {
      const score = r.impact * r.likelihood
      return score >= 10 && score < 16
    }).length,
  }
})

const averageScore = computed(() => {
  if (props.risks.length === 0) return 0
  const sum = props.risks.reduce((acc, r) => acc + (r.impact * r.likelihood), 0)
  return sum / props.risks.length
})

const getRisksInCell = (impact, likelihood) => {
  return props.risks.filter(r => r.impact === impact && r.likelihood === likelihood)
}

const getCellColor = (impact, likelihood) => {
  const score = impact * likelihood
  if (score >= 16) return 'bg-red-500/20 border border-red-500/30'
  if (score >= 10) return 'bg-yellow-500/20 border border-yellow-500/30'
  return 'bg-green-500/20 border border-green-500/30'
}

const getImpactLabel = (value) => {
  const labels = ['', 'Very Low', 'Low', 'Medium', 'High', 'Very High']
  return labels[value] || ''
}

const getLikelihoodLabel = (value) => {
  const labels = ['', 'Rare', 'Unlikely', 'Possible', 'Likely', 'Almost Certain']
  return labels[value] || ''
}

const showCellRisks = (impact, likelihood) => {
  const risks = getRisksInCell(impact, likelihood)
  if (risks.length > 0) {
    selectedCellRisks.value = risks
    selectedScore.value = impact * likelihood
    showCellModal.value = true
  }
}

const viewRisk = (id) => {
  router.visit(route('risks.show', id))
}

const can = (permission) => {
  return window.$page?.props?.auth?.user?.permissions?.includes(permission)
}
</script>
