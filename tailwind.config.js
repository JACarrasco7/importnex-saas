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
    ],
    theme: {
        extend: {
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
