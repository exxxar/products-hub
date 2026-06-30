<template>
    <!-- Триггер-бейдж -->
    <button type="button" class="online-badge" @click="showModal">
        <span class="pulse-dot"></span>
        <span class="count">{{ store.onlineCount }}</span>
        <span class="label">онлайн</span>
    </button>

    <!-- ✅ Teleport переносит модалку в body -->
    <Teleport to="body">
        <div class="modal fade" ref="modal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <!-- Header -->
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="pulse-dot"></span>
                            <span>Онлайн сейчас</span>
                            <span class="count-badge">{{ store.onlineCount }}</span>
                        </h5>
                        <button type="button" class="btn-close" @click="hide"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body">
                        <!-- Статистика -->
                        <div class="stats-row">
                            <div class="stat-item">
                                <strong>{{ store.onlineCount }}</strong>
                                <span>всего</span>
                            </div>
                            <div class="stat-item active">
                                <strong>{{ activeCount }}</strong>
                                <span>активных</span>
                            </div>
                            <div class="stat-item idle">
                                <strong>{{ idleCount }}</strong>
                                <span>неактивных</span>
                            </div>
                        </div>

                        <!-- Список пользователей -->
                        <div v-if="store.onlineUsers.length > 0" class="users-list">
                            <div
                                v-for="user in store.onlineUsers"
                                :key="user.key"
                                class="user-item"
                                :class="{ 'is-you': user.key === store.currentUserKey }"
                            >
                                <div class="user-avatar" :style="{ background: getAvatarColor(user.name) }">
                                    {{ getInitials(user.name) }}
                                </div>
                                <div class="user-info">
                                    <div class="user-name">
                                        {{ user.name }}
                                        <span v-if="user.key === store.currentUserKey" class="you-badge">
                                            вы
                                        </span>
                                    </div>
                                    <div class="user-status">
                                        <span
                                            v-if="user.idle_seconds < 30"
                                            class="status-dot active"
                                        ></span>
                                        <span
                                            v-else
                                            class="status-dot idle"
                                        ></span>
                                        <span class="status-text">{{ formatIdle(user.idle_seconds) }}</span>
                                    </div>
                                    <!-- ✅ IP адрес -->
                                    <div v-if="user.ip_address" class="user-ip">
                                        <i class="fa-solid fa-network-wired"></i>
                                        <span>{{ user.ip_address }} </span>
                                    </div>

                                    <div v-if="user.browser" class="user-browser">
                                        <i class="fa-brands fa-chrome"></i>
                                        <span>{{user.device_type}}, {{user.browser}}</span>
                                    </div>


                                </div>
                                <div v-if="user.page && user.page !== 'workspace'" class="user-page">
                                    <i :class="getPageIcon(user.page)"></i>
                                    <span>{{ formatPage(user.page) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Пустое состояние -->
                        <div v-else class="empty-state">
                            <i class="fa-solid fa-user-slash"></i>
                            <p>Пока никого нет</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer">
                        <div class="footer-hint">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Обновляется автоматически</span>
                        </div>
                        <button type="button" class="btn-close-modal" @click="hide">
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
import { Modal } from 'bootstrap'
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'OnlineBadge',

    data() {
        return {
            store: useWorkspaceStore(),
            modal: null,
        }
    },

    computed: {
        activeCount() {
            return this.store.onlineUsers.filter(u => u.idle_seconds < 30).length
        },

        idleCount() {
            return this.store.onlineUsers.filter(u => u.idle_seconds >= 30).length
        }
    },

    methods: {
        showModal() {
            if (this.modal) {
                this.modal.show()
            }
        },

        hide() {
            if (this.modal) {
                this.modal.hide()
            }
        },

        getInitials(name) {
            if (!name) return '?'
            return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
        },

        getAvatarColor(name) {
            if (!name) return '#6c757d'

            const colors = [
                'linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%)',
                'linear-gradient(135deg, #198754 0%, #0dcaf0 100%)',
                'linear-gradient(135deg, #fd7e14 0%, #dc3545 100%)',
                'linear-gradient(135deg, #6f42c1 0%, #d63384 100%)',
                'linear-gradient(135deg, #20c997 0%, #0d6efd 100%)',
                'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)',
            ]

            let hash = 0
            for (let i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash)
            }

            return colors[Math.abs(hash) % colors.length]
        },

        formatIdle(seconds) {
            if (seconds < 30) return 'активен'
            if (seconds < 60) return `${seconds} сек назад`
            const minutes = Math.floor(seconds / 60)
            if (minutes < 60) return `${minutes} мин назад`
            return `${Math.floor(minutes / 60)} ч назад`
        },

        formatPage(page) {
            const pages = {
                products: 'Товары',
                categories: 'Категории',
                collections: 'Коллекции',
                settings: 'Настройки',
            }
            return pages[page] || page
        },

        getPageIcon(page) {
            const icons = {
                products: 'fa-solid fa-box',
                categories: 'fa-solid fa-tags',
                collections: 'fa-solid fa-box-open',
                settings: 'fa-solid fa-gear',
            }
            return icons[page] || 'fa-solid fa-circle'
        }
    },

    mounted() {
        // ✅ Инициализация после Teleport
        this.$nextTick(() => {
            this.modal = new Modal(this.$refs.modal, {
                backdrop: true,
                keyboard: true,
            })
        })
    },

    beforeUnmount() {
        if (this.modal) {
            this.modal.dispose()
            this.modal = null
        }
    }
}
</script>

<style scoped>
/* === Бейдж-триггер === */
.online-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
}

.online-badge:hover {
    border-color: #198754;
    box-shadow: 0 2px 8px rgba(25, 135, 84, 0.15);
    transform: translateY(-1px);
}

.pulse-dot {
    width: 8px;
    height: 8px;
    background: #198754;
    border-radius: 50%;
    position: relative;
    flex-shrink: 0;
}

.pulse-dot::before {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    background: rgba(25, 135, 84, 0.3);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.5); opacity: 0; }
}

.count {
    font-size: 14px;
    font-weight: 700;
    color: #198754;
}

.label {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
}

/* === Модалка === */
.modal-content {
    border: none;
    border-radius: 14px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f3f5;
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin: 0;
}

.count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    border-radius: 12px;
    background: #198754;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
}

.btn-close {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    opacity: 0.5;
}

.btn-close:hover {
    opacity: 1;
    background: #f1f3f5;
}

/* === Body === */
.modal-body {
    padding: 16px 20px;
    max-height: 60vh;
    overflow-y: auto;
}

/* === Статистика === */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 16px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 10px 8px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-item strong {
    font-size: 18px;
    font-weight: 700;
    color: #212529;
    line-height: 1;
}

.stat-item span {
    font-size: 11px;
    color: #6c757d;
}

.stat-item.active strong {
    color: #198754;
}

.stat-item.idle strong {
    color: #6c757d;
}

/* === Список пользователей === */
.users-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.user-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    transition: background 0.15s ease;
}

.user-item:hover {
    background: #f8f9fa;
}

.user-item.is-you {
    background: #e7f1ff;
}

.user-item.is-you:hover {
    background: #d7e7ff;
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 600;
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-size: 13px;
    font-weight: 600;
    color: #212529;
    display: flex;
    align-items: center;
    gap: 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.you-badge {
    font-size: 10px;
    padding: 1px 6px;
    background: #0d6efd;
    color: #fff;
    border-radius: 8px;
    font-weight: 500;
    flex-shrink: 0;
}

.user-status {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 2px;
    font-size: 11px;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-dot.active {
    background: #198754;
    box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.2);
}

.status-dot.idle {
    background: #adb5bd;
}

.status-text {
    color: #6c757d;
}

.user-page {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    background: #f1f3f5;
    border-radius: 8px;
    font-size: 10px;
    color: #495057;
    font-weight: 500;
    flex-shrink: 0;
}

.user-page i {
    font-size: 9px;
    color: #0d6efd;
}

/* === Пустое состояние === */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px 20px;
    text-align: center;
    color: #adb5bd;
}

.empty-state i {
    font-size: 32px;
    margin-bottom: 8px;
    opacity: 0.5;
}

.empty-state p {
    margin: 0;
    font-size: 13px;
    color: #6c757d;
}

/* === Footer === */
.modal-footer {
    padding: 12px 20px;
    border-top: 1px solid #f1f3f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8f9fa;
}

.footer-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #6c757d;
}

.footer-hint i {
    color: #0d6efd;
    font-size: 11px;
}

.btn-close-modal {
    padding: 6px 16px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-close-modal:hover {
    background: #f1f3f5;
    color: #212529;
}

/* === Responsive === */
@media (max-width: 576px) {
    .user-page {
        display: none;
    }

    .stats-row {
        gap: 4px;
    }

    .stat-item {
        padding: 8px 4px;
    }

    .stat-item strong {
        font-size: 16px;
    }
}

/* === IP адрес === */
.user-ip {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 3px;
    font-size: 10px;
    color: #6c757d;
    font-family: 'Courier New', monospace;
}

.user-ip i {
    font-size: 9px;
    color: #adb5bd;
}

/* === IP адрес === */
.user-browser {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 3px;
    font-size: 10px;
    color: #6c757d;
    font-family: 'Courier New', monospace;
}

.user-browser i {
    font-size: 9px;
    color: #adb5bd;
}

</style>
