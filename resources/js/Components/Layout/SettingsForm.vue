<template>
    <form class="settings-form" @submit.prevent="save">
        <!-- Tabs -->
        <div class="settings-tabs">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="tab-btn"
                :class="{ active: activeTab === tab.key }"
                @click="activeTab = tab.key"
            >
                <i :class="tab.icon"></i>
                <span class="tab-label">{{ tab.label }}</span>
            </button>
        </div>

        <!-- Base Tab -->
        <div v-if="activeTab === 'base'" class="tab-content">


            <WebhooksManager />

            <div class="section-divider"></div>

            <div class="section-header">
                <h6 class="section-title">
                    <i class="fa-solid fa-key"></i>
                    Сессия
                </h6>
                <p class="section-desc">
                    Управление сессией для авторизации
                </p>
            </div>

            <div class="action-buttons">
                <button
                    type="button"
                    class="btn-action primary"
                    @click="createNewSession"
                >
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Создать новую сессию</span>
                </button>
                <button
                    type="button"
                    class="btn-action secondary"
                    @click="refreshSession"
                    :disabled="isLoading"
                >
                    <i class="fa-solid fa-arrows-rotate" :class="{ 'rotating': isLoading }"></i>
                    <span>Обновить ключ</span>
                </button>
            </div>

            <div class="section-divider"></div>
            <div class="section-header">
                <h6 class="section-title">
                    <i class="fa-solid fa-key"></i>
                    Токены доступа
                </h6>
                <p class="section-desc">
                    Управление токеном доступа
                </p>
            </div>

            <AccessLinkManager />
        </div>

        <!-- VK Tab -->
        <div v-if="activeTab === 'vk'" class="tab-content">
            <div class="section-header">
                <h6 class="section-title">
                    <i class="fa-brands fa-vk"></i>
                    Интеграция с VK
                </h6>
                <p class="section-desc">
                    Настройка синхронизации с группами ВКонтакте
                </p>
            </div>

            <div class="info-box">
                <i class="fa-solid fa-circle-info"></i>
                <div>
                    <strong>Внимание!</strong>
                    Укажите ссылки на группы ВК через запятую.
                    <br />
                    <small>Пример: https://vk.com/club123456, https://vk.com/club789012</small>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Список групп ВК</label>
                <textarea
                    v-model="localForm.vk_shop_links"
                    class="form-input form-textarea"
                    placeholder="https://vk.com/club123456, https://vk.com/club789012"
                    rows="4"
                ></textarea>
            </div>
        </div>

        <!-- IIKO Tab -->
        <div v-if="activeTab === 'iiko'" class="tab-content">
            <div class="section-header">
                <h6 class="section-title">
                    <i class="fa-solid fa-utensils"></i>
                    Интеграция с IIKO
                </h6>
                <p class="section-desc">
                    Настройка подключения к системе IIKO
                </p>
            </div>

            <div class="field-group">
                <label class="field-label">API Login</label>
                <input
                    v-model="localForm.iiko.api_login"
                    type="text"
                    class="form-input"
                    placeholder="Введите API login"
                />
            </div>

            <div class="field-group">
                <label class="field-label">Organization ID</label>
                <input
                    v-model="localForm.iiko.organization_id"
                    type="text"
                    class="form-input"
                    placeholder="Введите ID организации"
                />
            </div>

            <div class="field-group">
                <label class="field-label">Terminal Group ID</label>
                <input
                    v-model="localForm.iiko.terminal_group_id"
                    type="text"
                    class="form-input"
                    placeholder="Введите ID группы терминалов"
                />
            </div>
        </div>

        <!-- FrontPad Tab -->
        <div v-if="activeTab === 'frontpad'" class="tab-content">
            <div class="section-header">
                <h6 class="section-title">
                    <i class="fa-solid fa-mobile-screen"></i>
                    Интеграция с FrontPad
                </h6>
                <p class="section-desc">
                    Настройка подключения к системе FrontPad
                </p>
            </div>

            <div class="field-group">
                <label class="field-label">Secret Key</label>
                <input
                    v-model="localForm.frontpad.secret"
                    type="password"
                    class="form-input"
                    placeholder="Введите секретный ключ"
                />
                <small class="field-hint">
                    <i class="fa-solid fa-lock"></i>
                    Ключ хранится в зашифрованном виде
                </small>
            </div>
        </div>

        <!-- Actions -->
        <div class="form-actions">
            <div v-if="saveStatus" class="save-status" :class="saveStatus.type">
                <i :class="saveStatus.icon"></i>
                {{ saveStatus.message }}
            </div>

            <button
                type="submit"
                class="btn-save"
                :disabled="isLoading || !isDirty"
            >
                <i v-if="isLoading" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-check"></i>
                <span>{{ isLoading ? 'Сохранение...' : 'Сохранить настройки' }}</span>
            </button>
        </div>
    </form>
</template>

<script>
import WebhooksManager from './WebhooksManager.vue'
import AccessLinkManager from './AccessLinkManager.vue'
import {useWorkspaceStore} from "@/store/workspace.js";
export default {
    name: 'SettingsForm',
    components: {
        WebhooksManager,
        AccessLinkManager
    },
    props: {
        modelValue: {
            type: Object,
            default: () => ({})
        },

    },

    emits: ['update:modelValue', 'save', 'test'],

    data() {
        return {
            activeTab: 'base',
            isLoading: false,
            saveStatus: null,
            urlError: '',

            localForm: {
                url: '',
                vk_shop_links: '',
                iiko: {
                    api_login: '',
                    organization_id: '',
                    terminal_group_id: ''
                },
                frontpad: {
                    secret: ''
                }
            },

            tabs: [
                { key: 'base', label: 'Основные', icon: 'fa-solid fa-gear' },
                { key: 'vk', label: 'VK', icon: 'fa-brands fa-vk' },
                { key: 'iiko', label: 'IIKO', icon: 'fa-solid fa-utensils' },
                { key: 'frontpad', label: 'FrontPad', icon: 'fa-solid fa-mobile-screen' }
            ]
        }
    },

    computed: {
        store() {
            return useWorkspaceStore()
        },
        isDirty() {
            return JSON.stringify(this.localForm) !== JSON.stringify(this.modelValue)
        }
    },

    watch: {
        modelValue: {
            deep: true,
            immediate: true,
            handler(newVal) {
                this.fillForm(newVal)
            }
        },

        'localForm.url'(newVal) {
            this.validateUrl(newVal)
        }
    },

    methods: {
        fillForm(data) {
            if (!data) return

            this.localForm.url = data.url || ''
            this.localForm.vk_shop_links = data.vk_shop_links || ''

            this.localForm.iiko = {
                api_login: data.iiko?.api_login || '',
                organization_id: data.iiko?.organization_id || '',
                terminal_group_id: data.iiko?.terminal_group_id || ''
            }

            this.localForm.frontpad = {
                secret: data.frontpad?.secret || ''
            }
        },

        validateUrl(url) {
            if (!url) {
                this.urlError = ''
                return true
            }

            const urlPattern = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/
            if (!urlPattern.test(url)) {
                this.urlError = 'Некорректный URL'
                return false
            }

            this.urlError = ''
            return true
        },

        async save() {
            if (!this.validateUrl(this.localForm.url)) {
                this.activeTab = 'base'
                return
            }

            this.isLoading = true
            this.saveStatus = null

            try {
                this.$emit('save', { ...this.localForm })

                // Показываем успех
                this.saveStatus = {
                    type: 'success',
                    icon: 'fa-solid fa-circle-check',
                    message: 'Настройки сохранены'
                }

                // Очищаем статус через 3 секунды
                setTimeout(() => {
                    this.saveStatus = null
                }, 3000)
            } catch (error) {
                this.saveStatus = {
                    type: 'error',
                    icon: 'fa-solid fa-circle-xmark',
                    message: 'Ошибка сохранения'
                }
                console.error('Save failed:', error)
            } finally {
                this.isLoading = false
            }
        },

        onTest() {
            if (!this.validateUrl(this.localForm.url)) {
                return
            }

            this.$emit('test', { ...this.localForm })
        },

        createNewSession() {
            window.open('/create-session', '_blank')
        },

        async refreshSession() {
            this.isLoading = true
            try {
                // Здесь должна быть логика обновления сессии
                // Через emit или напрямую
                console.log('Refreshing session...')
            } finally {
                this.isLoading = false
            }
        }
    }
}
</script>

<style scoped>
.settings-form {

}

/* === Tabs === */
.settings-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0;
}

.tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: none;
    border-bottom: 2px solid transparent;
    background: transparent;
    color: #6c757d;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: -2px;
}

.tab-btn:hover {
    color: #0d6efd;
    background: #f8f9fa;
}

.tab-btn.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
}

.tab-btn i {
    font-size: 16px;
}

/* === Tab Content === */
.tab-content {
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* === Sections === */
.section-header {
    margin-bottom: 20px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin: 0 0 4px 0;
}

.section-title i {
    color: #0d6efd;
}

.section-desc {
    font-size: 13px;
    color: #6c757d;
    margin: 0;
}

.section-divider {
    height: 1px;
    background: #e9ecef;
    margin: 24px 0;
}

/* === Fields === */
.field-group {
    margin-bottom: 20px;
}

.field-label {
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

.form-input.is-invalid {
    border-color: #dc3545;
}

.form-input.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
}

.field-error {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    font-size: 13px;
    color: #dc3545;
}

.field-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    font-size: 12px;
    color: #6c757d;
}

/* === Input with action === */
.input-with-action {
    display: flex;
    gap: 8px;
}

.input-with-action .form-input {
    flex: 1;
}

.action-btn {
    padding: 10px 16px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover:not(:disabled) {
    background: #f8f9fa;
    color: #0d6efd;
    border-color: #0d6efd;
}

.action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* === Info box === */
.info-box {
    display: flex;
    gap: 12px;
    padding: 12px 16px;
    background: #e7f1ff;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #084298;
}

.info-box i {
    font-size: 16px;
    flex-shrink: 0;
    margin-top: 2px;
}

.info-box small {
    color: #6c757d;
}

/* === Action buttons === */
.action-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-action.primary {
    background: #0d6efd;
    color: #fff;
}

.btn-action.primary:hover {
    background: #0b5ed7;
}

.btn-action.secondary {
    background: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
}

.btn-action.secondary:hover:not(:disabled) {
    background: #e9ecef;
}

.btn-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

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

/* === Form actions === */
.form-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e9ecef;
}

.save-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-8px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.save-status.success {
    color: #198754;
}

.save-status.error {
    color: #dc3545;
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
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
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.btn-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* === Адаптив === */
@media (max-width: 576px) {
    .settings-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .tab-label {
        display: none;
    }

    .tab-btn {
        padding: 10px 12px;
    }

    .action-buttons {
        flex-direction: column;
    }

    .btn-action {
        width: 100%;
        justify-content: center;
    }

    .form-actions {
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .btn-save {
        width: 100%;
        justify-content: center;
    }
}
</style>
