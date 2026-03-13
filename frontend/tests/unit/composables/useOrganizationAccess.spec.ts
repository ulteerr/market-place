import { computed, ref } from 'vue';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { useOrganizationAccess } from '~/composables/useOrganizationAccess';

describe('useOrganizationAccess', () => {
  const user = ref<{ roles?: string[]; permissions?: string[] } | null>(null);

  afterEach(() => {
    user.value = null;
    vi.unstubAllGlobals();
  });

  const installGlobals = () => {
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('useAuth', () => ({
      user,
    }));
    vi.stubGlobal('usePermissions', () => ({
      hasPermission: (code: string) => user.value?.permissions?.includes(code) ?? false,
      hasAnyPermission: (required: string[]) =>
        required.some((code) => user.value?.permissions?.includes(code) ?? false),
      hasAllPermissions: (required: string[]) =>
        required.every((code) => user.value?.permissions?.includes(code) ?? false),
    }));
  };

  it('prefers explicit owner role over permission inference', () => {
    user.value = {
      roles: ['owner'],
      permissions: ['org.company.profile.read'],
    };

    installGlobals();

    const access = useOrganizationAccess();

    expect(access.inferredRole.value).toBe('owner');
    expect(access.accessLabel.value).toBe('Owner');
  });

  it('infers admin access from full write permission set', () => {
    user.value = {
      permissions: ['org.company.profile.update', 'org.members.write', 'org.children.write'],
    };

    installGlobals();

    const access = useOrganizationAccess();

    expect(access.inferredRole.value).toBe('admin');
    expect(access.canManageMembers.value).toBe(true);
    expect(access.canManageClients.value).toBe(true);
  });

  it('falls back to member when only read permissions exist', () => {
    user.value = {
      permissions: ['org.company.profile.read', 'org.members.read'],
    };

    installGlobals();

    const access = useOrganizationAccess();

    expect(access.inferredRole.value).toBe('member');
    expect(access.accessLabel.value).toBe('Member');
    expect(access.canViewJoinRequests.value).toBe(true);
    expect(access.canReviewJoinRequests.value).toBe(false);
  });

  it('returns none when organization permissions are absent', () => {
    user.value = {
      roles: ['user'],
      permissions: ['profile.read'],
    };

    installGlobals();

    const access = useOrganizationAccess();

    expect(access.inferredRole.value).toBe('none');
    expect(access.accessLabel.value).toBe('No access');
  });
});
