// ==========================================
// CACHE CONFIGURATION
// ==========================================

// Name of the cache storage.
// Change the version number when updating cached files.
const CACHE_NAME = 'kaya-portal-v1';

// Files that will be downloaded and stored locally
// during Service Worker installation.
const PRECACHE = [
  '/',                          // Home page
  '/index.php',                 // Main application page
  '/public/assets/style.css',   // CSS stylesheet
  '/public/assets/app.js',      // JavaScript file
  '/public/manifest.json',      // PWA manifest
  '/storage/data.json'          // Local data file
];


// ==========================================
// INSTALL EVENT
// ==========================================

// Runs when the Service Worker is installed
self.addEventListener('install', (event) => {

  // Prevent installation from finishing until caching is complete
  event.waitUntil(

    // Open the cache storage
    caches.open(CACHE_NAME)

      // Add all files listed in PRECACHE
      .then((cache) => cache.addAll(PRECACHE))

      // Activate the Service Worker immediately
      .then(() => self.skipWaiting())
  );
});


// ==========================================
// ACTIVATE EVENT
// ==========================================

// Runs after installation when the Service Worker becomes active
self.addEventListener('activate', (event) => {

  event.waitUntil(

    // Take control of all currently open pages immediately
    self.clients.claim()
  );
});


// ==========================================
// FETCH EVENT
// ==========================================

// Runs whenever the browser requests a resource
// such as HTML, CSS, JavaScript, images, or JSON files.
self.addEventListener('fetch', (event) => {

  // Ignore non-GET requests (POST, PUT, DELETE, etc.)
  if (event.request.method !== 'GET') return;

  // Intercept the request
  event.respondWith(

    // Check if the requested file already exists in cache
    caches.match(event.request)

      .then((cached) => {

        // If found in cache, return it immediately
        if (cached) return cached;

        // Otherwise fetch it from the server
        return fetch(event.request)

          .then((response) => {

            try {

              // Only cache successful responses
              if (
                response &&
                response.status === 200 &&
                response.type === 'basic'
              ) {

                // Create a copy because responses can only be read once
                const responseClone = response.clone();

                // Open cache storage
                caches.open(CACHE_NAME)

                  .then((cache) => {

                    // Save the fetched file for future requests
                    cache.put(event.request, responseClone);
                  });
              }

            } catch (e) {

              // Ignore caching errors
              console.error('Cache error:', e);
            }

            // Return the original server response
            return response;
          });
      })

      .catch(() => {

        // If cache lookup fails, try loading directly from network
        return fetch(event.request);
      })
  );
});