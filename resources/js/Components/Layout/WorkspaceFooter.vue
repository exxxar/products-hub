<template>
    <footer class="workspace-footer">
        <div class="footer-left">
            <!-- Ссылка на документацию -->
            <a href="/docs" target="_blank" class="footer-link">
                <i class="fa-solid fa-book"></i>
                <span>Документация</span>
            </a>

            <span class="footer-divider"></span>

            <!-- ✅ Статистика: товары -->
            <div class="footer-stat" title="Товаров в workspace">
                <i class="fa-solid fa-box"></i>
                <span class="stat-count">{{ store.products?.length || 0 }}</span>
                <span class="stat-label">{{ pluralize(store.products?.length || 0, 'товар', 'товара', 'товаров') }}</span>
            </div>

            <span class="footer-divider"></span>

            <!-- ✅ Статистика: категории -->
            <div class="footer-stat" title="Категорий в workspace">
                <i class="fa-solid fa-tags"></i>
                <span class="stat-count">{{ store.categories?.length || 0 }}</span>
                <span class="stat-label">{{ pluralize(store.categories?.length || 0, 'категория', 'категории', 'категорий') }}</span>
            </div>

            <span class="footer-divider"></span>

            <!-- ✅ Статистика: коллекции -->
            <div class="footer-stat" title="Коллекций в workspace">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span class="stat-count">{{ store.collections?.length || 0 }}</span>
                <span class="stat-label">{{ pluralize(store.collections?.length || 0, 'коллекция', 'коллекции', 'коллекций') }}</span>
            </div>
        </div>

        <div class="footer-center">
            <!-- Статус присутствия -->
            <div v-if="store.onlineCount > 0" class="footer-presence">
                <span class="presence-dot"></span>
                <span>{{ store.onlineCount }} {{ pluralize(store.onlineCount, 'онлайн', 'онлайн', 'онлайн') }}</span>
            </div>
        </div>

        <div class="footer-right">
            <!-- Версия -->
            <span class="footer-version">
                v{{ version }}
            </span>

            <span class="footer-divider"></span>

            <!-- Workspace UUID (для копирования) -->
            <button
                type="button"
                class="footer-link footer-uuid"
                @click="copyUuid"
                :title="`UUID: ${store.uuid}`"
            >
                <i class="fa-solid fa-fingerprint"></i>
                <span>{{ store.uuid?.substring(0, 8) }}...</span>
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
    height: 32px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    font-size: 11px;
    color: #6c757d;
    z-index: 100;
    user-select: none;
}

.footer-left,
.footer-center,
.footer-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.footer-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #6c757d;
    text-decoration: none;
    transition: color 0.15s ease;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    font-size: inherit;
    font-family: inherit;
}

.footer-link:hover {
    color: #0d6efd;
}

.footer-link i {
    font-size: 10px;
}

.footer-divider {
    width: 1px;
    height: 14px;
    background: #dee2e6;
}

/* === ✅ Статистика === */
.footer-stat {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.15s ease;
    cursor: default;
}

.footer-stat:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #f8f9ff;
}

.footer-stat i {
    font-size: 10px;
    color: #0d6efd;
}

.footer-stat:hover i {
    color: #0d6efd;
}

.stat-count {
    font-weight: 700;
    color: #212529;
    font-size: 11px;
    min-width: 12px;
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
}

.presence-dot {
    width: 6px;
    height: 6px;
    background: #198754;
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(25, 135, 84, 0);
    }
}

/* === Version === */
.footer-version {
    font-family: monospace;
    color: #adb5bd;
    font-size: 10px;
}

/* === UUID === */
.footer-uuid {
    font-family: monospace;
    font-size: 10px;
}

.footer-uuid:hover {
    color: #0d6efd;
}

/* === Responsive === */
@media (max-width: 768px) {
    .footer-center {
        display: none;
    }

    .stat-label {
        display: none;
    }

    .footer-stat {
        padding: 2px 6px;
    }
}

@media (max-width: 480px) {
    .footer-left {
        gap: 8px;
    }

    .footer-divider:nth-of-type(n+3) {
        display: none;
    }

    .footer-stat:nth-of-type(n+3) {
        display: none;
    }
}
</style>
