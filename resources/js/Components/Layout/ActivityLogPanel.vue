<template>
    <div class="activity-panel">
        <!-- Заголовок со статистикой -->
        <div class="panel-header">
            <div class="header-info">
                <h5>
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    История действий
                </h5>
                <div v-if="store.stats" class="stats-row">
                    <span class="stat-chip">
                        <i class="fa-solid fa-chart-simple"></i>
                        {{ store.stats.today }} сегодня
                    </span>
                    <span class="stat-chip">
                        <i class="fa-solid fa-database"></i>
                        {{ store.stats.total }} всего
                    </span>
                    <span v-if="store.stats.recent_failed_logins > 0" class="stat-chip danger">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        {{ store.stats.recent_failed_logins }} неудачных входов
                    </span>
                </div>
            </div>

            <button class="btn-close-panel" @click="$emit('close')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Фильтры -->
        <div class="filters-bar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    v-model="searchInput"
                    type="text"
                    placeholder="Поиск..."
                    @input="onSearchInput"
                />
                <button v-if="searchInput" class="clear-btn" @click="clearSearch">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <select v-model="store.logsFilters.entity_type" @change="onFilterChange" class="filter-select">
                <option :value="null">Все типы</option>
                <option value="product">Товары</option>
                <option value="category">Категории</option>
                <option value="collection">Коллекции</option>
                <option value="webhook">Вебхуки</option>
                <option value="master_code">Мастер-код</option>
                <option value="import">Импорт</option>
                <option value="export">Экспорт</option>
            </select>

            <select v-model="store.logsFilters.days" @change="onFilterChange" class="filter-select">
                <option :value="null">Всё время</option>
                <option :value="1">Сегодня</option>
                <option :value="7">Неделя</option>
                <option :value="30">Месяц</option>
                <option :value="90">3 месяца</option>
            </select>

            <button
                v-if="hasActiveFilters"
                class="btn-reset-filters"
                @click="resetFilters"
                title="Сбросить фильтры"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Список логов -->
        <div v-if="store.logsLoading" class="loading-state">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <p>Загрузка...</p>
        </div>

        <div v-else-if="store.logs.length === 0" class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <p>Нет записей</p>
            <small v-if="hasActiveFilters">Попробуйте изменить фильтры</small>
        </div>

        <div v-else class="logs-list">
            <div
                v-for="log in store.logs"
                :key="log.id"
                class="log-item"
            >
                <!-- Иконка -->
                <div class="log-icon" :style="{ background: log.color + '20', color: log.color }">
                    <i :class="'fa-solid ' + log.icon"></i>
                </div>

                <!-- Контент -->
                <div class="log-content">
                    <div class="log-header">
                        <span class="log-action" :style="{ color: log.color }">
                            {{ log.action_label }}
                        </span>
                        <span class="log-entity-type">{{ log.entity_type_label }}</span>
                        <span v-if="log.entity_name" class="log-entity-name">
                            «{{ log.entity_name }}»
                        </span>
                    </div>

                    <div class="log-description">{{ log.description }}</div>

                    <!-- Метаданные (если есть изменения) -->
                    <div v-if="log.metadata?.changes && Object.keys(log.metadata.changes).length" class="log-changes">
                        <div
                            v-for="(change, field) in log.metadata.changes"
                            :key="field"
                            class="change-item"
                        >
                            <span class="field-name">{{ formatFieldName(field) }}:</span>
                            <span class="old-value">{{ formatValue(change.old) }}</span>
                            <i class="fa-solid fa-arrow-right"></i>
                            <span class="new-value">{{ formatValue(change.new) }}</span>
                        </div>
                    </div>

                    <div class="log-meta">
                        <span class="log-time">
                            <i class="fa-regular fa-clock"></i>
                            {{ log.created_at_human }}
                        </span>
                        <span v-if="log.ip_address" class="log-ip">
                            <i class="fa-solid fa-network-wired"></i>
                            {{ log.ip_address }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Пагинация -->
        <div v-if="store.logsPagination && store.logsPagination.last_page > 1" class="pagination">
            <button
                class="page-btn"
                :disabled="store.logsFilters.page <= 1"
                @click="goToPage(store.logsFilters.page - 1)"
            >
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <span class="page-info">
                {{ store.logsFilters.page }} / {{ store.logsPagination.last_page }}
            </span>

            <button
                class="page-btn"
                :disabled="store.logsFilters.page >= store.logsPagination.last_page"
                @click="goToPage(store.logsFilters.page + 1)"
            >
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <!-- Очистка старых логов -->
        <div class="panel-footer">
            <button class="btn-clear-old" @click="clearOldLogs">
                <i class="fa-solid fa-broom"></i>
                Очистить записи старше 30 дней
            </button>
        </div>
    </div>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'ActivityLogPanel',

    emits: ['close'],

    data() {
        return {
            store: useWorkspaceStore(),
            searchInput: '',
            searchDebounce: null,
        }
    },

    computed: {
        hasActiveFilters() {
            const f = this.store.logsFilters
            return f.entity_type || f.days || f.search
        }
    },

    async mounted() {
        await Promise.all([
            this.store.loadActivityLogs(),
            this.store.loadActivityStats()
        ])
    },

    methods: {
        onSearchInput() {
            clearTimeout(this.searchDebounce)
            this.searchDebounce = setTimeout(() => {
                this.store.setActivityFilter('search', this.searchInput)
            }, 400)
        },

        clearSearch() {
            this.searchInput = ''
            this.store.setActivityFilter('search', '')
        },

        onFilterChange() {
            this.store.loadActivityLogs()
        },

        resetFilters() {
            this.searchInput = ''
            this.store.resetActivityFilters()
        },

        goToPage(page) {
            this.store.logsFilters.page = page
            this.store.loadActivityLogs()
        },

        formatFieldName(field) {
            const names = {
                name: 'Название',
                sku: 'Артикул',
                price: 'Цена',
                old_price: 'Старая цена',
                description: 'Описание',
                is_active: 'Активен',
                in_stop_list: 'В стоп-листе',
                parent_id: 'Родитель',
                sort_order: 'Порядок',
            }
            return names[field] || field
        },

        formatValue(value) {
            if (value === null || value === undefined) return '—'
            if (typeof value === 'boolean') return value ? 'Да' : 'Нет'
            if (typeof value === 'number') return value.toLocaleString('ru-RU')
            return String(value)
        },

        async clearOldLogs() {
            if (!confirm('Удалить записи старше 30 дней?')) return

            try {
                const result = await this.store.clearActivityLogs(30)
                this.$notify?.success({
                    title: 'Очистка завершена',
                    message: result.message
                })
            } catch (error) {
                this.$notify?.error('Ошибка при очистке')
            }
        }
    },

    beforeUnmount() {
        clearTimeout(this.searchDebounce)
    }
}
</script>

<style scoped>
.activity-panel {
    position: fixed;
    top: 0;
    right: 0;
    width: 480px;
    max-width: 100vw;
    height: 100vh;
    background: #fff;
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    animation: slideIn 0.25s ease;
}

@keyframes slideIn {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

.panel-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
}

.header-info h5 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 17px;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #212529;
}

.header-info h5 i {
    color: #0d6efd;
}

.stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    background: #e7f1ff;
    color: #084298;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 500;
}

.stat-chip i {
    font-size: 10px;
}

.stat-chip.danger {
    background: #f8d7da;
    color: #842029;
}

.btn-close-panel {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.btn-close-panel:hover {
    background: #f1f3f5;
    color: #212529;
}

/* === Фильтры === */
.filters-bar {
    padding: 12px 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    background: #fafbfc;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 160px;
}

.search-box > i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 12px;
    pointer-events: none;
}

.search-box input {
    width: 100%;
    padding: 7px 28px 7px 30px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
    background: #fff;
}

.search-box input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.search-box .clear-btn {
    position: absolute;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    border: none;
    border-radius: 50%;
    background: transparent;
    color: #adb5bd;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.filter-select {
    padding: 7px 10px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 13px;
    background: #fff;
    color: #495057;
    cursor: pointer;
    outline: none;
}

.filter-select:focus {
    border-color: #0d6efd;
}

.btn-reset-filters {
    width: 32px;
    height: 32px;
    border: 1px solid #f5c2c7;
    border-radius: 8px;
    background: #fff;
    color: #dc3545;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-reset-filters:hover {
    background: #dc3545;
    color: #fff;
}

/* === Список логов === */
.logs-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0;
}

.log-item {
    display: flex;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.15s ease;
}

.log-item:hover {
    background: #f8f9fa;
}

.log-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 14px;
}

.log-content {
    flex: 1;
    min-width: 0;
}

.log-header {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 2px;
}

.log-action {
    font-size: 13px;
    font-weight: 600;
}

.log-entity-type {
    font-size: 11px;
    padding: 1px 6px;
    background: #e9ecef;
    color: #495057;
    border-radius: 4px;
    font-weight: 500;
}

.log-entity-name {
    font-size: 12px;
    color: #6c757d;
    font-style: italic;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 200px;
}

.log-description {
    font-size: 12px;
    color: #495057;
    line-height: 1.4;
    margin-bottom: 4px;
}

/* === Изменения полей === */
.log-changes {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 6px 8px;
    margin: 6px 0;
    font-size: 11px;
}

.change-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 2px 0;
    flex-wrap: wrap;
}

.change-item + .change-item {
    border-top: 1px dashed #e9ecef;
    margin-top: 2px;
    padding-top: 4px;
}

.field-name {
    color: #6c757d;
    font-weight: 500;
}

.old-value {
    color: #dc3545;
    text-decoration: line-through;
    background: #f8d7da;
    padding: 1px 5px;
    border-radius: 3px;
}

.change-item i {
    color: #adb5bd;
    font-size: 9px;
}

.new-value {
    color: #198754;
    background: #d1e7dd;
    padding: 1px 5px;
    border-radius: 3px;
    font-weight: 500;
}

/* === Мета === */
.log-meta {
    display: flex;
    gap: 12px;
    font-size: 11px;
    color: #adb5bd;
    margin-top: 4px;
}

.log-meta i {
    margin-right: 3px;
    font-size: 10px;
}

/* === Состояния === */
.loading-state,
.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    color: #adb5bd;
    text-align: center;
}

.loading-state i,
.empty-state i {
    font-size: 36px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.loading-state p,
.empty-state p {
    margin: 0 0 4px 0;
    font-size: 14px;
    color: #6c757d;
}

.empty-state small {
    font-size: 12px;
    color: #adb5bd;
}

/* === Пагинация === */
.pagination {
    padding: 10px 20px;
    border-top: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: #fafbfc;
}

.page-btn {
    width: 30px;
    height: 30px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: #fff;
    color: #495057;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}

.page-btn:hover:not(:disabled) {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.page-info {
    font-size: 13px;
    color: #495057;
    font-weight: 500;
}

/* === Футер === */
.panel-footer {
    padding: 12px 20px;
    border-top: 1px solid #e9ecef;
    background: #fafbfc;
}

.btn-clear-old {
    width: 100%;
    padding: 8px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.15s ease;
}

.btn-clear-old:hover {
    background: #fff3cd;
    border-color: #ffecb5;
    color: #664d03;
}

/* === Адаптив === */
@media (max-width: 576px) {
    .activity-panel {
        width: 100vw;
    }

    .filters-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-select {
        width: 100%;
    }
}
</style>
