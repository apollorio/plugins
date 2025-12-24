const CACHE_NAME_MEDIA = 'apollo-rio-media-cache';

self.addEventListener('fetch', (event) => {
  if (event.request.url.includes('/wp-content/uploads/')) {
    event.respondWith(
      caches.open(CACHE_NAME_MEDIA).then((cache) => {
        return cache.match(event.request).then((cachedResponse) => {
          return cachedResponse || fetch(event.request).then((response) => {
            cache.put(event.request, response.clone());
            return response;
          });
        });
      })
    );
  }
});