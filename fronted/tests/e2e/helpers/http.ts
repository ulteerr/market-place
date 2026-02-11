import type { Route } from '@playwright/test';

export const readJsonBody = (route: Route): Record<string, unknown> => {
  return (route.request().postDataJSON() ?? {}) as Record<string, unknown>;
};
