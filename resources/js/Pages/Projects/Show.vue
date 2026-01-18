<template>
  <AppLayout :page-title="t('Project Details')" :page-description="t('Project Information')">
    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <GlassButton
            variant="ghost"
            @click="$inertia.visit(route('projects.index'))"
          >
            <ArrowLeft class="w-5 h-5" />
          </GlassButton>
          <div>
            <div class="flex items-center gap-3 mb-2">
              <h1 :class="['text-3xl font-bold', textPrimary]">{{ project.name }}</h1>
              <StatusBadge :status="project.calculated_rag_status ?? project.rag_status ?? 'gray'" />
            </div>
            <p :class="textSecondary">{{ project.project_code }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <GlassButton
            variant="secondary"
            @click="$inertia.visit(route('projects.edit', project.id))"
            v-if="can('edit projects')"
          >
            <Edit class="w-4 h-4 mr-2" />
            {{ t('Edit') }}
          </GlassButton>
          <GlassButton
            variant="danger"
            @click="confirmDelete"
            v-if="can('delete projects')"
          >
            <Trash2 class="w-4 h-4" />
          </GlassButton>
        </div>
      </div>

      <!-- Overview Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <GlassCard>
          <div class="flex items-center justify-between">
            <div>
              <p :class="[textMuted, 'text-sm mb-1']">{{ t('Completion') }}</p>
              <p :class="['text-2xl font-bold', textPrimary]">{{ project.calculated_completion_percent ?? project.completion_percent ?? 0 }}%</p>
            </div>
            <TrendingUp class="w-8 h-8 text-prism-400" />
          </div>
          <ProgressBar :progress="project.calculated_completion_percent ?? project.completion_percent ?? 0" :status="project.calculated_rag_status ?? project.rag_status" class="mt-3" />
        </GlassCard>

        <GlassCard>
          <div class="flex items-center justify-between">
            <div>
              <p :class="[textMuted, 'text-sm mb-1']">{{ t('Priority') }}</p>
              <p class="text-2xl font-bold" :class="priorityTextClass(project.priority)">{{ te('priority', project.priority) || '-' }}</p>
            </div>
            <Flag class="w-8 h-8" :class="priorityTextClass(project.priority)" />
          </div>
          <p :class="['text-sm mt-2', textSecondary]">FRS: {{ te('frs_status', project.frs_status) || '-' }}</p>
        </GlassCard>

        <GlassCard>
          <div class="flex items-center justify-between">
            <div>
              <p :class="[textMuted, 'text-sm mb-1']">{{ t('Risks') }}</p>
              <div class="flex items-center gap-2">
                <p :class="['text-2xl font-bold', textPrimary]">{{ project.risks_count || 0 }}</p>
                <span 
                  v-if="project.ml_risk_analysis" 
                  :class="[
                    'text-xs px-2 py-0.5 rounded-full font-bold',
                    riskLevelClass(project.ml_risk_analysis.level)
                  ]"
                  :title="`${t('ML Score')}: ${(project.ml_risk_analysis.score * 100).toFixed(0)}%`"
                >
                  {{ project.ml_risk_analysis.level }}
                </span>
              </div>
            </div>
            <AlertTriangle class="w-8 h-8 text-yellow-400" />
          </div>
          <GlassButton
            variant="ghost"
            size="sm"
            class="w-full mt-2"
            @click="activeTab = 'risks'"
          >
            {{ t('View Risks') }}
          </GlassButton>
        </GlassCard>

        <GlassCard>
          <div class="flex items-center justify-between">
            <div>
              <p :class="[textMuted, 'text-sm mb-1']">{{ t('Changes') }}</p>
              <p :class="['text-2xl font-bold', textPrimary]">{{ project.changes_count || 0 }}</p>
            </div>
            <FileText class="w-8 h-8 text-blue-400" />
          </div>
          <GlassButton
            variant="ghost"
            size="sm"
            class="mt-2 w-full"
            @click="activeTab = 'changes'"
          >
            {{ t('View Changes') }}
          </GlassButton>
        </GlassCard>
      </div>

      <!-- Details & Tabs -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar -->
        <div class="space-y-4">
          <GlassCard>
            <h3 :class="['text-lg font-semibold mb-4', textPrimary]">{{ t('Project Details') }}</h3>
            <div class="space-y-3">
              <div>
                <p :class="[textMuted, 'text-sm']">{{ t('Category') }}</p>
                <div class="flex items-center gap-2 mt-1">
                  <div
                    class="w-3 h-3 rounded-full"
                    :style="{ backgroundColor: project.category?.color || '#6366f1' }"
                  ></div>
                  <p :class="textPrimary">{{ project.category?.name || '-' }}</p>
                </div>
              </div>
              <div v-if="project.business_area">
                <p :class="[textMuted, 'text-sm']">{{ t('Business Area') }}</p>
                <p :class="textPrimary">{{ project.business_area }}</p>
              </div>
              <div>
                <p :class="[textMuted, 'text-sm']">{{ t('Dev Status') }}</p>
                <p :class="textPrimary">{{ te('dev_status', project.dev_status) || '-' }}</p>
              </div>
              <div>
                <p :class="[textMuted, 'text-sm']">{{ t('Target Date') }}</p>
                <p :class="textPrimary">{{ formatDate(project.target_date) }}</p>
              </div>
              <div v-if="project.submission_date">
                <p :class="[textMuted, 'text-sm']">{{ t('Submission Date') }}</p>
                <p :class="textPrimary">{{ formatDate(project.submission_date) }}</p>
              </div>
              <div v-if="project.planned_release">
                <p :class="[textMuted, 'text-sm']">{{ t('Planned Release') }}</p>
                <p :class="textPrimary">{{ formatPlannedRelease(project.planned_release) }}</p>
              </div>
              
              <!-- Owner - Editable -->
              <div>
                <div class="flex items-center justify-between">
                  <p :class="[textMuted, 'text-sm']">{{ t('Owner') }}</p>
                  <button 
                    v-if="can('edit projects') && !editingOwner" 
                    @click="editingOwner = true"
                    :class="['text-xs text-prism-400 hover:text-prism-300']"
                  >
                    {{ t('Edit') }}
                  </button>
                </div>
                <div v-if="editingOwner && can('edit projects')" class="mt-1 space-y-2">
                  <input 
                    v-model="ownerText"
                    type="text"
                    :placeholder="t('Owner name placeholder')"
                    :class="[
                      'w-full px-3 py-2 rounded-lg text-sm',
                      isDarkText ? 'bg-white border border-gray-300 text-gray-900 placeholder-gray-400' : 'bg-white/10 border border-white/20 text-white placeholder-gray-400'
                    ]"
                  />
                  <div class="flex gap-2">
                    <GlassButton size="sm" @click="updateOwner">{{ t('Save') }}</GlassButton>
                    <GlassButton size="sm" variant="ghost" @click="cancelOwnerEdit">{{ t('Cancel') }}</GlassButton>
                  </div>
                </div>
                <div v-else class="flex items-center gap-2 mt-1">
                  <User class="w-4 h-4" :class="textMuted" />
                  <p :class="textPrimary">{{ project.owner || t('Unassigned') }}</p>
                </div>
              </div>

              <div v-if="project.current_progress">
                <p :class="[textMuted, 'text-sm']">{{ t('Current Progress') }}</p>
                <p :class="textPrimary">{{ project.current_progress }}</p>
              </div>
            </div>
          </GlassCard>

          <!-- Blockers - Editable -->
          <GlassCard>
              <div class="flex items-center justify-between mb-3">
                <h3 :class="['text-lg font-semibold flex items-center gap-2', textPrimary]">
                  <AlertCircle class="w-5 h-5 text-red-400" />
                  {{ t('Blockers') }}
                </h3>
                <button 
                  v-if="can('edit projects') && !editingBlockers" 
                  @click="editingBlockers = true"
                  :class="['text-xs text-prism-400 hover:text-prism-300']"
                >
                  {{ t('Edit') }}
                </button>
              </div>
            
            <div v-if="editingBlockers && can('edit projects')" class="space-y-2">
              <textarea 
                v-model="blockersText"
                rows="3"
                :placeholder="t('Describe blockers')"
                :class="[
                  'w-full px-3 py-2 rounded-lg text-sm resize-none',
                  isDarkText ? 'bg-white border border-gray-300 text-gray-900 placeholder-gray-400' : 'bg-white/10 border border-white/20 text-white placeholder-gray-400'
                ]"
              ></textarea>
              <div class="flex gap-2">
                <GlassButton size="sm" @click="updateBlockers">{{ t('Save') }}</GlassButton>
                <GlassButton size="sm" variant="ghost" @click="cancelBlockersEdit">{{ t('Cancel') }}</GlassButton>
              </div>
            </div>
            <div v-else>
              <p v-if="project.blockers" :class="[textSecondary, 'text-sm']">{{ project.blockers }}</p>
              <p v-else :class="[textMuted, 'text-sm italic']">{{ t('No blockers') }}</p>
            </div>
          </GlassCard>

          <!-- Need PO Toggle -->
          <GlassCard>
            <div class="flex items-center justify-between">
              <div>
                <h3 :class="['text-lg font-semibold flex items-center gap-2', textPrimary]">
                  <AlertCircle class="w-5 h-5 text-orange-400" />
                  {{ t('Need PO') }}
                </h3>
              </div>
              <label v-if="can('edit projects')" class="relative inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  :checked="project.need_po"
                  @change="toggleNeedPO"
                  class="sr-only peer"
                >
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-prism-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-prism-500"></div>
              </label>
              <div v-else>
                <span v-if="project.need_po" class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 border border-orange-300">
                  {{ t('Yes') }}
                </span>
                <span v-else class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-300">
                  {{ t('No') }}
                </span>
              </div>
            </div>
          </GlassCard>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Tabs -->
          <GlassCard>
            <div :class="['flex border-b', borderColor]">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  'px-6 py-3 font-medium transition-colors',
                  activeTab === tab.id
                    ? (isDarkText ? 'text-prism-600 border-b-2 border-prism-500' : 'text-white border-b-2 border-prism-500')
                    : (isDarkText ? 'text-gray-500 hover:text-gray-900' : 'text-slate-400 hover:text-white')
                ]"
              >
                {{ tab.label }}
              </button>
            </div>

            <div class="mt-6">
              <!-- Overview Tab -->
              <div v-if="activeTab === 'overview'">
                <h3 :class="['text-lg font-semibold mb-3', textPrimary]">{{ t('Description') }}</h3>
                <p :class="[textSecondary, 'mb-6']">{{ project.description || t('No description provided') }}</p>

                <div class="flex justify-between items-center mb-3">
                  <h3 :class="['text-lg font-semibold', textPrimary]">{{ t('Phases') }}</h3>
                    <div v-if="can('edit projects')" class="flex items-center gap-2">
                    <span :class="['text-sm', textMuted]">{{ completedPhasesCount }}/{{ project.phases?.length || 0 }} {{ t('completed') }}</span>
                  </div>
                </div>
                
                <!-- Phase Timeline -->
                <div v-if="project.phases?.length" class="space-y-2">
                  <div
                    v-for="(phase, index) in project.phases"
                    :key="phase.id"
                    :class="[
                      'relative p-4 rounded-lg border-2 transition-all duration-200',
                      getPhaseCardClass(phase.status)
                    ]"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-3">
                        <!-- Phase Number -->
                        <div :class="[
                          'w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm',
                          getPhaseNumberClass(phase.status)
                        ]">
                          <Check v-if="phase.status === 'Completed'" class="w-5 h-5" />
                          <span v-else>{{ index + 1 }}</span>
                        </div>
                        
                        <div>
                          <h4 :class="['font-semibold', textPrimary]">{{ phase.phase }}</h4>
                          <div :class="['text-xs', textMuted]" v-if="phase.started_at || phase.completed_at">
                            <span v-if="phase.started_at">{{ t('Started') }}: {{ formatDate(phase.started_at) }}</span>
                            <span v-if="phase.completed_at"> • {{ t('Completed') }}: {{ formatDate(phase.completed_at) }}</span>
                          </div>
                          <p :class="['text-sm mt-1', textMuted]" v-if="phase.remarks">{{ phase.remarks }}</p>
                        </div>
                      </div>
                      
                      <!-- Status Selector -->
                      <div v-if="can('edit projects')" class="flex items-center gap-2">
                        <select
                          :value="phase.status"
                          @change="updatePhaseStatus(phase, $event.target.value)"
                          :class="[
                            'pl-3 pr-8 py-1.5 rounded-lg text-sm font-medium border cursor-pointer',
                            getPhaseSelectClass(phase.status)
                          ]"
                        >
                          <option value="Pending">⏳ {{ t('Pending') }}</option>
                          <option value="In Progress">🔄 {{ t('In Progress') }}</option>
                          <option value="Completed">✅ {{ t('Completed') }}</option>
                          <option value="Blocked">🚫 {{ t('Blocked') }}</option>
                        </select>
                      </div>
                      <StatusBadge v-else :status="phaseStatusToRag(phase.status)" size="sm" />
                    </div>
                    
                    <!-- Progress Line -->
                    <div v-if="index < project.phases.length - 1" 
                      :class="[
                        'absolute left-7 top-14 w-0.5 h-4',
                        phase.status === 'Completed' ? 'bg-green-500' : (isDarkText ? 'bg-gray-300' : 'bg-white/20')
                      ]"
                    ></div>
                  </div>
                </div>
                <div v-else :class="['text-center py-8', textMuted]">
                  {{ t('No phases defined') }}
                </div>

                <!-- Priority Section -->
                <div class="mt-6 pt-6" :class="[borderColor, 'border-t']">
                  <div class="flex justify-between items-center">
                    <h3 :class="['text-lg font-semibold', textPrimary]">{{ t('Priority') }}</h3>
                    <div v-if="can('edit projects')" class="flex gap-2">
                      <button
                        v-for="priority in ['Low', 'Medium', 'High']"
                        :key="priority"
                        @click="updatePriority(priority)"
                        :class="[
                          'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                          project.priority === priority 
                            ? getPriorityActiveClass(priority)
                            : (isDarkText ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : 'bg-white/10 text-gray-300 hover:bg-white/20')
                        ]"
                      >
                        {{ te('priority', priority) }}
                      </button>
                    </div>
                    <span v-else :class="priorityTextClass(project.priority)">{{ te('priority', project.priority) }}</span>
                  </div>
                </div>
              </div>

              <!-- Risks Tab -->
              <div v-if="activeTab === 'risks'">
                <div class="flex justify-between items-center mb-4">
                  <h3 :class="['text-lg font-semibold', textPrimary]">{{ t('Risks') }}</h3>
                  <GlassButton
                    variant="primary"
                    size="sm"
                    @click="createRisk"
                    v-if="can('create risks')"
                  >
                    <Plus class="w-4 h-4 mr-2" />
                    {{ t('Add Risk') }}
                  </GlassButton>
                </div>
                <div v-if="project.risks?.length" class="space-y-3">
                  <div
                    v-for="risk in project.risks"
                    :key="risk.id"
                    :class="['p-4 rounded-lg cursor-pointer transition', isDarkText ? 'bg-gray-100 hover:bg-gray-200' : 'glass hover:bg-white/10']"
                    @click="viewRisk(risk.id)"
                  >
                    <div class="flex justify-between items-start">
                      <div class="flex-1">
                        <p :class="['text-sm mb-2', textSecondary]">{{ risk.description }}</p>
                        <div class="flex gap-3 text-sm">
                          <span :class="textMuted">{{ t('Impact') }}: {{ risk.impact }}</span>
                          <span :class="textMuted">{{ t('Probability') }}: {{ risk.probability }}</span>
                          <span :class="riskScoreClass(risk.risk_score)">{{ t('Score') }}: {{ risk.risk_score }}</span>
                        </div>
                      </div>
                      <StatusBadge :status="riskStatusToRag(risk.status)" size="sm" />
                    </div>
                  </div>
                </div>
                <div v-else :class="['text-center py-8', textMuted]">
                  {{ t('No risks recorded') }}
                </div>
              </div>

              <!-- Changes Tab -->
              <div v-if="activeTab === 'changes'">
                <div class="flex justify-between items-center mb-4">
                  <h3 :class="['text-lg font-semibold', textPrimary]">{{ t('Change Requests') }}</h3>
                  <GlassButton
                    variant="primary"
                    size="sm"
                    @click="createChange"
                    v-if="can('create change-requests')"
                  >
                    <Plus class="w-4 h-4 mr-2" />
                    {{ t('Add Change') }}
                  </GlassButton>
                </div>
                <div v-if="project.changes?.length" class="space-y-3">
                  <div
                    v-for="change in project.changes"
                    :key="change.id"
                    :class="['p-4 rounded-lg cursor-pointer transition', isDarkText ? 'bg-gray-100 hover:bg-gray-200' : 'glass hover:bg-white/10']"
                    @click="viewChange(change.id)"
                  >
                    <div class="flex justify-between items-start">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                          <span class="text-xs px-2 py-1 rounded bg-prism-500/20 text-prism-600">
                            {{ change.change_type }}
                          </span>
                          <span :class="['text-xs', textMuted]">{{ change.change_code }}</span>
                        </div>
                        <p :class="['text-sm mb-2', textSecondary]">{{ change.description }}</p>
                        <p :class="['text-xs', textMuted]">
                          {{ t('Requested by') }} {{ change.requested_by?.name }} {{ t('on') }} {{ formatDate(change.requested_at) }}
                        </p>
                      </div>
                      <StatusBadge :status="changeStatusToRag(change.status)" size="sm" />
                    </div>
                  </div>
                </div>
                <div v-else :class="['text-center py-8', textMuted]">
                  {{ t('No change requests') }}
                </div>
              </div>

              <!-- Comments Tab -->
              <div v-if="activeTab === 'comments'">
                <h3 :class="['text-lg font-semibold mb-4 flex items-center gap-2', textPrimary]">
                  <MessageSquare class="w-5 h-5" />
                  {{ t('Comments') }} ({{ project.comments?.length || 0 }})
                </h3>
                
                <!-- New Comment Form -->
                <div class="mb-6 pb-6" :class="[borderColor, 'border-b']">
                  <div class="flex gap-3">
                    <div :class="[
                      'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
                      isDarkText ? 'bg-prism-100 text-prism-600' : 'bg-prism-500/20 text-prism-300'
                    ]">
                      <User class="w-5 h-5" />
                    </div>
                    <div class="flex-1">
                      <textarea 
                        v-model="newComment"
                        rows="3"
                        :placeholder="t('Add a comment')"
                        :class="[
                          'w-full px-3 py-2 rounded-lg text-sm resize-none',
                          isDarkText ? 'bg-white border border-gray-300 text-gray-900 placeholder-gray-400' : 'bg-white/10 border border-white/20 text-white placeholder-gray-400'
                        ]"
                      ></textarea>
                      <div class="flex justify-end mt-2">
                        <GlassButton 
                          size="sm" 
                          @click="submitComment"
                          :disabled="!newComment.trim()"
                        >
                          <Send class="w-4 h-4 mr-1" />
                          {{ t('Send') }}
                        </GlassButton>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Comments List -->
                <div v-if="project.comments?.length" class="space-y-4">
                  <div
                    v-for="comment in project.comments"
                    :key="comment.id"
                    class="space-y-3"
                  >
                    <!-- Main Comment -->
                    <div class="flex gap-3">
                      <div :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
                        isDarkText ? 'bg-gray-100 text-gray-600' : 'bg-white/10 text-gray-300'
                      ]">
                        <User class="w-5 h-5" />
                      </div>
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                          <span :class="['font-semibold text-sm', textPrimary]">{{ comment.user?.name }}</span>
                          <span :class="['text-xs', textMuted]">{{ formatDateTime(comment.created_at) }}</span>
                        </div>
                        <p :class="[textSecondary, 'text-sm']">{{ comment.content }}</p>
                        <div class="flex gap-4 mt-2">
                          <button 
                            @click="replyingTo = replyingTo === comment.id ? null : comment.id"
                            :class="['text-xs text-prism-400 hover:text-prism-300']"
                          >
                            {{ t('Reply') }}
                          </button>
                          <button 
                            v-if="comment.user_id === currentUserId || can('delete projects')"
                            @click="deleteComment(comment.id)"
                            class="text-xs text-red-400 hover:text-red-300"
                          >
                            {{ t('Delete') }}
                          </button>
                        </div>

                        <!-- Reply Form -->
                        <div v-if="replyingTo === comment.id" class="mt-3 flex gap-2">
                          <input 
                            v-model="replyText"
                            type="text"
                            :placeholder="t('Your reply')"
                            :class="[
                              'flex-1 px-3 py-2 rounded-lg text-sm',
                              isDarkText ? 'bg-white border border-gray-300 text-gray-900 placeholder-gray-400' : 'bg-white/10 border border-white/20 text-white placeholder-gray-400'
                            ]"
                            @keyup.enter="submitReply(comment.id)"
                          />
                          <GlassButton size="sm" @click="submitReply(comment.id)">
                            <Send class="w-4 h-4" />
                          </GlassButton>
                          <GlassButton size="sm" variant="ghost" @click="replyingTo = null">
                            <X class="w-4 h-4" />
                          </GlassButton>
                        </div>
                      </div>
                    </div>

                    <!-- Replies -->
                    <div v-if="comment.replies?.length" class="ml-12 space-y-3">
                      <div
                        v-for="reply in comment.replies"
                        :key="reply.id"
                        class="flex gap-3"
                      >
                        <div :class="[
                          'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
                          isDarkText ? 'bg-gray-100 text-gray-600' : 'bg-white/10 text-gray-300'
                        ]">
                          <User class="w-4 h-4" />
                        </div>
                        <div class="flex-1">
                          <div class="flex items-center gap-2 mb-1">
                            <span :class="['font-semibold text-sm', textPrimary]">{{ reply.user?.name }}</span>
                            <span :class="['text-xs', textMuted]">{{ formatDateTime(reply.created_at) }}</span>
                          </div>
                          <p :class="[textSecondary, 'text-sm']">{{ reply.content }}</p>
                          <button 
                            v-if="reply.user_id === currentUserId || can('delete projects')"
                            @click="deleteComment(reply.id)"
                            class="text-xs text-red-400 hover:text-red-300 mt-1"
                          >
                            {{ t('Delete') }}
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else :class="['text-center py-8', textMuted]">
                  <MessageSquare class="w-12 h-12 mx-auto mb-3 opacity-50" />
                  <p>{{ t('No comments yet') }}</p>
                  <p class="text-sm mt-1">{{ t('Be the first to comment') }}</p>
                </div>
              </div>

              <!-- Activities Tab -->
              <div v-if="activeTab === 'activities'">
                <h3 :class="['text-lg font-semibold mb-4', textPrimary]">{{ t('Recent Activities') }}</h3>
                <div v-if="project.activities?.length" class="space-y-3">
                  <div
                    v-for="activity in project.activities"
                    :key="activity.id"
                    :class="['flex gap-3 pb-3 border-b last:border-0', borderColor]"
                  >
                    <div class="w-2 h-2 rounded-full bg-prism-400 mt-2"></div>
                    <div class="flex-1">
                      <p :class="[textPrimary, 'text-sm']">{{ activity.description }}</p>
                      <p :class="['text-xs mt-1', textMuted]">{{ formatDateTime(activity.created_at) }} {{ t('by') }} {{ activity.user?.name }}</p>
                    </div>
                  </div>
                </div>
                <div v-else :class="['text-center py-8', textMuted]">
                  {{ t('No activities yet') }}
                </div>
              </div>
            </div>
          </GlassCard>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <GlassModal v-model="showDeleteModal" :title="t('Delete project')">
      <p :class="[textSecondary, 'mb-6']">
        {{ t('Delete project confirmation') }}
      </p>
      <div class="flex justify-end gap-3">
        <GlassButton variant="secondary" @click="showDeleteModal = false">
          {{ t('Cancel') }}
        </GlassButton>
        <GlassButton variant="danger" @click="deleteProject">
          {{ t('Delete project') }}
        </GlassButton>
      </div>
    </GlassModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from '@/Composables/useRoute'
import { useTheme } from '@/Composables/useTheme'
import { useTranslation } from '@/Composables/useTranslation'
import { useToast } from '@/Composables/useToast'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassModal from '@/Components/Glass/GlassModal.vue'
import StatusBadge from '@/Components/Glass/StatusBadge.vue'
import ProgressBar from '@/Components/Glass/ProgressBar.vue'
import {
  ArrowLeft, Edit, Trash2, TrendingUp, Flag,
  AlertTriangle, FileText, AlertCircle, Plus, Check,
  MessageSquare, Send, X, User
} from 'lucide-vue-next'

const props = defineProps({
  project: Object,
  users: Array,
})

const page = usePage()
const { isDarkText } = useTheme()
const { t, te, formatDate, formatDateTime, formatPlannedRelease } = useTranslation()
const { toast } = useToast()

// Classes de texte dynamiques selon le thème
const textPrimary = computed(() => isDarkText.value ? 'text-gray-900' : 'text-white')
const textSecondary = computed(() => isDarkText.value ? 'text-gray-700' : 'text-slate-300')
const textMuted = computed(() => isDarkText.value ? 'text-gray-500' : 'text-slate-400')
const borderColor = computed(() => isDarkText.value ? 'border-gray-200' : 'border-white/10')

const activeTab = ref('overview')
const showDeleteModal = ref(false)

// Inline editing states
const editingBlockers = ref(false)
const blockersText = ref(props.project.blockers || '')
const editingOwner = ref(false)
const ownerText = ref(props.project.owner || '')

// Comment form
const newComment = ref('')
const replyingTo = ref(null)
const replyText = ref('')

const tabs = computed(() => [
  { id: 'overview', label: t('Overview') },
  { id: 'risks', label: t('Risks') },
  { id: 'changes', label: t('Changes') },
  { id: 'comments', label: t('Comments') },
  { id: 'activities', label: t('Activities') },
])

const can = (permission) => {
  const user = page.props.auth?.user;
  
  // Admin a TOUTES les permissions
  if (user?.role === 'admin' || user?.roles?.includes?.('admin')) {
    return true;
  }
  
  const hasPermission = user?.permissions?.includes?.(permission);
  return hasPermission;
}

const priorityTextClass = (priority) => {
  const classes = {
    'High': 'text-red-400',
    'Medium': 'text-amber-400',
    'Low': 'text-green-400',
  }
  return classes[priority] || 'text-slate-400'
}

const riskScoreClass = (score) => {
  const classes = {
    'Critical': 'text-red-400 font-semibold',
    'High': 'text-orange-400',
    'Medium': 'text-amber-400',
    'Low': 'text-green-400',
  }
  return classes[score] || 'text-slate-400'
}

const riskLevelClass = (level) => {
  const classes = {
    'Critical': 'bg-red-500 text-white',
    'High': 'bg-orange-500 text-white',
    'Medium': 'bg-amber-500 text-white',
    'Low': 'bg-green-500 text-white',
  }
  return classes[level] || 'bg-gray-500 text-white'
}

const phaseStatusToRag = (status) => {
  const map = {
    'Completed': 'Green',
    'In Progress': 'Amber',
    'Pending': 'Amber',
    'Blocked': 'Red',
  }
  return map[status] || 'Amber'
}

const riskStatusToRag = (status) => {
  const map = {
    'Closed': 'Green',
    'Mitigated': 'Green',
    'In Progress': 'Amber',
    'Open': 'Red',
  }
  return map[status] || 'Amber'
}

const changeStatusToRag = (status) => {
  const map = {
    'Approved': 'Green',
    'Under Review': 'Amber',
    'Pending': 'Amber',
    'Rejected': 'Red',
  }
  return map[status] || 'Amber'
}

const confirmDelete = () => {
  showDeleteModal.value = true
}

const deleteProject = () => {
  router.delete(route('projects.destroy', props.project.id))
}

const createRisk = () => {
  router.visit(route('risks.create', { project_id: props.project.id }))
}

const viewRisk = (id) => {
  router.visit(route('risks.show', id))
}

const createChange = () => {
  router.visit(route('change-requests.create', { project_id: props.project.id }))
}

const viewChange = (id) => {
  router.visit(route('change-requests.show', id))
}

// Phase management
const completedPhasesCount = computed(() => {
  return props.project.phases?.filter(p => p.status === 'Completed').length || 0
})

const updatePhaseStatus = (phase, newStatus) => {
  router.put(route('phases.update-status', phase.id), {
    status: newStatus,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}

const getPhaseCardClass = (status) => {
  if (isDarkText.value) {
    const classes = {
      'Completed': 'bg-green-50 border-green-300',
      'In Progress': 'bg-blue-50 border-blue-300',
      'Blocked': 'bg-red-50 border-red-300',
      'Pending': 'bg-gray-50 border-gray-200',
    }
    return classes[status] || 'bg-gray-50 border-gray-200'
  } else {
    const classes = {
      'Completed': 'bg-green-500/10 border-green-500/50',
      'In Progress': 'bg-blue-500/10 border-blue-500/50',
      'Blocked': 'bg-red-500/10 border-red-500/50',
      'Pending': 'bg-white/5 border-white/10',
    }
    return classes[status] || 'bg-white/5 border-white/10'
  }
}

const getPhaseNumberClass = (status) => {
  if (isDarkText.value) {
    const classes = {
      'Completed': 'bg-green-500 text-white',
      'In Progress': 'bg-blue-500 text-white',
      'Blocked': 'bg-red-500 text-white',
      'Pending': 'bg-gray-300 text-gray-600',
    }
    return classes[status] || 'bg-gray-300 text-gray-600'
  } else {
    const classes = {
      'Completed': 'bg-green-500 text-white',
      'In Progress': 'bg-blue-500 text-white',
      'Blocked': 'bg-red-500 text-white',
      'Pending': 'bg-white/20 text-white',
    }
    return classes[status] || 'bg-white/20 text-white'
  }
}

const getPhaseSelectClass = (status) => {
  const classes = {
    'Completed': 'bg-green-100 text-green-800 border-green-300',
    'In Progress': 'bg-blue-100 text-blue-800 border-blue-300',
    'Blocked': 'bg-red-100 text-red-800 border-red-300',
    'Pending': 'bg-gray-100 text-gray-600 border-gray-300',
  }
  return classes[status] || 'bg-gray-100 text-gray-600 border-gray-300'
}

const getPriorityActiveClass = (priority) => {
  const classes = {
    'High': 'bg-red-500 text-white',
    'Medium': 'bg-amber-500 text-white',
    'Low': 'bg-green-500 text-white',
  }
  return classes[priority] || 'bg-gray-500 text-white'
}

const updatePriority = (priority) => {
  router.put(route('projects.update', props.project.id), {
    priority: priority,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}

// Owner update
const updateOwner = () => {
  router.put(route('projects.update', props.project.id), {
    owner: ownerText.value || null,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      editingOwner.value = false
      toast.success(t('Owner updated successfully'))
    },
    onError: () => {
      toast.error(t('Failed to update owner'))
    }
  })
}

// Blockers update
const updateBlockers = () => {
  router.put(route('projects.update', props.project.id), {
    blockers: blockersText.value || null,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      editingBlockers.value = false
      toast.success(t('Blockers updated successfully'))
    },
    onError: () => {
      toast.error(t('Failed to update blockers'))
    }
  })
}

const cancelBlockersEdit = () => {
  blockersText.value = props.project.blockers || ''
  editingBlockers.value = false
}

const cancelOwnerEdit = () => {
  ownerText.value = props.project.owner || ''
  editingOwner.value = false
}

// Need PO toggle
const toggleNeedPO = () => {
  router.put(route('projects.update', props.project.id), {
    need_po: !props.project.need_po,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}

// Comments
const submitComment = () => {
  if (!newComment.value.trim()) return
  
  router.post(route('projects.comments.store', props.project.id), {
    content: newComment.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      newComment.value = ''
    }
  })
}

const submitReply = (parentId) => {
  if (!replyText.value.trim()) return
  
  router.post(route('projects.comments.store', props.project.id), {
    content: replyText.value,
    parent_id: parentId,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      replyText.value = ''
      replyingTo.value = null
    }
  })
}

const deleteComment = (commentId) => {
  if (confirm(t('Confirm delete comment'))) {
    router.delete(route('comments.destroy', commentId), {
      preserveScroll: true,
    })
  }
}

const currentUserId = computed(() => page.props.auth?.user?.id)

const analyzeRisks = () => {
  if (confirm(t('Run ML risk analysis?'))) {
    router.post(route('projects.analyze-risks', props.project), {}, {
      preserveScroll: true,
      onSuccess: () => {
        alert(t('ML analysis finished, check Risks tab'))
      }
    })
  }
}
</script>
