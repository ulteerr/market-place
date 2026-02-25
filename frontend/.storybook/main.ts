import type { StorybookConfig } from '@storybook/vue3-vite';
import { fileURLToPath } from 'node:url';
import vue from '@vitejs/plugin-vue';
import AutoImport from 'unplugin-auto-import/vite';

const projectRoot = fileURLToPath(new URL('..', import.meta.url));

const config: StorybookConfig = {
  stories: ['../docs/*.mdx', '../components/**/*.stories.@(ts|tsx|js|jsx|mjs)'],
  addons: ['@storybook/addon-docs', '@storybook/addon-links', '@storybook/addon-a11y'],
  framework: {
    name: '@storybook/vue3-vite',
    options: {},
  },
  docs: {
    autodocs: 'tag',
  },
  viteFinal: async (viteConfig) => {
    viteConfig.resolve = viteConfig.resolve ?? {};
    viteConfig.resolve.alias = {
      ...(viteConfig.resolve.alias ?? {}),
      '~': projectRoot,
      '@': projectRoot,
    };

    viteConfig.plugins = [
      ...(viteConfig.plugins ?? []),
      vue(),
      AutoImport({
        imports: ['vue', { 'vue-i18n': ['useI18n'] }],
        dts: false,
        vueTemplate: true,
      }),
    ];
    return viteConfig;
  },
};

export default config;
