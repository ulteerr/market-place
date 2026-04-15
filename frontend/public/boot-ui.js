(function () {
  var root = document.documentElement;
  var path =
    window.location && typeof window.location.pathname === 'string'
      ? window.location.pathname
      : '/';
  var shouldGateUi =
    path.startsWith('/admin') || path.startsWith('/account') || path.startsWith('/organizations');

  root.setAttribute('data-ui-ready', shouldGateUi ? '0' : '1');

  var theme =
    window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
      ? 'dark'
      : 'light';

  try {
    var cookieMatch = document.cookie.match(/(?:^|; )auth_user=([^;]+)/);
    if (cookieMatch && cookieMatch[1]) {
      var user = JSON.parse(decodeURIComponent(cookieMatch[1]));
      var userTheme = user && user.settings && user.settings.theme;
      if (userTheme === 'dark' || userTheme === 'light') {
        theme = userTheme;
      }
    } else {
      var guestMatch =
        document.cookie.match(/(?:^|; )guest_preferences_v2=([^;]+)/) ||
        document.cookie.match(/(?:^|; )guest_preferences=([^;]+)/);
      if (guestMatch && guestMatch[1]) {
        var base64 = guestMatch[1].replace(/-/g, '+').replace(/_/g, '/');
        var padded = base64 + '==='.slice((base64.length + 3) % 4);
        var guest = JSON.parse(decodeURIComponent(escape(atob(padded))));
        var guestTheme = guest && guest.settings && guest.settings.theme;
        if (guestTheme === 'dark' || guestTheme === 'light') {
          theme = guestTheme;
        }
      }
    }
  } catch (e) {}

  root.setAttribute('data-theme', theme);
  root.classList.toggle('dark', theme === 'dark');
  root.style.colorScheme = theme;
  root.style.background = theme === 'dark' ? '#111319' : '#ffffff';
  root.style.color = theme === 'dark' ? '#e2e8f0' : '#111827';

  var renderBootLoader = function () {
    if (document.body) {
      document.body.style.background = theme === 'dark' ? '#111319' : '#ffffff';
      document.body.style.color = theme === 'dark' ? '#e2e8f0' : '#111827';
      if (!document.getElementById('app-boot-loader')) {
        var loader = document.createElement('div');
        loader.id = 'app-boot-loader';
        loader.style.background = theme === 'dark' ? '#111319' : '#ffffff';
        loader.innerHTML = '<div class="boot-spinner" aria-hidden="true"></div>';
        document.body.appendChild(loader);
      }
    }
  };

  var revealByTimeout = function () {
    root.setAttribute('data-ui-ready', '1');
    if (document.body) {
      document.body.style.visibility = 'visible';
    }
  };

  if (shouldGateUi) {
    if (document.readyState !== 'loading') {
      renderBootLoader();
    } else {
      document.addEventListener('DOMContentLoaded', renderBootLoader, { once: true });
    }
    setTimeout(revealByTimeout, 8000);
  }
})();
