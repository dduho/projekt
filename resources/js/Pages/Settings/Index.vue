<template>
  <AppLayout page-title="Paramètres" page-description="Configuration de l'application">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 :class="['text-3xl font-bold mb-2', isDarkText ? 'text-gray-900' : 'text-white']">Paramètres</h1>
          <p :class="isDarkText ? 'text-gray-600' : 'text-slate-300'">Configuration générale de l'application</p>
        </div>
      </div>

      <!-- Settings Sections -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar Navigation -->
        <div class="lg:col-span-1">
          <GlassCard>
            <nav class="space-y-1">
              <button
                v-for="section in sections"
                :key="section.id"
                @click="activeSection = section.id"
                :class="[
                  'w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-all',
                  activeSection === section.id 
                    ? 'bg-purple-600 text-white' 
                    : isDarkText 
                      ? 'text-gray-700 hover:bg-gray-100' 
                      : 'text-gray-300 hover:bg-white/10'
                ]"
              >
                <component :is="section.icon" class="w-5 h-5" />
                <span>{{ section.name }}</span>
              </button>
            </nav>
          </GlassCard>
        </div>

        <!-- Content Area -->
        <div class="lg:col-span-2">
          <!-- Profile Section -->
          <GlassCard v-if="activeSection === 'profile'" title="Profil">
            <form @submit.prevent="updateProfile" class="space-y-4">
              <GlassInput
                v-model="profileForm.name"
                label="Nom complet"
                placeholder="Votre nom"
                :error="profileForm.errors.name"
              />
              <GlassInput
                v-model="profileForm.email"
                label="Email"
                type="email"
                placeholder="votre@email.com"
                :error="profileForm.errors.email"
              />
              <div class="flex justify-end">
                <GlassButton type="submit" variant="primary" :disabled="profileForm.processing">
                  <Save class="w-4 h-4 mr-2" />
                  Enregistrer
                </GlassButton>
              </div>
            </form>
          </GlassCard>

          <!-- Password Section -->
          <GlassCard v-if="activeSection === 'password'" title="Changer le mot de passe">
            <form @submit.prevent="updatePassword" class="space-y-4">
              <GlassInput
                v-model="passwordForm.current_password"
                label="Mot de passe actuel"
                type="password"
                placeholder="••••••••"
                :error="passwordForm.errors.current_password"
              />
              <GlassInput
                v-model="passwordForm.password"
                label="Nouveau mot de passe"
                type="password"
                placeholder="••••••••"
                :error="passwordForm.errors.password"
              />
              <GlassInput
                v-model="passwordForm.password_confirmation"
                label="Confirmer le mot de passe"
                type="password"
                placeholder="••••••••"
                :error="passwordForm.errors.password_confirmation"
              />
              <div class="flex justify-end">
                <GlassButton type="submit" variant="primary" :disabled="passwordForm.processing">
                  <Lock class="w-4 h-4 mr-2" />
                  Mettre à jour
                </GlassButton>
              </div>
            </form>
          </GlassCard>

          <!-- Roles & Permissions Section -->
          <GlassCard v-if="activeSection === 'roles'" title="Rôles & Permissions">
            <div class="space-y-6">
              <!-- Admin Role -->
              <div :class="['p-4 rounded-lg', isDarkText ? 'bg-gray-100' : 'bg-white/5']">
                <div class="flex items-center gap-3 mb-3">
                  <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                    <Shield class="w-5 h-5 text-red-400" />
                  </div>
                  <div>
                    <h4 :class="['font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">Administrateur</h4>
                    <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">Accès complet à toutes les fonctionnalités</p>
                  </div>
                </div>
                <div class="flex flex-wrap gap-2">
                  <span v-for="perm in adminPermissions" :key="perm" class="px-2 py-1 text-xs bg-red-500/20 text-red-400 rounded">
                    {{ perm }}
                  </span>
                </div>
              </div>

              <!-- Manager Role -->
              <div :class="['p-4 rounded-lg', isDarkText ? 'bg-gray-100' : 'bg-white/5']">
                <div class="flex items-center gap-3 mb-3">
                  <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center">
                    <UserCog class="w-5 h-5 text-blue-400" />
                  </div>
                  <div>
                    <h4 :class="['font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">Manager</h4>
                    <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">Gestion des projets et équipes</p>
                  </div>
                </div>
                <div class="flex flex-wrap gap-2">
                  <span v-for="perm in managerPermissions" :key="perm" class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded">
                    {{ perm }}
                  </span>
                </div>
              </div>

              <!-- User Role -->
              <div :class="['p-4 rounded-lg', isDarkText ? 'bg-gray-100' : 'bg-white/5']">
                <div class="flex items-center gap-3 mb-3">
                  <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
                    <User class="w-5 h-5 text-green-400" />
                  </div>
                  <div>
                    <h4 :class="['font-semibold', isDarkText ? 'text-gray-900' : 'text-white']">Utilisateur</h4>
                    <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">Accès en lecture et contributions limitées</p>
                  </div>
                </div>
                <div class="flex flex-wrap gap-2">
                  <span v-for="perm in userPermissions" :key="perm" class="px-2 py-1 text-xs bg-green-500/20 text-green-400 rounded">
                    {{ perm }}
                  </span>
                </div>
              </div>
            </div>
          </GlassCard>

          <!-- Notifications Section -->
          <GlassCard v-if="activeSection === 'notifications'" title="Préférences de notifications">
            <div class="space-y-4">
              <div 
                v-for="notif in notificationSettings" 
                :key="notif.key"
                :class="['flex items-center justify-between p-4 rounded-lg', isDarkText ? 'bg-gray-100' : 'bg-white/5']"
              >
                <div>
                  <h4 :class="['font-medium', isDarkText ? 'text-gray-900' : 'text-white']">{{ notif.label }}</h4>
                  <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ notif.description }}</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="notif.enabled" class="sr-only peer">
                  <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                </label>
              </div>
            </div>
          </GlassCard>

          <!-- About Section -->
          <GlassCard v-if="activeSection === 'about'" title="À propos">
            <div class="space-y-4">
              <div :class="['p-4 rounded-lg text-center', isDarkText ? 'bg-gray-100' : 'bg-white/5']">
                <div class="w-16 h-16 mx-auto mb-4 rounded-xl bg-gradient-to-br from-purple-600 to-blue-500 flex items-center justify-center">
                  <Zap class="w-8 h-8 text-white" />
                </div>
                <h3 :class="['text-xl font-bold mb-1', isDarkText ? 'text-gray-900' : 'text-white']">PRISM</h3>
                <p :class="['text-sm mb-4', isDarkText ? 'text-gray-600' : 'text-gray-400']">Project Intelligence System for Management</p>
                <p :class="['text-xs', isDarkText ? 'text-gray-500' : 'text-gray-500']">Version 1.0.0</p>
              </div>

              <div :class="['p-4 rounded-lg', isDarkText ? 'bg-gray-100' : 'bg-white/5']">
                <h4 :class="['font-medium mb-3', isDarkText ? 'text-gray-900' : 'text-white']">Technologies</h4>
                <div class="flex flex-wrap gap-2">
                  <span v-for="tech in technologies" :key="tech" :class="['px-3 py-1 text-xs rounded-full', isDarkText ? 'bg-gray-200 text-gray-700' : 'bg-white/10 text-gray-300']">
                    {{ tech }}
                  </span>
                </div>
              </div>

              <div :class="['p-4 rounded-lg', isDarkText ? 'bg-gray-100' : 'bg-white/5']">
                <h4 :class="['font-medium mb-2', isDarkText ? 'text-gray-900' : 'text-white']">Développé par</h4>
                <p :class="isDarkText ? 'text-gray-600' : 'text-gray-400'">Moov Money Togo</p>
                <p :class="['text-sm', isDarkText ? 'text-gray-500' : 'text-gray-500']">© 2026 Tous droits réservés</p>
              </div>
            </div>
          </GlassCard>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import GlassCard from '@/Components/Glass/GlassCard.vue'
import GlassButton from '@/Components/Glass/GlassButton.vue'
import GlassInput from '@/Components/Glass/GlassInput.vue'
import { 
  User, 
  Lock, 
  Shield, 
  Bell, 
  Info, 
  Save,
  UserCog,
  Zap
} from 'lucide-vue-next'
import { useTheme } from '@/Composables/useTheme'

const { isDarkText } = useTheme()

const page = usePage()
const user = page.props.auth.user

const activeSection = ref('profile')

const sections = [
  { id: 'profile', name: 'Profil', icon: User },
  { id: 'password', name: 'Mot de passe', icon: Lock },
  { id: 'roles', name: 'Rôles & Permissions', icon: Shield },
  { id: 'notifications', name: 'Notifications', icon: Bell },
  { id: 'about', name: 'À propos', icon: Info },
]

// Profile Form
const profileForm = useForm({
  name: user.name,
  email: user.email,
})

const updateProfile = () => {
  profileForm.put(route('profile.update'), {
    preserveScroll: true,
    onSuccess: () => {
      // Success notification
    }
  })
}

// Password Form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const updatePassword = () => {
  passwordForm.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
    }
  })
}

// Roles & Permissions
const adminPermissions = [
  'Gérer les utilisateurs',
  'Gérer les catégories',
  'Gérer les projets',
  'Approuver les changements',
  'Exporter les données',
  'Voir les statistiques',
  'Configurer le système'
]

const managerPermissions = [
  'Créer des projets',
  'Modifier des projets',
  'Gérer les risques',
  'Soumettre des changements',
  'Voir les rapports',
  'Importer des données'
]

const userPermissions = [
  'Voir les projets',
  'Commenter',
  'Soumettre des changements',
  'Voir les risques'
]

// Notification Settings
const notificationSettings = reactive([
  { key: 'project_updates', label: 'Mises à jour de projets', description: 'Recevoir des notifications quand un projet est mis à jour', enabled: true },
  { key: 'risk_alerts', label: 'Alertes de risques', description: 'Être notifié des nouveaux risques critiques', enabled: true },
  { key: 'change_requests', label: 'Demandes de changement', description: 'Notifications pour les nouvelles demandes', enabled: true },
  { key: 'weekly_digest', label: 'Résumé hebdomadaire', description: 'Recevoir un résumé par email chaque semaine', enabled: false },
])

// Technologies
const technologies = [
  'Laravel 11',
  'Vue.js 3',
  'Inertia.js',
  'Tailwind CSS',
  'PostgreSQL',
  'Docker'
]
</script>
