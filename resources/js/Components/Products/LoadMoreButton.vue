<template>
    <div class="load-more-wrapper">
        <!-- ✅ Панель настроек -->
        <div class="config-toggle-wrapper">
            <button type="button" class="config-btn" @click="toggleConfig" title="Настройки загрузки">
                <i class="fa-solid fa-sliders"></i>
                <span>Настройки загрузки</span>
                <i class="fa-solid fa-chevron-down config-arrow" :class="{ 'is-open': showConfig }"></i>
            </button>

            <Transition name="config-slide">
                <div v-if="showConfig" class="config-panel">
                    <div class="config-row">
                        <label class="config-label">Фильтр:</label>
                        <div class="config-pills">
                            <button :class="{ active: config.filter === 'all' }" @click="setFilter('all')">
                                <i class="fa-solid fa-list"></i>
                                <span>Все</span>
                            </button>
                            <button :class="{ active: config.filter === 'active' }" @click="setFilter('active')">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Активные</span>
                            </button>
                            <button :class="{ active: config.filter === 'stop' }" @click="setFilter('stop')">
                                <i class="fa-solid fa-ban"></i>
                                <span>В стопе</span>
                            </button>
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
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span>Применить и обновить</span>
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
            <span class="btn-text">
                {{ store.productsLoadingMore ? 'Загрузка...' : `Загрузить ещё` }}
            </span>
            <span v-if="!store.productsLoadingMore" class="btn-count">
                ({{ remaining }})
            </span>
        </button>

        <!-- Прогресс -->
        <div v-if="store.hasMoreProducts || store.productsLoadingMore" class="load-progress">
            <div class="progress-bar">
                <div class="progress-fill" :style="{ width: store.loadProgress + '%' }"></div>
            </div>
            <span class="progress-text">
                <span class="progress-loaded">{{ store.products.length }}</span>
                <span class="progress-sep">из</span>
                <span class="progress-total">{{ store.totalProducts }}</span>
            </span>
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
        remaining() {
            return Math.max(0, this.store.totalProducts - this.store.products.length)
        }
    },
    methods: {
        toggleConfig() {
            this.showConfig = !this.showConfig
        },
        setFilter(f) {
            this.config.filter = f
        },
        async applyConfig() {
            this.showConfig = false
            try {
                await this.store.loadProducts(true, {
                    limit: this.config.limit,
                    filter: this.config.filter
                })
                this.$notify?.success('Настройки применены')
            } catch (e) {
                this.$notify?.error('Ошибка обновления')
            }
        },
        async loadMore() {
            try {
                await this.store.loadMoreProducts({
                    limit: this.config.limit,
                    filter: this.config.filter
                })
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
/* === Wrapper === */
.load-more-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 20px;
    margin: 16px 0;
    background: #fafbfc;
    border-radius: 12px;
    border: 1px dashed #dee2e6;
    position: relative;
}

/* === Config Toggle === */
.config-toggle-wrapper {
    width: 100%;
}

.config-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    color: #6c757d;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.15s ease;
}

.config-btn:hover {
    color: #0d6efd;
    border-color: #0d6efd;
    background: #f8f9ff;
}

.config-arrow {
    font-size: 10px;
    transition: transform 0.2s ease;
    margin-left: auto;
}

.config-arrow.is-open {
    transform: rotate(180deg);
}

/* === Config Panel === */
.config-panel {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 16px;
    margin-top: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.config-row {
    margin-bottom: 14px;
}

.config-row:last-child {
    margin-bottom: 0;
}

.config-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

/* === Pills === */
.config-pills {
    display: flex;
    gap: 6px;
}

.config-pills button {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #495057;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.config-pills button i {
    font-size: 11px;
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

/* === Select === */
.config-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 13px;
    background: #fff;
    outline: none;
    cursor: pointer;
    transition: all 0.15s ease;
}

.config-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

/* === Apply Button === */
.btn-apply {
    width: 100%;
    padding: 10px;
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
    gap: 8px;
    transition: all 0.15s ease;
    margin-top: 14px;
}

.btn-apply:hover {
    background: #0b5ed7;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

/* === Load More Button === */
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

.btn-count {
    opacity: 0.7;
    font-weight: 500;
}

/* === Progress === */
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
    display: flex;
    align-items: center;
    gap: 4px;
}

.progress-loaded {
    font-weight: 700;
    color: #0d6efd;
}

.progress-sep {
    color: #adb5bd;
}

.progress-total {
    font-weight: 700;
    color: #495057;
}

/* === All Loaded === */
.all-loaded {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px;
    color: #198754;
    font-size: 13px;
    font-weight: 500;
    text-align: center;
}

.all-loaded i {
    font-size: 14px;
}

/* === Transitions === */
.config-slide-enter-active,
.config-slide-leave-active {
    transition: all 0.2s ease;
}

.config-slide-enter-from,
.config-slide-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

/* ============================================
   АДАПТИВНАЯ ВЕРСТКА
   ============================================ */

/* Планшет (768px - 1024px) */
@media (min-width: 768px) and (max-width: 1024px) {
    .load-more-wrapper {
        padding: 24px;
    }

    .config-panel {
        padding: 20px;
    }
}

/* ✅ Мобильный (до 767px) */
@media (max-width: 767px) {
    .load-more-wrapper {
        padding: 16px 12px;
        margin: 12px 0;
        margin-bottom: 80px; /* Отступ для FAB кнопки */
        gap: 10px;
    }

    .config-btn {
        padding: 10px 12px;
        font-size: 12px;
    }

    .config-panel {
        padding: 12px;
        margin-top: 10px;
    }

    .config-row {
        margin-bottom: 12px;
    }

    .config-label {
        font-size: 11px;
        margin-bottom: 6px;
    }

    /* Pills в колонку на очень маленьких экранах */
    .config-pills {
        flex-direction: column;
        gap: 6px;
    }

    .config-pills button {
        padding: 10px 12px;
        font-size: 12px;
        justify-content: flex-start;
    }

    .config-pills button i {
        font-size: 12px;
        width: 16px;
        text-align: center;
    }

    .config-select {
        padding: 10px;
        font-size: 13px;
    }

    .btn-apply {
        padding: 12px;
        font-size: 13px;
        margin-top: 12px;
    }

    /* Кнопка загрузки на всю ширину */
    .load-more-btn {
        width: 100%;
        justify-content: center;
        padding: 14px 20px;
        font-size: 14px;
    }

    .btn-text {
        flex: 1;
        text-align: center;
    }

    /* Прогресс */
    .load-progress {
        max-width: 100%;
    }

    .progress-bar {
        height: 5px;
    }

    .progress-text {
        font-size: 11px;
    }

    /* Все загружены */
    .all-loaded {
        padding: 12px;
        font-size: 12px;
    }

    .all-loaded i {
        font-size: 13px;
    }
}

/* ✅ Очень маленький экран (до 380px) */
@media (max-width: 380px) {
    .load-more-wrapper {
        padding: 12px 8px;
        margin: 10px 0;
        margin-bottom: 70px;
    }

    .config-btn {
        padding: 8px 10px;
        font-size: 11px;
        gap: 6px;
    }

    .config-panel {
        padding: 10px;
    }

    .config-pills button {
        padding: 9px 10px;
        font-size: 11px;
    }

    .config-select {
        padding: 9px;
        font-size: 12px;
    }

    .btn-apply {
        padding: 10px;
        font-size: 12px;
    }

    .load-more-btn {
        padding: 12px 16px;
        font-size: 13px;
    }

    .progress-text {
        font-size: 10px;
    }

    .all-loaded {
        padding: 10px;
        font-size: 11px;
    }
}

/* Ландшафтная ориентация */
@media (max-height: 500px) and (orientation: landscape) and (max-width: 900px) {
    .load-more-wrapper {
        padding: 12px;
        margin-bottom: 60px;
    }

    .config-panel {
        padding: 10px;
    }

    .config-row {
        margin-bottom: 8px;
    }

    .load-more-btn {
        padding: 10px 20px;
    }
}

/* Тёмная тема */
@media (prefers-color-scheme: dark) {
    .load-more-wrapper {
        background: #2c3034;
        border-color: #343a40;
    }

    .config-btn {
        border-color: #343a40;
        color: #adb5bd;
    }

    .config-btn:hover {
        border-color: #4dabf7;
        color: #4dabf7;
        background: #343a40;
    }

    .config-panel {
        background: #343a40;
        border-color: #495057;
    }

    .config-label {
        color: #adb5bd;
    }

    .config-pills button {
        background: #2c3034;
        border-color: #495057;
        color: #adb5bd;
    }

    .config-pills button.active {
        background: #4dabf7;
        border-color: #4dabf7;
        color: #212529;
    }

    .config-select {
        background: #2c3034;
        border-color: #495057;
        color: #e9ecef;
    }

    .load-more-btn {
        background: #2c3034;
        border-color: #4dabf7;
        color: #4dabf7;
    }

    .load-more-btn:hover:not(:disabled) {
        background: #4dabf7;
        color: #212529;
    }

    .progress-bar {
        background: #343a40;
    }

    .progress-text {
        color: #adb5bd;
    }

    .progress-loaded {
        color: #4dabf7;
    }

    .all-loaded {
        color: #51cf66;
    }
}
</style>
