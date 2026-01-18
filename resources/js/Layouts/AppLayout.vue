<template>
    <div class="min-h-screen flex relative">
        <!-- Background animé avec pictogrammes -->
        <BackgroundIcons />
        
        <!-- Sidebar -->
        <aside 
            class="fixed inset-y-0 left-0 z-50 w-64 glass transform transition-all duration-500 ease-out animate-fadeInLeft"
            :class="{ '-translate-x-full': !sidebarOpen }"
        >
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center justify-center h-20 border-b border-white/10 animate-fadeInDown">
                    <Link href="/" class="flex items-center gap-3 hover-scale-sm transition-smooth">
                        <div class="relative">
                            <div class="w-10 h-10 bg-white/10 backdrop-blur-md rounded-lg flex items-center justify-center shadow-glass glow">
                                <Zap :class="['w-6 h-6', isDarkText ? 'text-gray-900' : 'text-white']" />
                            </div>
                        </div>
                        <div>
                            <h1 :class="['text-xl font-bold', isDarkText ? 'text-gray-900' : 'text-white']">PRISM</h1>
                            <p :class="['text-xs', isDarkText ? 'text-gray-600' : 'text-gray-400']">Project Intelligence</p>
                        </div>
                    </Link>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                    <Link 
                        v-for="(item, index) in navigation" 
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 ripple hover-scale-sm',
                            isCurrentRoute(item.href) 
                                ? 'bg-purple-600 text-white' 
                                : isDarkText 
                                    ? 'text-gray-700 hover:bg-white/30 hover:text-gray-900' 
                                    : 'text-gray-300 hover:bg-white/10 hover:text-white'
                        ]"
                        :style="{ animationDelay: `${index * 0.05}s` }"
                    >
                        <component :is="item.icon" class="w-5 h-5 transition-smooth" />
                        <span>{{ item.name }}</span>
                    </Link>
                </nav>

                <!-- Theme Switcher -->
                <div :class="['px-4 pb-4 border-t', isDarkText ? 'border-gray-200' : 'border-white/10']">
                    <button
                        @click="showThemeSelector = !showThemeSelector"
                        :class="[
                            'w-full px-4 py-2 rounded-lg text-sm transition-smooth hover-scale-sm flex items-center gap-2 justify-center',
                            isDarkText ? 'text-gray-700 hover:bg-white/30' : 'text-white hover:bg-white/10'
                        ]"
                    >
                        <Palette class="w-4 h-4" />
                        {{ t('Theme') }}
                    </button>
                    
                    <div v-if="showThemeSelector" class="mt-2 space-y-2 animate-fadeInUp">
                        <button
                            v-for="(theme, key) in themes"
                            :key="key"
                            @click="changeTheme(key)"
                            :class="[
                                'w-full p-2 rounded-lg text-xs transition-smooth hover-scale-sm',
                                currentTheme === key 
                                    ? (isDarkText ? 'bg-gray-300 ring-2 ring-gray-400 text-gray-900' : 'bg-white/20 ring-2 ring-white/30 text-white')
                                    : (isDarkText ? 'bg-white/20 hover:bg-white/30 text-gray-800' : 'bg-white/5 hover:bg-white/10 text-white')
                            ]"
                        >
                            {{ theme.name }}
                        </button>
                    </div>
                </div>

                <!-- User Section -->
                <div :class="['p-4 border-t animate-fadeInUp', isDarkText ? 'border-gray-200' : 'border-white/10']">
                    <div class="flex items-center gap-3 mb-3">
                        <div :class="['w-10 h-10 rounded-full backdrop-blur-md flex items-center justify-center glow', isDarkText ? 'bg-gray-200' : 'bg-white/10']">
                            <span :class="['text-sm font-bold', isDarkText ? 'text-gray-900' : 'text-white']">{{ userInitials }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p :class="['text-sm font-medium truncate', isDarkText ? 'text-gray-900' : 'text-white']">{{ user.name }}</p>
                            <p :class="['text-xs truncate', isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ user.email }}</p>
                        </div>
                    </div>
                    <button
                        @click="logout"
                        :class="[
                            'w-full text-sm transition-smooth hover-scale-sm ripple flex items-center gap-2 justify-center px-4 py-2 rounded-lg',
                            isDarkText ? 'text-gray-700 hover:bg-white/30 hover:text-gray-900' : 'text-white hover:bg-white/10'
                        ]"
                    >
                        <LogOut class="w-4 h-4" />
                        {{ t('Logout') }}
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 transition-all duration-500 ease-out" :class="{ 'lg:ml-64': sidebarOpen }">
            <!-- Header -->
            <header :class="['sticky top-0 z-40 glass h-20 flex items-center justify-between px-6 border-b animate-fadeInDown backdrop-blur-xl', isDarkText ? 'border-gray-200' : 'border-white/10']">
                <div class="flex items-center gap-4">
                    <button 
                        @click="toggleSidebar" 
                        :class="[
                            'lg:hidden transition-smooth hover-scale ripple p-2 rounded-lg',
                            isDarkText ? 'text-gray-700 hover:bg-white/30' : 'text-white hover:bg-white/10'
                        ]"
                    >
                        <Menu class="w-6 h-6" />
                    </button>
                    <div class="animate-fadeInLeft">
                        <h2 :class="['text-xl font-bold', isDarkText ? 'text-gray-900' : 'text-white']">{{ props.pageTitle }}</h2>
                        <p :class="['text-sm', isDarkText ? 'text-gray-600' : 'text-gray-400']">{{ props.pageDescription }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 animate-fadeInRight">
                    <!-- Language Switcher -->
                    <LanguageSwitcher />

                    <!-- Search -->
                    <button :class="[
                        'transition-smooth hover-scale ripple p-2 rounded-lg',
                        isDarkText ? 'text-gray-700 hover:bg-white/30' : 'text-white hover:bg-white/10'
                    ]">
                        <Search class="w-5 h-5" />
                    </button>

                    <!-- Notifications -->
                    <NotificationBell />
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6 animate-fadeInUp">
                <slot />
            </main>
        </div>

        <!-- Mobile Sidebar Overlay -->
        <div 
            v-if="sidebarOpen" 
            @click="toggleSidebar"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden animate-fadeIn"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
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
    Tag,
    Palette
} from 'lucide-vue-next';
import NotificationBell from '@/Components/NotificationBell.vue';
import BackgroundIcons from '@/Components/BackgroundIcons.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { useTheme } from '@/Composables/useTheme';
import { useTranslation } from '@/Composables/useTranslation';

const { t } = useTranslation();

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
const showThemeSelector = ref(false);

// Utiliser le composable de thème - store GLOBAL réactif
const { isDarkText, setTheme, setTextMode } = useTheme();

// Thèmes disponibles - couleurs CLAIRES professionnelles
const themes = {
    orangeBlue: {
        name: 'Orange & Bleu ✨',
        gradient: {
            start: '#fdd5c3',
            mid1: '#f5e6d3',
            mid2: '#d4e8f0',
            end: '#c5dde8'
        }
    },
    purplePink: {
        name: 'Violet & Magenta',
        gradient: {
            start: '#d5d9f0',
            mid1: '#ddd5e8',
            mid2: '#f0d5e5',
            end: '#f5c5d5'
        }
    },
    greenTeal: {
        name: 'Vert & Turquoise',
        gradient: {
            start: '#c8e6c9',
            mid1: '#d7f0d4',
            mid2: '#b3e5f0',
            end: '#a8dce8'
        }
    },
    sunsetGlow: {
        name: 'Coucher de Soleil',
        gradient: {
            start: '#ffcccc',
            mid1: '#ffe5b3',
            mid2: '#ffd4d9',
            end: '#ffc5d0'
        }
    },
    navy: {
        name: 'Marine Professionnel',
        gradient: {
            start: '#8fa8c7',
            mid1: '#a3b8d4',
            mid2: '#b8cedf',
            end: '#cdddf0'
        }
    },
    slate: {
        name: 'Ardoise',
        gradient: {
            start: '#b0bcc9',
            mid1: '#c2cdd8',
            mid2: '#d4dde6',
            end: '#e3eaf0'
        }
    }
};

const currentTheme = ref('orangeBlue');

onMounted(() => {
    const savedTheme = localStorage.getItem('prism-theme');
    if (savedTheme && themes[savedTheme]) {
        currentTheme.value = savedTheme;
        applyTheme(savedTheme);
    } else {
        applyTheme('orangeBlue');
    }
});

// Calculer la luminosité d'une couleur hex
const getLuminance = (hex) => {
    const rgb = parseInt(hex.slice(1), 16);
    const r = (rgb >> 16) & 0xff;
    const g = (rgb >> 8) & 0xff;
    const b = (rgb >> 0) & 0xff;
    
    // Formule de luminosité relative
    return (0.299 * r + 0.587 * g + 0.114 * b) / 255;
};

// Déterminer si le texte doit être clair ou foncé
const shouldUseDarkText = (colors) => {
    // Calculer la luminosité moyenne du dégradé
    const luminances = [
        getLuminance(colors.start),
        getLuminance(colors.mid1),
        getLuminance(colors.mid2),
        getLuminance(colors.end)
    ];
    const avgLuminance = luminances.reduce((a, b) => a + b, 0) / luminances.length;
    
    // Si luminosité > 0.5, utiliser texte foncé
    return avgLuminance > 0.5;
};

const applyTheme = (themeName) => {
    const theme = themes[themeName];
    if (!theme) return;
    
    const root = document.documentElement;
    root.style.setProperty('--theme-gradient-start', theme.gradient.start);
    root.style.setProperty('--theme-gradient-mid1', theme.gradient.mid1);
    root.style.setProperty('--theme-gradient-mid2', theme.gradient.mid2);
    root.style.setProperty('--theme-gradient-end', theme.gradient.end);
    
    // Détection automatique de la couleur de texte et mise à jour du composable
    const useDarkText = shouldUseDarkText(theme.gradient);
    console.log(`🎨 Theme ${themeName}: avgLuminance > 0.5 ? ${useDarkText}`, theme.gradient);
    console.log(`📝 Text will be: ${useDarkText ? 'DARK (gray-900)' : 'LIGHT (white)'}`);
    
    // Appliquer les styles glass selon le type de thème
    if (useDarkText) {
        // Thème CLAIR - glass avec bordures visibles
        root.style.setProperty('--glass-bg', 'rgba(255, 255, 255, 0.6)');
        root.style.setProperty('--glass-bg-hover', 'rgba(255, 255, 255, 0.75)');
        root.style.setProperty('--glass-border', 'rgba(0, 0, 0, 0.12)');
        root.style.setProperty('--glass-shadow', 'rgba(0, 0, 0, 0.1)');
    } else {
        // Thème FONCÉ - glass classique
        root.style.setProperty('--glass-bg', 'rgba(255, 255, 255, 0.08)');
        root.style.setProperty('--glass-bg-hover', 'rgba(255, 255, 255, 0.12)');
        root.style.setProperty('--glass-border', 'rgba(255, 255, 255, 0.18)');
        root.style.setProperty('--glass-shadow', 'rgba(102, 126, 234, 0.15)');
    }
    
    setTextMode(useDarkText);
    setTheme(themeName);
    
    localStorage.setItem('prism-theme', themeName);
};

const changeTheme = (themeName) => {
    currentTheme.value = themeName;
    applyTheme(themeName);
    showThemeSelector.value = false;
};

const navigation = computed(() => [
    { name: t('Dashboard'), href: '/dashboard', icon: LayoutDashboard },
    { name: t('Projects'), href: '/projects', icon: FolderKanban },
    { name: t('Risks'), href: '/risks', icon: AlertTriangle },
    { name: t('Import Excel'), href: '/import', icon: Upload },
    { name: t('Categories'), href: '/categories', icon: Tag },
    { name: t('Users'), href: '/users', icon: Users },
    { name: t('Settings'), href: '/settings', icon: Settings },
]);

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
