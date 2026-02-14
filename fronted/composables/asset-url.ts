export const resolveAssetUrl = (baseUrl: string, url: string | null | undefined): string | null => {
  if (!url) {
    return null;
  }

  try {
    const resolved = new URL(url, baseUrl);
    const apiOrigin = new URL(baseUrl).origin;

    const isLikelyLocalStorageAsset =
      resolved.pathname.startsWith('/storage/') || resolved.pathname.startsWith('/uploads/');

    const isFrontendLocalhostAsset =
      (resolved.hostname === 'localhost' || resolved.hostname === '127.0.0.1') &&
      (resolved.port === '' || resolved.port === '80') &&
      isLikelyLocalStorageAsset;

    if (isFrontendLocalhostAsset && resolved.origin !== apiOrigin) {
      return `${apiOrigin}${resolved.pathname}${resolved.search}${resolved.hash}`;
    }

    return resolved.toString();
  } catch {
    return url;
  }
};
