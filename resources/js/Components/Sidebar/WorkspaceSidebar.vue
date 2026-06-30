<template>
    <div class="workspace-sidebar">
        <!-- Header с переключателем -->
        <div class="sidebar-header">
            <div class="segmented-control">
                <button
                    type="button"
                    class="segment-btn"
                    :class="{ active: activeTab === 'categories' }"
                    @click="activeTab = 'categories'"
                >
                    <i class="fa-solid fa-tags"></i>
                    <span class="segment-label">Категории</span>
                    <span class="segment-count">{{ store.categories.length }}</span>
                </button>
                <button
                    type="button"
                    class="segment-btn"
                    :class="{ active: activeTab === 'collections' }"
                    @click="activeTab = 'collections'"
                >
                    <i class="fa-solid fa-box-open"></i>
                    <span class="segment-label">Коллекции</span>
                    <span class="segment-count">{{ store.collections.length }}</span>
                </button>
            </div>
        </div>

        <!-- Контент: Коллекции -->
        <Transition name="tab-fade" mode="out-in">
            <div v-if="activeTab === 'collections'" key="collections" class="sidebar-content">
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
                    v-for="collection in store.collections"
                    :key="collection.id"
                    class="collection-card"
                    :class="{
                        active: selectedCollection?.id === collection.id,
                        'in-stop-list': collection.in_stop_list,
                        inactive: !collection.is_active
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

                        <div class="card-meta">
                            <span class="meta-type" :title="collection.type_label">
                                <i :class="getTypeIcon(collection.type)"></i>
                                <span class="type-text">{{ collection.products_count }} шт.</span>
                            </span>
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
                            @click="openEditCollection(collection)"
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
                <div v-if="store.collections.length === 0" class="empty-hint">
                    <i class="fa-solid fa-box-open"></i>
                    <p>Нет коллекций</p>
                    <small>Создайте первую коллекцию-пакет</small>
                </div>

                <!-- Кнопка создания -->
                <div class="sidebar-footer">
                    <button
                        type="button"
                        class="btn-create"
                        @click="openCreateCollection"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Новая коллекция
                    </button>
                </div>
            </div>

            <!-- Контент: Категории -->
            <div v-else key="categories" class="sidebar-content">
                <div
                    v-for="category in store.categories"
                    :key="category.id"
                    class="sidebar-item"
                    :class="{ active: selectedCategory?.id === category.id }"
                    @click="selectCategory(category)"
                >
                    <i class="fa-solid fa-tag"></i>
                    <span class="item-name">{{ category.name }}</span>
                    <span class="item-count">{{ category.products_count || 0 }}</span>

                    <div class="item-actions">
                        <button
                            type="button"
                            class="btn-action"
                            @click.stop="$emit('edit-category', category)"
                            title="Редактировать"
                        >
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button
                            type="button"
                            class="btn-action danger"
                            @click.stop="confirmDeleteCategory(category)"
                            title="Удалить"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div v-if="store.categories.length === 0" class="empty-hint">
                    <i class="fa-solid fa-tags"></i>
                    <p>Нет категорий</p>
                    <small>Создайте первую категорию</small>
                </div>

                <!-- Кнопки создания -->
                <div class="sidebar-footer">
                    <button
                        type="button"
                        class="btn-preset"
                        @click="$emit('open-presets')"
                    >
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        Использовать шаблон
                    </button>

                    <button
                        type="button"
                        class="btn-create"
                        @click="$emit('create-category')"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Новая категория
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Модалка создания/редактирования коллекции -->
        <CollectionFormModal
            ref="collectionFormModal"
            @saved="onCollectionSaved"
        />

        <!-- Модалка подтверждения удаления коллекции -->
        <div v-if="showDeleteCollectionModal" class="modal-overlay" @click="showDeleteCollectionModal = false">
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
                    Товары не будут удалены, только отвязаны от коллекции.
                </p>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" @click="showDeleteCollectionModal = false">
                        Отмена
                    </button>
                    <button
                        type="button"
                        class="btn-delete-confirm"
                        @click="deleteCollection"
                        :disabled="isDeletingCollection"
                    >
                        <i v-if="isDeletingCollection" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-trash"></i>
                        {{ isDeletingCollection ? 'Удаление...' : 'Удалить' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Модалка подтверждения удаления категории -->
        <div v-if="showDeleteCategoryModal" class="modal-overlay" @click="showDeleteCategoryModal = false">
            <div class="modal-content" @click.stop>
                <h6 class="delete-title">
                    <i class="fa-solid fa-triangle-exclamation text-danger"></i>
                    Удалить категорию?
                </h6>

                <p class="delete-description">
                    Вы уверены, что хотите удалить категорию
                    <strong>"{{ categoryToDelete?.name }}"</strong>?
                </p>

                <p class="delete-warning">
                    <i class="fa-solid fa-circle-info"></i>
                    Товары не будут удалены, только отвязаны от категории.
                </p>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" @click="showDeleteCategoryModal = false">
                        Отмена
                    </button>
                    <button
                        type="button"
                        class="btn-delete-confirm"
                        @click="deleteCategory"
                        :disabled="isDeletingCategory"
                    >
                        <i v-if="isDeletingCategory" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-trash"></i>
                        {{ isDeletingCategory ? 'Удаление...' : 'Удалить' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'
import CollectionFormModal from '@/Components/Collections/CollectionFormModal.vue'

export default {
    name: 'WorkspaceSidebar',

    components: {
        CollectionFormModal
    },

    emits: [
        'select-collection',
        'select-category',
        'create-category',
        'edit-category'
    ],

    props: {
        selectedCollection: {
            type: Object,
            default: null
        },
        selectedCategory: {
            type: Object,
            default: null
        }
    },

    data() {
        return {
            store: useWorkspaceStore(),
            activeTab: 'categories',

            // Collections
            showDeleteCollectionModal: false,
            collectionToDelete: null,
            isDeletingCollection: false,

            // Categories
            showDeleteCategoryModal: false,
            categoryToDelete: null,
            isDeletingCategory: false
        }
    },

    methods: {
        // === Collections ===
        selectCollection(collection) {
            this.$emit('select-collection', collection)
        },

        openCreateCollection() {
            this.$refs.collectionFormModal.show()
        },

        openEditCollection(collection) {
            this.$refs.collectionFormModal.show(collection)
        },

        async onCollectionSaved() {
            await this.store.loadCollections()
        },

        async toggleStopList(collection) {
            try {
                const result = await this.store.toggleCollectionStopList(collection.id)

                if (result?.success) {
                    this.$notify?.success({
                        title: result.in_stop_list ? 'Добавлено в стоп-лист' : 'Убрано из стоп-листа',
                        message: collection.name
                    })
                }
            } catch (error) {
                console.error('Toggle stop list failed:', error)
                this.$notify?.error('Ошибка при изменении статуса')
            }
        },

        confirmDeleteCollection(collection) {
            this.collectionToDelete = collection
            this.showDeleteCollectionModal = true
        },

        async deleteCollection() {
            if (!this.collectionToDelete) return

            this.isDeletingCollection = true

            try {
                await this.store.deleteCollection(this.collectionToDelete.id)
                await this.store.loadCollections()

                if (this.selectedCollection?.id === this.collectionToDelete.id) {
                    this.$emit('select-collection', null)
                }

                this.showDeleteCollectionModal = false
                this.collectionToDelete = null

                this.$notify?.success('Коллекция удалена')
            } catch (error) {
                console.error('Failed to delete collection:', error)
                this.$notify?.error('Ошибка при удалении')
            } finally {
                this.isDeletingCollection = false
            }
        },

        // === Categories ===
        selectCategory(category) {
            this.$emit('select-category', category)
        },

        confirmDeleteCategory(category) {
            this.categoryToDelete = category
            this.showDeleteCategoryModal = true
        },

        async deleteCategory() {
            if (!this.categoryToDelete) return

            this.isDeletingCategory = true

            try {
                await this.store.deleteCategory(this.categoryToDelete.id)
                await this.store.loadCategories()

                if (this.selectedCategory?.id === this.categoryToDelete.id) {
                    this.$emit('select-category', null)
                }

                this.showDeleteCategoryModal = false
                this.categoryToDelete = null
            } catch (error) {
                console.error('Failed to delete category:', error)
                const message = error.response?.data?.message || 'Ошибка при удалении'
                this.$notify?.error(message)
            } finally {
                this.isDeletingCategory = false
            }
        },

        // === Helpers ===
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
            if (price === null || price === undefined) return '0 ₽'
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
        }
    }
}
</script>

<style scoped>
.workspace-sidebar {
    width: 330px;
    background: #fff;
    border-right: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    height: 100%;
    flex-shrink: 0;
}

/* === Header с переключателем === */
.sidebar-header {
    padding: 16px;
    border-bottom: 1px solid #e9ecef;
}

.segmented-control {
    display: flex;
    background: #f1f3f5;
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
}

.segment-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 10px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: #6c757d;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.segment-btn:hover:not(.active) {
    color: #495057;
    background: rgba(255, 255, 255, 0.5);
}

.segment-btn.active {
    background: #fff;
    color: #0d6efd;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.segment-btn i {
    font-size: 13px;
}

.segment-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9px;
    background: #e9ecef;
    color: #6c757d;
    font-size: 11px;
    font-weight: 600;
}

.segment-btn.active .segment-count {
    background: #e7f1ff;
    color: #0d6efd;
}

/* === Content === */
.sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
}

/* === Карточки коллекций === */
.collection-card {
    display: flex;
    gap: 10px;
    padding: 10px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.collection-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
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

.all-products {
    border-style: dashed;
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
}

.all-products:hover {
    border-color: #0d6efd;
    background: linear-gradient(135deg, #e7f1ff 0%, #fff 100%);
}

.all-products .card-image {
    background: linear-gradient(135deg, #e7f1ff 0%, #cfe2ff 100%);
}

.all-products .card-image i {
    color: #0d6efd;
    font-size: 22px;
}

/* === Изображение === */
.card-image {
    width: 56px;
    height: 56px;
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
    font-size: 18px;
    color: #adb5bd;
}

/* === Бейджи === */
.card-badges {
    position: absolute;
    top: 2px;
    right: 2px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.badge {
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 8px;
    font-weight: 700;
    line-height: 1;
}

.badge-stop {
    background: #dc3545;
    color: #fff;
    padding: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.badge-stop i {
    font-size: 8px;
    color: #fff;
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
    gap: 2px;
}

.card-name {
    font-size: 13px;
    font-weight: 600;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.card-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #6c757d;
}

.meta-type {
    display: flex;
    align-items: center;
    gap: 3px;
}

.meta-type i {
    font-size: 10px;
    color: #0d6efd;
}

.type-text {
    color: #0d6efd;
    font-weight: 500;
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
    font-size: 14px;
    font-weight: 700;
    color: #212529;
}

.old-price {
    font-size: 11px;
    color: #adb5bd;
    text-decoration: line-through;
}

/* === Кнопки действий === */
.card-actions {
    display: flex;
    flex-direction: column;
    gap: 3px;
    opacity: 0;
    transition: opacity 0.15s ease;
    flex-shrink: 0;
}

.collection-card:hover .card-actions {
    opacity: 1;
}

.action-btn {
    width: 24px;
    height: 24px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
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

/* === Items (для категорий) === */
.sidebar-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
    margin-bottom: 4px;
    position: relative;
}

.sidebar-item:hover {
    background: #f8f9fa;
}

.sidebar-item.active {
    background: #e7f1ff;
    color: #0d6efd;
}

.sidebar-item > i {
    font-size: 14px;
    color: #6c757d;
    flex-shrink: 0;
}

.sidebar-item.active > i {
    color: #0d6efd;
}

.item-name {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.item-count {
    font-size: 12px;
    color: #6c757d;
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 10px;
    flex-shrink: 0;
}

.sidebar-item.active .item-count {
    background: #0d6efd;
    color: #fff;
}

/* === Actions (появляются при hover) === */
.item-actions {
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.15s ease;
    flex-shrink: 0;
}

.sidebar-item:hover .item-actions {
    opacity: 1;
}

.sidebar-item:hover .item-count {
    display: none;
}

.btn-action {
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.btn-action:hover {
    background: #e7f1ff;
    color: #0d6efd;
}

.btn-action.danger:hover {
    background: #f8d7da;
    color: #dc3545;
}

.btn-action i {
    font-size: 11px;
}

/* === Empty state === */
.empty-hint {
    text-align: center;
    padding: 40px 20px;
    color: #adb5bd;
    margin: auto 0;
}

.empty-hint i {
    font-size: 32px;
    margin-bottom: 12px;
    opacity: 0.4;
    display: block;
}

.empty-hint p {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 500;
    color: #6c757d;
}

.empty-hint small {
    font-size: 12px;
}

/* === Footer === */
.sidebar-footer {
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid #f1f3f5;
}

.btn-create {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border: 1px dashed #dee2e6;
    border-radius: 8px;
    background: transparent;
    color: #6c757d;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-create:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #f8f9fa;
}

.btn-create i {
    font-size: 12px;
}

.btn-preset {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border: 1px solid #6f42c1;
    border-radius: 8px;
    background: transparent;
    color: #6f42c1;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    margin-bottom: 8px;
}

.btn-preset:hover {
    background: #6f42c1;
    color: #fff;
}

/* === Modals === */
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
    animation: fadeIn 0.15s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    max-width: 440px;
    width: 90%;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    animation: slideUp 0.2s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-content h6 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 20px 0;
    color: #212529;
}

.delete-title {
    display: flex;
    align-items: center;
    gap: 10px;
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
    margin-top: 24px;
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
    transition: all 0.15s ease;
}

.btn-cancel:hover {
    background: #f8f9fa;
}

.btn-delete-confirm {
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
    transition: all 0.15s ease;
}

.btn-delete-confirm:hover:not(:disabled) {
    background: #bb2d3b;
}

.btn-delete-confirm:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* === Transitions === */
.tab-fade-enter-active {
    transition: all 0.2s ease-out;
}

.tab-fade-leave-active {
    transition: all 0.15s ease-in;
}

.tab-fade-enter-from {
    opacity: 0;
    transform: translateY(4px);
}

.tab-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

/* === Responsive === */
@media (max-width: 768px) {
    .workspace-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e9ecef;
        max-height: 400px;
    }

    .segment-label {
        display: none;
    }

    .segment-btn {
        padding: 8px;
    }

    .card-actions {
        opacity: 1;
    }
}
</style>
