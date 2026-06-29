<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid" :class="modalIcon"></i>
                        {{ modalTitle }}
                    </h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">
                    <!-- Состояние загрузки -->
                    <div v-if="isLoading" class="sync-loading">
                        <div class="loading-spinner">
                            <i class="fa-solid fa-arrows-rotate fa-spin"></i>
                        </div>
                        <h6>Синхронизация...</h6>
                        <p>Отправляем данные на вебхуки</p>
                    </div>

                    <!-- Результаты -->
                    <div v-else class="sync-results">
                        <!-- Общая статистика -->
                        <div class="results-summary">
                            <div class="summary-item success">
                                <i class="fa-solid fa-circle-check"></i>
                                <div>
                                    <strong>{{ successCount }}</strong>
                                    <span>успешно</span>
                                </div>
                            </div>
                            <div class="summary-item failed">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <div>
                                    <strong>{{ failedCount }}</strong>
                                    <span>ошибок</span>
                                </div>
                            </div>
                            <div class="summary-item total">
                                <i class="fa-solid fa-globe"></i>
                                <div>
                                    <strong>{{ totalCount }}</strong>
                                    <span>всего</span>
                                </div>
                            </div>
                        </div>

                        <!-- Список вебхуков -->
                        <div class="webhooks-list">
                            <div
                                v-for="result in results"
                                :key="result.id"
                                class="webhook-result"
                                :class="{ success: result.success, failed: !result.success }"
                            >
                                <div class="webhook-icon">
                                    <i class="fa-solid" :class="result.success ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
                                </div>
                                <div class="webhook-info">
                                    <div class="webhook-name">{{ result.name }}</div>
                                    <div class="webhook-url">{{ result.url }}</div>
                                    <div v-if="result.error" class="webhook-error">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        {{ result.error }}
                                    </div>
                                </div>
                                <div class="webhook-status">
                                    <span class="status-badge" :class="result.success ? 'success' : 'failed'">
                                        {{ result.success ? 'OK' : result.status || 'FAIL' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Время выполнения -->
                        <div v-if="executionTime" class="execution-time">
                            <i class="fa-regular fa-clock"></i>
                            Выполнено за {{ executionTime }}
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        v-if="!isLoading && failedCount > 0"
                        type="button"
                        class="btn-retry"
                        @click="retry"
                    >
                        <i class="fa-solid fa-rotate-right"></i>
                        Повторить ошибки
                    </button>
                    <button type="button" class="btn-close-modal" @click="hide">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'

export default {
    name: 'SyncResultModal',

    emits: ['retry'],

    data() {
        return {
            modal: null,
            isLoading: false,
            results: [],
            executionTime: null,
        }
    },

    computed: {
        successCount() {
            return this.results.filter(r => r.success).length
        },

        failedCount() {
            return this.results.filter(r => !r.success).length
        },

        totalCount() {
            return this.results.length
        },

        modalTitle() {
            if (this.isLoading) return 'Синхронизация вебхуков'
            if (this.failedCount === 0) return 'Синхронизация завершена'
            return 'Синхронизация завершена с ошибками'
        },

        modalIcon() {
            if (this.isLoading) return 'fa-arrows-rotate fa-spin'
            if (this.failedCount === 0) return 'fa-circle-check text-success'
            return 'fa-triangle-exclamation text-warning'
        },
    },

    methods: {
        show() {
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

        setLoading() {
            this.isLoading = true
            this.results = []
            this.executionTime = null
        },

        setResults(results, executionTime) {
            this.isLoading = false
            this.results = results
            this.executionTime = executionTime
        },

        retry() {
            this.$emit('retry')
        },
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

.modal-title .text-success {
    color: #198754;
}

.modal-title .text-warning {
    color: #ffc107;
}

.modal-body {
    padding: 24px;
}

/* === Loading State === */
.sync-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e7f1ff 0%, #cfe2ff 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.loading-spinner i {
    font-size: 24px;
    color: #0d6efd;
}

.sync-loading h6 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 4px 0;
    color: #212529;
}

.sync-loading p {
    font-size: 13px;
    color: #6c757d;
    margin: 0;
}

/* === Results Summary === */
.results-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    border-radius: 8px;
    background: #f8f9fa;
}

.summary-item i {
    font-size: 20px;
}

.summary-item.success {
    background: #d1e7dd;
}

.summary-item.success i {
    color: #198754;
}

.summary-item.failed {
    background: #f8d7da;
}

.summary-item.failed i {
    color: #dc3545;
}

.summary-item.total {
    background: #e7f1ff;
}

.summary-item.total i {
    color: #0d6efd;
}

.summary-item strong {
    display: block;
    font-size: 18px;
    font-weight: 700;
    line-height: 1;
}

.summary-item span {
    font-size: 11px;
    color: #6c757d;
}

/* === Webhooks List === */
.webhooks-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 300px;
    overflow-y: auto;
}

.webhook-result {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    transition: all 0.15s ease;
}

.webhook-result:hover {
    border-color: #dee2e6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.webhook-result.success {
    border-left: 3px solid #198754;
}

.webhook-result.failed {
    border-left: 3px solid #dc3545;
}

.webhook-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
}

.webhook-result.success .webhook-icon {
    color: #198754;
}

.webhook-result.failed .webhook-icon {
    color: #dc3545;
}

.webhook-info {
    flex: 1;
    min-width: 0;
}

.webhook-name {
    font-size: 14px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 2px;
}

.webhook-url {
    font-size: 12px;
    color: #6c757d;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.webhook-error {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
    font-size: 11px;
    color: #dc3545;
}

.webhook-error i {
    font-size: 10px;
}

.webhook-status {
    flex-shrink: 0;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.success {
    background: #d1e7dd;
    color: #0f5132;
}

.status-badge.failed {
    background: #f8d7da;
    color: #842029;
}

/* === Execution Time === */
.execution-time {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 16px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 12px;
    color: #6c757d;
}

.execution-time i {
    font-size: 12px;
}

/* === Modal Footer === */
.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 16px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.btn-retry {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1px solid #ffc107;
    border-radius: 8px;
    background: #fff3cd;
    color: #664d03;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-retry:hover {
    background: #ffe69c;
}

.btn-close-modal {
    padding: 8px 20px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 14px;
    cursor: pointer;
}

.btn-close-modal:hover {
    background: #f8f9fa;
}
</style>
