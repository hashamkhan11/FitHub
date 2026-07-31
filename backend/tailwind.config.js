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
                // "Pulse" identity — dense dark-mode operator dashboard, shared by
                // both the fh-* gym app and the pf-* RankSol platform panel (one
                // unified surface language now, rather than two separate palettes).
                // Token *names* are kept stable where possible so existing fh-*/pf-*
                // component classes and ad-hoc utility usage repaint with minimal
                // file churn; `ink` and `chalk` swap roles (ink = light text,
                // chalk = dark surface) and `void` is new (the near-black base,
                // for the handful of spots that used to rely on dark `bg-ink`).
                void: '#0D0F12',
                ink: { DEFAULT: '#E7EAEE', 2: '#0D0F12' },
                chalk: { DEFAULT: '#171B20', 2: '#1A1E23', 3: '#262B31' },
                gold: { DEFAULT: '#3DD6D0', 2: '#22B0AB', 3: '#7FE9E4' },
                blue: { DEFAULT: '#5B8DEF', 2: '#3D6FD1' },
                turf: '#4ADE80',
                tape: '#FB6B6B',
                warn: '#F5B94D',
                'warn-soft': 'rgba(245, 185, 77, 0.12)',
                steel: { DEFAULT: '#8D96A0', 2: '#565F6A' },
                graphite: { DEFAULT: '#171B20', 2: '#14171B', 3: '#1A1E23' },
                teal: { DEFAULT: '#3DD6D0', 2: '#22B0AB', 3: '#1C918C' },
                mist: { DEFAULT: '#8D96A0', 2: '#565F6A' },
                'gold-soft': 'rgba(61, 214, 208, 0.14)',
                'blue-soft': 'rgba(91, 141, 239, 0.14)',
                'turf-soft': 'rgba(74, 222, 128, 0.12)',
                'tape-soft': 'rgba(251, 107, 107, 0.12)',
                'steel-soft': 'rgba(141, 150, 160, 0.1)',
            },
            fontFamily: {
                // System-only stack, no webfont downloads.
                display: ['-apple-system', '"Segoe UI"', 'Roboto', 'Helvetica', 'Arial', 'sans-serif'],
                sans: ['-apple-system', '"Segoe UI"', 'Roboto', 'Arial', 'sans-serif'],
                mono: ['ui-monospace', '"Cascadia Mono"', 'Consolas', '"Courier New"', 'monospace'],
            },
            borderRadius: {
                // One shared scale: hairline-bordered cards/inputs get the smaller
                // radius, cards/modals get the larger one. Both surfaces (fh-*/pf-*)
                // now share it — no more sharp-vs-soft split between the two apps.
                DEFAULT: '6px',
                lg: '8px',
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
