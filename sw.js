const CACHE_NAME = 'ymca-portal-cache-v1';
const ASSETS_TO_CACHE = [
  './css/bootstrap.min.css',
  './font-awesome/css/font-awesome.css',
  './css/animate.css',
  './css/style.css',
  './css/custom_modern.css',
  './js/jquery-3.1.1.min.js',
  './js/bootstrap.min.js',
  './js/inspinia.js',
  './app_menu/menu.js'
];

// Install Event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache and caching core assets');
        // Cache assets (use catch block to prevent install failure on partial load)
        return cache.addAll(ASSETS_TO_CACHE.map(url => new Request(url, { mode: 'no-cors' })))
          .catch(err => console.log('Asset caching warning:', err));
      })
      .then(() => self.skipWaiting())
  );
});

// Activate Event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('Clearing old cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event (Network-First Fallback-to-Cache pattern for dynamic database apps)
self.addEventListener('fetch', event => {
  if (!event.request.url.startsWith(self.location.origin)) {
    return;
  }
  
  event.respondWith(
    fetch(event.request).catch(error => {
      return caches.match(event.request).then(cachedResponse => {
        return cachedResponse || Promise.reject(error);
      });
    })
  );
});
