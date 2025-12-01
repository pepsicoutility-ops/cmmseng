// Service Worker for CMMS PWA
const CACHE_NAME = 'cmms-pwa-v2';
const OFFLINE_URL = '/offline.html';

// Assets to cache immediately
const PRECACHE_ASSETS = [
    '/',
    '/offline.html',
    '/manifest.json',
    'https://cdn.tailwindcss.com',
    '/images/pepsico-pwa.png'
];

// Install event - cache essential assets
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Precaching assets');
      return cache.addAll(PRECACHE_ASSETS);
    })
  );
  
  // Activate immediately
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...');
  
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('[Service Worker] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  
  // Take control immediately
  self.clients.claim();
});

// Fetch event - Network first, fallback to cache
self.addEventListener('fetch', (event) => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }
  
  // Skip Chrome extension requests
  if (event.request.url.startsWith('chrome-extension://')) {
    return;
  }
  
  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Clone the response
        const responseClone = response.clone();
        
        // Cache successful responses
        if (response.status === 200) {
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseClone);
          });
        }
        
        return response;
      })
      .catch(() => {
        // Network failed, try cache
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          
          // If no cache, show offline page for navigation requests
          if (event.request.mode === 'navigate') {
            return caches.match(OFFLINE_URL);
          }
          
          // Return a basic offline response for other requests
          return new Response('Offline', {
            status: 503,
            statusText: 'Service Unavailable'
          });
        });
      })
  );
});

// Background sync for offline form submissions
self.addEventListener('sync', (event) => {
    console.log('[Service Worker] Sync event:', event.tag);
    
    if (event.tag === 'sync-work-orders') {
        event.waitUntil(syncWorkOrders());
    } else if (event.tag === 'sync-running-hours') {
        event.waitUntil(syncRunningHours());
    } else if (event.tag === 'sync-pm-checklist') {
        event.waitUntil(syncPMChecklist());
    } else if (event.tag === 'sync-parts-request') {
        event.waitUntil(syncPartsRequest());
    }
});

// Sync pending work orders when online
async function syncWorkOrders() {
  try {
    const db = await openIndexedDB();
    const pendingWOs = await getAllPendingWOs(db);
    
    if (pendingWOs.length === 0) {
      console.log('[Service Worker] No pending work orders to sync');
      return;
    }
    
    console.log(`[Service Worker] Syncing ${pendingWOs.length} work order(s)`);
    
    for (const wo of pendingWOs) {
      try {
        const formData = new FormData();
        
        // Add all form fields
        Object.keys(wo.data).forEach(key => {
          if (key === 'photos' && wo.data[key]) {
            wo.data[key].forEach(photo => {
              formData.append('photos[]', photo);
            });
          } else {
            formData.append(key, wo.data[key]);
          }
        });
        
        // Submit to server
        const response = await fetch('/barcode/wo/submit', {
          method: 'POST',
          body: formData
        });
        
        if (response.ok) {
          console.log('[Service Worker] Work order synced:', wo.id);
          await deletePendingWO(db, wo.id);
          
          // Show notification
          self.registration.showNotification('Work Order Submitted', {
            body: `WO #${wo.id} has been successfully submitted`,
            icon: '/images/pwa-icon-192.png',
            badge: '/images/badge-icon.png',
            tag: 'wo-sync'
          });
        }
      } catch (error) {
        console.error('[Service Worker] Failed to sync work order:', error);
      }
    }
  } catch (error) {
    console.error('[Service Worker] Sync failed:', error);
  }
}

// IndexedDB helpers
function openIndexedDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('cmms-offline', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('workOrders')) {
        db.createObjectStore('workOrders', { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

function getAllPendingWOs(db) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['workOrders'], 'readonly');
    const store = transaction.objectStore('workOrders');
    const request = store.getAll();
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
  });
}

function deletePendingWO(db, id) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['workOrders'], 'readwrite');
    const store = transaction.objectStore('workOrders');
    const request = store.delete(id);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve();
  });
}

// Sync functions for other forms
async function syncRunningHours() {
    const db = await openIndexedDB();
    const tx = db.transaction('runningHours', 'readonly');
    const store = tx.objectStore('runningHours');
    const records = await store.getAll();
    
    for (const record of records) {
        const formData = new FormData();
        Object.keys(record).forEach(key => formData.append(key, record[key]));
        
        const response = await fetch('/barcode/running-hours/submit', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const deleteTx = db.transaction('runningHours', 'readwrite');
            await deleteTx.objectStore('runningHours').delete(record.id);
        }
    }
}

async function syncPMChecklist() {
    const db = await openIndexedDB();
    const tx = db.transaction('pmChecklists', 'readonly');
    const store = tx.objectStore('pmChecklists');
    const records = await store.getAll();
    
    for (const record of records) {
        const response = await fetch('/barcode/pm-checklist/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(record)
        });
        
        if (response.ok) {
            const deleteTx = db.transaction('pmChecklists', 'readwrite');
            await deleteTx.objectStore('pmChecklists').delete(record.id);
        }
    }
}

async function syncPartsRequest() {
    const db = await openIndexedDB();
    const tx = db.transaction('partsRequests', 'readonly');
    const store = tx.objectStore('partsRequests');
    const records = await store.getAll();
    
    for (const record of records) {
        const formData = new FormData();
        Object.keys(record).forEach(key => formData.append(key, record[key]));
        
        const response = await fetch('/barcode/request-parts/submit', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const deleteTx = db.transaction('partsRequests', 'readwrite');
            await deleteTx.objectStore('partsRequests').delete(record.id);
        }
    }
}

// Push notification event
self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : {};
  
  const options = {
    body: data.body || 'New notification from CMMS',
    icon: '/images/pwa-icon-192.png',
    badge: '/images/badge-icon.png',
    vibrate: [200, 100, 200],
    data: {
      url: data.url || '/'
    }
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title || 'CMMS Notification', options)
  );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  
  event.waitUntil(
    clients.openWindow(event.notification.data.url || '/')
  );
});
