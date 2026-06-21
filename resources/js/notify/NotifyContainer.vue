<!-- src/notify/NotifyContainer.vue -->
<template>
    <Teleport to="body">
        <div class="notify-container" :class="`position-${NotifyService.state.position}`">
            <TransitionGroup name="notify" tag="div" class="notify-list">
                <div
                    v-for="notification in NotifyService.state.notifications"
                    :key="notification.id"
                    class="notify-item"
                    :class="[
                        `notify-${notification.type}`,
                        { 'notify-removing': notification.removing }
                    ]"
                >
                    <div class="notify-icon">
                        <i :class="notification.icon"></i>
                    </div>

                    <div class="notify-content">
                        <div v-if="notification.title" class="notify-title">
                            {{ notification.title }}
                        </div>
                        <div class="notify-message">
                            {{ notification.message }}
                        </div>
                    </div>

                    <button
                        v-if="notification.closable"
                        type="button"
                        class="notify-close"
                        @click="NotifyService.remove(notification.id)"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                    <!-- Прогресс-бар -->
                    <div
                        v-if="notification.duration > 0"
                        class="notify-progress"
                        :style="{ animationDuration: notification.duration + 'ms' }"
                    ></div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script>
import { NotifyService } from '@/notify/NotifyService.js'

export default {
    name: 'NotifyContainer',

    setup() {
        return { NotifyService }
    }
}
</script>

<style scoped>
.notify-container {
    position: fixed;
    z-index: 9999;
    pointer-events: none;
    max-width: 420px;
    width: calc(100% - 32px);
}

/* === Позиции === */
.notify-container.position-top-right {
    top: 20px;
    right: 20px;
}

.notify-container.position-top-left {
    top: 20px;
    left: 20px;
}

.notify-container.position-bottom-right {
    bottom: 20px;
    right: 20px;
}

.notify-container.position-bottom-left {
    bottom: 20px;
    left: 20px;
}

.notify-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: auto;
}

.position-top-right .notify-list,
.position-bottom-right .notify-list {
    align-items: flex-end;
}

.position-top-left .notify-list,
.position-bottom-left .notify-list {
    align-items: flex-start;
}

/* === Уведомление === */
.notify-item {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 2px 6px rgba(0, 0, 0, 0.06);
    border-left: 4px solid;
    min-width: 300px;
    max-width: 420px;
    overflow: hidden;
}

/* === Типы === */
.notify-success {
    border-left-color: #198754;
}

.notify-success .notify-icon {
    color: #198754;
    background: #d1e7dd;
}

.notify-error {
    border-left-color: #dc3545;
}

.notify-error .notify-icon {
    color: #dc3545;
    background: #f8d7da;
}

.notify-warning {
    border-left-color: #ffc107;
}

.notify-warning .notify-icon {
    color: #856404;
    background: #fff3cd;
}

.notify-info {
    border-left-color: #0d6efd;
}

.notify-info .notify-icon {
    color: #0d6efd;
    background: #cfe2ff;
}

/* === Иконка === */
.notify-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
}

/* === Контент === */
.notify-content {
    flex: 1;
    min-width: 0;
    padding-right: 8px;
}

.notify-title {
    font-size: 14px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 2px;
    line-height: 1.3;
}

.notify-message {
    font-size: 13px;
    color: #495057;
    line-height: 1.4;
    word-wrap: break-word;
}

/* === Кнопка закрытия === */
.notify-close {
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #adb5bd;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s ease;
    padding: 0;
}

.notify-close:hover {
    background: #f1f3f5;
    color: #495057;
}

/* === Прогресс-бар === */
.notify-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: currentColor;
    opacity: 0.3;
    transform-origin: left;
    animation: progress-shrink linear forwards;
}

.notify-success .notify-progress { color: #198754; }
.notify-error .notify-progress { color: #dc3545; }
.notify-warning .notify-progress { color: #ffc107; }
.notify-info .notify-progress { color: #0d6efd; }

@keyframes progress-shrink {
    from {
        transform: scaleX(1);
    }
    to {
        transform: scaleX(0);
    }
}

/* === Анимации === */
.notify-enter-active {
    animation: notify-slide-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.notify-leave-active {
    animation: notify-slide-out 0.3s ease-in forwards;
}

.notify-move {
    transition: transform 0.3s ease;
}

@keyframes notify-slide-in {
    from {
        opacity: 0;
        transform: translateX(100%) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
}

@keyframes notify-slide-out {
    from {
        opacity: 1;
        transform: translateX(0) scale(1);
        max-height: 200px;
        margin-bottom: 0;
    }
    to {
        opacity: 0;
        transform: translateX(100%) scale(0.8);
        max-height: 0;
        margin-bottom: -10px;
    }
}

/* === Для левых позиций === */
.position-top-left .notify-enter-active,
.position-bottom-left .notify-enter-active {
    animation: notify-slide-in-left 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.position-top-left .notify-leave-active,
.position-bottom-left .notify-leave-active {
    animation: notify-slide-out-left 0.3s ease-in forwards;
}

@keyframes notify-slide-in-left {
    from {
        opacity: 0;
        transform: translateX(-100%) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
}

@keyframes notify-slide-out-left {
    from {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
    to {
        opacity: 0;
        transform: translateX(-100%) scale(0.8);
    }
}

/* === Адаптив === */
@media (max-width: 480px) {
    .notify-container {
        left: 12px !important;
        right: 12px !important;
        max-width: none;
        width: auto;
    }

    .notify-item {
        min-width: 0;
        max-width: none;
    }
}
</style>
