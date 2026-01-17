<template>
  <div class="relative">
    <button
      @click="toggleDropdown"
      class="relative p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
    >
      <Bell class="w-5 h-5" />
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-80 bg-slate-800/95 backdrop-blur-xl border border-white/20 rounded-xl shadow-xl overflow-hidden z-50"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
          <h3 class="text-white font-semibold">Notifications</h3>
          <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            class="text-xs text-primary-400 hover:text-primary-300"
          >
            Tout marquer comme lu
          </button>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <div v-if="loading" class="p-4 text-center">
            <div class="animate-spin w-6 h-6 border-2 border-white/30 border-t-white rounded-full mx-auto"></div>
          </div>

          <div v-else-if="notifications.length === 0" class="p-8 text-center">
            <BellOff class="w-12 h-12 text-white/30 mx-auto mb-3" />
            <p class="text-white/50 text-sm">Aucune notification</p>
          </div>

          <div v-else>
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="px-4 py-3 border-b border-white/5 hover:bg-white/5 transition-colors cursor-pointer"
              :class="{ 'bg-primary-500/10': !notification.read_at }"
              @click="handleNotificationClick(notification)"
            >
              <div class="flex items-start gap-3">
                <div
                  class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                  :class="getIconBgClass(notification.data.type)"
                >
                  <component :is="getIcon(notification.data.type)" class="w-4 h-4 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-white" :class="{ 'font-medium': !notification.read_at }">
                    {{ notification.data.message }}
                  </p>
                  <p class="text-xs text-white/50 mt-1">
                    {{ formatTime(notification.created_at) }}
                  </p>
                </div>
                <button
                  v-if="!notification.read_at"
                  @click.stop="markAsRead(notification.id)"
                  class="text-white/40 hover:text-white/70"
                >
                  <Check class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-white/10">
          <a
            href="/notifications"
            class="text-sm text-primary-400 hover:text-primary-300 flex items-center justify-center gap-1"
          >
            Voir toutes les notifications
            <ChevronRight class="w-4 h-4" />
          </a>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import {
  Bell,
  BellOff,
  Check,
  ChevronRight,
  AlertTriangle,
  FolderKanban,
  FileText,
  Info,
} from 'lucide-vue-next';
import axios from 'axios';

const isOpen = ref(false);
const loading = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value && notifications.value.length === 0) {
    fetchNotifications();
  }
};

const fetchNotifications = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/notifications/unread');
    notifications.value = response.data.notifications;
    unreadCount.value = response.data.unread_count;
  } catch (error) {
    console.error('Error fetching notifications:', error);
  } finally {
    loading.value = false;
  }
};

const markAsRead = async (id) => {
  try {
    await axios.post(`/notifications/${id}/read`);
    const notification = notifications.value.find(n => n.id === id);
    if (notification) {
      notification.read_at = new Date().toISOString();
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    }
  } catch (error) {
    console.error('Error marking notification as read:', error);
  }
};

const markAllAsRead = async () => {
  try {
    await axios.post('/notifications/read-all');
    notifications.value.forEach(n => n.read_at = new Date().toISOString());
    unreadCount.value = 0;
  } catch (error) {
    console.error('Error marking all as read:', error);
  }
};

const handleNotificationClick = (notification) => {
  if (!notification.read_at) {
    markAsRead(notification.id);
  }

  // Navigate based on notification type
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

  window.location.href = url;
};

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

const formatTime = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diff = now - date;

  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(diff / 3600000);
  const days = Math.floor(diff / 86400000);

  if (minutes < 1) return "A l'instant";
  if (minutes < 60) return `Il y a ${minutes}min`;
  if (hours < 24) return `Il y a ${hours}h`;
  if (days < 7) return `Il y a ${days}j`;
  return date.toLocaleDateString('fr-FR');
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (isOpen.value && !event.target.closest('.relative')) {
    isOpen.value = false;
  }
};

// Fetch notifications on mount
onMounted(() => {
  fetchNotifications();
  document.addEventListener('click', handleClickOutside);

  // Poll for new notifications every 30 seconds
  const interval = setInterval(fetchNotifications, 30000);
  onUnmounted(() => {
    clearInterval(interval);
    document.removeEventListener('click', handleClickOutside);
  });
});
</script>
