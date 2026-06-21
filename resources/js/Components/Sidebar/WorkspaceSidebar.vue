<template>
    <div class="workspace-sidebar">
        <!-- Header с переключателем -->
        <div class="sidebar-header">
            <div class="segmented-control">
                <button
                    type="button"
                    class="segment-btn"
                    :class="{ active: activeTab === 'collections' }"
                    @click="activeTab = 'collections'"
                >
                    <i class="fa-solid fa-folder"></i>
                    <span class="segment-label">Коллекции</span>
                    <span class="segment-count">{{ store.collections.length }}</span>
                </button>
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
            </div>
        </div>

        <!-- Контент: Коллекции -->
        <Transition name="tab-fade" mode="out-in">
            <div v-if="activeTab === 'collections'" key="collections" class="sidebar-content">
                <!-- Все товары -->
                <div
                    class="sidebar-item"
                    :class="{ active: !selectedCollection }"
                    @click="selectCollection(null)"
                >
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span class="item-name">Все товары</span>
                    <span class="item-count">{{ store.products.length }}</span>
                </div>

                <!-- Коллекции -->
                <div
                    v-for="collection in store.collections"
                    :key="collection.id"
                    class="sidebar-item"
                    :class="{ active: selectedCollection?.id === collection.id }"
                    @click="selectCollection(collection)"
                >
                    <i class="fa-solid fa-folder"></i>
                    <span class="item-name">{{ collection.name }}</span>
                    <span class="item-count">{{ collection.products_count || 0 }}</span>

                    <button
                        type="button"
                        class="btn-delete"
                        @click.stop="confirmDeleteCollection(collection)"
                        title="Удалить коллекцию"
                    >
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>

                <div v-if="store.collections.length === 0" class="empty-hint">
                    <i class="fa-solid fa-folder-plus"></i>
                    <p>Нет коллекций</p>
                    <small>Создайте первую коллекцию</small>
                </div>

                <!-- Кнопка создания -->
                <div class="sidebar-footer">
                    <button
                        type="button"
                        class="btn-create"
                        @click="showCreateCollectionModal = true"
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

                <!-- Кнопка создания -->
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

        <!-- Модалка создания коллекции -->
        <div v-if="showCreateCollectionModal" class="modal-overlay" @click="showCreateCollectionModal = false">
            <div class="modal-content" @click.stop>
                <h6>Создать коллекцию</h6>

                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input
                        v-model="newCollection.name"
                        type="text"
                        class="form-input"
                        placeholder="Например: Хиты продаж"
                        @keyup.enter="createCollection"
                        ref="collectionNameInput"
                    />
                </div>

                <div class="form-group">
                    <label class="form-label">Описание (необязательно)</label>
                    <textarea
                        v-model="newCollection.description"
                        class="form-input"
                        placeholder="Описание коллекции..."
                        rows="3"
                    ></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" @click="showCreateCollectionModal = false">
                        Отмена
                    </button>
                    <button
                        type="button"
                        class="btn-save"
                        @click="createCollection"
                        :disabled="!newCollection.name.trim()"
                    >
                        Создать
                    </button>
                </div>
            </div>
        </div>

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

export default {
    name: 'WorkspaceSidebar',

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
            activeTab: 'collections',

            // Collections
            showCreateCollectionModal: false,
            showDeleteCollectionModal: false,
            collectionToDelete: null,
            isDeletingCollection: false,
            newCollection: {
                name: '',
                description: ''
            },

            // Categories
            showDeleteCategoryModal: false,
            categoryToDelete: null,
            isDeletingCategory: false
        }
    },

    watch: {
        showCreateCollectionModal(val) {
            if (val) {
                this.$nextTick(() => {
                    this.$refs.collectionNameInput?.focus()
                })
            }
        }
    },

    methods: {
        // === Collections ===
        selectCollection(collection) {
            this.$emit('select-collection', collection)
        },

        async createCollection() {
            if (!this.newCollection.name.trim()) return

            try {
                await this.store.saveCollection({
                    name: this.newCollection.name,
                    description: this.newCollection.description
                })

                this.showCreateCollectionModal = false
                this.newCollection = { name: '', description: '' }
            } catch (error) {
                console.error('Failed to create collection:', error)
                alert('Ошибка при создании коллекции')
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
            } catch (error) {
                console.error('Failed to delete collection:', error)
                alert('Ошибка при удалении коллекции')
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
                alert(message)
            } finally {
                this.isDeletingCategory = false
            }
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
    position: relative;
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

.segment-label {
    font-weight: 500;
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
    transition: all 0.2s ease;
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

/* === Items === */
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
.item-actions,
.btn-delete {
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.15s ease;
    flex-shrink: 0;
}

.sidebar-item:hover .item-actions,
.sidebar-item:hover .btn-delete {
    opacity: 1;
}

.sidebar-item:hover .item-count {
    display: none;
}

.btn-action,
.btn-delete {
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

.btn-action.danger:hover,
.btn-delete:hover {
    background: #f8d7da;
    color: #dc3545;
}

.btn-action i,
.btn-delete i {
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

.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #495057;
    margin-bottom: 6px;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    color: #212529;
    background: #fff;
    transition: all 0.15s ease;
    outline: none;
    font-family: inherit;
}

.form-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

textarea.form-input {
    resize: vertical;
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

.btn-save {
    padding: 8px 20px;
    border: none;
    border-radius: 8px;
    background: #0d6efd;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-save:hover:not(:disabled) {
    background: #0b5ed7;
}

.btn-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
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
</style>
