export default defineNuxtConfig({
  compatibilityDate: '2026-02-06',
  devtools: { enabled: true },
  modules: ['@nuxtjs/i18n'],
  css: ['~/assets/styles/tailwind.css', '~/assets/styles/global.scss'],
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
  root.setAttribute('data-ui-ready', '0');

  var theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

  try {
    var cookieMatch = document.cookie.match(/(?:^|; )auth_user=([^;]+)/);
    if (cookieMatch && cookieMatch[1]) {
      var user = JSON.parse(decodeURIComponent(cookieMatch[1]));
      var userTheme = user && user.settings && user.settings.theme;
      if (userTheme === 'dark' || userTheme === 'light') {
        theme = userTheme;
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

  if (document.readyState !== 'loading') {
    renderBootLoader();
  } else {
    document.addEventListener('DOMContentLoaded', renderBootLoader, { once: true });
  }

  setTimeout(revealByTimeout, 8000);
})();`,
        },
      ],
    },
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8080',
    },
  },
  i18n: {
    strategy: 'no_prefix',
    defaultLocale: 'ru',
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
