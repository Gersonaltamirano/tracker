// Service Worker para GPS Tracker PWA
const APP_VERSION = '1.0.0';
const CACHE_VERSION = `gps-tracker-v${APP_VERSION}-${Date.now()}`;
const CACHE_NAME = `gps-tracker-app-${CACHE_VERSION}`;
const API_CACHE = `gps-tracker-api-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `gps-tracker-dynamic-${CACHE_VERSION}`;

// Configuración de cache
const CACHE_STRATEGIES = {
  // Archivos que siempre deben ser los más recientes
  NETWORK_FIRST: [
    '/pwa/js/app.js',
    '/pwa/css/styles.css',
    '/pwa/manifest.json'
  ],
  // Archivos estáticos que pueden ser cacheados por más tiempo
  CACHE_FIRST: [
    '/pwa/',
    '/pwa/index.html',
    '/pwa/offline.html',
    '/pwa/icons/',
    'https://unpkg.com/dexie@3.2.4/dist/dexie.js'
  ],
  // API endpoints
  API_CACHE: [
    '/api/'
  ]
};

// URLs para cachear inicialmente
const CORE_URLS = [
  '/pwa/',
  '/pwa/index.html',
  '/pwa/offline.html',
  '/pwa/manifest.json',
  'https://unpkg.com/dexie@3.2.4/dist/dexie.js'
];

// Instalar service worker
self.addEventListener('install', (event) => {
  console.log(`Service Worker v${APP_VERSION}: Installing...`);
  console.log('Cache version:', CACHE_NAME);

  event.waitUntil(
    Promise.all([
      // Cache de archivos principales
      caches.open(CACHE_NAME).then((cache) => {
        console.log('Service Worker: Caching core files');
        return cache.addAll(CORE_URLS);
      }),
      // Cache de íconos
      caches.open(CACHE_NAME).then((cache) => {
        const iconUrls = [
          '/pwa/icons/icon-72x72.png',
          '/pwa/icons/icon-96x96.png',
          '/pwa/icons/icon-128x128.png',
          '/pwa/icons/icon-144x144.png',
          '/pwa/icons/icon-152x152.png',
          '/pwa/icons/icon-192x192.png',
          '/pwa/icons/icon-384x384.png',
          '/pwa/icons/icon-512x512.png'
        ];
        console.log('Service Worker: Caching icons');
        return cache.addAll(iconUrls);
      })
    ]).then(() => {
      console.log('Service Worker: Installation complete, skipping waiting');
      return self.skipWaiting();
    }).catch((error) => {
      console.error('Service Worker: Installation failed', error);
    })
  );
});

// Activar service worker
self.addEventListener('activate', (event) => {
  console.log(`Service Worker v${APP_VERSION}: Activating...`);

  event.waitUntil(
    Promise.all([
      // Limpiar caches antiguos
      caches.keys().then((cacheNames) => {
        console.log('Service Worker: Cleaning old caches');
        return Promise.all(
          cacheNames.map((cacheName) => {
            // Mantener solo los caches de la versión actual
            if (!cacheName.includes(APP_VERSION)) {
              console.log('Service Worker: Deleting old cache', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      // Tomar control de todos los clientes
      self.clients.claim()
    ]).then(() => {
      console.log('Service Worker: Activation complete');

      // Notificar a los clientes que hay una nueva versión
      return self.clients.matchAll().then((clients) => {
        clients.forEach((client) => {
          client.postMessage({
            type: 'SW_UPDATE_AVAILABLE',
            version: APP_VERSION,
            cacheVersion: CACHE_VERSION
          });
        });
      });
    })
  );
});

// Fetch event - Estrategias inteligentes de cache
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorar peticiones de Chrome extensions
  if (url.protocol === 'chrome-extension:') return;

  // Estrategia basada en el tipo de recurso
  if (isNetworkFirstResource(request)) {
    event.respondWith(networkFirstStrategy(request));
  } else if (isCacheFirstResource(request)) {
    event.respondWith(cacheFirstStrategy(request));
  } else if (isStaleWhileRevalidateResource(request)) {
    event.respondWith(staleWhileRevalidateStrategy(request));
  } else if (request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(htmlStrategy(request));
  } else {
    // Default: Network first
    event.respondWith(networkFirstStrategy(request));
  }
});

// Determinar qué estrategia usar para cada recurso
function isNetworkFirstResource(request) {
  const url = new URL(request.url);
  return CACHE_STRATEGIES.NETWORK_FIRST.some(pattern =>
    url.pathname.includes(pattern)
  ) || url.pathname.startsWith('/api/');
}

function isCacheFirstResource(request) {
  const url = new URL(request.url);
  return CACHE_STRATEGIES.CACHE_FIRST.some(pattern =>
    url.pathname.includes(pattern)
  ) || isStaticAsset(request);
}

function isStaleWhileRevalidateResource(request) {
  // Para recursos que pueden ser servidos del cache pero se actualizan en segundo plano
  return false; // Por ahora no usamos esta estrategia
}

// Estrategia Network First (para archivos críticos que siempre deben estar actualizados)
async function networkFirstStrategy(request) {
  try {
    console.log('Network First:', request.url);
    const networkResponse = await fetch(request);

    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
    }

    return networkResponse;
  } catch (error) {
    console.log('Network failed, trying cache for:', request.url);
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

// Estrategia Cache First (para recursos estáticos)
async function cacheFirstStrategy(request) {
  try {
    console.log('Cache First:', request.url);
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

// Estrategia para páginas HTML
async function htmlStrategy(request) {
  try {
    console.log('HTML Strategy:', request.url);
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
  return url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|webmanifest)$/);
}

// Verificación periódica de actualizaciones
setInterval(() => {
  console.log('Service Worker: Periodic update check');
  checkForUpdate();
}, 5 * 60 * 1000); // Cada 5 minutos

// Verificar actualizaciones cuando el service worker se active
self.addEventListener('activate', (event) => {
  event.waitUntil(
    Promise.all([
      // ... existing activation logic ...
      checkForUpdate()
    ])
  );
});

// Manejar mensajes del cliente
self.addEventListener('message', (event) => {
  console.log('Service Worker: Message received', event.data);

  if (event.data && event.data.type === 'SKIP_WAITING') {
    console.log('Service Worker: Skip waiting requested');
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'GET_VERSION') {
    console.log('Service Worker: Version requested');
    event.ports[0].postMessage({
      version: APP_VERSION,
      cacheVersion: CACHE_VERSION,
      timestamp: Date.now()
    });
  }

  if (event.data && event.data.type === 'CHECK_FOR_UPDATE') {
    console.log('Service Worker: Manual update check requested');
    checkForUpdate();
  }
});

// Función para verificar actualizaciones
async function checkForUpdate() {
  try {
    console.log('Service Worker: Checking for updates...');

    // Verificar si hay una nueva versión del manifest
    const manifestResponse = await fetch('/pwa/manifest.json', {
      cache: 'no-cache'
    });

    if (manifestResponse.ok) {
      const manifest = await manifestResponse.json();
      const newVersion = manifest.version || APP_VERSION;

      if (newVersion !== APP_VERSION) {
        console.log('Service Worker: New version detected:', newVersion);

        // Notificar a los clientes sobre la nueva versión
        const clients = await self.clients.matchAll();
        clients.forEach((client) => {
          client.postMessage({
            type: 'NEW_VERSION_AVAILABLE',
            currentVersion: APP_VERSION,
            newVersion: newVersion
          });
        });
      } else {
        console.log('Service Worker: App is up to date');
      }
    }
  } catch (error) {
    console.error('Service Worker: Update check failed', error);
  }
}

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