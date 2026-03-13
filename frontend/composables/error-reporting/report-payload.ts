import type { SelectedReportBlock } from '~/composables/useUiErrorReporter';
import type { ReportAttachmentMeta } from '~/composables/error-reporting/attachments';

type RouteLike = {
  fullPath: string;
  name?: string | null;
  path?: string;
};

type ClientSnapshot = {
  href: string;
  userAgent: string;
  viewport: {
    width: number;
    height: number;
  };
  theme: string;
};

export type UiErrorReportPayload = {
  page: {
    url: string;
    path: string;
    routeName: string;
  };
  block: {
    id: string;
    strategy: string;
    queryPath: string;
    selectedAt: string;
  };
  description: string;
  attachments: ReportAttachmentMeta[];
  context: {
    userAgent: string;
    viewport: {
      width: number;
      height: number;
    };
    theme: string;
    locale: string;
    timestamp: string;
  };
};

const getClientSnapshot = (): ClientSnapshot => {
  if (!process.client) {
    return {
      href: '',
      userAgent: 'server',
      viewport: {
        width: 0,
        height: 0,
      },
      theme: 'unknown',
    };
  }

  const root = document.documentElement;
  const resolvedTheme =
    root.getAttribute('data-theme') || (root.classList.contains('dark') ? 'dark' : 'light');

  return {
    href: window.location.href,
    userAgent: navigator.userAgent,
    viewport: {
      width: window.innerWidth,
      height: window.innerHeight,
    },
    theme: resolvedTheme,
  };
};

export const buildUiErrorReportPayload = (input: {
  selectedBlock: SelectedReportBlock;
  description: string;
  attachments?: ReportAttachmentMeta[];
  route: RouteLike;
  locale: string;
  now?: Date;
  clientSnapshot?: ClientSnapshot;
}): UiErrorReportPayload => {
  const description = input.description.trim();
  const snapshot = input.clientSnapshot ?? getClientSnapshot();
  const timestamp = (input.now ?? new Date()).toISOString();

  return {
    page: {
      url: snapshot.href || input.route.fullPath,
      path: input.route.path || input.route.fullPath,
      routeName: String(input.route.name ?? ''),
    },
    block: {
      id: input.selectedBlock.blockId,
      strategy: input.selectedBlock.strategy,
      queryPath: input.selectedBlock.queryPath,
      selectedAt: input.selectedBlock.selectedAt,
    },
    description,
    attachments: input.attachments ? [...input.attachments] : [],
    context: {
      userAgent: snapshot.userAgent,
      viewport: snapshot.viewport,
      theme: snapshot.theme,
      locale: input.locale,
      timestamp,
    },
  };
};
