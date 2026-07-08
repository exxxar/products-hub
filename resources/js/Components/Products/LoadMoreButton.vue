<template>
    <div class="load-more-wrapper">
        <!-- ✅ Панель настроек -->
        <div class="config-toggle-wrapper">
            <button type="button" class="config-btn" @click="showConfig = !showConfig" title="Настройки загрузки">
                <i class="fa-solid fa-sliders"></i>
                <span>Настройки</span>
            </button>

            <Transition name="fade">
                <div v-if="showConfig" class="config-panel">
                    <div class="config-row">
                        <label class="config-label">Фильтр:</label>
                        <div class="config-pills">
                            <button :class="{ active: config.filter === 'all' }" @click="setFilter('all')">Все</button>
                            <button :class="{ active: config.filter === 'active' }" @click="setFilter('active')">Активные</button>
                            <button :class="{ active: config.filter === 'stop' }" @click="setFilter('stop')">В стопе</button>
                        </div>
                    </div>
                    <div class="config-row">
                        <label class="config-label">Пакет:</label>
                        <select v-model.number="config.limit" class="config-select">
                            <option :value="20">20 товаров</option>
                            <option :value="50">50 товаров</option>
                            <option :value="100">100 товаров</option>
                        </select>
                    </div>
                    <button class="btn-apply" @click="applyConfig">
                        <i class="fa-solid fa-arrows-rotate"></i> Применить и обновить
                    </button>
                </div>
            </Transition>
        </div>

        <!-- Кнопка загрузки -->
        <button
            v-if="store.hasMoreProducts || store.productsLoadingMore"
            type="button"
            class="load-more-btn"
            @click="loadMore"
            :disabled="store.productsLoadingMore"
        >
            <i v-if="store.productsLoadingMore" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-arrow-down"></i>
            <span>
                {{ store.productsLoadingMore ? 'Загрузка...' : `Загрузить ещё (${remaining} ${pluralize(remaining, 'товар', 'товара', 'товаров')})` }}
            </span>
        </button>

        <!-- Прогресс -->
        <div v-if="store.hasMoreProducts || store.productsLoadingMore" class="load-progress">
            <div class="progress-bar">
                <div class="progress-fill" :style="{ width: store.loadProgress + '%' }"></div>
            </div>
            <span class="progress-text">Загружено {{ store.products.length }} из {{ store.totalProducts }}</span>
        </div>

        <!-- Все загружены -->
        <div v-else-if="store.products.length > 0" class="all-loaded">
            <i class="fa-solid fa-circle-check"></i>
            <span>Все товары загружены ({{ store.totalProducts }})</span>
        </div>
    </div>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'LoadMoreButton',
    data() {
        return {
            store: useWorkspaceStore(),
            showConfig: false,
            config: { limit: 50, filter: 'all' }
        }
    },
    computed: {
        remaining() { return Math.max(0, this.store.totalProducts - this.store.products.length) }
    },
    methods: {
        setFilter(f) { this.config.filter = f },
        async applyConfig() {
            this.showConfig = false
            try {
                await this.store.loadProducts(true, { limit: this.config.limit, filter: this.config.filter })
                this.$notify?.success('Настройки применены')
            } catch (e) {
                this.$notify?.error('Ошибка обновления')
            }
        },
        async loadMore() {
            try {
                await this.store.loadMoreProducts({ limit: this.config.limit, filter: this.config.filter })
                // Автоскролл к новым
                this.$nextTick(() => {
                    const btn = this.$el.querySelector('.load-more-btn')
                    btn?.scrollIntoView({ behavior: 'smooth', block: 'center' })
                })
            } catch (e) {
                this.$notify?.error('Ошибка при загрузке')
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

.load-more-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 24px 16px;
    margin: 16px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px dashed #dee2e6;
}

.load-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    border: 1px solid #0d6efd;
    border-radius: 10px;
    background: #fff;
    color: #0d6efd;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.load-more-btn:hover:not(:disabled) {
    background: #0d6efd;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.load-more-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.load-more-btn i {
    font-size: 13px;
}

/* === Прогресс === */
.load-progress {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    width: 100%;
    max-width: 400px;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0d6efd 0%, #6f42c1 100%);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
}

/* === Все загружены === */
.all-loaded {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px;
    margin: 16px;
    color: #198754;
    font-size: 13px;
    font-weight: 500;
}

.all-loaded i {
    font-size: 14px;
}

/* === Responsive === */
@media (max-width: 576px) {
    .load-more-wrapper {
        margin: 12px;
        padding: 20px 12px;
    }

    .load-more-btn {
        width: 100%;
        justify-content: center;
    }
}

.load-more-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 20px;
    margin: 16px;
    background: #fafbfc;
    border-radius: 12px;
    border: 1px dashed #dee2e6;
    position: relative;
}

.config-toggle-wrapper {
    width: 100%;
}

.config-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px;
    background: transparent;
    color: #6c757d;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
}
.config-btn:hover { color: #0d6efd; }

.config-panel {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.config-row {
    margin-bottom: 14px;
}
.config-row:last-child { margin-bottom: 14px; }

.config-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 6px;
}

.config-pills {
    display: flex;
    gap: 6px;
}
.config-pills button {
    padding: 6px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #495057;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.15s;
}
.config-pills button.active {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}
.config-pills button:hover:not(.active) {
    border-color: #0d6efd;
    color: #0d6efd;
}

.config-select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 13px;
    background: #fff;
    outline: none;
}

.btn-apply {
    width: 100%;
    padding: 9px;
    background: #0d6efd;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.btn-apply:hover { background: #0b5ed7; }

/* Существующие стили кнопки и прогресса */
.load-more-btn { /* ... ваши стили ... */ }
.load-progress { /* ... ваши стили ... */ }
.all-loaded { /* ... ваши стили ... */ }

/* Адаптив */
@media (max-width: 576px) {
    .config-pills { flex-wrap: wrap; }
    .config-pills button { flex: 1; }
}
</style>
