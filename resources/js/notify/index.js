// src/notify/index.js
import NotifyContainer from './NotifyContainer.vue'
import { NotifyService } from './NotifyService.js'

export default {
    install(app) {
        // Регистрируем глобальный компонент
        app.component('NotifyContainer', NotifyContainer)

        // Добавляем $notify в глобальный контекст
        app.config.globalProperties.$notify = NotifyService

        // Делаем доступным через provide для Composition API
        app.provide('notify', NotifyService)
    }
}
