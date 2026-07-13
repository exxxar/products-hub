<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="modelValue" class="modal-overlay" @click.self="close">
                <div class="modal-content-custom">
                    <!-- Header -->
                    <div class="modal-header-custom">
                        <h5 class="modal-title-custom">
                            <i class="fa-solid fa-link"></i>
                            <span>Вебхуки группы</span>
                        </h5>
                        <button class="btn-close-custom" @click="close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body-custom">
                        <div class="group-info-header">
                            <div class="group-icon" :style="{ background: group?.color }">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div>
                                <div class="group-name">{{ group?.name }}</div>
                                <div class="group-meta">
                                    {{ groupWorkspaces.length }} {{ pluralize(groupWorkspaces.length, 'доска', 'доски', 'досок') }}
                                </div>
                            </div>
                        </div>

                        <p class="modal-description">
                            Настройте вебхук для каждой доски. Если вебхук уже существует, он будет обновлён.
                        </p>

                        <!-- Список досок с полями вебхуков -->
                        <div class="webhook-list">
                            <div
                                v-for="ws in groupWorkspaces"
                                :key="ws.id"
                                class="webhook-row"
                                :class="{ 'has-error': getWebhook(ws.id)?.last_status === 'failed' }"
                            >
                                <!-- Инфо о доске -->
                                <div class="ws-info">
                                    <div class="ws-icon" :style="{ background: ws.color }">
                                        {{ ws.initials || ws.name?.substring(0, 2) }}
                                    </div>
                                    <div class="ws-details">
                                        <div class="ws-name">{{ ws.name }}</div>
                                        <div v-if="getWebhook(ws.id)" class="ws-status">
                                            <span
                                                class="status-badge"
                                                :class="getWebhook(ws.id).last_status"
                                            >
                                                {{ getStatusText(getWebhook(ws.id).last_status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Поля вебхука -->
                                <div class="webhook-fields">
                                    <input
                                        v-model="webhookData[ws.id].name"
                                        type="text"
                                        class="form-input input-sm"
                                        placeholder="Название (напр. Основной)"
                                    />
                                    <div class="url-input-wrapper">
                                        <input
                                            v-model="webhookData[ws.id].url"
                                            type="url"
                                            class="form-input"
                                            placeholder="https://api.example.com/webhook"
                                        />
                                        <label class="toggle-switch" title="Синхронизировать при обновлении товара">
                                            <input
                                                type="checkbox"
                                                v-model="webhookData[ws.id].sync_on_update"
                                            />
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    <div v-if="getWebhook(ws.id)?.last_error" class="error-text">
                                        <i class="fa-solid fa-circle-exclamation"></i>
                                        {{ getWebhook(ws.id).last_error }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer-custom">
                        <button class="btn-cancel" @click="close">Отмена</button>
                        <button
                            class="btn-save"
                            :disabled="isSaving"
                            @click="save"
                        >
                            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-check"></i>
                            Сохранить вебхуки
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
    name: 'GroupWebhookModal',

    props: {
        modelValue: { type: Boolean, default: false },
        group: { type: Object, default: null }
    },

    emits: ['update:modelValue', 'saved'],

    data() {
        return {
            store: useWorkspaceStore(),
            isSaving: false,
            webhookData: {} // { [ws.id]: { id, name, url, sync_on_update } }
        }
    },

    computed: {
        groupWorkspaces() {
            return this.group?.workspaces || []
        }
    },

    watch: {
        modelValue(val) {
            if (val && this.group) {
                this.initWebhookData()
                document.body.style.overflow = 'hidden'
            } else {
                document.body.style.overflow = ''
            }
        }
    },

    mounted() {
       console.log("workspaces",this.groupWorkspaces)
    },
    methods: {
        close() {
            this.$emit('update:modelValue', false)
        },

        initWebhookData() {
            this.webhookData = {}
            this.groupWorkspaces.forEach(ws => {
                // Берем первый вебхук, если он есть, иначе создаем шаблон
                const existing = ws.webhooks && ws.webhooks.length > 0 ? ws.webhooks[0] : null

                this.webhookData[ws.id] = {
                    id: existing?.id || null,
                    name: existing?.name || 'Групповой',
                    url: existing?.url || '',
                    sync_on_update: existing?.sync_on_update ?? true,
                    last_status: existing?.last_status || null,
                    last_error: existing?.last_error || null
                }
            })
        },

        getWebhook(wsId) {
            return this.webhookData[wsId]
        },

        getStatusText(status) {
            const map = {
                'success': 'Успешно',
                'failed': 'Ошибка',
                'pending': 'Ожидание',
                null: 'Не запускался'
            }
            return map[status] || 'Неизвестно'
        },

        async save() {
            this.isSaving = true
            try {
                // Формируем массив только с заполненными URL
                const payload = Object.entries(this.webhookData)
                    .filter(([_, data]) => data.url.trim() !== '')
                    .map(([wsId, data]) => ({
                        workspace_id: Number(wsId),
                        id: data.id,
                        name: data.name.trim() || 'Групповой',
                        url: data.url.trim(),
                        sync_on_update: !!data.sync_on_update
                    }))

                await this.store.updateGroupWebhooks(this.group.id, payload)
                this.$emit('saved')
                this.close()
            } catch (e) {
                console.error('Save webhooks failed:', e)
                this.$notify?.error('Ошибка сохранения вебхуков')
            } finally {
                this.isSaving = false
            }
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
    max-width: 600px;
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

.group-info-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 16px;
}

.group-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
    flex-shrink: 0;
}

.group-name {
    font-size: 15px;
    font-weight: 600;
    color: #212529;
}

.group-meta {
    font-size: 12px;
    color: #6c757d;
    margin-top: 2px;
}

.modal-description {
    font-size: 13px;
    color: #6c757d;
    margin: 0 0 16px 0;
}

.webhook-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.webhook-row {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    background: #fff;
    transition: all 0.15s ease;
}

.webhook-row.has-error {
    border-color: #f5c2c7;
    background: #fff5f5;
}

.ws-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ws-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.ws-details {
    flex: 1;
    min-width: 0;
}

.ws-name {
    font-size: 14px;
    font-weight: 600;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.form-input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
}

.form-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
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

    .webhook-row {
        flex-direction: column;
        align-items: stretch;
    }

    .ws-info {
        min-width: auto;
    }
}

.status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    margin-top: 4px;
}

.status-badge.success { background: #d1e7dd; color: #0f5132; }
.status-badge.failed { background: #f8d7da; color: #842029; }
.status-badge.pending { background: #fff3cd; color: #664d03; }
.status-badge.null { background: #f1f3f5; color: #6c757d; }

.webhook-fields {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.url-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}

.url-input-wrapper .form-input {
    flex: 1;
}

.input-sm {
    padding: 6px 10px !important;
    font-size: 13px !important;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 22px;
    flex-shrink: 0;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #dee2e6;
    transition: .3s;
    border-radius: 22px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #0d6efd;
}

input:checked + .slider:before {
    transform: translateX(18px);
}

.error-text {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #dc3545;
    margin-top: 4px;
}

.error-text i {
    font-size: 10px;
}

/* Адаптив для полей */
@media (min-width: 576px) {
    .webhook-row {
        flex-direction: row;
        align-items: flex-start;
    }
    .ws-info {
        min-width: 180px;
        flex-shrink: 0;
    }
    .webhook-fields {
        flex: 1;
    }
}
</style>
