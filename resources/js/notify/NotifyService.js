// src/notify/NotifyService.js
import { reactive } from 'vue'

const state = reactive({
    notifications: [],
    position: 'top-right', // top-right, top-left, bottom-right, bottom-left
    maxNotifications: 5
})

let nextId = 1

const icons = {
    success: 'fa-solid fa-circle-check',
    error: 'fa-solid fa-circle-xmark',
    warning: 'fa-solid fa-triangle-exclamation',
    info: 'fa-solid fa-circle-info'
}

const defaultDurations = {
    success: 3000,
    error: 5000,
    warning: 4000,
    info: 3000
}

function createNotification(options) {
    // Нормализация аргументов
    let config = {}

    if (typeof options === 'string') {
        config = { message: options }
    } else {
        config = { ...options }
    }

    // Убираем лишние уведомления если превышен лимит
    while (state.notifications.length >= state.maxNotifications) {
        removeNotification(state.notifications[0].id)
    }

    const notification = {
        id: nextId++,
        type: config.type || 'info',
        title: config.title || null,
        message: config.message || '',
        icon: config.icon || icons[config.type || 'info'],
        duration: config.duration !== undefined ? config.duration : defaultDurations[config.type || 'info'],
        closable: config.closable !== false,
        createdAt: Date.now()
    }

    state.notifications.push(notification)

    // Автоматическое удаление
    if (notification.duration > 0) {
        setTimeout(() => {
            removeNotification(notification.id)
        }, notification.duration)
    }

    return notification.id
}

function removeNotification(id) {
    const index = state.notifications.findIndex(n => n.id === id)
    if (index > -1) {
        // Помечаем как удаляемое для анимации
        state.notifications[index].removing = true

        setTimeout(() => {
            const idx = state.notifications.findIndex(n => n.id === id)
            if (idx > -1) {
                state.notifications.splice(idx, 1)
            }
        }, 300) // Длительность анимации удаления
    }
}

function clearAll() {
    state.notifications.forEach(n => {
        n.removing = true
    })
    setTimeout(() => {
        state.notifications.splice(0, state.notifications.length)
    }, 300)
}

function setPosition(position) {
    const validPositions = ['top-right', 'top-left', 'bottom-right', 'bottom-left']
    if (validPositions.includes(position)) {
        state.position = position
    }
}

export const NotifyService = {
    state,

    success(options) {
        return createNotification({ ...normalizeOptions(options), type: 'success' })
    },

    error(options) {
        return createNotification({ ...normalizeOptions(options), type: 'error' })
    },

    warning(options) {
        return createNotification({ ...normalizeOptions(options), type: 'warning' })
    },

    info(options) {
        return createNotification({ ...normalizeOptions(options), type: 'info' })
    },

    remove: removeNotification,
    clear: clearAll,
    setPosition
}

function normalizeOptions(options) {
    if (typeof options === 'string') {
        return { message: options }
    }
    return options
}
