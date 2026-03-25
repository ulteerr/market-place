export default defineNuxtConfig({
  compatibilityDate: '2026-02-06',
  devtools: { enabled: true },
  modules: ['@nuxtjs/i18n'],
  features: {
    inlineStyles: true,
  },
  routeRules: {
    '/': { ssr: true },
    '/catalog/**': { ssr: true },
    '/content/**': { ssr: true },
    '/account/**': { ssr: false },
    '/organizations/**': { ssr: false },
  },
  css: ['~/assets/styles/tailwind.css'],
  postcss: {
    plugins: {
      '@tailwindcss/postcss': {},
    },
  },
  app: {
    head: {
      title: 'Marketplace Frontend',
      meta: [
        {
          name: 'description',
          content: 'Frontend for public and admin pages. Backend is API/Auth only.',
        },
      ],
      link: [
        {
          key: 'boot-ui-style',
          rel: 'stylesheet',
          href: '/boot-ui.css',
        },
      ],

      script: [
        {
          key: 'boot-ui-script',
          tagPosition: 'head',
          src: '/boot-ui.js',
        },
      ],
    },
  },
  runtimeConfig: {
    apiInternalBase: process.env.NUXT_API_INTERNAL_BASE || 'http://web',
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8080',
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000',
      reverbEnabled: process.env.NUXT_PUBLIC_REVERB_ENABLED !== 'false',
      reverbAppKey: process.env.NUXT_PUBLIC_REVERB_APP_KEY || '',
      reverbHost: process.env.NUXT_PUBLIC_REVERB_HOST || 'localhost',
      reverbPort: Number(process.env.NUXT_PUBLIC_REVERB_PORT || 8083),
      reverbScheme: process.env.NUXT_PUBLIC_REVERB_SCHEME || 'http',
      reverbAuthEndpoint: process.env.NUXT_PUBLIC_REVERB_AUTH_ENDPOINT || '/broadcasting/auth',
      presenceHeartbeatIntervalSeconds: Number(
        process.env.NUXT_PUBLIC_PRESENCE_HEARTBEAT_INTERVAL_SECONDS || 30
      ),
      presenceHeartbeatMaxBackoffSeconds: Number(
        process.env.NUXT_PUBLIC_PRESENCE_HEARTBEAT_MAX_BACKOFF_SECONDS || 300
      ),
      presenceHeartbeatPauseWhenHidden:
        process.env.NUXT_PUBLIC_PRESENCE_HEARTBEAT_PAUSE_WHEN_HIDDEN !== 'false',
    },
  },
  i18n: {
    strategy: 'no_prefix',
    defaultLocale: 'ru',
    vueI18n: './i18n.config.ts',
    langDir: 'locales',
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'i18n_redirected',
      redirectOn: 'root',
    },
    locales: [
      { code: 'ru', name: 'Русский', file: 'ru.ts' },
      { code: 'en', name: 'English', file: 'en.ts' },
    ],
  },
});
