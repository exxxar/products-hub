const CACHE_VERSION = 'v1.0.2';
const STATIC_CACHE = `static-products-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `dynamic-products-${CACHE_VERSION}`;
const IMAGE_CACHE = `images-products-${CACHE_VERSION}`;

// ==========================================
// Что кэшируем сразу при установке (App Shell)
// ==========================================
const PRECACHE_URLS = [
    '/workspace/',                          // index.html (точка входа Vue)
    '/offline.html',                  // fallback при оффлайне
    // Пути к критичной статике добавятся автоматически через manifest
];

// ==========================================
// INSTALL — пре-кэшируем App Shell
// ==========================================
self.addEventListener('install', (event) => {
    console.log('[SW] Install');
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[SW] Precaching app shell');
                return cache.addAll(PRECACHE_URLS);
            })
            .then(() => self.skipWaiting()) // Активируем сразу, не ждём закрытия вкладок
    );
});

// ==========================================
// ACTIVATE — чистим старые кэши
// ==========================================
self.addEventListener('activate', (event) => {
    console.log('[SW] Activate');
    const currentCaches = [STATIC_CACHE, DYNAMIC_CACHE, IMAGE_CACHE];

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => !currentCaches.includes(name))
                        .map((name) => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => self.clients.claim()) // Берём контроль над всеми вкладками сразу
    );
});

// ==========================================
// FETCH — основная логика кэширования
// ==========================================
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Игнорируем не-GET запросы
    if (request.method !== 'GET') return;

    // Игнорируем chrome-extension, devtools и т.п.
    if (!url.protocol.startsWith('http')) return;

    // --------------------------------------
    // 1. Статика с хэшами в именах (JS/CSS из Vite build)
    //    Пример: /assets/app-a1b2c3d4.js
    //    Кэшируем навсегда — имя файла меняется при каждом билде
    // --------------------------------------
    if (url.pathname.startsWith('/assets/') ||
        url.pathname.match(/\.(js|css|woff2?|ttf|eot)(\?.*)?$/)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    // --------------------------------------
    // 2. Картинки — cache-first с лимитом
    // --------------------------------------
    if (request.destination === 'image' ||
        url.pathname.match(/\.(png|jpg|jpeg|gif|svg|webp|ico)(\?.*)?$/)) {
        event.respondWith(cacheFirstWithLimit(request, IMAGE_CACHE, 100));
        return;
    }

    // --------------------------------------
    // 3. API запросы — network-first с fallback на кэш
    //    (чтобы приложение работало оффлайн)
    // --------------------------------------
    if (url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/workspace/api/')) {
        event.respondWith(networkFirst(request, DYNAMIC_CACHE));
        return;
    }

    // --------------------------------------
    // 4. HTML страницы (включая /workspace/) — stale-while-revalidate
    //    Показываем кэш сразу, а в фоне обновляем
    // --------------------------------------
    if (request.destination === 'document' ||
        url.pathname.startsWith('/workspace')) {
        event.respondWith(staleWhileRevalidate(request, STATIC_CACHE));
        return;
    }

    // --------------------------------------
    // 5. Всё остальное — network-first
    // --------------------------------------
    event.respondWith(networkFirst(request, DYNAMIC_CACHE));
});

// ==========================================
// Стратегии кэширования
// ==========================================

// Cache-first: сначала кэш, если нет — сеть
async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        return offlineFallback(request);
    }
}

// Cache-first с лимитом (для картинок, чтобы не раздуть кэш)
async function cacheFirstWithLimit(request, cacheName, maxItems) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            await cache.put(request, response.clone());
            await trimCache(cacheName, maxItems);
        }
        return response;
    } catch (error) {
        return offlineFallback(request);
    }
}

// Network-first: сначала сеть, если нет — кэш
async function networkFirst(request, cacheName) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        const cached = await caches.match(request);
        return cached || offlineFallback(request);
    }
}

// Stale-while-revalidate: сразу отдаём кэш, в фоне обновляем
async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);

    const fetchPromise = fetch(request)
        .then((networkResponse) => {
            if (networkResponse.ok) {
                cache.put(request, networkResponse.clone());
            }
            return networkResponse;
        })
        .catch(() => cached);

    return cached || (await fetchPromise) || offlineFallback(request);
}

// Оффлайн-fallback
async function offlineFallback(request) {
    if (request.destination === 'document') {
        const offlinePage = await caches.match('/offline.html');
        if (offlinePage) return offlinePage;
    }
    // Для API возвращаем пустой JSON
    if (request.url.includes('/api/')) {
        return new Response(JSON.stringify({ offline: true }), {
            headers: { 'Content-Type': 'application/json' }
        });
    }
    return new Response('Offline', { status: 503, statusText: 'Offline' });
}

// Очистка старых записей из кэша (LRU — удаляем самое старое)
async function trimCache(cacheName, maxItems) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    if (keys.length > maxItems) {
        await cache.delete(keys[0]);
        await trimCache(cacheName, maxItems);
    }
}

// ==========================================
// PUSH-УВЕДОМЛЕНИЯ
// ==========================================
self.addEventListener('push', (event) => {
    console.log("[SW] push")
    let data = { title: 'Уведомление', body: 'Новое сообщение', icon: '/icons/icon-192x192.png' };

    if (event.data) {
        try {
            data = { ...data, ...event.data.json() };
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge || data.icon,
        vibrate: [100, 50, 100],
        data: { url: data.url || '/workspace/#/menu' },
        actions: data.actions || []
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );

    console.log('[SW] Отправляем в BroadcastChannel...');
    const channel = new BroadcastChannel('push-notifications');
    channel.postMessage({
        type: 'PUSH_RECEIVED',
        title: data.title ||'Заголовок',
        body: data.body || '',
        url: data.url || '/',
        notificationType: data.notificationType || 'success'
    });
    console.log('[SW] Сообщение отправлено');
    channel.close();
});

// Клик по уведомлению — открываем нужную страницу
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const urlToOpen = event.notification.data?.url || '/workspace/#/menu';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                // Если вкладка уже открыта — фокусируемся на ней
                for (const client of windowClients) {

                    if (client.url.includes('/workspace') && 'focus' in client) {
                        // Меняем хэш через postMessage (SW не может напрямую менять URL)
                        client.postMessage({ type: 'NAVIGATE', url: urlToOpen });
                        client.postMessage({ type: 'NOTIFICATION_CLICKED', url: urlToOpen });
                        return client.focus();
                    }
                }
                // Иначе открываем новую вкладку
                return clients.openWindow(urlToOpen);
            })
    );
});

// ==========================================
// Сообщения от клиента (Vue-приложения)
// ==========================================
self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (event.data === 'CLEAR_CACHE') {
        caches.keys().then((names) => {
            names.forEach((name) => caches.delete(name));
        });
    }
});
