<template>
    <div class="min-h-screen flex items-center justify-center p-6 relative">
        <!-- Background animé avec pictogrammes -->
        <BackgroundIcons />
        
        <div class="w-full max-w-md relative z-10">
            <!-- Logo -->
            <div class="text-center mb-8 animate-fade-in">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-prism rounded-2xl shadow-glass-lg mb-4">
                    <Zap class="w-10 h-10 text-white" />
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">PRISM</h1>
                <p class="text-gray-400">Project Intelligence & Status Manager</p>
            </div>

            <!-- Content Card -->
            <div class="glass-card animate-slide-up">
                <slot />
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 text-sm text-gray-400">
                <p>&copy; {{ currentYear }} MOOV MONEY TOGO - Tous droits réservés</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Zap } from 'lucide-vue-next';
import BackgroundIcons from '@/Components/BackgroundIcons.vue';

const currentYear = computed(() => new Date().getFullYear());
</script>
