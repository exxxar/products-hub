<template>
    <div class="collections-sidebar">
        <div class="sidebar-header">
            <h6 class="sidebar-title">
                <i class="fa-solid fa-box-open"></i>
                Коллекции
            </h6>
            <button
                type="button"
                class="btn-create"
                @click="openCreateModal"
                title="Создать коллекцию"
            >
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        <!-- Статистика -->
        <div class="sidebar-stats">
            <div class="stat-item">
                <span class="stat-label">Всего</span>
                <span class="stat-value">{{ store.collections.length }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Активных</span>
                <span class="stat-value active">{{ store.activeCollections.length }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Стоп</span>
                <span class="stat-value stop">{{ store.stopListCollections.length }}</span>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="sidebar-filters">
            <button
                class="filter-btn"
                :class="{ active: filter === 'all' }"
                @click="filter = 'all'"
            >
                Все
            </button>
            <button
                class="filter-btn"
                :class="{ active: filter === 'active' }"
                @click="filter = 'active'"
            >
                Активные
            </button>
            <button
                class="filter-btn"
                :class="{ active: filter === 'stop' }"
                @click="filter = 'stop'"
            >
                Стоп
            </button>
        </div>

        <div class="sidebar-content">
            <!-- Все товары -->
            <div
                class="collection-card all-products"
                :class="{ active: !selectedCollection }"
                @click="selectCollection(null)"
            >
                <div class="card-image">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <div class="card-info">
                    <div class="card-name">Все товары</div>
                    <div class="card-meta">
                        <span class="meta-count">{{ store.products.length }} товаров</span>
                    </div>
                </div>
            </div>

            <!-- Коллекции -->
            <div
                v-for="collection in filteredCollections"
                :key="collection.id"
                class="collection-card"
                :class="{
                    active: selectedCollection?.id === collection.id,
                    'in-stop-list': collection.in_stop_list,
                    'inactive': !collection.is_active
                }"
                @click="selectCollection(collection)"
            >
                <!-- Изображение -->
                <div class="card-image">
                    <img
                        v-if="collection.images && collection.images.length > 0"
                        :src="collection.images[0].url"
                        :alt="collection.name"
                    />
                    <div v-else class="image-placeholder">
                        <i class="fa-solid fa-box-open"></i>
                    </div>

                    <!-- Бейджи -->
                    <div class="card-badges">
                        <div v-if="collection.in_stop_list" class="badge badge-stop">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                        <div
                            v-if="collection.discount_percent > 0"
                            class="badge badge-discount"
                        >
                            -{{ collection.discount_percent }}%
                        </div>
                    </div>
                </div>

                <!-- Информация -->
                <div class="card-info">
                    <div class="card-name">{{ collection.name }}</div>

                    <div v-if="collection.short_description" class="card-description">
                        {{ collection.short_description }}
                    </div>

                    <div class="card-meta">
                        <span class="meta-type">
                            <i :class="getTypeIcon(collection.type)"></i>
                            {{ collection.type_label }}
                        </span>
                        <span class="meta-count">{{ collection.products_count }} товаров</span>
                    </div>

                    <!-- Цена -->
                    <div class="card-price">
                        <span
                            v-if="collection.old_price && collection.old_price > collection.price"
                            class="old-price"
                        >
                            {{ formatPrice(collection.old_price) }}
                        </span>
                        <span class="current-price">
                            {{ formatPrice(collection.price) }}
                        </span>
                    </div>
                </div>

                <!-- Кнопки действий -->
                <div class="card-actions" @click.stop>
                    <button
                        type="button"
                        class="action-btn"
                        @click="toggleStopList(collection)"
                        :title="collection.in_stop_list ? 'Убрать из стоп-листа' : 'В стоп-лист'"
                        :class="{ active: collection.in_stop_list }"
                    >
                        <i class="fa-solid" :class="collection.in_stop_list ? 'fa-circle-check' : 'fa-ban'"></i>
                    </button>
                    <button
                        type="button"
                        class="action-btn"
                        @click="openEditModal(collection)"
                        title="Редактировать"
                    >
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button
                        type="button"
                        class="action-btn delete"
                        @click="confirmDeleteCollection(collection)"
                        title="Удалить"
                    >
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-if="filteredCollections.length === 0" class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <p v-if="filter === 'all'">Нет коллекций</p>
                <p v-else>Нет коллекций в этой категории</p>
                <button
                    v-if="filter !== 'all'"
                    type="button"
                    class="btn-reset-filter"
                    @click="filter = 'all'"
                >
                    Показать все
                </button>
            </div>
        </div>

        <!-- Модалка создания/редактирования -->
        <CollectionFormModal
            ref="collectionFormModal"
            @saved="onCollectionSaved"
        />

        <!-- Модалка подтверждения удаления -->
        <div v-if="showDeleteModal" class="modal-overlay" @click="cancelDelete">
            <div class="modal-content" @click.stop>
                <h6 class="delete-title">
                    <i class="fa-solid fa-triangle-exclamation text-danger"></i>
                    Удалить коллекцию?
                </h6>

                <p class="delete-description">
                    Вы уверены, что хотите удалить коллекцию
                    <strong>"{{ collectionToDelete?.name }}"</strong>?
                </p>

                <p class="delete-warning">
                    <i class="fa-solid fa-circle-info"></i>
                    Товары из коллекции не будут удалены, только связь с коллекцией.
                </p>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" @click="cancelDelete">
                        Отмена
                    </button>
                    <button
                        type="button"
                        class="btn-delete"
                        @click="deleteCollection"
                        :disabled="isDeleting"
                    >
                        <i v-if="isDeleting" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-trash"></i>
                        {{ isDeleting ? 'Удаление...' : 'Удалить' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'
import CollectionFormModal from './CollectionFormModal.vue'

export default {
    name: 'CollectionsSidebar',

    components: {
        CollectionFormModal
    },

    emits: ['select-collection'],

    data() {
        return {
            store: useWorkspaceStore(),
            selectedCollection: null,
            filter: 'all',
            showDeleteModal: false,
            collectionToDelete: null,
            isDeleting: false,
        }
    },

    computed: {
        filteredCollections() {
            switch (this.filter) {
                case 'active':
                    return this.store.activeCollections
                case 'stop':
                    return this.store.stopListCollections
                default:
                    return this.store.collections
            }
        }
    },

    methods: {
        async selectCollection(collection) {
            this.selectedCollection = collection
            await this.store.selectCollection(collection)
            this.$emit('select-collection', collection)
        },

        openCreateModal() {
            this.$refs.collectionFormModal.show()
        },

        openEditModal(collection) {
            this.$refs.collectionFormModal.show(collection)
        },

        async onCollectionSaved(collection) {
            await this.store.loadCollections()
        },

        async toggleStopList(collection) {
            try {
                const result = await this.store.toggleCollectionStopList(collection.id)

                if (result.success) {
                    this.$notify?.success({
                        title: result.in_stop_list ? 'Добавлено в стоп-лист' : 'Убрано из стоп-листа',
                        message: collection.name
                    })
                }
            } catch (error) {
                this.$notify?.error('Ошибка при изменении статуса')
            }
        },

        confirmDeleteCollection(collection) {
            this.collectionToDelete = collection
            this.showDeleteModal = true
        },

        cancelDelete() {
            this.showDeleteModal = false
            this.collectionToDelete = null
        },

        async deleteCollection() {
            if (!this.collectionToDelete) return

            this.isDeleting = true

            try {
                await this.store.deleteCollection(this.collectionToDelete.id)

                if (this.selectedCollection?.id === this.collectionToDelete.id) {
                    this.selectedCollection = null
                    this.$emit('select-collection', null)
                }

                this.showDeleteModal = false
                this.collectionToDelete = null

                this.$notify?.success('Коллекция удалена')
            } catch (error) {
                console.error('Failed to delete collection:', error)
                this.$notify?.error('Ошибка при удалении')
            } finally {
                this.isDeleting = false
            }
        },

        getTypeIcon(type) {
            const icons = {
                manual: 'fa-solid fa-hand-pointer',
                category_all: 'fa-solid fa-folder-open',
                categories_all: 'fa-solid fa-folder-tree',
                workspace_all: 'fa-solid fa-boxes-stacked',
                category_select: 'fa-solid fa-list-check',
            }
            return icons[type] || 'fa-solid fa-box'
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
        }
    }
}
</script>

<style scoped>
.collections-sidebar {
    width: 320px;
    background: #fff;
    border-right: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid #e9ecef;
}

.sidebar-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 600;
    color: #212529;
    margin: 0;
}

.sidebar-title i {
    color: #6f42c1;
}

.btn-create {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: #e7f1ff;
    color: #0d6efd;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.btn-create:hover {
    background: #0d6efd;
    color: #fff;
}

/* === Статистика === */
.sidebar-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 12px 16px;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.stat-label {
    font-size: 11px;
    color: #6c757d;
}

.stat-value {
    font-size: 16px;
    font-weight: 700;
    color: #212529;
}

.stat-value.active {
    color: #198754;
}

.stat-value.stop {
    color: #dc3545;
}

/* === Фильтры === */
.sidebar-filters {
    display: flex;
    gap: 6px;
    padding: 12px 16px;
    border-bottom: 1px solid #e9ecef;
}

.filter-btn {
    flex: 1;
    padding: 6px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: #fff;
    color: #6c757d;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.filter-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
}

.filter-btn.active {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

/* === Контент === */
.sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}

/* === Карточка коллекции === */
.collection-card {
    display: flex;
    gap: 12px;
    padding: 12px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.collection-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
    transform: translateY(-1px);
}

.collection-card.active {
    border-color: #0d6efd;
    background: #f8f9ff;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
}

.collection-card.in-stop-list {
    border-color: #f5c2c7;
    background: linear-gradient(to bottom, #fff5f5 0%, #fff 100%);
}

.collection-card.inactive {
    opacity: 0.6;
}

/* === Изображение === */
.card-image {
    width: 70px;
    height: 70px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-image i {
    font-size: 24px;
    color: #adb5bd;
}

.all-products .card-image {
    background: linear-gradient(135deg, #e7f1ff 0%, #cfe2ff 100%);
}

.all-products .card-image i {
    color: #0d6efd;
    font-size: 28px;
}

/* === Бейджи === */
.card-badges {
    position: absolute;
    top: 4px;
    right: 4px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.badge {
    padding: 2px 5px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 600;
    line-height: 1;
}

.badge-stop {
    background: #dc3545;
    color: #fff;
    padding: 3px;
}

.badge-discount {
    background: #fd7e14;
    color: #fff;
}

/* === Информация === */
.card-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.card-name {
    font-size: 14px;
    font-weight: 600;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.card-description {
    font-size: 11px;
    color: #6c757d;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.card-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    color: #6c757d;
}

.meta-type {
    display: flex;
    align-items: center;
    gap: 4px;
}

.meta-type i {
    font-size: 10px;
    color: #0d6efd;
}

.meta-count {
    color: #0d6efd;
    font-weight: 500;
}

/* === Цена === */
.card-price {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 2px;
}

.current-price {
    font-size: 15px;
    font-weight: 700;
    color: #212529;
}

.old-price {
    font-size: 12px;
    color: #adb5bd;
    text-decoration: line-through;
}

/* === Кнопки действий === */
.card-actions {
    display: flex;
    flex-direction: column;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.collection-card:hover .card-actions {
    opacity: 1;
}

.action-btn {
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
    font-size: 11px;
    transition: all 0.15s ease;
}

.action-btn:hover {
    background: #e7f1ff;
    border-color: #0d6efd;
    color: #0d6efd;
}

.action-btn.active {
    background: #f8d7da;
    border-color: #dc3545;
    color: #dc3545;
}

.action-btn.delete:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

/* === Пустое состояние === */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: #adb5bd;
}

.empty-state i {
    font-size: 36px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.empty-state p {
    margin: 0 0 12px 0;
    font-size: 13px;
    color: #6c757d;
}

.btn-reset-filter {
    padding: 6px 14px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: #fff;
    color: #0d6efd;
    font-size: 12px;
    cursor: pointer;
}

.btn-reset-filter:hover {
    background: #e7f1ff;
}

/* === Модалка удаления === */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.delete-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 16px 0;
}

.delete-title i {
    color: #dc3545;
}

.delete-description {
    font-size: 14px;
    color: #495057;
    margin: 0 0 12px 0;
}

.delete-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #6c757d;
    background: #e7f1ff;
    padding: 12px;
    border-radius: 8px;
    margin: 0 0 20px 0;
}

.delete-warning i {
    color: #0d6efd;
    flex-shrink: 0;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.btn-cancel {
    padding: 8px 16px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.btn-cancel:hover {
    background: #f8f9fa;
}

.btn-delete {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    border: none;
    border-radius: 8px;
    background: #dc3545;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.btn-delete:hover:not(:disabled) {
    background: #bb2d3b;
}

.btn-delete:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* === Responsive === */
@media (max-width: 768px) {
    .collections-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e9ecef;
        max-height: 400px;
    }

    .card-actions {
        opacity: 1;
    }
}
</style>
