// register-sw.js — registers service-worker.js for offline/PWA support.
// 1. Only runs if the browser supports the Service Worker API.
// 2. Registers after `load` so it never competes with the initial page load.
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker
      .register('/service-worker.js')
      .then((reg) => console.log('Service worker registered:', reg.scope))
      .catch((err) => console.error('Service worker registration failed:', err));
  });
}