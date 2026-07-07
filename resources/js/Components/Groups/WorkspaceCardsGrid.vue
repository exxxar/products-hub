<template>
    <div class="workspace-cards-container">
        <!-- Заголовок -->
        <div class="aggregator-header">
            <h2 class="aggregator-title">
                <i class="fa-solid fa-layer-group"></i>
                Мои доски
            </h2>
            <p class="aggregator-subtitle">
                {{ filteredWorkspaces.length }} из {{ workspaces.length }} {{ pluralize(filteredWorkspaces.length, 'доска', 'доски', 'досок') }}
            </p>
        </div>

        <!-- Поиск и фильтр -->
        <div class="aggregator-controls">
            <div class="aggregator-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Найти доску..."
                />
            </div>

            <!-- ✅ Переключатель фильтра -->
            <label class="filter-switch">
                <input
                    type="checkbox"
                    v-model="onlyWithProducts"
                />
                <span class="switch-slider"></span>
                <span class="switch-label">
                    <i class="fa-solid fa-box"></i>
                    Только с товарами
                </span>
            </label>
        </div>

        <!-- Сетка карточек -->
        <div v-if="filteredWorkspaces.length > 0" class="cards-grid">
            <div
                v-for="workspace in filteredWorkspaces"
                :key="workspace.uuid"
                class="workspace-card"
                :class="{ 'is-current': workspace.is_current }"
                @click="$emit('select', workspace)"
            >
                <div class="card-icon" :style="{ background: workspace.color || '#0d6efd' }">
                    <img v-if="workspace.logo_url" :src="workspace.logo_url" alt="" />
                    <span v-else>{{ getInitials(workspace) }}</span>
                </div>

                <div class="card-body">
                    <div class="card-name">{{ workspace.name }}</div>
                    <div v-if="workspace.label" class="card-label">{{ workspace.label }}</div>

                    <!-- ✅ Блок со статистикой -->
                    <div class="card-stats">
                        <div class="stat-item" :title="`${workspace.stats?.products_count || 0} товаров`">
                            <i class="fa-solid fa-box"></i>
                            <span>{{ formatNumber(workspace.stats?.products_count) }}</span>
                        </div>
                        <div class="stat-item" :title="`${workspace.stats?.categories_count || 0} категорий`">
                            <i class="fa-solid fa-folder"></i>
                            <span>{{ formatNumber(workspace.stats?.categories_count) }}</span>
                        </div>
                        <div class="stat-item" :title="`${workspace.stats?.collections_count || 0} коллекций`">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>{{ formatNumber(workspace.stats?.collections_count) }}</span>
                        </div>
                    </div>

                    <div class="card-meta">
                        <span v-if="workspace.is_current" class="badge current">
                            <i class="fa-solid fa-check"></i> Текущая
                        </span>
                        <span v-else class="badge linked">
                            <i class="fa-solid fa-link"></i> Связанная
                        </span>
                    </div>
                </div>

                <!-- ✅ Кнопки действий -->
                <div class="card-actions">
                    <button
                        type="button"
                        class="card-action-btn"
                        @click.stop="openInNewTab(workspace)"
                        title="Открыть в новом окне"
                    >
                        <i class="fa-solid fa-up-right-from-square"></i>
                    </button>
                    <div class="card-arrow">
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else-if="searchQuery || onlyWithProducts" class="empty-state">
            <i class="fa-solid fa-filter"></i>
            <p>Ничего не найдено</p>
            <small v-if="onlyWithProducts">Попробуйте отключить фильтр "Только с товарами"</small>
        </div>
        <div v-else class="empty-state">
            <i class="fa-solid fa-layer-group"></i>
            <p>Нет связанных досок</p>
            <small>Добавьте доски через настройки → "Доски"</small>
        </div>
    </div>
</template>

<script>
export default {
    name: 'WorkspaceCardsGrid',
    props: {
        workspaces: {
            type: Array,
            default: () => []
        }
    },
    emits: ['select'],
    data() {
        return {
            searchQuery: '',
            onlyWithProducts: false // ✅ Новый фильтр
        }
    },
    computed: {
        filteredWorkspaces() {
            let result = this.workspaces

            // Поиск по имени/метке
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase()
                result = result.filter(w =>
                    w.name?.toLowerCase().includes(q) ||
                    w.label?.toLowerCase().includes(q)
                )
            }

            // ✅ Фильтр по наличию товаров
            if (this.onlyWithProducts) {
                result = result.filter(w => {
                    const productsCount = w.stats?.products_count || 0
                    return productsCount > 0
                })
            }

            return result
        }
    },
    mounted() {
        // ✅ Если статистики нет в данных — подгружаем
        this.loadStatsIfNeeded()
    },
    methods: {
        getInitials(workspace) {
            if (workspace.label) return workspace.label
            return (workspace.name || 'WS').substring(0, 2).toUpperCase()
        },
        pluralize(count, one, two, five) {
            let n = Math.abs(count) % 100
            if (n >= 5 && n <= 20) return five
            n %= 10
            if (n === 1) return one
            if (n >= 2 && n <= 4) return two
            return five
        },
        formatNumber(num) {
            if (num === null || num === undefined) return '0'
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K'
            }
            return num.toString()
        },
        openInNewTab(workspace) {
            // ✅ Открываем доску в новой вкладке
            window.open(`/workspace/${workspace.uuid}`, '_blank')
        },
        async loadStatsIfNeeded() {
            // Проверяем, есть ли статистика хотя бы у одного workspace
            const hasStats = this.workspaces.some(w => w.stats)
            if (hasStats) return

            // Загружаем статистику для всех workspace'ов
            try {
                const uuids = this.workspaces
                    .filter(w => !w.is_current)
                    .map(w => w.uuid)

                if (uuids.length === 0) return

                const response = await axios.post('/api/workspaces/stats', { uuids })

                // Мерджим статистику в workspaces
                if (response.data?.stats) {
                    this.workspaces.forEach(w => {
                        if (response.data.stats[w.uuid]) {
                            w.stats = response.data.stats[w.uuid]
                        }
                    })
                }
            } catch (error) {
                console.error('Failed to load workspace stats:', error)
            }
        }
    }
}
</script>

<style scoped>
.workspace-cards-container {
    max-width: 1200px;
    margin: 0 auto;
}

.aggregator-header {
    margin-bottom: 24px;
}

.aggregator-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 24px;
    font-weight: 700;
    color: #212529;
    margin: 0 0 4px 0;
}

.aggregator-title i {
    color: #0d6efd;
}

.aggregator-subtitle {
    font-size: 14px;
    color: #6c757d;
    margin: 0;
}

/* ✅ Блок управления (поиск + фильтр) */
.aggregator-controls {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    align-items: center;
}

.aggregator-search {
    position: relative;
    flex: 1;
}

.aggregator-search > i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 14px;
}

.aggregator-search input {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    font-size: 14px;
    outline: none;
    background: #fff;
    transition: all 0.15s ease;
}

.aggregator-search input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

/* ✅ Переключатель фильтра */
.filter-switch {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
    padding: 10px 16px;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    background: #fff;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.filter-switch:hover {
    border-color: #0d6efd;
    background: #f8f9ff;
}

.filter-switch input {
    display: none;
}

.switch-slider {
    position: relative;
    width: 40px;
    height: 22px;
    background: #dee2e6;
    border-radius: 22px;
    transition: background 0.2s ease;
    flex-shrink: 0;
}

.switch-slider::before {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    left: 2px;
    top: 2px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.filter-switch input:checked + .switch-slider {
    background: #0d6efd;
}

.filter-switch input:checked + .switch-slider::before {
    transform: translateX(18px);
}

.switch-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #495057;
}

.switch-label i {
    font-size: 12px;
    color: #0d6efd;
}

.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.workspace-card {
    display: flex;
    align-items: self-start;
    gap: 14px;
    padding: 18px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.workspace-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 6px 20px rgba(13, 110, 253, 0.12);
    transform: translateY(-2px);
}

.workspace-card.is-current {
    border-color: #0d6efd;
    background: linear-gradient(135deg, #f8f9ff 0%, #fff 100%);
}

.card-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    font-weight: 700;
    flex-shrink: 0;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-body {
    flex: 1;
    min-width: 0;
}

.card-name {
    font-size: 15px;
    font-weight: 600;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
    word-break: break-all;
}

.card-label {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 8px;
}

/* ✅ Статистика */
.card-stats {
    display: flex;
    gap: 10px;
    margin-bottom: 8px;
    padding: 6px 8px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #495057;
    font-weight: 500;
}

.stat-item i {
    font-size: 10px;
    color: #0d6efd;
    opacity: 0.7;
}

.card-meta {
    display: flex;
    gap: 6px;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
}

.badge.current {
    background: #0d6efd;
    color: #fff;
}

.badge.linked {
    background: #e7f1ff;
    color: #084298;
}

/* ✅ Блок действий */
.card-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex-shrink: 0;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.workspace-card:hover .card-actions {
    opacity: 1;
}

.card-action-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.15s ease;
}

.card-action-btn:hover {
    background: #e7f1ff;
    border-color: #0d6efd;
    color: #0d6efd;
}

.card-arrow {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 12px;
    transition: all 0.15s ease;
}

.workspace-card:hover .card-arrow {
    background: #0d6efd;
    color: #fff;
    transform: translateX(2px);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    text-align: center;
    color: #6c757d;
}

.empty-state i {
    font-size: 56px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-state p {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #495057;
}

.empty-state small {
    font-size: 13px;
}

/* ✅ Мобильная адаптация */
@media (max-width: 768px) {
    .cards-grid {
        grid-template-columns: 1fr;
    }

    .aggregator-title {
        font-size: 20px;
    }

    /* На мобильном кнопки всегда видны */
    .card-actions {
        opacity: 1;
    }

    .card-stats {
        gap: 8px;
    }

    .stat-item {
        font-size: 10px;
    }

    /* ✅ Мобильная адаптация для контролов */
    .aggregator-controls {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-switch {
        justify-content: center;
    }
}
</style>
