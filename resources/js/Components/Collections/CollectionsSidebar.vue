<template>
    <div class="collections-sidebar">
        <div class="sidebar-header">
            <h6 class="sidebar-title">
                <i class="fa-solid fa-folder"></i>
                Коллекции
            </h6>
            <button
                type="button"
                class="btn-create"
                @click="showCreateModal = true"
                title="Создать коллекцию"
            >
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        <div class="sidebar-content">
            <!-- Все товары -->
            <div
                class="collection-item"
                :class="{ active: !selectedCollection }"
                @click="selectCollection(null)"
            >
                <i class="fa-solid fa-boxes-stacked"></i>
                <span class="collection-name">Все товары</span>
                <span class="collection-count">{{ store.products.length }}</span>
            </div>

            <!-- Коллекции -->
            <div
                v-for="collection in store.collections"
                :key="collection.id"
                class="collection-item"
                :class="{ active: selectedCollection?.id === collection.id }"
                @click="selectCollection(collection)"
            >
                <i class="fa-solid fa-folder"></i>
                <span class="collection-name">{{ collection.name }}</span>
                <span class="collection-count">{{ collection.products_count || 0 }}</span>

                <!-- Кнопка удаления (появляется при hover) -->
                <button
                    type="button"
                    class="btn-delete-collection"
                    @click.stop.prevent="confirmDeleteCollection(collection)"
                    title="Удалить коллекцию"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>

            <div v-if="store.collections.length === 0" class="empty-hint">
                <small>Нет коллекций</small>
            </div>
        </div>

        <!-- Модалка создания коллекции -->
        <div v-if="showCreateModal" class="modal-overlay" @click="showCreateModal = false">
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
                    <button type="button" class="btn-cancel" @click="showCreateModal = false">
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

export default {
    name: 'CollectionsSidebar',

    emits: ['select-collection'],

    data() {
        return {
            store: useWorkspaceStore(),
            selectedCollection: null,
            showCreateModal: false,
            showDeleteModal: false,
            collectionToDelete: null,
            isDeleting: false,
            newCollection: {
                name: '',
                description: ''
            }
        }
    },

    methods: {
        selectCollection(collection) {
            this.selectedCollection = collection
            this.$emit('select-collection', collection)
        },

        async createCollection() {
            if (!this.newCollection.name.trim()) return

            try {
                await this.store.saveCollection({
                    name: this.newCollection.name,
                    description: this.newCollection.description
                })

                this.showCreateModal = false
                this.newCollection = { name: '', description: '' }
            } catch (error) {
                console.error('Failed to create collection:', error)
                alert('Ошибка при создании коллекции')
            }
        },

        confirmDeleteCollection(collection) {
            // Останавливаем всплытие события
            event.stopPropagation()
            event.preventDefault()

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

                // Обновляем список коллекций
                await this.store.loadCollections()

                // Если удалили выбранную коллекцию - сбрасываем выбор
                if (this.selectedCollection?.id === this.collectionToDelete.id) {
                    this.selectedCollection = null
                    this.$emit('select-collection', null)
                }

                this.showDeleteModal = false
                this.collectionToDelete = null
            } catch (error) {
                console.error('Failed to delete collection:', error)
                alert('Ошибка при удалении коллекции')
            } finally {
                this.isDeleting = false
            }
        }
    }
}
</script>

<style scoped>
.collections-sidebar {
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

.collection-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
    margin-bottom: 4px;
}

.collection-item:hover {
    background: #f8f9fa;
}

.collection-item.active {
    background: #e7f1ff;
    color: #0d6efd;
}

.collection-item i {
    font-size: 14px;
    color: #6c757d;
}

.collection-item.active i {
    color: #0d6efd;
}

.collection-name {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.collection-count {
    font-size: 12px;
    color: #6c757d;
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 10px;
}

.collection-item.active .collection-count {
    background: #0d6efd;
    color: #fff;
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
    max-width: 500px;
    width: 90%;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.modal-content h6 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 20px 0;
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

/* === Responsive === */
@media (max-width: 768px) {
    .collections-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e9ecef;
        max-height: 300px;
    }
}

/* === Кнопка удаления коллекции === */
.btn-delete-collection {
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #adb5bd;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.collection-item:hover .btn-delete-collection {
    opacity: 1;
}

.btn-delete-collection:hover {
    background: #f8d7da;
    color: #dc3545;
}

.btn-delete-collection i {
    font-size: 12px;
}

/* === Модалка удаления === */
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
    transition: all 0.15s ease;
}

.btn-delete:hover:not(:disabled) {
    background: #bb2d3b;
}

.btn-delete:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
