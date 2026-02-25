export const E2E_VIEWPORTS = {
  ultraNarrow280: { name: 'ultra-narrow-280', width: 280, height: 740 },
  mobile390: { name: 'mobile-390', width: 390, height: 844 },
  tablet768: { name: 'tablet-768', width: 768, height: 1024 },
  desktop1366: { name: 'desktop-1366', width: 1366, height: 900 },
} as const;

export type E2EViewport = (typeof E2E_VIEWPORTS)[keyof typeof E2E_VIEWPORTS];

export const E2E_RESPONSIVE_VIEWPORTS = [
  E2E_VIEWPORTS.ultraNarrow280,
  E2E_VIEWPORTS.mobile390,
  E2E_VIEWPORTS.tablet768,
  E2E_VIEWPORTS.desktop1366,
] as const;
