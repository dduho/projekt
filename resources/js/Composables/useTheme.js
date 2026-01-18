import { reactive, toRefs } from 'vue';

// Store GLOBAL réactif - partagé entre TOUS les composants
const state = reactive({
    currentTheme: 'orangeBlue',
    isDarkText: true // Par défaut true car thèmes clairs
});

export function useTheme() {
    const setTheme = (themeName) => {
        state.currentTheme = themeName;
    };

    const setTextMode = (isDark) => {
        state.isDarkText = isDark;
        console.log('🔄 isDarkText updated to:', isDark);
    };

    return {
        ...toRefs(state),
        setTheme,
        setTextMode,
    };
}
