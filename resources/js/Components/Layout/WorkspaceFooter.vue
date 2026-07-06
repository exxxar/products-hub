<template>
    <footer class="workspace-footer">
        <div class="footer-left">
            <!-- Документация -->
            <a href="/docs" target="_blank" class="footer-link footer-docs">
                <i class="fa-solid fa-book"></i>
                <span class="link-label">Документация</span>
            </a>

            <span class="footer-divider"></span>

            <!-- Статистика -->
            <div class="footer-stats">
                <div class="footer-stat" title="Товаров в workspace">
                    <i class="fa-solid fa-box"></i>
                    <span class="stat-count">{{ store.products?.length || 0 }}</span>
                    <span class="stat-label">{{ pluralize(store.products?.length || 0, 'товар', 'товара', 'товаров') }}</span>
                </div>

                <div class="footer-stat" title="Категорий в workspace">
                    <i class="fa-solid fa-tags"></i>
                    <span class="stat-count">{{ store.categories?.length || 0 }}</span>
                    <span class="stat-label">{{ pluralize(store.categories?.length || 0, 'категория', 'категории', 'категорий') }}</span>
                </div>

                <div class="footer-stat" title="Коллекций в workspace">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span class="stat-count">{{ store.collections?.length || 0 }}</span>
                    <span class="stat-label">{{ pluralize(store.collections?.length || 0, 'коллекция', 'коллекции', 'коллекций') }}</span>
                </div>
            </div>
        </div>

        <div class="footer-center">
            <!-- Статус присутствия -->
            <div v-if="store.onlineCount > 0" class="footer-presence">
                <span class="presence-dot"></span>
                <span class="presence-text">{{ store.onlineCount }} онлайн</span>
            </div>
        </div>

        <div class="footer-right">
            <!-- Версия -->
            <span class="footer-version">v{{ version }}</span>

            <span class="footer-divider"></span>

            <!-- UUID -->
            <button
                type="button"
                class="footer-link footer-uuid"
                @click="copyUuid"
                :title="`UUID: ${store.uuid}`"
            >
                <i class="fa-solid fa-fingerprint"></i>
                <span class="uuid-text">{{ store.uuid?.substring(0, 8) }}...</span>
            </button>
        </div>
    </footer>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'WorkspaceFooter',

    data() {
        return {
            store: useWorkspaceStore(),
            version: '1.0.0',
        }
    },

    methods: {
        async copyUuid() {
            try {
                await navigator.clipboard.writeText(this.store.uuid)
                this.$notify?.success({
                    title: 'UUID скопирован',
                    message: this.store.uuid
                })
            } catch (error) {
                console.error('Copy failed:', error)
            }
        },

        pluralize(count, one, two, five) {
            let n = Math.abs(count) % 100
            if (n >= 5 && n <= 20) return five
            n %= 10
            if (n === 1) return one
            if (n >= 2 && n <= 4) return two
            return five
        }
    }
}
</script>

<style scoped>
.workspace-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 36px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    font-size: 12px;
    color: #6c757d;
    z-index: 100;
    user-select: none;
    gap: 12px;
}

.footer-left,
.footer-center,
.footer-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.footer-left {
    flex: 1;
    min-width: 0;
}

.footer-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #6c757d;
    text-decoration: none;
    transition: color 0.15s ease;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    font-size: inherit;
    font-family: inherit;
    white-space: nowrap;
}

.footer-link:hover {
    color: #0d6efd;
}

.footer-link i {
    font-size: 11px;
}

.footer-divider {
    width: 1px;
    height: 16px;
    background: #dee2e6;
    flex-shrink: 0;
}

/* === Статистика === */
.footer-stats {
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.footer-stats::-webkit-scrollbar {
    display: none;
}

.footer-stat {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    transition: all 0.15s ease;
    cursor: default;
    white-space: nowrap;
    flex-shrink: 0;
}

.footer-stat:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #f8f9ff;
}

.footer-stat i {
    font-size: 11px;
    color: #0d6efd;
}

.stat-count {
    font-weight: 700;
    color: #212529;
    font-size: 12px;
    min-width: 14px;
    text-align: center;
}

.footer-stat:hover .stat-count {
    color: #0d6efd;
}

.stat-label {
    font-size: 11px;
    color: #6c757d;
}

.footer-stat:hover .stat-label {
    color: #0d6efd;
}

/* === Presence === */
.footer-presence {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
}

.presence-dot {
    width: 7px;
    height: 7px;
    background: #198754;
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
    flex-shrink: 0;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(25, 135, 84, 0);
    }
}

.presence-text {
    font-size: 11px;
    font-weight: 500;
    color: #495057;
    white-space: nowrap;
}

/* === Version === */
.footer-version {
    font-family: 'Courier New', monospace;
    color: #adb5bd;
    font-size: 11px;
    font-weight: 500;
}

/* === UUID === */
.footer-uuid {
    font-family: 'Courier New', monospace;
    font-size: 11px;
}

.footer-uuid:hover {
    color: #0d6efd;
}

/* ============================================
   АДАПТИВНАЯ ВЕРСТКА
   ============================================ */

/* Планшет (768px - 1024px) */
@media (max-width: 1024px) {
    .workspace-footer {
        padding: 0 12px;
        gap: 10px;
    }

    .footer-left,
    .footer-center,
    .footer-right {
        gap: 10px;
    }

    .footer-stats {
        gap: 6px;
    }

    .footer-stat {
        padding: 3px 8px;
    }
}

/* Малый планшет / большой мобильный (577px - 768px) */
@media (max-width: 768px) {
    .workspace-footer {
        height: 34px;
        padding: 0 10px;
        font-size: 11px;
        gap: 8px;
    }

    .footer-left,
    .footer-center,
    .footer-right {
        gap: 8px;
    }

    /* Скрываем текст у документации */
    .link-label {
        display: none;
    }

    .footer-docs {
        padding: 4px;
        border-radius: 6px;
    }

    .footer-docs:hover {
        background: #e7f1ff;
    }

    /* Скрываем текст у статистики */
    .stat-label {
        display: none;
    }

    .footer-stat {
        padding: 3px 8px;
        gap: 4px;
    }

    .footer-stat i {
        font-size: 10px;
    }

    .stat-count {
        font-size: 11px;
        min-width: 12px;
    }

    /* Скрываем версию */
    .footer-version {
        display: none;
    }

    /* Сокращаем UUID */
    .uuid-text {
        font-size: 10px;
    }

    .footer-uuid i {
        font-size: 10px;
    }
}

/* Мобильный (до 576px) */
@media (max-width: 576px) {
    .workspace-footer {
        height: 32px;
        padding: 0 8px;
        padding-bottom: env(safe-area-inset-bottom, 0);
        gap: 6px;
    }

    .footer-left {
        flex: 1;
        overflow: hidden;
    }

    .footer-center {
        display: none;
    }

    .footer-right {
        gap: 6px;
    }

    /* Скрываем разделители */
    .footer-divider {
        display: none;
    }

    /* Статистика с горизонтальным скроллом */
    .footer-stats {
        gap: 4px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
    }

    .footer-stat {
        padding: 2px 6px;
        border-radius: 10px;
        scroll-snap-align: start;
    }

    .footer-stat i {
        font-size: 9px;
    }

    .stat-count {
        font-size: 10px;
        min-width: 10px;
    }

    /* Документация */
    .footer-docs {
        padding: 3px;
    }

    .footer-docs i {
        font-size: 10px;
    }

    /* UUID */
    .footer-uuid i {
        font-size: 9px;
    }

    .uuid-text {
        font-size: 9px;
    }
}

/* Очень маленький экран (до 380px) */
@media (max-width: 380px) {
    .workspace-footer {
        padding: 0 6px;
    }

    /* Скрываем третью статистику (коллекции) */
    .footer-stat:nth-child(3) {
        display: none;
    }

    .footer-stats {
        gap: 3px;
    }

    .footer-stat {
        padding: 2px 5px;
    }

    .footer-docs {
        display: none;
    }
}

/* Ландшафтная ориентация на мобильном */
@media (max-height: 500px) and (orientation: landscape) {
    .workspace-footer {
        height: 28px;
        font-size: 10px;
    }

    .footer-stat {
        padding: 2px 6px;
    }

    .stat-count {
        font-size: 10px;
    }
}

/* Тёмная тема (если есть) */
@media (prefers-color-scheme: dark) {
    .workspace-footer {
        background: #212529;
        border-top-color: #343a40;
        color: #adb5bd;
    }

    .footer-link {
        color: #adb5bd;
    }

    .footer-link:hover {
        color: #4dabf7;
    }

    .footer-divider {
        background: #343a40;
    }

    .footer-stat {
        background: #2c3034;
        border-color: #343a40;
    }

    .footer-stat:hover {
        border-color: #4dabf7;
        background: #2c3034;
    }

    .footer-stat i {
        color: #4dabf7;
    }

    .stat-count {
        color: #e9ecef;
    }

    .stat-label {
        color: #adb5bd;
    }

    .footer-presence {
        background: #2c3034;
        border-color: #343a40;
    }

    .presence-text {
        color: #e9ecef;
    }

    .footer-version {
        color: #6c757d;
    }
}
</style>
