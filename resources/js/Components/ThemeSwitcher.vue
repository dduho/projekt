<script setup>
import { ref, onMounted, watch } from 'vue';

// Thèmes disponibles
const themes = {
    orangeBlue: {
        name: 'Orange & Bleu (Défaut)',
        gradient: {
            start: '#ffa07a',
            mid1: '#ffb366',
            mid2: '#87ceeb',
            end: '#6eb5d4'
        },
        primary: '255, 160, 122',
        secondary: '135, 206, 235'
    },
    purplePink: {
        name: 'Violet & Magenta',
        gradient: {
            start: '#667eea',
            mid1: '#764ba2',
            mid2: '#c471ed',
            end: '#f64f59'
        },
        primary: '102, 126, 234',
        secondary: '118, 75, 162'
    },
    greenTeal: {
        name: 'Vert & Turquoise',
        gradient: {
            start: '#56ab2f',
            mid1: '#a8e063',
            mid2: '#00b4db',
            end: '#0083b0'
        },
        primary: '86, 171, 47',
        secondary: '0, 180, 219'
    },
    sunsetGlow: {
        name: 'Coucher de Soleil',
        gradient: {
            start: '#ff6b6b',
            mid1: '#feca57',
            mid2: '#ee5a6f',
            end: '#c44569'
        },
        primary: '255, 107, 107',
        secondary: '254, 202, 87'
    }
};

const currentTheme = ref('orangeBlue');

onMounted(() => {
    // Charger le thème sauvegardé
    const savedTheme = localStorage.getItem('prism-theme');
    if (savedTheme && themes[savedTheme]) {
        currentTheme.value = savedTheme;
        applyTheme(savedTheme);
    } else {
        applyTheme('orangeBlue');
    }
});

const applyTheme = (themeName) => {
    const theme = themes[themeName];
    if (!theme) return;
    
    const root = document.documentElement;
    root.style.setProperty('--theme-gradient-start', theme.gradient.start);
    root.style.setProperty('--theme-gradient-mid1', theme.gradient.mid1);
    root.style.setProperty('--theme-gradient-mid2', theme.gradient.mid2);
    root.style.setProperty('--theme-gradient-end', theme.gradient.end);
    root.style.setProperty('--theme-primary', theme.primary);
    root.style.setProperty('--theme-secondary', theme.secondary);
    
    localStorage.setItem('prism-theme', themeName);
};

const changeTheme = (themeName) => {
    currentTheme.value = themeName;
    applyTheme(themeName);
};

defineExpose({
    themes,
    currentTheme,
    changeTheme
});
</script>

<template>
    <div class="theme-switcher">
        <slot :themes="themes" :current-theme="currentTheme" :change-theme="changeTheme" />
    </div>
</template>
