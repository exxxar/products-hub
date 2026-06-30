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
        generateUserKey() {
            // Генерируем стабильный ключ для сессии
            let key = sessionStorage.getItem('presence_user_key')
            if (!key) {
                key = 'session_' + Math.random().toString(36).substring(2, 15)
                sessionStorage.setItem('presence_user_key', key)
            }
            return key
        },
        startPresenceTracking() {
            if (this.isTracking) return
            this.isTracking = true

            // ✅ Сохраняем текущий user_key
            this.currentUserKey = this.generateUserKey()

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
