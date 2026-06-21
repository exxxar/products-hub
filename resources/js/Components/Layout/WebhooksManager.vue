<template>
    <div class="webhooks-manager">
        <div class="section-header-with-action">
            <h6 class="section-title">
                <i class="fa-solid fa-bolt"></i>
                Вебхуки
            </h6>
            <button type="button" class="btn-add" @click="showAddForm = true">
                <i class="fa-solid fa-plus"></i>
                Добавить вебхук
            </button>
        </div>

        <!-- Список вебхуков -->
        <div v-if="webhooks.length > 0" class="webhooks-list">
            <div
                v-for="webhook in webhooks"
                :key="webhook.id"
                class="webhook-card"
            >
                <div class="webhook-header">
                    <div class="webhook-info">
                        <h6 class="webhook-name">{{ webhook.name }}</h6>
                        <p class="webhook-url">{{ webhook.url }}</p>
                    </div>
                    <div class="webhook-status" :class="webhook.last_status">
                        <i :class="getStatusIcon(webhook.last_status)"></i>
                        <span>{{ getStatusText(webhook.last_status) }}</span>
                    </div>
                </div>

                <div class="webhook-meta">
                    <label class="sync-checkbox">
                        <input
                            type="checkbox"
                            v-model="webhook.sync_on_update"
                            @change="updateWebhook(webhook)"
                        />
                        <span>Синхронизировать при обновлении</span>
                    </label>

                    <div v-if="webhook.last_synced_at" class="last-sync">
                        <i class="fa-solid fa-clock"></i>
                        {{ formatLastSync(webhook.last_synced_at) }}
                    </div>
                </div>

                <div class="webhook-actions">
                    <button
                        type="button"
                        class="btn-sync"
                        @click="syncWebhook(webhook)"
                        :disabled="isSyncing(webhook.id)"
                    >
                        <i class="fa-solid fa-arrows-rotate" :class="{ 'rotating': isSyncing(webhook.id) }"></i>
                        Синхронизировать
                    </button>
                    <button
                        type="button"
                        class="btn-edit"
                        @click="editWebhook(webhook)"
                    >
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button
                        type="button"
                        class="btn-delete"
                        @click="confirmDelete(webhook)"
                    >
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <div v-else class="empty-state">
            <i class="fa-solid fa-bolt"></i>
            <p>Вебхуки не настроены</p>
            <small>Добавьте вебхук для синхронизации товаров с внешними системами</small>
        </div>

        <!-- Кнопка синхронизации всех -->
        <div v-if="webhooks.length > 0" class="sync-all-section">
            <button
                type="button"
                class="btn-sync-all"
                @click="syncAllWebhooks"
                :disabled="isSyncingAll"
            >
                <i class="fa-solid fa-arrows-rotate" :class="{ 'rotating': isSyncingAll }"></i>
                Синхронизировать все вебхуки
            </button>
        </div>

        <!-- Форма добавления/редактирования -->
        <div v-if="showAddForm || editingWebhook" class="webhook-form-overlay" @click="closeForm">
            <div class="webhook-form" @click.stop>
                <h6>{{ editingWebhook ? 'Редактировать вебхук' : 'Добавить вебхук' }}</h6>

                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input
                        v-model="formData.name"
                        type="text"
                        class="form-input"
                        placeholder="Например: Telegram Bot"
                    />
                </div>

                <div class="form-group">
                    <label class="form-label">URL</label>
                    <input
                        v-model="formData.url"
                        type="url"
                        class="form-input"
                        placeholder="https://example.com/webhook"
                    />
                </div>

                <div class="form-group">
                    <label class="sync-checkbox">
                        <input
                            type="checkbox"
                            v-model="formData.sync_on_update"
                        />
                        <span>Синхронизировать при обновлении товаров</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" @click="closeForm">
                        Отмена
                    </button>
                    <button type="button" class="btn-save" @click="saveWebhook">
                        <i class="fa-solid fa-check"></i>
                        {{ editingWebhook ? 'Сохранить' : 'Добавить' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'WebhooksManager',

    data() {
        return {
            store: useWorkspaceStore(),
            showAddForm: false,
            editingWebhook: null,
            isSyncingAll: false,
            syncingIds: new Set(), // Для отслеживания isSyncing на конкретных вебхуках
            formData: {
                name: '',
                url: '',
                sync_on_update: false
            }
        }
    },

    computed: {
        webhooks() {
            return this.store.webhooks
        }
    },

    mounted() {
        // Загружаем вебхуки если их нет в store
        if (this.store.webhooks.length === 0 && this.store.uuid) {
            this.store.loadWebhooks()
        }
    },

    methods: {
        isSyncing(id) {
            return this.syncingIds.has(id)
        },

        async saveWebhook() {
            try {
                if (this.editingWebhook) {
                    await this.store.saveWebhook(this.formData, this.editingWebhook.id)
                } else {
                    await this.store.saveWebhook(this.formData)
                }

                this.closeForm()
            } catch (error) {
                console.error('Failed to save webhook:', error)
                alert('Ошибка при сохранении вебхука')
            }
        },

        async updateWebhook(webhook) {
            try {
                await this.store.saveWebhook(
                    { sync_on_update: webhook.sync_on_update },
                    webhook.id
                )
            } catch (error) {
                console.error('Failed to update webhook:', error)
                // Откатываем значение
                webhook.sync_on_update = !webhook.sync_on_update
            }
        },

        async syncWebhook(webhook) {
            this.syncingIds.add(webhook.id)
            try {
                await this.store.syncWebhook(webhook.id)
            } catch (error) {
                console.error('Failed to sync webhook:', error)
                alert('Ошибка при синхронизации')
            } finally {
                this.syncingIds.delete(webhook.id)
            }
        },

        async syncAllWebhooks() {
            this.isSyncingAll = true
            try {
                await this.store.syncAllWebhooks()
            } catch (error) {
                console.error('Failed to sync all webhooks:', error)
                alert('Ошибка при синхронизации')
            } finally {
                this.isSyncingAll = false
            }
        },

        editWebhook(webhook) {
            this.editingWebhook = webhook
            this.formData = {
                name: webhook.name,
                url: webhook.url,
                sync_on_update: webhook.sync_on_update
            }
        },

        confirmDelete(webhook) {
            if (confirm(`Удалить вебхук "${webhook.name}"?`)) {
                this.deleteWebhook(webhook)
            }
        },

        async deleteWebhook(webhook) {
            try {
                await this.store.deleteWebhook(webhook.id)
            } catch (error) {
                console.error('Failed to delete webhook:', error)
                alert('Ошибка при удалении')
            }
        },

        closeForm() {
            this.showAddForm = false
            this.editingWebhook = null
            this.formData = {
                name: '',
                url: '',
                sync_on_update: false
            }
        },

        getStatusIcon(status) {
            if (status === 'success') return 'fa-solid fa-circle-check'
            if (status === 'failed') return 'fa-solid fa-circle-xmark'
            return 'fa-solid fa-circle-question'
        },

        getStatusText(status) {
            if (status === 'success') return 'Успешно'
            if (status === 'failed') return 'Ошибка'
            return 'Не синхронизирован'
        },

        formatLastSync(date) {
            if (!date) return ''
            const d = new Date(date)
            return d.toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
        }
    }
}
</script>
<style scoped>
.webhooks-manager {
    margin-top: 24px;
}

.section-header-with-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin: 0;
}

.section-title i {
    color: #0d6efd;
}

.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1px solid #0d6efd;
    border-radius: 8px;
    background: transparent;
    color: #0d6efd;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-add:hover {
    background: #0d6efd;
    color: #fff;
}

/* === Webhooks list === */
.webhooks-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.webhook-card {
    padding: 16px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    background: #f8f9fa;
    transition: all 0.15s ease;
}

.webhook-card:hover {
    border-color: #dee2e6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.webhook-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.webhook-info {
    flex: 1;
}

.webhook-name {
    font-size: 15px;
    font-weight: 600;
    color: #212529;
    margin: 0 0 4px 0;
}

.webhook-url {
    font-size: 12px;
    color: #6c757d;
    margin: 0;
    word-break: break-all;
}

.webhook-status {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
}

.webhook-status.success {
    background: #d1e7dd;
    color: #0f5132;
}

.webhook-status.failed {
    background: #f8d7da;
    color: #842029;
}

.webhook-status:not(.success):not(.failed) {
    background: #e9ecef;
    color: #6c757d;
}

.webhook-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding: 10px 0;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
}

.sync-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 13px;
    color: #495057;
}

.sync-checkbox input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.last-sync {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #6c757d;
}

.webhook-actions {
    display: flex;
    gap: 8px;
}

.btn-sync {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: 1px solid #0d6efd;
    border-radius: 6px;
    background: #e7f1ff;
    color: #0d6efd;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-sync:hover:not(:disabled) {
    background: #0d6efd;
    color: #fff;
}

.btn-sync:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-edit,
.btn-delete {
    width: 32px;
    height: 32px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.btn-edit:hover {
    background: #e7f1ff;
    color: #0d6efd;
    border-color: #0d6efd;
}

.btn-delete:hover {
    background: #f8d7da;
    color: #842029;
    border-color: #dc3545;
}

/* === Sync all === */
.sync-all-section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e9ecef;
}

.btn-sync-all {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border: 1px solid #198754;
    border-radius: 8px;
    background: #d1e7dd;
    color: #0f5132;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-sync-all:hover:not(:disabled) {
    background: #198754;
    color: #fff;
}

.btn-sync-all:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* === Empty state === */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.3;
}

.empty-state p {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 500;
}

.empty-state small {
    font-size: 12px;
}

/* === Form overlay === */
.webhook-form-overlay {
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

.webhook-form {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.webhook-form h6 {
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
}

.form-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.form-actions {
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
    display: inline-flex;
    align-items: center;
    gap: 6px;
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

.btn-save:hover {
    background: #0b5ed7;
}

/* === Animations === */
.rotating {
    animation: rotate 1s linear infinite;
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
