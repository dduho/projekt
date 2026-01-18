import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Lexend', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                prism: {
                    50: '#f5f7ff',
                    100: '#ebedff',
                    200: '#d6dbff',
                    300: '#b8bfff',
                    400: '#9ca3ff',
                    500: '#667eea', // Violet principal
                    600: '#5564d6',
                    700: '#4551c2',
                    800: '#3641ae',
                    900: '#2a3399',
                    950: '#1e2370',
                },
                magenta: {
                    50: '#fdf4ff',
                    100: '#fae8ff',
                    200: '#f5d0fe',
                    300: '#f0abfc',
                    400: '#e879f9',
                    500: '#764ba2', // Magenta principal
                    600: '#693d8f',
                    700: '#5c2f7d',
                    800: '#4f226a',
                    900: '#421658',
                    950: '#350d45',
                },
            },
            backgroundImage: {
                'gradient-prism': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'gradient-prism-r': 'linear-gradient(135deg, #764ba2 0%, #667eea 100%)',
                'gradient-orange-blue': 'linear-gradient(135deg, #ffa07a 0%, #87ceeb 100%)',
                'gradient-orange-blue-animated': 'linear-gradient(-45deg, #ffa07a, #ffb366, #87ceeb, #6eb5d4)',
            },
            boxShadow: {
                'glass': '0 8px 32px 0 rgba(102, 126, 234, 0.15)',
                'glass-hover': '0 8px 40px 0 rgba(102, 126, 234, 0.25)',
                'glass-lg': '0 16px 48px 0 rgba(102, 126, 234, 0.20)',
            },
            backdropBlur: {
                'xs': '2px',
            },
            animation: {
                'fade-in': 'fadeIn 0.3s ease-in-out',
                'slide-up': 'slideUp 0.3s ease-out',
                'slide-down': 'slideDown 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { transform: 'translateY(10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                slideDown: {
                    '0%': { transform: 'translateY(-10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                scaleIn: {
                    '0%': { transform: 'scale(0.95)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
            },
        },
    },

    plugins: [
        forms,
        typography,
    ],
};

