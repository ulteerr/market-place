const extractLocale = (): 'ru' | 'en' => {
  if (typeof window === 'undefined') {
    return 'ru';
  }

  const globalsRaw = new URL(window.location.href).searchParams.get('globals') ?? '';
  const decoded = decodeURIComponent(globalsRaw);
  const match = decoded.match(/(?:^|[;,])locale:([^;,]+)/i);
  const locale = (match?.[1] ?? '').toLowerCase();

  return locale === 'en' ? 'en' : 'ru';
};

const applyLocale = () => {
  if (typeof document === 'undefined') {
    return;
  }

  const locale = extractLocale();
  document.documentElement.setAttribute('data-locale', locale);
  document.body?.setAttribute('data-locale', locale);
};

applyLocale();

if (typeof window !== 'undefined') {
  window.addEventListener('popstate', applyLocale);
  window.addEventListener('hashchange', applyLocale);
  setInterval(applyLocale, 250);
}
