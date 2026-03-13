import { afterEach, describe, expect, it, vi } from 'vitest';

describe('organizations-permission middleware', () => {
  afterEach(() => {
    vi.resetModules();
    vi.unstubAllGlobals();
    vi.clearAllMocks();
  });

  const loadMiddleware = async (permissions: string[] = []) => {
    const navigateToMock = vi.fn((path: string) => path);

    vi.stubGlobal('defineNuxtRouteMiddleware', (callback: unknown) => callback);
    vi.stubGlobal('navigateTo', navigateToMock);
    vi.stubGlobal('usePermissions', () => ({
      hasAnyPermission: (required: string[]) => required.some((code) => permissions.includes(code)),
      hasAllPermissions: (required: string[]) =>
        required.every((code) => permissions.includes(code)),
    }));

    const module = await import('~/middleware/organizations-permission');

    return {
      middleware: module.default,
      navigateToMock,
    };
  };

  it('ignores non-organizations routes', async () => {
    const { middleware, navigateToMock } = await loadMiddleware();

    const result = middleware({
      path: '/account',
      meta: {},
    } as never);

    expect(result).toBeUndefined();
    expect(navigateToMock).not.toHaveBeenCalled();
  });

  it('allows access when required permission is present', async () => {
    const { middleware, navigateToMock } = await loadMiddleware(['org.members.read']);

    const result = middleware({
      path: '/organizations/members',
      meta: {
        permission: 'org.members.read',
      },
    } as never);

    expect(result).toBeUndefined();
    expect(navigateToMock).not.toHaveBeenCalled();
  });

  it('redirects to organizations overview when permission is missing', async () => {
    const { middleware, navigateToMock } = await loadMiddleware([]);

    const result = middleware({
      path: '/organizations/join-requests',
      meta: {
        permission: 'org.members.read',
      },
    } as never);

    expect(result).toBe('/organizations');
    expect(navigateToMock).toHaveBeenCalledWith('/organizations');
  });

  it('supports all-mode permission checks', async () => {
    const { middleware, navigateToMock } = await loadMiddleware(['org.company.profile.read']);

    const result = middleware({
      path: '/organizations',
      meta: {
        permission: ['org.company.profile.read', 'org.members.read'],
        permissionMode: 'all',
      },
    } as never);

    expect(result).toBe('/organizations');
    expect(navigateToMock).toHaveBeenCalledWith('/organizations');
  });
});
