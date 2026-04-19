import { appendResponseHeader, setResponseHeader } from 'h3';

const PRIVATE_ROUTE_PREFIXES = ['/admin', '/account', '/organizations'];
const PERSONALIZED_CACHE_CONTROL = 'private, no-store, max-age=0, must-revalidate';

interface RequestEventLike {
  node?: {
    req?: {
      headers?: {
        cookie?: string;
      };
    };
  };
}

export const isPublicHtmlRoute = (path: string): boolean => {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;

  return !PRIVATE_ROUTE_PREFIXES.some(
    (prefix) => normalizedPath === prefix || normalizedPath.startsWith(`${prefix}/`)
  );
};

export const requestHasCookies = (event: RequestEventLike | null | undefined): boolean => {
  return Boolean(event?.node?.req?.headers?.cookie?.trim());
};

export default defineNuxtRouteMiddleware((to) => {
  if (typeof window !== 'undefined' || !isPublicHtmlRoute(to.path)) {
    return;
  }

  const event = useRequestEvent();
  if (!event) {
    return;
  }

  appendResponseHeader(event, 'Vary', 'Cookie');

  if (requestHasCookies(event)) {
    setResponseHeader(event, 'Cache-Control', PERSONALIZED_CACHE_CONTROL);
  }
});
