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
      style: [
        {
          key: 'public-critical-shell',
          innerHTML: `
html, body {
  margin: 0;
  min-height: 100%;
}
body {
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}
.public-default-layout {
  min-height: 100vh;
  background: var(--page-background, #ffffff);
  color: var(--text, #111827);
}
.public-header-shell {
  position: sticky;
  top: 0;
  z-index: 40;
  border-bottom: 1px solid var(--border, #e5e7eb);
  background: var(--surface, #ffffff);
}
.public-header-shell [data-test="public-header-logo"] {
  color: inherit;
  text-decoration: none;
}
.public-header-shell [data-test="public-header-bottom-row"] {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.public-section-shell {
  margin: 0 auto;
  width: 100%;
  max-width: 72rem;
  padding: 2rem 1rem 3rem;
}
.public-card-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 1.6rem;
}
.public-card-grid__link {
  color: inherit;
  text-decoration: none;
}
.public-card-grid__card {
  height: 100%;
  overflow: hidden;
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 1rem;
  background: var(--surface-elevated, #ffffff);
  box-shadow: 0 10px 30px rgb(15 23 42 / 6%);
}
.public-card-grid__media {
  position: relative;
  overflow: hidden;
  aspect-ratio: 4 / 5;
  background: linear-gradient(180deg, rgba(148, 163, 184, 0.15), rgba(148, 163, 184, 0.05));
}
.public-card-grid__media img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.public-card-grid__copy {
  display: grid;
  gap: 0.65rem;
  padding: 1.3rem;
}
.public-app-footer {
  border-top: 1px solid var(--border, #e5e7eb);
  background: var(--surface, #ffffff);
}
@media (min-width: 640px) {
  .public-card-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (min-width: 900px) {
  .public-card-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
@media (min-width: 1280px) {
  .public-card-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}
          `.trim(),
        },
        {
          key: 'initial-ui-gate',
          innerHTML: `
html[data-ui-ready='0'] body { visibility: hidden; }
html[data-ui-ready='0'] #app-boot-loader {
  opacity: 1;
  visibility: visible;
}
html[data-ui-ready='1'] #app-boot-loader {
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
}
#app-boot-loader {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: grid;
  place-items: center;
  transition: opacity .2s ease;
}
#app-boot-loader .boot-spinner {
  width: 30px;
  height: 30px;
  border-radius: 9999px;
  border: 2px solid rgba(148, 163, 184, .4);
  border-top-color: #fb923c;
  animation: app-boot-spin .8s linear infinite;
}
@keyframes app-boot-spin {
  to { transform: rotate(360deg); }
}
          `.trim(),
        },
      ],
      script: [
        {
          key: 'initial-theme-and-ui-gate',
          tagPosition: 'head',
          innerHTML: `(function () {
  var root = document.documentElement;
  var path = window.location && typeof window.location.pathname === 'string'
    ? window.location.pathname
    : '/';
  var shouldGateUi = path.startsWith('/admin')
    || path.startsWith('/account')
    || path.startsWith('/organizations');

  root.setAttribute('data-ui-ready', shouldGateUi ? '0' : '1');

  var theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

  try {
    var cookieMatch = document.cookie.match(/(?:^|; )auth_user=([^;]+)/);
    if (cookieMatch && cookieMatch[1]) {
      var user = JSON.parse(decodeURIComponent(cookieMatch[1]));
      var userTheme = user && user.settings && user.settings.theme;
      if (userTheme === 'dark' || userTheme === 'light') {
        theme = userTheme;
      }
    } else {
      var guestMatch = document.cookie.match(/(?:^|; )guest_preferences=([^;]+)/);
      if (guestMatch && guestMatch[1]) {
        var base64 = guestMatch[1].replace(/-/g, '+').replace(/_/g, '/');
        var padded = base64 + '==='.slice((base64.length + 3) % 4);
        var guest = JSON.parse(decodeURIComponent(escape(atob(padded))));
        var guestTheme = guest && guest.settings && guest.settings.theme;
        if (guestTheme === 'dark' || guestTheme === 'light') {
          theme = guestTheme;
        }
      }
    }
  } catch (e) {}

  root.setAttribute('data-theme', theme);
  root.classList.toggle('dark', theme === 'dark');
  root.style.colorScheme = theme;
  root.style.background = theme === 'dark' ? '#111319' : '#ffffff';
  root.style.color = theme === 'dark' ? '#e2e8f0' : '#111827';

  var renderBootLoader = function () {
    if (document.body) {
      document.body.style.background = theme === 'dark' ? '#111319' : '#ffffff';
      document.body.style.color = theme === 'dark' ? '#e2e8f0' : '#111827';
      if (!document.getElementById('app-boot-loader')) {
        var loader = document.createElement('div');
        loader.id = 'app-boot-loader';
        loader.style.background = theme === 'dark' ? '#111319' : '#ffffff';
        loader.innerHTML = '<div class="boot-spinner" aria-hidden="true"></div>';
        document.body.appendChild(loader);
      }
    }
  };

  var revealByTimeout = function () {
    root.setAttribute('data-ui-ready', '1');
    if (document.body) {
      document.body.style.visibility = 'visible';
    }
  };

  if (shouldGateUi) {
    if (document.readyState !== 'loading') {
      renderBootLoader();
    } else {
      document.addEventListener('DOMContentLoaded', renderBootLoader, { once: true });
    }
  }

  if (shouldGateUi) {
    setTimeout(revealByTimeout, 8000);
  }
})();`,
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
