export default defineNuxtConfig({
  compatibilityDate: '2026-02-06',
  devtools: { enabled: true },
  css: ['~/assets/styles/tailwind.css', '~/assets/styles/global.scss'],
  postcss: {
    plugins: {
      '@tailwindcss/postcss': {}
    }
  },
  app: {
    head: {
      title: 'Marketplace Fronted',
      meta: [
        {
          name: 'description',
          content: 'Frontend for public and admin pages. Backend is API/Auth only.'
        }
      ]
    }
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8080'
    }
  }
})
