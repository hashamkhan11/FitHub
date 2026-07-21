/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                ink: { DEFAULT: '#101826', 2: '#182236' },
                chalk: { DEFAULT: '#F4F5F1', 2: '#E7E8E2' },
                gold: { DEFAULT: '#D9A441', 2: '#B9862E' },
                turf: '#2F5D50',
                tape: '#B23A2E',
                steel: { DEFAULT: '#5B6472', 2: '#8891A0' },
                graphite: { DEFAULT: '#F3F7F7', 2: '#FFFFFF', 3: '#EAF1F1' },
                teal: { DEFAULT: '#0E9488', 2: '#0B786E' },
                mist: { DEFAULT: '#57636D', 2: '#8B98A1' },
            },
            fontFamily: {
                display: ['Oswald', 'Arial Narrow', 'sans-serif'],
                sans: ['"Work Sans"', 'Segoe UI', 'system-ui', 'sans-serif'],
                mono: ['"IBM Plex Mono"', 'ui-monospace', 'Consolas', 'monospace'],
            },
            borderRadius: {
                DEFAULT: '3px',
                sm: '2px',
                md: '3px',
                lg: '4px',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(14px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.7s cubic-bezier(0.16, 1, 0.3, 1) both',
            },
        },
    },
    plugins: [],
};
