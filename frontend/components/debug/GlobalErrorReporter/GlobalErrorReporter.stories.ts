import type { Meta, StoryObj } from '@storybook/vue3';
import GlobalErrorReporterStatePreview from './GlobalErrorReporterStatePreview.vue';

const meta = {
  title: 'Debug/GlobalErrorReporter',
  component: GlobalErrorReporterStatePreview,
  tags: ['autodocs'],
  args: {
    selectedBlock: {
      blockId: 'home-public-routes',
      strategy: 'data-test',
      queryPath: 'section:nth-of-type(1)',
    },
    description: 'Блок отображается некорректно на главной странице.',
    showPreview: true,
  },
} satisfies Meta<typeof GlobalErrorReporterStatePreview>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Draft: Story = {
  args: {
    reportState: 'draft',
  },
};

export const Sending: Story = {
  args: {
    reportState: 'sending',
  },
};

export const Sent: Story = {
  args: {
    reportState: 'sent',
    sendResult: {
      reportId: 'rep-story-001',
      status: 'received',
    },
  },
};

export const Error: Story = {
  args: {
    reportState: 'error',
    formError: 'Не удалось отправить отчет. Попробуйте позже.',
  },
};

export const AttachmentValidation: Story = {
  args: {
    reportState: 'draft',
    attachmentError: 'malware.exe: Недопустимый тип файла.',
    attachments: [],
    showPreview: false,
  },
};

export const Dark: Story = {
  ...Draft,
  globals: {
    theme: 'dark',
  },
};
