/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios'
import { useWorkspaceStore } from '@/store/workspace.js'

// === Извлечение токена из URL при первом входе ===
function extractTokenFromUrl() {
    const urlParams = new URLSearchParams(window.location.search)
    const token = urlParams.get('token')

    if (token) {
        // Сохраняем в localStorage как резервную копию
        localStorage.setItem('workspace_token', token)

        // Очищаем URL от токена (чтобы не светился в истории)
        const cleanUrl = window.location.pathname + window.location.hash
        window.history.replaceState({}, document.title, cleanUrl)

        return token
    }

    return null
}

// === Получение токена: приоритет store > localStorage ===
function getWorkspaceToken() {
    // Пытаемся получить из store
    try {
        const store = useWorkspaceStore()
        if (store.accessToken) {
            return store.accessToken
        }
    } catch (e) {
        // Store может быть не инициализирован ещё
    }

    // Fallback на localStorage
    return localStorage.getItem('workspace_token')
}

// === Синхронизация токена между store и localStorage ===
function syncTokenToStore(token) {
    if (!token) return

    // Сохраняем в localStorage
    localStorage.setItem('workspace_token', token)

    // Пытаемся обновить store
    try {
        const store = useWorkspaceStore()
        store.accessToken = token
    } catch (e) {
        // Store может быть не инициализирован
    }
}

// === Инициализация при загрузке страницы ===
const initialToken = extractTokenFromUrl()
if (initialToken) {
    syncTokenToStore(initialToken)
}

// === Request interceptor ===
axios.interceptors.request.use(config => {
    const token = getWorkspaceToken()

    console.log("tokken", token)
    if (token) {
        config.headers['X-Workspace-Token'] = token
    }

    return config
})

// === Response interceptor ===
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 403) {
            // Токен невалиден - очищаем везде
            localStorage.removeItem('workspace_token')

            try {
                const store = useWorkspaceStore()
                store.accessToken = null
                store.accessUrl = ''
            } catch (e) {
                // ignore
            }

            // Показываем уведомление (можно заменить на toast)
            alert('Ссылка доступа недействительна или устарела')
        }
        return Promise.reject(error)
    }
)

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'


/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });

let deferredPrompt = null

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt = e

    // Показываем кнопку "Установить"
    const modal = new bootstrap.Modal(document.getElementById('installPwaModal'))
    if (modal)
        modal.show()
})

window.installPWA = ()=> {
    if (!deferredPrompt) return

    deferredPrompt.prompt()

    deferredPrompt.userChoice.then(() => {
        deferredPrompt = null

        // Закрываем модалку после установки
        const modal = bootstrap.Modal.getInstance(document.getElementById('installPwaModal'))
        if (modal)
            modal.hide()
    })
}
