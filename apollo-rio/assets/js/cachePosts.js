const CACHE_NAME_POSTS = 'apollo-rio-posts-cache';

self.addEventListener('fetch', (event) => {
  if (event.request.url.includes('/wp-json/wp/v2/posts')) {
    event.respondWith(
      caches.open(CACHE_NAME_POSTS).then((cache) => {
        return fetch(event.request).then((response) => {
          cache.put(event.request, response.clone());
          return response;
        }).catch(() => {
          return cache.match(event.request);
        });
      })
    );
  }
});