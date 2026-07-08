<template>
    <div class="workspace-cards-container">
        <!-- Заголовок -->
        <div class="aggregator-header">
            <div class="aggregator-header-top">
                <div>
                    <h2 class="aggregator-title">
                        <i class="fa-solid fa-layer-group"></i>
                        Мои доски
                    </h2>
                    <p class="aggregator-subtitle">
                        {{ filteredWorkspaces.length }} из {{ workspaces.length }} {{ pluralize(filteredWorkspaces.length, 'доска', 'доски', 'досок') }}
                    </p>
                </div>

                <!-- ✅ Кнопка редактирования порядка -->
                <button
                    v-if="!isOrdering"
                    type="button"
                    class="btn-order-mode"
                    @click="startOrdering"
                    title="Изменить порядок"
                >
                    <i class="fa-solid fa-arrows-up-down"></i>
                    <span>Упорядочить</span>
                </button>
                <div v-else class="ordering-controls">
                    <button
                        type="button"
                        class="btn-order-save"
                        @click="saveOrder"
                        :disabled="isSaving"
                    >
                        <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        <span>{{ isSaving ? 'Сохранение...' : 'Сохранить' }}</span>
                    </button>
                    <button
                        type="button"
                        class="btn-order-cancel"
                        @click="cancelOrdering"
                    >
                        <i class="fa-solid fa-xmark"></i>
                        <span>Отмена</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Поиск и фильтр -->
        <div class="aggregator-controls">
            <div class="aggregator-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Найти доску..."
                    :disabled="isOrdering"
                />
            </div>

            <label class="filter-switch" :class="{ disabled: isOrdering }">
                <input
                    type="checkbox"
                    v-model="onlyWithProducts"
                    :disabled="isOrdering"
                />
                <span class="switch-slider"></span>
                <span class="switch-label">
                    <i class="fa-solid fa-box"></i>
                    Только с товарами
                </span>
            </label>
        </div>

        <!-- Подсказка в режиме редактирования -->
        <div v-if="isOrdering" class="ordering-hint">
            <i class="fa-solid fa-info-circle"></i>
            Перетаскивайте карточки, чтобы изменить порядок. Порядок сохраняется для текущей доски.
        </div>

        <!-- ✅ Сетка карточек (обычный режим) -->
        <div v-if="!isOrdering && filteredWorkspaces.length > 0" class="cards-grid">
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

        <!-- Режим редактирования порядка (drag-and-drop) -->
        <div v-else-if="isOrdering" class="ordering-mode">
            <draggable
                v-model="orderingList"
                item-key="uuid"
                handle=".drag-handle"
                ghost-class="ghost-card"
                :animation="200"
                class="ordering-grid"
            >
                <template #item="{ element: workspace, index }">
                    <div class="workspace-card ordering-card">
                        <div class="drag-handle" title="Перетащите">
                            <i class="fa-solid fa-grip-vertical"></i>
                        </div>

                        <div class="ordering-number">{{ index + 1 }}</div>

                        <div class="card-icon" :style="{ background: workspace.color || '#0d6efd' }">
                            <img v-if="workspace.logo_url" :src="workspace.logo_url" alt="" />
                            <span v-else>{{ getInitials(workspace) }}</span>
                        </div>

                        <div class="card-body">
                            <div class="card-name">{{ workspace.name }}</div>
                            <div v-if="workspace.label" class="card-label">{{ workspace.label }}</div>
                        </div>

                        <div class="ordering-arrows">
                            <button
                                type="button"
                                class="arrow-btn"
                                :disabled="index === 0"
                                @click="moveUp(index)"
                                title="Вверх"
                            >
                                <i class="fa-solid fa-chevron-up"></i>
                            </button>
                            <button
                                type="button"
                                class="arrow-btn"
                                :disabled="index === orderingList.length - 1"
                                @click="moveDown(index)"
                                title="Вниз"
                            >
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </draggable>
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
import draggable from 'vuedraggable'
import { useWorkspaceStore } from '@/store/workspace.js'
export default {
    name: 'WorkspaceCardsGrid',
    components: { draggable },
    props: {
        workspaces: {
            type: Array,
            default: () => []
        }
    },
    emits: ['select'],
    data() {
        return {
            store: useWorkspaceStore(),
            searchQuery: '',
            onlyWithProducts: false,
            isOrdering: false,
            isSaving: false,
            orderingList: [], // Копия списка для редактирования
            originalOrder: [] // Для отмены
        }
    },
    computed: {
        filteredWorkspaces() {
            let result = [...this.workspaces]

            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase()
                result = result.filter(w =>
                    w.name?.toLowerCase().includes(q) ||
                    w.label?.toLowerCase().includes(q)
                )
            }

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
            window.open(`/workspace/${workspace.uuid}`, '_blank')
        },
        async loadStatsIfNeeded() {
            const hasStats = this.workspaces.some(w => w.stats)
            if (hasStats) return

            try {
                const uuids = this.workspaces
                    .filter(w => !w.is_current)
                    .map(w => w.uuid)

                if (uuids.length === 0) return

                const response = await axios.post(`/api/workspaces/${this.store.uuid}/workspace/stats`, { uuids })

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
        },

        // === Управление порядком ===
        startOrdering() {
            // Сохраняем текущий порядок для возможной отмены
            this.originalOrder = this.workspaces.map(w => w.uuid)
            // Копируем список для редактирования (исключая текущий)
            this.orderingList = this.workspaces.filter(w => !w.is_current)
            this.isOrdering = true
        },
        cancelOrdering() {
            this.isOrdering = false
            this.orderingList = []
            this.originalOrder = []
        },
        async saveOrder() {
            this.isSaving = true
            try {
                const uuids = this.orderingList.map(w => w.uuid)
                await axios.post(`/api/workspaces/${this.store.uuid}/workspace/linked/order`, { uuids })

                // Обновляем порядок в основном списке
                const currentWs = this.workspaces.find(w => w.is_current)
                const others = this.orderingList
                const newOrder = currentWs ? [currentWs, ...others] : others

                // Очищаем старый массив и заполняем новым
                this.workspaces.splice(0, this.workspaces.length, ...newOrder)

                this.isOrdering = false
                this.orderingList = []
                this.originalOrder = []

                this.$notify?.success({
                    title: 'Порядок сохранён',
                    message: 'Новый порядок досок успешно применён'
                })
            } catch (error) {
                console.error('Save order failed:', error)
                this.$notify?.error('Ошибка при сохранении порядка')
            } finally {
                this.isSaving = false
            }
        },
        moveUp(index) {
            if (index === 0) return
            const list = [...this.orderingList]
            const item = list.splice(index, 1)[0]
            list.splice(index - 1, 0, item)
            this.orderingList = list
        },
        moveDown(index) {
            if (index === this.orderingList.length - 1) return
            const list = [...this.orderingList]
            const item = list.splice(index, 1)[0]
            list.splice(index + 1, 0, item)
            this.orderingList = list
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

.aggregator-header-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 4px;
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

/* ✅ Кнопка режима упорядочивания */
.btn-order-mode {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    background: #fff;
    color: #495057;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.btn-order-mode:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #f8f9ff;
}

.btn-order-mode i {
    font-size: 12px;
}

/* ✅ Контроли режима редактирования */
.ordering-controls {
    display: flex;
    gap: 8px;
}

.btn-order-save {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    border-radius: 10px;
    background: #0d6efd;
    color: #fff;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-order-save:hover:not(:disabled) {
    background: #0b5ed7;
}

.btn-order-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-order-cancel {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    background: #fff;
    color: #495057;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-order-cancel:hover {
    border-color: #dc3545;
    color: #dc3545;
    background: #fff5f5;
}

/* ✅ Подсказка */
.ordering-hint {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    margin-bottom: 16px;
    background: #fff3cd;
    border: 1px solid #ffe69c;
    border-radius: 10px;
    color: #664d03;
    font-size: 13px;
}

.ordering-hint i {
    font-size: 14px;
    color: #ff9800;
}

/* ✅ Контролы (поиск + фильтр) */
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

.aggregator-search input:disabled {
    background: #f8f9fa;
    cursor: not-allowed;
}

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

.filter-switch:hover:not(.disabled) {
    border-color: #0d6efd;
    background: #f8f9ff;
}

.filter-switch.disabled {
    opacity: 0.5;
    cursor: not-allowed;
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

/* ✅ Режим редактирования порядка */
.ordering-mode {
    margin-bottom: 24px;
}

.ordering-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
    gap: 16px !important;
}

.ordering-card {
    position: relative;
    align-items: center;
    padding: 14px;
    gap: 10px;
}

.ordering-card:hover {
    transform: none !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
    border-color: #0d6efd !important;
}

.drag-handle {
    width: 24px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: grab;
    color: #adb5bd;
    border-radius: 6px;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.drag-handle:hover {
    background: #f8f9fa;
    color: #0d6efd;
}

.drag-handle:active {
    cursor: grabbing;
}

.drag-handle i {
    font-size: 14px;
}

.ordering-number {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #e7f1ff;
    color: #0d6efd;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ordering-arrows {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex-shrink: 0;
}

.arrow-btn {
    width: 26px;
    height: 26px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    transition: all 0.15s ease;
}

.arrow-btn:hover:not(:disabled) {
    background: #e7f1ff;
    border-color: #0d6efd;
    color: #0d6efd;
}

.arrow-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

/* ✅ Анимация drag */
.ghost-card {
    opacity: 0.4;
    background: #e7f1ff !important;
    border-color: #0d6efd !important;
    border-style: dashed !important;
}

/* ✅ Мобильная адаптация для режима редактирования */
@media (max-width: 768px) {
    .ordering-grid {
        grid-template-columns: 1fr !important;
    }

    .ordering-card {
        padding: 12px;
        gap: 8px;
    }

    .drag-handle {
        width: 20px;
        height: 32px;
    }

    .ordering-number {
        width: 24px;
        height: 24px;
        font-size: 10px;
    }

    .arrow-btn {
        width: 24px;
        height: 24px;
    }
}

/* ✅ Планшеты — 2-3 колонки */
@media (min-width: 769px) and (max-width: 1024px) {
    .ordering-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)) !important;
    }
}
</style>
