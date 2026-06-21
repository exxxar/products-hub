<template>
    <div class="categories-sidebar">
        <div class="sidebar-header">
            <h6 class="sidebar-title">
                <i class="fa-solid fa-tags"></i>
                Категории
            </h6>
            <button
                type="button"
                class="btn-create"
                @click="$emit('create-category')"
                title="Создать категорию"
            >
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        <div class="sidebar-content">
            <div
                v-for="category in store.categories"
                :key="category.id"
                class="category-item"
                :class="{ active: selectedCategory?.id === category.id }"
                @click="$emit('select-category', category)"
            >
                <i class="fa-solid fa-tag"></i>
                <span class="category-name">{{ category.name }}</span>
                <span class="category-count">{{ category.products_count || 0 }}</span>

                <!-- Действия при hover -->
                <div class="category-actions">
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
                        @click.stop="confirmDelete(category)"
                        title="Удалить"
                    >
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>

            <div v-if="store.categories.length === 0" class="empty-hint">
                <small>Нет категорий</small>
            </div>
        </div>

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
        <!-- Подтверждение удаления -->
        <div v-if="showDeleteModal" class="modal-overlay" @click="showDeleteModal = false">
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
                    <button type="button" class="btn-cancel" @click="showDeleteModal = false">
                        Отмена
                    </button>
                    <button
                        type="button"
                        class="btn-delete"
                        @click="deleteCategory"
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

export default {
    name: 'CategoriesSidebar',

    emits: ['select-category', 'create-category', 'edit-category'],

    props: {
        selectedCategory: {
            type: Object,
            default: null
        }
    },

    data() {
        return {
            store: useWorkspaceStore(),
            showDeleteModal: false,
            categoryToDelete: null,
            isDeleting: false
        }
    },

    methods: {
        confirmDelete(category) {
            this.categoryToDelete = category
            this.showDeleteModal = true
        },

        async deleteCategory() {
            if (!this.categoryToDelete) return

            this.isDeleting = true

            try {
                await this.store.deleteCategory(this.categoryToDelete.id)

                // Перезагружаем список категорий
                await this.store.loadCategories()

                this.showDeleteModal = false
                this.categoryToDelete = null
            } catch (error) {
                console.error('Failed to delete category:', error)
                const message = error.response?.data?.message || 'Ошибка при удалении'
                alert(message)
            } finally {
                this.isDeleting = false
            }
        }
    }
}
</script>

<style scoped>
.categories-sidebar {
    width: 260px;
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
    color: #0d6efd;
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

.sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}

.category-item {
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

.category-item:hover {
    background: #f8f9fa;
}

.category-item.active {
    background: #e7f1ff;
    color: #0d6efd;
}

.category-item i.fa-tag {
    font-size: 14px;
    color: #6c757d;
}

.category-item.active i.fa-tag {
    color: #0d6efd;
}

.category-name {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.category-count {
    font-size: 12px;
    color: #6c757d;
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 10px;
}

.category-item.active .category-count {
    background: #0d6efd;
    color: #fff;
}

/* === Actions (появляются при hover) === */
.category-actions {
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.category-item:hover .category-actions {
    opacity: 1;
}

.category-item:hover .category-count {
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

.empty-hint {
    text-align: center;
    padding: 20px;
    color: #adb5bd;
}

/* === Modal === */
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
    max-width: 400px;
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
    cursor: pointer;
}

.btn-delete:hover:not(:disabled) {
    background: #bb2d3b;
}

.btn-delete:disabled {
    opacity: 0.5;
    cursor: not-allowed;
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
