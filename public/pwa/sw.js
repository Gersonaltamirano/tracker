// Service Worker para GPS Tracker PWA
const CACHE_NAME = 'gps-tracker-v1';
const API_CACHE = 'gps-tracker-api-v1';

// URLs para cachear
const urlsToCache = [
  '/pwa/',
  '/pwa/index.html',
  '/pwa/manifest.json',
  '/pwa/css/styles.css',
  '/pwa/js/app.js',
  '/pwa/js/dexie.js',
  '/pwa/icons/icon-192x192.png',
  '/pwa/icons/icon-512x512.png'
];

// Instalar service worker
self.addEventListener('install', (event) => {
  console.log('Service Worker: Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Service Worker: Caching files');
        return cache.addAll(urlsToCache);
      })
      .then(() => {
        console.log('Service Worker: Skip waiting');
        return self.skipWaiting();
      })
  );
});

// Activar service worker
self.addEventListener('activate', (event) => {
  console.log('Service Worker: Activating...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME && cacheName !== API_CACHE) {
            console.log('Service Worker: Deleting old cache', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('Service Worker: Claiming clients');
      return self.clients.claim();
    })
  );
});

// Fetch event - Estrategia de cache first para recursos estáticos
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Si es una petición a la API, usar network first con fallback a cache
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(networkFirstStrategy(request));
  }
  // Para recursos estáticos, usar cache first
  else if (isStaticAsset(request)) {
    event.respondWith(cacheFirstStrategy(request));
  }
  // Para páginas HTML, usar network first con fallback a cache
  else if (request.headers.get('accept').includes('text/html')) {
    event.respondWith(networkFirstWithFallbackStrategy(request));
  }
});

// Estrategia Cache First para recursos estáticos
async function cacheFirstStrategy(request) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }

    return networkResponse;
  } catch (error) {
    console.error('Cache First Strategy failed:', error);
    return new Response('Offline', { status: 503 });
  }
}

// Estrategia Network First para API
async function networkFirstStrategy(request) {
  try {
    const networkResponse = await fetch(request);

    if (networkResponse.ok) {
      const cache = await caches.open(API_CACHE);
      cache.put(request, networkResponse.clone());
    }

    return networkResponse;
  } catch (error) {
    console.log('Network failed, trying cache for API:', request.url);
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
      return cachedResponse;
    }

    // Si no hay cache y es una petición POST, intentar guardar para más tarde
    if (request.method === 'POST') {
      return handleOfflinePost(request);
    }

    return new Response('Offline', { status: 503 });
  }
}

// Estrategia Network First con fallback para páginas HTML
async function networkFirstWithFallbackStrategy(request) {
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.log('Network failed for HTML, trying cache');
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    // Fallback a página offline
    return caches.match('/pwa/offline.html');
  }
}

// Manejar POST requests cuando está offline
async function handleOfflinePost(request) {
  try {
    const requestData = await request.json();

    // Guardar en IndexedDB para sincronizar después
    const clients = await self.clients.matchAll();
    if (clients.length > 0) {
      clients[0].postMessage({
        type: 'OFFLINE_ACTION',
        action: 'SAVE_FOR_LATER',
        data: requestData,
        url: request.url
      });
    }

    return new Response(JSON.stringify({
      success: false,
      offline: true,
      message: 'Datos guardados para sincronizar cuando vuelva la conexión'
    }), {
      status: 202,
      headers: { 'Content-Type': 'application/json' }
    });
  } catch (error) {
    return new Response(JSON.stringify({
      success: false,
      error: 'Error procesando petición offline'
    }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

// Verificar si es un recurso estático
function isStaticAsset(request) {
  const url = new URL(request.url);
  return url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/);
}

// Manejar mensajes del cliente
self.addEventListener('message', (event) => {
  console.log('Service Worker: Message received', event.data);

  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: CACHE_NAME });
  }
});

// Sincronización en segundo plano
self.addEventListener('sync', (event) => {
  console.log('Service Worker: Background sync', event.tag);

  if (event.tag === 'location-sync') {
    event.waitUntil(syncLocationData());
  }

  if (event.tag === 'event-sync') {
    event.waitUntil(syncEventData());
  }
});

// Función para sincronizar datos de ubicación
async function syncLocationData() {
  try {
    const clients = await self.clients.matchAll();
    if (clients.length > 0) {
      clients[0].postMessage({
        type: 'SYNC_START',
        dataType: 'location'
      });
    }

    // Aquí iría la lógica para sincronizar datos pendientes
    console.log('Sincronizando datos de ubicación...');

  } catch (error) {
    console.error('Error syncing location data:', error);
  }
}

// Función para sincronizar eventos
async function syncEventData() {
  try {
    console.log('Sincronizando eventos...');

    const clients = await self.clients.matchAll();
    if (clients.length > 0) {
      clients[0].postMessage({
        type: 'SYNC_START',
        dataType: 'events'
      });
    }

  } catch (error) {
    console.error('Error syncing event data:', error);
  }
}

// Notificaciones push (para futuras funcionalidades)
self.addEventListener('push', (event) => {
  console.log('Service Worker: Push received');

  if (event.data) {
    const data = event.data.json();

    const options = {
      body: data.body,
      icon: '/pwa/icons/icon-192x192.png',
      badge: '/pwa/icons/icon-72x72.png',
      vibrate: [100, 50, 100],
      data: {
        dateOfArrival: Date.now(),
        primaryKey: data.primaryKey
      },
      actions: [
        {
          action: 'explore',
          title: 'Ver detalles',
          icon: '/pwa/icons/icon-96x96.png'
        },
        {
          action: 'close',
          title: 'Cerrar',
          icon: '/pwa/icons/icon-96x96.png'
        }
      ]
    };

    event.waitUntil(
      self.registration.showNotification(data.title, options)
    );
  }
});

// Manejar clicks en notificaciones
self.addEventListener('notificationclick', (event) => {
  console.log('Service Worker: Notification clicked');

  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/pwa/dashboard')
    );
  }
});