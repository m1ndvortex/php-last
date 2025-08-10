// Service Worker for Jewelry Platform PWA
const CACHE_NAME = "jewelry-platform-v1";
const OFFLINE_URL = "/offline.html";

// Files to cache for offline functionality
const STATIC_CACHE_URLS = ["/", "/offline.html", "/manifest.json"];

// Install event - cache static resources
self.addEventListener("install", (event) => {
  console.log("Service Worker installing...");

  event.waitUntil(
    caches
      .open(CACHE_NAME)
      .then((cache) => {
        console.log("Caching static resources");
        return cache.addAll(STATIC_CACHE_URLS);
      })
      .then(() => {
        // Force the waiting service worker to become the active service worker
        return self.skipWaiting();
      }),
  );
});

// Activate event - clean up old caches
self.addEventListener("activate", (event) => {
  console.log("Service Worker activating...");

  event.waitUntil(
    caches
      .keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== CACHE_NAME) {
              console.log("Deleting old cache:", cacheName);
              return caches.delete(cacheName);
            }
          }),
        );
      })
      .then(() => {
        // Ensure the new service worker takes control immediately
        return self.clients.claim();
      }),
  );
});

// Fetch event - handle network requests
self.addEventListener("fetch", (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Handle API requests with network-first strategy
  if (url.pathname.startsWith("/api/")) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Clone the response before caching
          const responseClone = response.clone();

          // Cache successful API responses
          if (response.status === 200) {
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone);
            });
          }

          return response;
        })
        .catch(() => {
          // Network failed, try cache
          return caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }

            // Return offline response for critical API endpoints
            if (isCriticalApiEndpoint(url.pathname)) {
              return new Response(
                JSON.stringify({
                  error: "Offline",
                  message: "This data is not available offline",
                }),
                {
                  status: 503,
                  statusText: "Service Unavailable",
                  headers: { "Content-Type": "application/json" },
                },
              );
            }

            throw new Error("Network error and no cache available");
          });
        }),
    );
    return;
  }

  // Handle navigation requests
  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request).catch(() => {
        // Network failed, return offline page
        return caches.match(OFFLINE_URL);
      }),
    );
    return;
  }

  // Handle other requests with cache-first strategy
  event.respondWith(
    caches.match(request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }

      return fetch(request).then((response) => {
        // Don't cache non-successful responses
        if (!response || response.status !== 200 || response.type !== "basic") {
          return response;
        }

        // Clone the response
        const responseToCache = response.clone();

        caches.open(CACHE_NAME).then((cache) => {
          cache.put(request, responseToCache);
        });

        return response;
      });
    }),
  );
});

// Background sync event
self.addEventListener("sync", (event) => {
  console.log("Background sync triggered:", event.tag);

  if (event.tag === "background-sync") {
    event.waitUntil(syncOfflineData());
  }
});

// Push notification event
self.addEventListener("push", (event) => {
  console.log("Push notification received:", event);

  const options = {
    body: event.data ? event.data.text() : "New notification",
    icon: "/icons/icon-192x192.png",
    badge: "/icons/icon-72x72.png",
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1,
    },
    actions: [
      {
        action: "explore",
        title: "View",
        icon: "/icons/checkmark.png",
      },
      {
        action: "close",
        title: "Close",
        icon: "/icons/xmark.png",
      },
    ],
  };

  event.waitUntil(
    self.registration.showNotification("Jewelry Platform", options),
  );
});

// Notification click event
self.addEventListener("notificationclick", (event) => {
  console.log("Notification clicked:", event);

  event.notification.close();

  if (event.action === "explore") {
    // Open the app
    event.waitUntil(clients.openWindow("/"));
  }
});

// Helper function to check if API endpoint is critical
function isCriticalApiEndpoint(pathname) {
  const criticalEndpoints = [
    "/api/customers",
    "/api/invoices",
    "/api/inventory",
    "/api/accounting/transactions",
  ];

  return criticalEndpoints.some((endpoint) => pathname.startsWith(endpoint));
}

// Sync offline data function
async function syncOfflineData() {
  try {
    console.log("Syncing offline data...");

    // This would integrate with the offline service to sync pending data
    // For now, just log the attempt
    console.log("Offline data sync completed");

    // Notify clients about sync completion
    const clients = await self.clients.matchAll();
    clients.forEach((client) => {
      client.postMessage({
        type: "SYNC_COMPLETE",
        timestamp: Date.now(),
      });
    });
  } catch (error) {
    console.error("Offline data sync failed:", error);

    // Notify clients about sync failure
    const clients = await self.clients.matchAll();
    clients.forEach((client) => {
      client.postMessage({
        type: "SYNC_FAILED",
        error: error.message,
        timestamp: Date.now(),
      });
    });
  }
}
