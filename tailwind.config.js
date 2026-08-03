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
    ],
    safelist: [
        'bg-indigo-50', 'bg-blue-50', 'bg-emerald-50', 'bg-amber-50', 'bg-rose-50', 'bg-purple-50', 'bg-sky-50', 'bg-gray-100',
        'bg-indigo-100', 'bg-blue-100', 'bg-emerald-100', 'bg-amber-100', 'bg-rose-100', 'bg-purple-100', 'bg-sky-100',
        'bg-indigo-300', 'bg-rose-500', 'bg-emerald-500', 'bg-amber-500',
        'text-indigo-600', 'text-blue-600', 'text-emerald-600', 'text-amber-600', 'text-rose-600', 'text-purple-600', 'text-sky-600', 'text-gray-600',
        'text-indigo-700', 'text-blue-700', 'text-emerald-700', 'text-amber-700', 'text-rose-700', 'text-purple-700', 'text-sky-700',
        'ring-indigo-200', 'ring-blue-200', 'ring-emerald-200', 'ring-amber-200', 'ring-rose-200', 'ring-purple-200', 'ring-sky-200', 'ring-gray-200',
        'ring-indigo-300', 'ring-emerald-300',
        'border-emerald-300', 'border-rose-300',
        'from-indigo-500', 'to-purple-600', 'from-indigo-600', 'to-purple-700',
        'from-indigo-50', 'to-indigo-100', 'from-blue-50', 'to-indigo-50',
        'bg-estoril-50', 'bg-estoril-100', 'bg-estoril-600', 'bg-estoril-700',
        'text-estoril-600', 'text-estoril-700', 'ring-estoril-200', 'ring-estoril-300',
        'border-estoril-300', 'from-estoril-50', 'to-estoril-50', 'from-estoril-600', 'to-estoril-800',
    ],
    theme: {
        extend: {
            colors: {
                // === MARCA JJ Import Motors ===
                // Deep Estoril blue #1A306D (primario), Asphalt grey #38393D (neutro),
                // Platinum silver #BEC0C3 (acento). Ver docs/BRAND.md
                estoril: {
                    50: '#eef1fa',
                    100: '#dce3f5',
                    200: '#b9c6ea',
                    300: '#8fa3d9',
                    400: '#5c73bd',
                    500: '#3a4f9e',
                    600: '#2a3d87',
                    700: '#1A306D',
                    800: '#152756',
                    900: '#101d42',
                },
                asphalt: {
                    50: '#f6f6f7',
                    100: '#e7e7e9',
                    200: '#cfcfd2',
                    300: '#a7a8ab',
                    400: '#7e7f83',
                    500: '#5d5e62',
                    600: '#4a4b4f',
                    700: '#38393D',
                    800: '#2a2b2e',
                    900: '#1e1f21',
                },
                platinum: {
                    50: '#fafafa',
                    100: '#f3f3f4',
                    200: '#e6e7e8',
                    300: '#d5d6d8',
                    400: '#BEC0C3',
                    500: '#a6a8ac',
                    600: '#909296',
                },
            },
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            animation: {
                'fade-in': 'fadeIn 0.2s ease-in-out',
                'slide-up': 'slideUp 0.3s ease-out',
            },
            keyframes: {
                fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                slideUp: { '0%': { transform: 'translateY(10px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
            },
        },
    },
    plugins: [forms, typography],
};
