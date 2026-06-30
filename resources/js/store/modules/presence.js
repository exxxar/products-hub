// store/modules/presence.js
import axios from 'axios'

export default {
    state: () => ({
        onlineUsers: [],
        onlineCount: 0,
        heartbeatInterval: null,
        usersInterval: null,
        currentUserKey: null, // ✅ Добавляем
        isTracking: false,
    }),

    actions: {
        // ✅ Стабильный browser_id — один на все вкладки браузера
        getBrowserId() {
            const STORAGE_KEY = 'workspace_browser_id'

            let browserId = localStorage.getItem(STORAGE_KEY)

            if (!browserId) {
                // Генерируем один раз и сохраняем навсегда
                browserId = 'browser_' + this.generateFingerprint()
                localStorage.setItem(STORAGE_KEY, browserId)
            }

            return browserId
        },

        // ✅ Fingerprint на основе характеристик браузера
        generateFingerprint() {
            const components = [
                navigator.userAgent,
                navigator.language,
                screen.width + 'x' + screen.height,
                screen.colorDepth,
                new Date().getTimezoneOffset(),
                navigator.hardwareConcurrency || 'unknown',
                navigator.platform || 'unknown',
            ]

            const str = components.join('|')

            // Простой хэш
            let hash = 0
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i)
                hash = ((hash << 5) - hash) + char
                hash = hash & hash // Convert to 32bit integer
            }

            return Math.abs(hash).toString(36) + '_' + Date.now().toString(36)
        },

        startPresenceTracking() {
            if (this.isTracking) return
            this.isTracking = true

            // ✅ Сохраняем текущий user_key
            this.currentUserKey = this.getBrowserId()

            // Сразу шлём heartbeat
            this.sendHeartbeat()
            this.loadOnlineUsers()

            // Heartbeat каждые 15 сек
            this.heartbeatInterval = setInterval(() => {
                this.sendHeartbeat()
            }, 15000)

            // Список пользователей каждые 10 сек
            this.usersInterval = setInterval(() => {
                this.loadOnlineUsers()
            }, 10000)

            // При закрытии вкладки — уходим
            window.addEventListener('beforeunload', this.handleBeforeUnload)
        },

        stopPresenceTracking() {
            if (this.heartbeatInterval) clearInterval(this.heartbeatInterval)
            if (this.usersInterval) clearInterval(this.usersInterval)

            this.isTracking = false
            this.leave()

            window.removeEventListener('beforeunload', this.handleBeforeUnload)
        },

        async sendHeartbeat() {
            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/presence/heartbeat`
                )
                this.onlineCount = response.data.online_count
            } catch (error) {
                // ignore
            }
        },

        async loadOnlineUsers() {
            try {
                const response = await axios.get(
                    `/api/workspaces/${this.uuid}/presence/users`
                )
                this.onlineUsers = response.data.users
                this.onlineCount = response.data.count
            } catch (error) {
                // ignore
            }
        },

        async leave() {
            try {
                await axios.post(`/api/workspaces/${this.uuid}/presence/leave`)
            } catch (error) {
                // ignore
            }
        },

        handleBeforeUnload() {
            // sendBeacon — надёжнее чем fetch при закрытии вкладки
            navigator.sendBeacon(`/api/workspaces/${this.uuid}/presence/leave`)
        },
    },
}
