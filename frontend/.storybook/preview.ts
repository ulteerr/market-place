import type { Preview } from '@storybook/vue3';
import { setup } from '@storybook/vue3';
import { createI18n } from 'vue-i18n';
import en from '../i18n/locales/en';
import ru from '../i18n/locales/ru';
import '../assets/styles/tailwind.css';
import '../assets/styles/global.scss';
import './docs-locale.css';
import './docs-locale-sync';

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  fallbackLocale: 'en',
  messages: { ru, en },
});

setup((app) => {
  app.use(i18n);
});

const preview: Preview = {
  globalTypes: {
    locale: {
      name: 'Locale',
      description: 'Application locale',
      defaultValue: 'ru',
      toolbar: {
        icon: 'globe',
        items: [
          { value: 'ru', title: 'Russian' },
          { value: 'en', title: 'English' },
        ],
      },
    },
    theme: {
      name: 'Theme',
      description: 'Application theme',
      defaultValue: 'light',
      toolbar: {
        icon: 'mirror',
        items: [
          { value: 'light', title: 'Light' },
          { value: 'dark', title: 'Dark' },
        ],
      },
    },
    density: {
      name: 'Density',
      description: 'Story container density',
      defaultValue: 'comfortable',
      toolbar: {
        icon: 'sidebaralt',
        items: [
          { value: 'compact', title: 'Compact' },
          { value: 'comfortable', title: 'Comfortable' },
        ],
      },
    },
  },
  decorators: [
    (story, context) => {
      const locale = String(context.globals.locale ?? 'ru');
      i18n.global.locale.value = locale;

      const localeArgs = context.parameters?.localeArgs as
        | Record<string, Record<string, unknown>>
        | undefined;
      if (localeArgs) {
        const localized = localeArgs[locale] ?? localeArgs.ru ?? localeArgs.en;
        if (localized && typeof localized === 'object') {
          Object.assign(context.args, localized);
        }
      }

      const theme = String(context.globals.theme ?? 'light');
      if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('data-locale', locale);
      }

      const isCompact = String(context.globals.density ?? 'comfortable') === 'compact';
      return {
        components: { story },
        template: `
          <div style="padding: ${isCompact ? '12px' : '24px'}; min-height: calc(100vh - 48px);">
            <story />
          </div>
        `,
      };
    },
  ],
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
    a11y: {
      test: 'error',
    },
    layout: 'fullscreen',
  },
};

export default preview;
