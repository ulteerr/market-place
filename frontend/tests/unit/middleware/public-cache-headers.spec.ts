import { afterEach, describe, expect, it, vi } from 'vitest';

const appendResponseHeaderMock = vi.fn();
const setResponseHeaderMock = vi.fn();

vi.mock('h3', () => ({
  appendResponseHeader: appendResponseHeaderMock,
  setResponseHeader: setResponseHeaderMock,
}));

describe('public-cache-headers middleware', () => {
  afterEach(() => {
    vi.resetModules();
    vi.unstubAllGlobals();
    vi.clearAllMocks();
  });

  const loadMiddleware = async (cookieHeader?: string) => {
    const event = {
      node: {
        req: {
          headers: {
            cookie: cookieHeader,
          },
        },
      },
    };

    vi.stubGlobal('defineNuxtRouteMiddleware', (callback: unknown) => callback);
    vi.stubGlobal('useRequestEvent', () => event);

    const module = await import('~/middleware/public-cache-headers.global');

    return {
      event,
      middleware: module.default,
      isPublicHtmlRoute: module.isPublicHtmlRoute,
      requestHasCookies: module.requestHasCookies,
    };
  };

  it('marks public routes as cache-sensitive and excludes private areas', async () => {
    const { isPublicHtmlRoute } = await loadMiddleware();

    expect(isPublicHtmlRoute('/')).toBe(true);
    expect(isPublicHtmlRoute('/catalog/chess')).toBe(true);
    expect(isPublicHtmlRoute('/activities/demo')).toBe(true);
    expect(isPublicHtmlRoute('/admin')).toBe(false);
    expect(isPublicHtmlRoute('/account/profile')).toBe(false);
    expect(isPublicHtmlRoute('/organizations/members')).toBe(false);
  });

  it('detects whether the incoming request is personalized by cookies', async () => {
    const { requestHasCookies } = await loadMiddleware('guest_preferences_v2=abc');

    expect(requestHasCookies(null)).toBe(false);
    expect(requestHasCookies({})).toBe(false);
    expect(requestHasCookies({ node: { req: { headers: {} } } })).toBe(false);
    expect(requestHasCookies({ node: { req: { headers: { cookie: 'auth_token=1' } } } })).toBe(
      true
    );
  });

  it('adds Vary: Cookie and disables shared caching for personalized public responses', async () => {
    const { event, middleware } = await loadMiddleware('guest_preferences_v2=abc');

    middleware({
      path: '/catalog',
      meta: {},
    } as never);

    expect(appendResponseHeaderMock).toHaveBeenCalledWith(event, 'Vary', 'Cookie');
    expect(setResponseHeaderMock).toHaveBeenCalledWith(
      event,
      'Cache-Control',
      'private, no-store, max-age=0, must-revalidate'
    );
  });

  it('only appends Vary for anonymous public responses', async () => {
    const { event, middleware } = await loadMiddleware('');

    middleware({
      path: '/',
      meta: {},
    } as never);

    expect(appendResponseHeaderMock).toHaveBeenCalledWith(event, 'Vary', 'Cookie');
    expect(setResponseHeaderMock).not.toHaveBeenCalled();
  });

  it('ignores private routes entirely', async () => {
    const { middleware } = await loadMiddleware('auth_token=1');

    middleware({
      path: '/admin/users',
      meta: {},
    } as never);

    expect(appendResponseHeaderMock).not.toHaveBeenCalled();
    expect(setResponseHeaderMock).not.toHaveBeenCalled();
  });
});
