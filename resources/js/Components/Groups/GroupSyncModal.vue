<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="modelValue" class="modal-overlay">
                <div class="modal-content-custom">
                    <!-- Header -->
                    <div class="modal-header-custom">
                        <h5 class="modal-title-custom">
                            <i class="fa-solid fa-arrows-rotate" :class="{ 'fa-spin': step === 'syncing' }"></i>
                            <span>{{ stepTitle }}</span>
                        </h5>
                        <button v-if="step !== 'syncing'" class="btn-close-custom" @click="close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body-custom">
                        <!-- ШАГ 1: Выбор досок -->
                        <div v-if="step === 'select'">
                            <p class="modal-description">
                                Отметьте доски, которые нужно синхронизировать прямо сейчас:
                            </p>
                            <div class="workspace-list">
                                <label
                                    v-for="ws in groupWorkspaces"
                                    :key="ws.id"
                                    class="ws-checkbox"
                                >
                                    <input
                                        type="checkbox"
                                        :value="ws.id"
                                        v-model="selectedIds"
                                    />
                                    <div class="ws-icon" :style="{ background: ws.color }">
                                        {{ ws.initials || ws.name?.substring(0, 2) }}
                                    </div>
                                    <span class="ws-name">{{ ws.name }}</span>
                                </label>
                            </div>
                            <small class="field-hint" v-if="selectedIds.length === 0">
                                <i class="fa-solid fa-circle-info"></i>
                                Выберите хотя бы одну доску
                            </small>
                        </div>

                        <!-- ШАГ 2: Прогресс -->
                        <div v-if="step === 'syncing'" class="syncing-state">
                            <div class="spinner-wrapper">
                                <i class="fa-solid fa-circle-notch fa-spin"></i>
                            </div>
                            <h6 class="syncing-title">Синхронизация данных...</h6>
                            <p class="syncing-count">
                                Обработано {{ syncedCount }} из {{ selectedIds.length }}
                            </p>
                            <div class="progress-bar-wrapper">
                                <div
                                    class="progress-fill"
                                    :style="{ width: progressPercent + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- ШАГ 3: Сводка -->
                        <div v-if="step === 'done'" class="summary-state">
                            <div class="summary-header" :class="hasErrors ? 'has-errors' : 'success'">
                                <i :class="hasErrors ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-check'"></i>
                                <span>
                                    {{ hasErrors ? 'Синхронизация завершена с ошибками' : 'Успешно синхронизировано' }}
                                </span>
                            </div>

                            <div class="summary-list">
                                <div
                                    v-for="res in syncResults"
                                    :key="res.workspace_id"
                                    class="summary-item"
                                    :class="{ 'is-error': !res.success }"
                                >
                                    <div class="item-info">
                                        <strong>{{ res.workspace_name }}</strong>
                                        <span class="item-status" :class="res.success ? 'success' : 'error'">
                                            {{ res.success ? 'Успех' : 'Ошибка' }}
                                        </span>
                                    </div>
                                    <div class="item-details">
                                        <span v-if="res.success" class="text-success">
                                            <i class="fa-solid fa-box"></i>
                                            {{ res.products_synced }} тов.
                                        </span>
                                        <span v-else class="text-danger">
                                            <i class="fa-solid fa-circle-xmark"></i>
                                            {{ res.error }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Общая статистика -->
                            <div class="summary-total">
                                <div class="total-item">
                                    <i class="fa-solid fa-check-circle text-success"></i>
                                    <span>{{ successCount }} успешно</span>
                                </div>
                                <div class="total-item" v-if="failCount > 0">
                                    <i class="fa-solid fa-times-circle text-danger"></i>
                                    <span>{{ failCount }} с ошибками</span>
                                </div>
                                <div class="total-item">
                                    <i class="fa-solid fa-box text-primary"></i>
                                    <span>{{ totalProducts }} товаров</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer-custom">
                        <button v-if="step === 'select'" class="btn-cancel" @click="close">
                            Отмена
                        </button>
                        <button
                            v-if="step === 'select'"
                            class="btn-save"
                            :disabled="selectedIds.length === 0"
                            @click="startSync"
                        >
                            <i class="fa-solid fa-play"></i>
                            Начать синхронизацию
                        </button>
                        <button v-if="step === 'done'" class="btn-save" @click="close">
                            <i class="fa-solid fa-check"></i>
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'GroupSyncModal',

    props: {
        modelValue: {
            type: Boolean,
            default: false
        },
        group: {
            type: Object,
            default: null
        }
    },

    emits: ['update:modelValue', 'synced'],

    data() {
        return {
            store: useWorkspaceStore(),
            step: 'select', // select, syncing, done
            selectedIds: [],
            syncResults: [],
            syncedCount: 0
        }
    },

    computed: {
        groupWorkspaces() {
            return this.group?.workspaces || []
        },

        stepTitle() {
            const titles = {
                select: 'Выбор досок для синхронизации',
                syncing: 'Синхронизация...',
                done: 'Результаты синхронизации'
            }
            return titles[this.step]
        },

        progressPercent() {
            if (this.selectedIds.length === 0) return 0
            return Math.round((this.syncedCount / this.selectedIds.length) * 100)
        },

        hasErrors() {
            return this.syncResults.some(r => !r.success)
        },

        successCount() {
            return this.syncResults.filter(r => r.success).length
        },

        failCount() {
            return this.syncResults.filter(r => !r.success).length
        },

        totalProducts() {
            return this.syncResults.reduce((sum, r) => sum + (r.products_synced || 0), 0)
        }
    },

    watch: {
        modelValue(val) {
            if (val && this.group) {
                // Инициализируем форму при открытии
                this.step = 'select'
                this.selectedIds = this.group.workspaces.map(w => w.id)
                this.syncResults = []
                this.syncedCount = 0
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

        async startSync() {
            this.step = 'syncing'
            this.syncedCount = 0
            this.syncResults = []

            try {
                const results = await this.store.syncGroup(this.group.id, this.selectedIds)

                // Пошаговый прогресс для анимации
                for (let i = 0; i < results.length; i++) {
                    this.syncResults.push(results[i])
                    this.syncedCount = i + 1
                    await new Promise(r => setTimeout(r, 300))
                }

                this.step = 'done'
                this.$emit('synced', results)
            } catch (e) {
                console.error('Sync failed:', e)
                this.$notify?.error('Ошибка при синхронизации')
                this.close()
            }
        }
    },

    beforeUnmount() {
        document.body.style.overflow = ''
    }
}
</script>

<style scoped>
.modal-overlay {
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

.modal-content-custom {
    background: #fff;
    border-radius: 14px;
    width: 100%;
    max-width: 500px;
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

.modal-header-custom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #e9ecef;
}

.modal-title-custom {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 17px;
    font-weight: 600;
    margin: 0;
}

.modal-title-custom i {
    color: #0d6efd;
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
}

.btn-close-custom:hover {
    background: #e9ecef;
    color: #212529;
}

.modal-body-custom {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.modal-description {
    font-size: 13px;
    color: #6c757d;
    margin: 0 0 16px 0;
}

.workspace-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 8px;
}

.ws-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
}

.ws-checkbox:hover {
    background: #f8f9fa;
}

.ws-checkbox input {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.ws-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
}

.ws-name {
    flex: 1;
    font-size: 13px;
    color: #212529;
}

.field-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    font-size: 12px;
    color: #dc3545;
}

.field-hint i {
    font-size: 11px;
}

/* === Syncing State === */
.syncing-state {
    text-align: center;
    padding: 30px 20px;
}

.spinner-wrapper {
    font-size: 48px;
    color: #0d6efd;
    margin-bottom: 16px;
}

.syncing-title {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin: 0 0 8px 0;
}

.syncing-count {
    font-size: 13px;
    color: #6c757d;
    margin: 0 0 16px 0;
}

.progress-bar-wrapper {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0d6efd, #6f42c1);
    border-radius: 4px;
    transition: width 0.3s ease;
}

/* === Summary State === */
.summary-state {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.summary-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
}

.summary-header.success {
    background: #d1e7dd;
    color: #0f5132;
}

.summary-header.has-errors {
    background: #fff3cd;
    color: #664d03;
}

.summary-header i {
    font-size: 18px;
}

.summary-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 300px;
    overflow-y: auto;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 3px solid #198754;
}

.summary-item.is-error {
    border-left-color: #dc3545;
}

.item-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.item-info strong {
    font-size: 13px;
    color: #212529;
}

.item-status {
    font-size: 11px;
    font-weight: 600;
}

.item-status.success {
    color: #198754;
}

.item-status.error {
    color: #dc3545;
}

.item-details {
    font-size: 13px;
    font-weight: 500;
}

.text-success {
    color: #198754;
}

.text-danger {
    color: #dc3545;
}

.text-primary {
    color: #0d6efd;
}

.summary-total {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    justify-content: center;
    flex-wrap: wrap;
}

.total-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #495057;
}

.total-item i {
    font-size: 14px;
}

.modal-footer-custom {
    padding: 14px 20px;
    border-top: 1px solid #e9ecef;
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
    cursor: pointer;
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    border: none;
    border-radius: 8px;
    background: #0d6efd;
    color: #fff;
    font-weight: 500;
    cursor: pointer;
}

.btn-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Transitions */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

/* Responsive */
@media (max-width: 576px) {
    .modal-overlay {
        padding: 0;
    }

    .modal-content-custom {
        max-width: 100%;
        max-height: 100%;
        border-radius: 0;
    }

    .summary-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
}
</style>
