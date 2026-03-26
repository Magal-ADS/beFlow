const CACHE_NAME = 'beflow-v1';
const urlsToCache = [
  '/beFlow/',
  '/beFlow/login'
];

// Instala o Service Worker e guarda os arquivos básicos em cache
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

// Intercepta as requisições (aqui podemos fazer o app funcionar offline no futuro)
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        return response || fetch(event.request);
      })
  );
});