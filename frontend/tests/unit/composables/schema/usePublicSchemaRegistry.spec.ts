import { describe, expect, it } from 'vitest';
import { isPublicSchemaRoute } from '~/composables/schema/usePublicSchemaRegistry';

describe('isPublicSchemaRoute', () => {
  it('allows public nested activity routes', () => {
    expect(isPublicSchemaRoute('/sport/futbol/faz-zenit-activity-uuid')).toBe(true);
    expect(isPublicSchemaRoute('/activities/faz-zenit-activity-uuid')).toBe(true);
    expect(isPublicSchemaRoute('/catalog')).toBe(true);
    expect(isPublicSchemaRoute('/content/article-slug')).toBe(true);
  });

  it('blocks admin and private routes', () => {
    expect(isPublicSchemaRoute('/admin/activities')).toBe(false);
    expect(isPublicSchemaRoute('/account')).toBe(false);
    expect(isPublicSchemaRoute('/organizations/activity-leads')).toBe(false);
  });
});
