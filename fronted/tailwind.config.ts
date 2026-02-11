import type { Config } from 'tailwindcss';

export default {
  content: [
    './app.vue',
    './components/**/*.{vue,js,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './plugins/**/*.{js,ts}',
    './composables/**/*.{js,ts}',
  ],
  theme: {
    extend: {
      colors: {
        ink: '#111827',
        slate: '#475569',
        ember: '#ea580c',
        skyline: '#0ea5e9',
        fog: '#f8fafc',
      },
    },
  },
  plugins: [],
} satisfies Config;
