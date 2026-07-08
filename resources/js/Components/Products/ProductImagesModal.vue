<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="modelValue" class="images-modal-overlay" @click.self="close">
                <div class="images-modal">
                    <!-- Header -->
                    <div class="modal-header-custom">
                        <div class="header-icon-wrapper">
                            <i class="fa-solid fa-images"></i>
                        </div>
                        <div class="header-content">
                            <h5 class="modal-title-custom">Изображения товара</h5>
                            <p class="modal-subtitle">{{ product?.name }}</p>
                        </div>
                        <button type="button" class="btn-close-custom" @click="close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body-custom">
                        <!-- Drop Zone -->
                        <div
                            class="drop-zone"
                            :class="{ 'is-dragging': isDragging, 'is-disabled': isUploading }"
                            @dragover.prevent="onDragOver"
                            @dragleave.prevent="onDragLeave"
                            @drop.prevent="onDrop"
                            @click="triggerFileInput"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                accept="image/*"
                                @change="onFileSelect"
                                style="display: none"
                            />

                            <div class="drop-zone-content">
                                <div class="drop-icon">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                </div>
                                <div class="drop-text">
                                    <strong>Перетащите изображения сюда</strong>
                                    <span>или нажмите для выбора файлов</span>
                                </div>
                                <div class="drop-hint">
                                    <i class="fa-solid fa-circle-info"></i>
                                    JPG, PNG, WebP, GIF · до 5MB · до 10 файлов
                                </div>
                            </div>
                        </div>

                        <!-- Pending files -->
                        <div v-if="pendingFiles.length > 0" class="pending-section">
                            <div class="section-title">
                                <i class="fa-solid fa-clock"></i>
                                <span>Готовы к загрузке ({{ pendingFiles.length }})</span>
                                <button type="button" class="btn-clear-pending" @click="clearPending">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <div class="pending-grid">
                                <div
                                    v-for="(file, index) in pendingFiles"
                                    :key="index"
                                    class="pending-item"
                                >
                                    <img v-lazy="file.preview" :alt="file.name" />
                                    <button
                                        type="button"
                                        class="btn-remove-pending"
                                        @click="removePending(index)"
                                    >
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                    <div class="pending-name">{{ file.name }}</div>
                                    <div class="pending-size">{{ formatSize(file.size) }}</div>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="btn-upload-pending"
                                @click="uploadPending"
                                :disabled="isUploading"
                            >
                                <i v-if="isUploading" class="fa-solid fa-spinner fa-spin"></i>
                                <i v-else class="fa-solid fa-upload"></i>
                                {{ isUploading ? 'Загрузка...' : `Загрузить (${pendingFiles.length})` }}
                            </button>
                        </div>

                        <!-- Existing images -->
                        <div v-if="productImages.length > 0" class="existing-section">
                            <div class="section-title">
                                <i class="fa-solid fa-images"></i>
                                <span>Текущие изображения ({{ productImages.length }})</span>
                            </div>
                            <div class="images-grid">
                                <div
                                    v-for="(image, index) in productImages"
                                    :key="index"
                                    class="image-item"
                                    :class="{ 'is-main': index === 0 }"
                                >
                                    <img v-lazy="image.url" :alt="image.name" />

                                    <div v-if="index === 0" class="main-badge">
                                        <i class="fa-solid fa-star"></i>
                                        Главная
                                    </div>

                                    <div class="image-overlay">
                                        <button
                                            type="button"
                                            class="overlay-btn"
                                            @click="previewImage(image)"
                                            title="Просмотр"
                                        >
                                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                                        </button>
                                        <button
                                            v-if="index > 0"
                                            type="button"
                                            class="overlay-btn"
                                            @click="moveImage(index, index - 1)"
                                            title="Сделать главной"
                                        >
                                            <i class="fa-solid fa-star"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="overlay-btn danger"
                                            @click="confirmDelete(index)"
                                            title="Удалить"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-else-if="pendingFiles.length === 0" class="empty-images">
                            <i class="fa-solid fa-image"></i>
                            <p>Нет изображений</p>
                            <span>Загрузите первое изображение</span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer-custom">
                        <div class="footer-hint">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Первое изображение будет главным</span>
                        </div>
                        <button type="button" class="btn-done" @click="close">
                            Готово
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Fullscreen preview -->
        <Transition name="modal">
            <div v-if="previewingImage" class="preview-overlay" @click="previewingImage = null">
                <img v-lazy="previewingImage.url" :alt="previewingImage.name" class="preview-image" />
                <button type="button" class="preview-close" @click="previewingImage = null">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </Transition>

        <!-- Delete confirm -->
        <ConfirmModal
            v-model:show="showDeleteConfirm"
            title="Удалить изображение?"
            description="Это действие нельзя отменить. Изображение будет удалено с сервера."
            type="danger"
            confirm-text="Удалить"
            @accept="deleteImage"
        />
    </Teleport>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'
import ConfirmModal from '@/Components/Layout/ConfirmModal.vue'

export default {
    name: 'ProductImagesModal',

    components: {
        ConfirmModal
    },

    props: {
        modelValue: {
            type: Boolean,
            default: false
        },
        product: {
            type: Object,
            default: null
        }
    },

    emits: ['update:modelValue'],

    data() {
        return {
            store: useWorkspaceStore(),
            pendingFiles: [],
            isDragging: false,
            isUploading: false,
            showDeleteConfirm: false,
            imageToDeleteIndex: null,
            previewingImage: null,
        }
    },

    computed: {
        productImages() {
            return this.product?.images || []
        }
    },

    watch: {
        // ✅ При открытии модалки — сбрасываем состояние
        modelValue(val) {
            if (val) {
                this.pendingFiles = []
                document.body.style.overflow = 'hidden'
            } else {
                document.body.style.overflow = ''
            }
        }
    },

    methods: {
        close() {
            this.$emit('update:modelValue', false)
        },

        // === File Input ===
        triggerFileInput() {
            if (this.isUploading) return
            this.$refs.fileInput.click()
        },

        onFileSelect(event) {
            const files = Array.from(event.target.files)
            this.addPendingFiles(files)
            event.target.value = ''
        },

        // === Drag & Drop ===
        onDragOver(e) {
            if (this.isUploading) return
            this.isDragging = true
        },

        onDragLeave(e) {
            this.isDragging = false
        },

        onDrop(e) {
            if (this.isUploading) return
            this.isDragging = false

            const files = Array.from(e.dataTransfer.files).filter(f =>
                f.type.startsWith('image/')
            )
            this.addPendingFiles(files)
        },

        addPendingFiles(files) {
            const validFiles = files.filter(file => {
                if (!file.type.startsWith('image/')) {
                    this.$notify?.error(`${file.name} не является изображением`)
                    return false
                }
                if (file.size > 5 * 1024 * 1024) {
                    this.$notify?.error(`${file.name} больше 5MB`)
                    return false
                }
                return true
            })

            const remaining = 10 - this.productImages.length - this.pendingFiles.length
            if (remaining <= 0) {
                this.$notify?.error('Достигнут лимит изображений (10)')
                return
            }

            const toAdd = validFiles.slice(0, remaining)

            toAdd.forEach(file => {
                const reader = new FileReader()
                reader.onload = (e) => {
                    this.pendingFiles.push({
                        file,
                        preview: e.target.result,
                        name: file.name,
                        size: file.size,
                    })
                }
                reader.readAsDataURL(file)
            })
        },

        removePending(index) {
            this.pendingFiles.splice(index, 1)
        },

        clearPending() {
            this.pendingFiles = []
        },

        // === Upload ===
        async uploadPending() {
            if (this.pendingFiles.length === 0 || this.isUploading) return

            this.isUploading = true

            try {
                const files = this.pendingFiles.map(p => p.file)
                await this.store.uploadProductImages(this.product.id, files)

                this.$notify?.success({
                    title: 'Изображения загружены',
                    message: `Загружено ${files.length} ${this.pluralize(files.length, 'файл', 'файла', 'файлов')}`
                })

                this.pendingFiles = []
            } catch (error) {
                this.$notify?.error('Ошибка при загрузке')
            } finally {
                this.isUploading = false
            }
        },

        // === Delete ===
        confirmDelete(index) {
            this.imageToDeleteIndex = index
            this.showDeleteConfirm = true
        },

        async deleteImage() {
            if (this.imageToDeleteIndex === null) return

            try {
                await this.store.deleteProductImage(this.product.id, this.imageToDeleteIndex)
                this.$notify?.success('Изображение удалено')
                this.showDeleteConfirm = false
                this.imageToDeleteIndex = null
            } catch (error) {
                this.$notify?.error('Ошибка при удалении')
            }
        },

        // === Reorder ===
        async moveImage(fromIndex, toIndex) {
            const order = this.productImages.map((_, i) => i)
            const [item] = order.splice(fromIndex, 1)
            order.splice(toIndex, 0, item)

            try {
                await this.store.reorderProductImages(this.product.id, order)
                this.$notify?.success('Порядок изменён')
            } catch (error) {
                this.$notify?.error('Ошибка при изменении порядка')
            }
        },

        // === Preview ===
        previewImage(image) {
            this.previewingImage = image
        },

        // === Helpers ===
        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B'
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
        },

        pluralize(count, one, two, five) {
            let n = Math.abs(count) % 100
            if (n >= 5 && n <= 20) return five
            n %= 10
            if (n === 1) return one
            if (n >= 2 && n <= 4) return two
            return five
        }
    },

    beforeUnmount() {
        document.body.style.overflow = ''
    }
}
</script>

<style scoped>
/* === Overlay === */
.images-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

/* === Modal === */
.images-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 720px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    animation: modalSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalSlideIn {
    from { opacity: 0; transform: translateY(-20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* === Header === */
.modal-header-custom {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 24px;
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
}

.header-icon-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    flex-shrink: 0;
}

.header-content {
    flex: 1;
    min-width: 0;
}

.modal-title-custom {
    font-size: 17px;
    font-weight: 700;
    color: #212529;
    margin: 0 0 2px 0;
}

.modal-subtitle {
    font-size: 12px;
    color: #6c757d;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-close-custom {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: #f1f3f5;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.btn-close-custom:hover {
    background: #e9ecef;
    color: #212529;
}

/* === Body === */
.modal-body-custom {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
}

/* === Drop Zone === */
.drop-zone {
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    padding: 32px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fafbfc;
}

.drop-zone:hover:not(.is-disabled) {
    border-color: #0d6efd;
    background: #f8f9ff;
}

.drop-zone.is-dragging {
    border-color: #0d6efd;
    background: #e7f1ff;
    transform: scale(1.02);
}

.drop-zone.is-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.drop-zone-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.drop-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e7f1ff 0%, #cfe2ff 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0d6efd;
    font-size: 24px;
}

.drop-text strong {
    display: block;
    font-size: 15px;
    color: #212529;
    margin-bottom: 4px;
}

.drop-text span {
    font-size: 13px;
    color: #6c757d;
}

.drop-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #adb5bd;
    margin-top: 4px;
}

.drop-hint i {
    color: #0d6efd;
}

/* === Section Title === */
.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    margin: 20px 0 12px 0;
}

.section-title i {
    color: #0d6efd;
    font-size: 13px;
}

.section-title .btn-clear-pending {
    margin-left: auto;
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
    font-size: 11px;
}

.section-title .btn-clear-pending:hover {
    background: #f1f3f5;
    color: #dc3545;
}

/* === Pending Grid === */
.pending-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
}

.pending-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e9ecef;
    background: #f8f9fa;
}

.pending-item img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    display: block;
}

.btn-remove-pending {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 22px;
    height: 22px;
    border: none;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.btn-remove-pending:hover {
    background: #dc3545;
}

.pending-name {
    padding: 4px 6px 2px;
    font-size: 10px;
    color: #495057;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pending-size {
    padding: 0 6px 6px;
    font-size: 9px;
    color: #adb5bd;
}

.btn-upload-pending {
    width: 100%;
    margin-top: 12px;
    padding: 10px;
    border: none;
    border-radius: 8px;
    background: #0d6efd;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.15s ease;
}

.btn-upload-pending:hover:not(:disabled) {
    background: #0b5ed7;
}

.btn-upload-pending:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* === Existing Images Grid === */
.images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
}

.image-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid #e9ecef;
    aspect-ratio: 1;
    cursor: pointer;
    transition: all 0.2s ease;
}

.image-item:hover {
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
}

.image-item.is-main {
    border-color: #ffc107;
}

.image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.main-badge {
    position: absolute;
    top: 6px;
    left: 6px;
    padding: 3px 8px;
    background: rgba(255, 193, 7, 0.95);
    color: #212529;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    backdrop-filter: blur(4px);
}

.main-badge i {
    font-size: 9px;
}

.image-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.image-item:hover .image-overlay {
    opacity: 1;
}

.overlay-btn {
    width: 34px;
    height: 34px;
    border: none;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.95);
    color: #495057;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    transition: all 0.15s ease;
}

.overlay-btn:hover {
    background: #fff;
    color: #0d6efd;
    transform: scale(1.1);
}

.overlay-btn.danger:hover {
    background: #dc3545;
    color: #fff;
}

/* === Empty State === */
.empty-images {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: #adb5bd;
}

.empty-images i {
    font-size: 40px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.empty-images p {
    margin: 0 0 4px 0;
    font-size: 15px;
    font-weight: 600;
    color: #495057;
}

.empty-images span {
    font-size: 13px;
}

/* === Footer === */
.modal-footer-custom {
    padding: 14px 24px;
    border-top: 1px solid #e9ecef;
    background: #fafbfc;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.footer-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #6c757d;
}

.footer-hint i {
    color: #0d6efd;
    font-size: 11px;
}

.btn-done {
    padding: 8px 20px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #495057;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-done:hover {
    background: #f1f3f5;
    color: #212529;
}

/* === Preview Overlay === */
.preview-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 40px;
}

.preview-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 8px;
}

.preview-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: all 0.15s ease;
}

.preview-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* === Transitions === */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

/* === Responsive === */
@media (max-width: 576px) {
    .images-modal-overlay {
        padding: 0;
    }

    .images-modal {
        max-width: 100%;
        max-height: 100%;
        border-radius: 0;
    }

    .modal-header-custom {
        padding: 16px;
    }

    .modal-body-custom {
        padding: 16px;
    }

    .drop-zone {
        padding: 24px 16px;
    }

    .images-grid,
    .pending-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 8px;
    }

    .pending-item img {
        height: 80px;
    }

    .modal-footer-custom {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-done {
        width: 100%;
        text-align: center;
    }
}
</style>
