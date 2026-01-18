<template>
  <AppLayout :page-title="t('Notifications')" :page-description="t('History of your notifications')">
    <div class="max-w-4xl mx-auto">
      <!-- Header Actions -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <span class="text-white/60">
            {{ unreadCount }} {{ t('unread') }} / {{ notifications.total }} {{ t('total') }}
          </span>
        </div>
        <div class="flex items-center gap-3">
          <GlassButton
            v-if="unreadCount > 0"
            variant="secondary"
            size="sm"
            @click="markAllAsRead"
          >
            <CheckCheck class="w-4 h-4 mr-2" />
            {{ t('Mark all as read') }}
          </GlassButton>
          <GlassButton
            v-if="notifications.total > 0"
            variant="ghost"
            size="sm"
            @click="deleteAll"
          >
            <Trash2 class="w-4 h-4 mr-2" />
            {{ t('Delete all') }}
          </GlassButton>
        </div>
      </div>

      <!-- Empty State -->
      <GlassCard v-if="notifications.data.length === 0">
        <div class="py-12 text-center">
          <BellOff class="w-16 h-16 text-white/20 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-white mb-2">{{ t('No notifications') }}</h3>
          <p class="text-white/50">{{ t('You have no notifications yet.') }}</p>
        </div>
      </GlassCard>

      <!-- Notifications List -->
      <div v-else class="space-y-3">
        <GlassCard
          v-for="notification in notifications.data"
          :key="notification.id"
          class="cursor-pointer"
          :class="{ 'bg-primary-500/5 border-primary-500/30': !notification.read_at }"
          hoverable
          @click="handleClick(notification)"
        >
          <div class="flex items-start gap-4">
            <!-- Icon -->
            <div
              class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
              :class="getIconBgClass(notification.data.type)"
            >
              <component :is="getIcon(notification.data.type)" class="w-5 h-5 text-white" />
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <p class="text-white" :class="{ 'font-medium': !notification.read_at }">
                {{ notification.data.message }}
              </p>
              <div class="flex items-center gap-4 mt-2">
                <span class="text-xs text-white/50">
                  {{ formatDate(notification.created_at) }}
                </span>
                <span
                  v-if="notification.data.project_name"
                  class="text-xs px-2 py-0.5 bg-white/10 rounded-full text-white/70"
                >
                  {{ notification.data.project_name }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
              <button
                v-if="!notification.read_at"
                @click.stop="markAsRead(notification.id)"
                class="p-2 text-white/40 hover:text-emerald-400 hover:bg-white/5 rounded-lg transition-colors"
                title="Marquer comme lu"
              >
                <Check class="w-4 h-4" />
              </button>
              <button
                @click.stop="deleteNotification(notification.id)"
                class="p-2 text-white/40 hover:text-red-400 hover:bg-white/5 rounded-lg transition-colors"
                title="Supprimer"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </GlassCard>

        <!-- Pagination -->
        <div v-if="notifications.last_page > 1" class="flex justify-center gap-2 mt-6">
          <GlassButton
            v-for="page in notifications.last_page"
            :key="page"
            :variant="page === notifications.current_page ? 'primary' : 'ghost'"
            size="sm"
            @click="goToPage(page)"
          >
            {{ page }}
          </GlassButton>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import { useTranslation } from '@/Composables/useTranslation';
import {
  BellOff,
  Check,
  CheckCheck,
  Trash2,
  AlertTriangle,
  FolderKanban,
  FileText,
  Info,
} from 'lucide-vue-next';

const { t } = useTranslation();

const props = defineProps({
  notifications: Object,
  unread_count: Number,
});

const unreadCount = computed(() => props.unread_count || 0);

const getIcon = (type) => {
  switch (type) {
    case 'project_status_changed':
      return FolderKanban;
    case 'risk_created':
      return AlertTriangle;
    case 'change_request_pending':
      return FileText;
    default:
      return Info;
  }
};

const getIconBgClass = (type) => {
  switch (type) {
    case 'project_status_changed':
      return 'bg-blue-500/20';
    case 'risk_created':
      return 'bg-red-500/20';
    case 'change_request_pending':
      return 'bg-amber-500/20';
    default:
      return 'bg-white/10';
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diff = now - date;

  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(diff / 3600000);
  const days = Math.floor(diff / 86400000);

  if (minutes < 1) return "A l'instant";
  if (minutes < 60) return `Il y a ${minutes} min`;
  if (hours < 24) return `Il y a ${hours}h`;
  if (days < 7) return `Il y a ${days}j`;

  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
  });
};

const handleClick = (notification) => {
  if (!notification.read_at) {
    markAsRead(notification.id);
  }

  const data = notification.data;
  let url = '/dashboard';

  switch (data.type) {
    case 'project_status_changed':
      url = `/projects/${data.project_id}`;
      break;
    case 'risk_created':
      url = '/risks';
      break;
    case 'change_request_pending':
      url = `/change-requests/${data.change_request_id}`;
      break;
  }

  router.visit(url);
};

const markAsRead = (id) => {
  router.post(`/notifications/${id}/read`, {}, {
    preserveScroll: true,
    preserveState: true,
  });
};

const markAllAsRead = () => {
  router.post('/notifications/read-all', {}, {
    preserveScroll: true,
  });
};

const deleteNotification = (id) => {
  router.delete(`/notifications/${id}`, {
    preserveScroll: true,
  });
};

const deleteAll = () => {
  if (confirm('Etes-vous sur de vouloir supprimer toutes les notifications?')) {
    router.delete('/notifications', {
      preserveScroll: true,
    });
  }
};

const goToPage = (page) => {
  router.get('/notifications', { page }, {
    preserveScroll: true,
    preserveState: true,
  });
};
</script>
