const path = require('node:path');

const projectRoot = path.resolve(__dirname, '..');

/** @type {import('@storybook/vue3-vite').StorybookConfig} */
const config = {
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
    const vue = (await import('@vitejs/plugin-vue')).default;
    const AutoImport = (await import('unplugin-auto-import/vite')).default;

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

module.exports = config;
