<template>
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside 
            class="fixed inset-y-0 left-0 z-50 w-64 glass transform transition-transform duration-300 ease-in-out"
            :class="{ '-translate-x-full': !sidebarOpen }"
        >
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center justify-center h-20 border-b border-white/10">
                    <Link href="/" class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 bg-gradient-prism rounded-lg flex items-center justify-center shadow-glass">
                                <Zap class="w-6 h-6 text-white" />
                            </div>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold gradient-text">PRISM</h1>
                            <p class="text-xs text-gray-400">Project Intelligence</p>
                        </div>
                    </Link>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                    <Link 
                        v-for="item in navigation" 
                        :key="item.name"
                        :href="item.href"
                        class="sidebar-link"
                        :class="{ 'active': isCurrentRoute(item.href) }"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        <span>{{ item.name }}</span>
                    </Link>
                </nav>

                <!-- User Section -->
                <div class="p-4 border-t border-white/10">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-prism flex items-center justify-center">
                            <span class="text-sm font-bold">{{ userInitials }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ user.name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ user.email }}</p>
                        </div>
                    </div>
                    <button @click="logout" class="btn btn-ghost w-full text-sm">
                        <LogOut class="w-4 h-4" />
                        Déconnexion
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1" :class="{ 'lg:ml-64': sidebarOpen }">
            <!-- Header -->
            <header class="sticky top-0 z-40 glass h-20 flex items-center justify-between px-6 border-b border-white/10">
                <div class="flex items-center gap-4">
                    <button 
                        @click="toggleSidebar" 
                        class="lg:hidden btn btn-ghost"
                    >
                        <Menu class="w-6 h-6" />
                    </button>
                    <div>
                        <h2 class="text-xl font-bold">{{ pageTitle }}</h2>
                        <p class="text-sm text-gray-400">{{ pageDescription }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Search -->
                    <button class="btn btn-ghost">
                        <Search class="w-5 h-5" />
                    </button>

                    <!-- Notifications -->
                    <NotificationBell />
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <slot />
            </main>
        </div>

        <!-- Mobile Sidebar Overlay -->
        <div 
            v-if="sidebarOpen" 
            @click="toggleSidebar"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    FolderKanban,
    AlertTriangle,
    FileEdit,
    Upload,
    Users,
    Settings,
    Zap,
    Menu,
    Search,
    LogOut,
    Tag
} from 'lucide-vue-next';
import NotificationBell from '@/Components/NotificationBell.vue';

const props = defineProps({
    pageTitle: {
        type: String,
        default: 'Dashboard'
    },
    pageDescription: {
        type: String,
        default: ''
    }
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const sidebarOpen = ref(true);

const navigation = [
    { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { name: 'Projects', href: '/projects', icon: FolderKanban },
    { name: 'Risks', href: '/risks', icon: AlertTriangle },
    { name: 'Changes', href: '/change-requests', icon: FileEdit },
    { name: 'Categories', href: '/categories', icon: Tag },
    { name: 'Users', href: '/users', icon: Users },
    { name: 'Settings', href: '/settings', icon: Settings },
];

const userInitials = computed(() => {
    if (!user.value) return '';
    const names = user.value.name.split(' ');
    return names.map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const isCurrentRoute = (href) => {
    return page.url.startsWith(href);
};

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const logout = () => {
    router.post('/logout');
};
</script>
