<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-file-excel"></i>
                        Им из Excel
                    </h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">
                    <!-- Шаг 1: Загрузка файла -->
                    <div v-if="step === 'upload'" class="upload-step">
                        <!-- Dropzone -->
                        <div
                            class="file-dropzone"
                            :class="{ 'drag-over': isDragOver, 'has-file': selectedFile }"
                            @dragover.prevent="isDragOver = true"
                            @dragleave="isDragOver = false"
                            @drop.prevent="onFileDrop"
                            @click="triggerFileInput"
                        >
                            <template v-if="!selectedFile">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <p>Перетащите файл сюда или нажмите для выбора</p>
                                <small>Поддерживаемые форматы: .xlsx, .xls, .csv (до 10MB)</small>
                            </template>
                            <template v-else>
                                <i class="fa-solid fa-file-excel file-icon"></i>
                                <p class="file-name">{{ selectedFile.name }}</p>
                                <small>{{ formatFileSize(selectedFile.size) }}</small>
                                <button type="button" class="btn-remove-file" @click.stop="selectedFile = null">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </template>
                        </div>
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            style="display: none"
                            @change="onFileSelect"
                        />

                        <!-- Структура файла -->
                        <div class="template-info">
                            <h6>Структура файла:</h6>
                            <div class="sheets-list">
                                <div class="sheet-item categories">
                                    <i class="fa-solid fa-tags"></i>
                                    <div>
                                        <strong>Категории</strong>
                                        <small>name, description</small>
                                    </div>
                                </div>
                                <div class="sheet-item products">
                                    <i class="fa-solid fa-box"></i>
                                    <div>
                                        <strong>Товары</strong>
                                        <small>name, sku, price, old_price, description, categories, width, height, length, weight, images, attr_*</small>
                                    </div>
                                </div>
                                <div class="sheet-item collections">
                                    <i class="fa-solid fa-folder"></i>
                                    <div>
                                        <strong>Коллекции</strong>
                                        <small>name, description, product_skus</small>
                                    </div>
                                </div>
                                <div class="sheet-item attributes">
                                    <i class="fa-solid fa-list-check"></i>
                                    <div>
                                        <strong>Свойства</strong>
                                        <small>product_sku, attribute_name, attribute_value</small>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn-template" @click="downloadTemplate">
                                <i class="fa-solid fa-download"></i>
                                Скачать шаблон
                            </button>
                        </div>
                    </div>

                    <!-- Шаг 2: Импорт -->
                    <div v-if="step === 'importing'" class="importing-step">
                        <div class="progress-indicator">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                            <h6>Импорт данных...</h6>
                            <p>Это может занять несколько минут</p>
                        </div>
                    </div>

                    <!-- Шаг 3: Результат -->
                    <div v-if="step === 'result'" class="result-step">
                        <div v-if="importResult.success" class="result-success">
                            <i class="fa-solid fa-circle-check"></i>
                            <h6>Импорт завершён успешно!</h6>

                            <div class="result-stats">
                                <div class="stat-item">
                                    <i class="fa-solid fa-box"></i>
                                    <span>Товары: <strong>{{ importResult.stats.products }}</strong></span>
                                </div>
                                <div class="stat-item">
                                    <i class="fa-solid fa-tags"></i>
                                    <span>Категории: <strong>{{ importResult.stats.categories }}</strong></span>
                                </div>
                                <div class="stat-item">
                                    <i class="fa-solid fa-folder"></i>
                                    <span>Коллекции: <strong>{{ importResult.stats.collections }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="result-error">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <h6>Ошибка при импорте</h6>
                            <p>{{ importResult.message }}</p>

                            <div v-if="importResult.errors?.length" class="error-list">
                                <div v-for="(error, i) in importResult.errors.slice(0, 10)" :key="i" class="error-item">
                                    <strong>Строка {{ error.row }}</strong>
                                    <span>{{ error.errors.join(', ') }}</span>
                                </div>
                                <p v-if="importResult.errors.length > 10" class="more-errors">
                                    ... и ещё {{ importResult.errors.length - 10 }} ошибок
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        v-if="step === 'upload'"
                        type="button"
                        class="btn-cancel"
                        @click="hide"
                    >
                        Отмена
                    </button>
                    <button
                        v-if="step === 'upload'"
                        type="button"
                        class="btn-import"
                        @click="startImport"
                        :disabled="!selectedFile"
                    >
                        <i class="fa-solid fa-upload"></i>
                        Импортировать
                    </button>
                    <button
                        v-if="step === 'result'"
                        type="button"
                        class="btn-cancel"
                        @click="hide"
                    >
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'ImportModal',

    emits: ['import'],

    data() {
        return {
            store: useWorkspaceStore(),
            modal: null,
            step: 'upload',
            selectedFile: null,
            isDragOver: false,
            importResult: null
        }
    },

    methods: {
        show() {
            this.resetState()
            this.$nextTick(() => {
                if (this.modal) {
                    this.modal.show()
                }
            })
        },

        hide() {
            if (this.modal) {
                this.modal.hide()
            }
        },

        resetState() {
            this.step = 'upload'
            this.selectedFile = null
            this.isDragOver = false
            this.importResult = null
        },

        triggerFileInput() {
            if (!this.selectedFile) {
                this.$refs.fileInput.click()
            }
        },

        onFileSelect(e) {
            const file = e.target.files[0]
            if (file) {
                this.selectedFile = file
            }
            e.target.value = ''
        },

        onFileDrop(e) {
            this.isDragOver = false
            const file = e.dataTransfer.files[0]
            if (file) {
                const validTypes = [
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-excel',
                    'text/csv'
                ]
                if (validTypes.includes(file.type) || file.name.match(/\.(xlsx|xls|csv)$/i)) {
                    this.selectedFile = file
                } else {
                    alert('Неподдерживаемый формат файла')
                }
            }
        },

        async startImport() {
            if (!this.selectedFile) return

            this.step = 'importing'

            try {
                const result = await this.store.importProducts(this.selectedFile)

                if (result.success === false) {
                    this.importResult = result
                } else {
                    this.importResult = {
                        success: true,
                        stats: result.stats || { products: 0, categories: 0, collections: 0 }
                    }
                }
            } catch (error) {
                this.importResult = {
                    success: false,
                    message: error.message || 'Неизвестная ошибка',
                    errors: []
                }
            } finally {
                this.step = 'result'
            }
        },

        async downloadTemplate() {
            try {
                await this.store.downloadImportTemplate()
            } catch (error) {
                console.error('Download failed:', error)
                alert('Ошибка при скачивании шаблона')
            }
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes'
            const k = 1024
            const sizes = ['Bytes', 'KB', 'MB', 'GB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
        }
    },

    mounted() {
        this.modal = new Modal(this.$refs.modal)
    },

    beforeUnmount() {
        if (this.modal) {
            this.modal.dispose()
            this.modal = null
        }
    }
}
</script>

<style scoped>
.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 16px 24px;
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
    font-weight: 600;
}

.modal-title i {
    color: #198754;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 16px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

/* === Upload === */
.file-dropzone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 48px 24px;
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    background: #f8f9fa;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    position: relative;
}

.file-dropzone:hover,
.file-dropzone.drag-over {
    border-color: #198754;
    background: #d1e7dd;
    color: #0f5132;
}

.file-dropzone.has-file {
    border-style: solid;
    border-color: #198754;
    background: #d1e7dd;
    color: #0f5132;
}

.file-dropzone > i {
    font-size: 40px;
}

.file-dropzone p {
    margin: 0;
    font-size: 15px;
    font-weight: 500;
}

.file-dropzone small {
    font-size: 12px;
    opacity: 0.7;
}

.file-icon {
    font-size: 48px !important;
    color: #198754;
}

.file-name {
    font-weight: 600 !important;
}

.btn-remove-file {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.btn-remove-file:hover {
    background: #dc3545;
    color: #fff;
}

/* === Template info === */
.template-info {
    margin-top: 24px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 10px;
}

.template-info h6 {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 12px 0;
}

.sheets-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 16px;
}

.sheet-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: #fff;
    border-radius: 8px;
    border-left: 4px solid;
}

.sheet-item.categories { border-color: #0d6efd; }
.sheet-item.products { border-color: #198754; }
.sheet-item.collections { border-color: #6f42c1; }
.sheet-item.attributes { border-color: #fd7e14; }

.sheet-item i {
    font-size: 18px;
    width: 24px;
    text-align: center;
}

.sheet-item.categories i { color: #0d6efd; }
.sheet-item.products i { color: #198754; }
.sheet-item.collections i { color: #6f42c1; }
.sheet-item.attributes i { color: #fd7e14; }

.sheet-item strong {
    display: block;
    font-size: 14px;
}

.sheet-item small {
    font-size: 12px;
    color: #6c757d;
}

.btn-template {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border: 1px solid #198754;
    border-radius: 8px;
    background: transparent;
    color: #198754;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-template:hover {
    background: #198754;
    color: #fff;
}

/* === Importing === */
.importing-step {
    text-align: center;
    padding: 60px 20px;
}

.progress-indicator i {
    font-size: 48px;
    color: #0d6efd;
    margin-bottom: 16px;
}

.progress-indicator h6 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px 0;
}

.progress-indicator p {
    font-size: 14px;
    color: #6c757d;
    margin: 0;
}

/* === Result === */
.result-step {
    text-align: center;
    padding: 20px;
}

.result-success > i,
.result-error > i {
    font-size: 56px;
    margin-bottom: 16px;
}

.result-success > i { color: #198754; }
.result-error > i { color: #dc3545; }

.result-success h6,
.result-error h6 {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 20px 0;
}

.result-stats {
    display: flex;
    justify-content: center;
    gap: 24px;
    margin-top: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-item i {
    font-size: 18px;
    color: #0d6efd;
}

.error-list {
    text-align: left;
    margin-top: 20px;
}

.error-item {
    display: flex;
    gap: 12px;
    padding: 8px 12px;
    background: #f8d7da;
    border-radius: 6px;
    margin-bottom: 6px;
    font-size: 13px;
}

.error-item strong {
    white-space: nowrap;
}

.more-errors {
    text-align: center;
    font-size: 13px;
    color: #6c757d;
    margin-top: 8px;
}

/* === Buttons === */
.btn-cancel {
    padding: 10px 20px;
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

.btn-import {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    background: #198754;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-import:hover:not(:disabled) {
    background: #157347;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
}

.btn-import:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
