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
            <!-- ✅ Информация о workspace -->
            <div class="section-header">
                <h6 class="section-title">
                    <i class="fa-solid fa-circle-info"></i>
                    Информация о workspace
                </h6>
                <p class="section-desc">
                    Основные параметры вашей рабочей области
                </p>
            </div>

            <div class="field-group">
                <label class="field-label">Название workspace *</label>
                <input
                    v-model="localForm.name"
                    type="text"
                    class="form-input"
                    placeholder="Например: Магазин одежды"
                    maxlength="255"
                />
            </div>

            <div class="field-group">
                <label class="field-label">Описание</label>
                <textarea
                    v-model="localForm.description"
                    class="form-input form-textarea"
                    placeholder="Краткое описание вашего workspace..."
                    rows="3"
                    maxlength="500"
                ></textarea>
                <small class="field-hint">
                    <i class="fa-solid fa-circle-info"></i>
                    {{ localForm.description?.length || 0 }} / 500 символов
                </small>
            </div>

            <div class="field-group">
                <label class="field-label">URL сайта</label>
                <input
                    v-model="localForm.url"
                    type="url"
                    class="form-input"
                    placeholder="https://example.com"
                />
                <div v-if="urlError" class="field-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ urlError }}
                </div>
            </div>

            <!-- Визуальные настройки -->
            <div class="section-divider"></div>

            <div class="section-header">
                <h6 class="section-title">
                    <i class="fa-solid fa-palette"></i>
                    Визуальное оформление
                </h6>
                <p class="section-desc">
                    Настройте внешний вид workspace
                </p>
            </div>

            <div class="visual-settings-grid">
                <!-- Логотип -->
                <div class="field-group">
                    <label class="field-label">Логотип</label>
                    <div class="logo-upload-wrapper">
                        <div v-if="logoPreview" class="logo-preview">
                            <img :src="logoPreview" alt="Logo" />
                            <button
                                type="button"
                                class="btn-remove-logo"
                                @click="removeLogo"
                                title="Удалить логотип"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <label v-else class="logo-upload-btn">
                            <input
                                type="file"
                                accept="image/*"
                                @change="uploadLogo"
                                style="display: none"
                            />
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Загрузить</span>
                        </label>
                    </div>
                </div>

                <!-- Цвет и метка -->
                <div class="visual-fields">
                    <div class="field-group">
                        <label class="field-label">Цвет</label>
                        <div class="color-picker">
                            <input
                                v-model="localForm.visual.color"
                                type="color"
                                class="color-input"
                            />
                            <input
                                v-model="localForm.visual.color"
                                type="text"
                                class="form-input color-text"
                                maxlength="7"
                            />
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Метка (2-3 символа)</label>
                        <input
                            v-model="localForm.visual.label"
                            type="text"
                            class="form-input"
                            placeholder="МС"
                            maxlength="3"
                        />
                        <small class="field-hint">
                            Отображается в иконке workspace
                        </small>
                    </div>
                </div>
            </div>

            <!-- Превью -->
            <div class="workspace-preview">
                <div class="preview-card">
                    <div class="preview-icon" :style="{ background: localForm.visual.color }">
                        <img v-if="logoPreview" :src="logoPreview" alt="" />
                        <span v-else>{{ localForm.visual.label || localForm.name?.substring(0, 2) || 'WS' }}</span>
                    </div>
                    <div class="preview-info">
                        <div class="preview-name">{{ localForm.name || 'Название workspace' }}</div>
                        <div class="preview-desc" v-if="localForm.description">{{ localForm.description }}</div>
                    </div>
                </div>
            </div>

            <div class="section-divider"></div>

            <!-- Вебхуки -->
            <WebhooksManager />

            <div class="section-divider"></div>

            <!-- Сессия -->
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

            <!-- Токены доступа -->
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

        <!-- Linked Workspaces Tab -->
        <div v-if="activeTab === 'linked'" class="tab-content">
            <div class="section-header">
                <h6 class="section-title">
                    <i class="fa-solid fa-link"></i>
                    Связанные доски
                </h6>
                <p class="section-desc">
                    Объединяйте доски для быстрого переключения между ними
                </p>
            </div>

            <div class="info-box">
                <i class="fa-solid fa-circle-info"></i>
                <div>
                    <strong>Как это работает?</strong>
                    Связанные доски видны друг другу. Вы можете быстро переключаться
                    между ними через меню в верхней панели или горячие клавиши
                    <kbd>Ctrl</kbd> + <kbd>K</kbd>.
                </div>
            </div>

            <div v-if="isLoadingLinked" class="loading-state">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Загрузка связанных досок...</span>
            </div>

            <template v-else>
                <div v-if="store.linkedWorkspaces.length > 0" class="linked-list">
                    <div
                        v-for="workspace in store.linkedWorkspaces"
                        :key="workspace.id"
                        class="linked-card"
                    >
                        <div class="linked-icon" :style="{ background: workspace.color }">
                            <img v-if="workspace.logo_url" :src="workspace.logo_url" alt="" />
                            <span v-else>{{ workspace.initials }}</span>
                        </div>
                        <div class="linked-info">
                            <div class="linked-name">{{ workspace.name }}</div>
                            <div class="linked-meta">
                                <span v-if="workspace.label" class="linked-label">{{ workspace.label }}</span>
                                <span class="linked-uuid">{{ workspace.uuid.substring(0, 8) }}...</span>
                            </div>
                        </div>
                        <div class="linked-actions">
                            <button
                                type="button"
                                class="icon-btn"
                                @click="goToWorkspace(workspace)"
                                title="Перейти"
                            >
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </button>
                            <button
                                type="button"
                                class="icon-btn danger"
                                @click="confirmUnlink(workspace)"
                                title="Удалить связь"
                            >
                                <i class="fa-solid fa-link-slash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="empty-linked">
                    <i class="fa-solid fa-link-slash"></i>
                    <p>Нет связанных досок</p>
                    <span>Добавьте существующую доску или создайте новую</span>
                </div>

                <div class="linked-actions-bar">
                    <button
                        type="button"
                        class="btn-action primary"
                        @click="openCreateModal"
                    >
                        <i class="fa-solid fa-plus"></i>
                        <span>Создать и связать</span>
                    </button>
                    <button
                        type="button"
                        class="btn-action secondary"
                        @click="openAddModal"
                    >
                        <i class="fa-solid fa-link"></i>
                        <span>Добавить существующую</span>
                    </button>
                </div>
            </template>
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

            <button
                type="button"
                class="tool-btn"
                @click="$emit('open-activity-log')"
                title="История действий"
            >
                <i class="fa-solid fa-clock-rotate-left"></i> Лог действий
            </button>
        </div>
    </form>

    <!-- Модалки -->
    <CreateWorkspaceModal
        ref="createModal"
        @created="onWorkspaceCreated"
    />

    <AddWorkspaceModal
        ref="addModal"
        @added="onWorkspaceAdded"
    />

    <ConfirmModal
        v-model:show="showUnlinkConfirm"
        title="Удалить связь?"
        :description="`Удалить доску «${workspaceToUnlink?.name}» из связанных? Доска не будет удалена, только связь с ней.`"
        @accept="unlinkWorkspace"
        @reject="showUnlinkConfirm = false"
    />
</template>
<script>
import WebhooksManager from './WebhooksManager.vue'
import AccessLinkManager from './AccessLinkManager.vue'
import CreateWorkspaceModal from '@/Components/Groups/WorkspaceCreateModal.vue'
import AddWorkspaceModal from '@/Components/Groups/AddWorkspaceModal.vue'
import ConfirmModal from '@/Components/Layout/ConfirmModal.vue'
import { useWorkspaceStore } from "@/store/workspace.js"

export default {
    name: 'SettingsForm',
    components: {
        WebhooksManager,
        AccessLinkManager,
        CreateWorkspaceModal,
        AddWorkspaceModal,
        ConfirmModal
    },
    props: {
        modelValue: {
            type: Object,
            default: () => ({})
        },
    },

    emits: ['update:modelValue', 'save', 'test', 'open-activity-log'],

    data() {
        return {
            activeTab: 'base',
            isLoading: false,
            isLoadingLinked: false,
            saveStatus: null,
            urlError: '',
            showUnlinkConfirm: false,
            workspaceToUnlink: null,
            logoPreview: null,

            localForm: {
                name: '',
                description: '',
                url: '',
                visual: {
                    label: '',
                    color: '#0d6efd'
                },
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
                { key: 'linked', label: 'Доски', icon: 'fa-solid fa-link' },
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
        // ✅ Следим за изменениями в store (актуальные данные workspace)
        'store.name': {
            handler(newVal) {
                if (newVal && !this.localForm.name) {
                    this.initFromStore()
                }
            },
            immediate: true
        },
        modelValue: {
            deep: true,
            handler(newVal) {
                // Заполняем только если есть реальные данные
                if (newVal && (newVal.name || newVal.description || newVal.visual)) {
                    this.fillForm(newVal)
                }
            }
        },
        'localForm.url'(newVal) {
            this.validateUrl(newVal)
        },
        activeTab(newTab) {
            if (newTab === 'linked') {
                this.loadLinkedData()
            }
        }
    },

    mounted() {
        // ✅ Инициализируем форму из store при монтировании
        this.initFromStore()
    },

    methods: {
        // ✅ Новый метод — берём данные из store
        initFromStore() {


            const data = {
                name: this.store.name || '',
                description: this.store.description || '',
                url: this.store.url || '',
                visual: {
                    label: this.store.settings?.visual?.label || '',
                    color: this.store.color || '#0d6efd',
                    logo_url: this.store.logo_url || null
                },
                vk_shop_links: this.store.settings?.vk_shop_links || '',
                iiko: this.store.settings?.iiko || {
                    api_login: '',
                    organization_id: '',
                    terminal_group_id: ''
                },
                frontpad: this.store.settings?.frontpad || {
                    secret: ''
                }
            }

            this.fillForm(data)
        },

        fillForm(data) {
            if (!data) return

            this.localForm.name = data.name || ''
            this.localForm.description = data.description || ''
            this.localForm.url = data.url || ''
            this.localForm.vk_shop_links = data.vk_shop_links || ''

            this.localForm.visual = {
                label: data.visual?.label || '',
                color: data.visual?.color || '#0d6efd'
            }

            this.localForm.iiko = {
                api_login: data.iiko?.api_login || '',
                organization_id: data.iiko?.organization_id || '',
                terminal_group_id: data.iiko?.terminal_group_id || ''
            }

            this.localForm.frontpad = {
                secret: data.frontpad?.secret || ''
            }

            this.logoPreview = data.visual?.logo_url || null
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

        async uploadLogo(event) {
            const file = event.target.files[0]
            if (!file) return

            const reader = new FileReader()
            reader.onload = (e) => {
                this.logoPreview = e.target.result
            }
            reader.readAsDataURL(file)

            try {
                const response = await this.store.uploadWorkspaceLogo(file)
                this.logoPreview = response.logo_url
                this.$notify?.success('Логотип загружен')
            } catch (error) {
                console.error('Upload logo failed:', error)
                this.$notify?.error('Ошибка при загрузке логотипа')
                this.logoPreview = null
            }

            event.target.value = ''
        },

        async removeLogo() {
            try {
                await axios.delete(`/api/workspaces/${this.store.uuid}/workspace/logo`)
                this.logoPreview = null
                this.$notify?.success('Логотип удалён')
            } catch (error) {
                console.error('Remove logo failed:', error)
                this.$notify?.error('Ошибка при удалении')
            }
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

                this.saveStatus = {
                    type: 'success',
                    icon: 'fa-solid fa-circle-check',
                    message: 'Настройки сохранены'
                }

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

        createNewSession() {
            window.open('/create-session', '_blank')
        },

        async refreshSession() {
            this.isLoading = true
            try {
                console.log('Refreshing session...')
            } finally {
                this.isLoading = false
            }
        },

        async loadLinkedData() {
            this.isLoadingLinked = true
            try {
                await this.store.loadLinkedWorkspaces()
            } catch (error) {
                console.error('Load linked workspaces failed:', error)
            } finally {
                this.isLoadingLinked = false
            }
        },

        openCreateModal() {
            this.$refs.createModal.show()
        },

        openAddModal() {
            this.$refs.addModal.show()
        },

        confirmUnlink(workspace) {
            this.workspaceToUnlink = workspace
            this.showUnlinkConfirm = true
        },

        async unlinkWorkspace() {
            if (!this.workspaceToUnlink) return

            try {
                await this.store.unlinkWorkspace(this.workspaceToUnlink.uuid)

                this.$notify?.success({
                    title: 'Связь удалена',
                    message: `Доска «${this.workspaceToUnlink.name}» больше не связана`
                })

                this.showUnlinkConfirm = false
                this.workspaceToUnlink = null
            } catch (error) {
                this.$notify?.error('Ошибка при удалении связи')
            }
        },

        async onWorkspaceCreated(workspace) {
            await this.loadLinkedData()
            this.$notify?.success({
                title: 'Доска создана',
                message: `«${workspace.name}» добавлена в связанные`
            })
        },

        async onWorkspaceAdded() {
            await this.loadLinkedData()
        },

        goToWorkspace(workspace) {
            this.store.switchWorkspace(workspace.uuid)
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

/* === Кнопки инструментов === */
.tool-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: #f1f3f5;
    color: #495057;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.tool-btn:hover {
    background: #e7f1ff;
    color: #0d6efd;
    border-color: #cfe2ff;
}

.tool-btn.accent {
    background: #e7f1ff;
    color: #0d6efd;
    border-color: #cfe2ff;
}

.tool-btn .btn-label {
    font-weight: 500;
    font-size: 13px;
}

/* === Toggle Section === */
.toggle-section {
    margin-bottom: 24px;
}

.toggle-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.toggle-label:hover {
    background: #e9ecef;
}

.toggle-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.toggle-info strong {
    font-size: 14px;
    color: #212529;
}

.toggle-info span {
    font-size: 12px;
    color: #6c757d;
}

.toggle-switch {
    position: relative;
    width: 48px;
    height: 26px;
    flex-shrink: 0;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .3s;
    border-radius: 26px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: #0d6efd;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(22px);
}

.toggle-switch input:disabled + .toggle-slider {
    opacity: 0.5;
    cursor: not-allowed;
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
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
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

.info-box strong {
    display: block;
    margin-bottom: 4px;
}

.info-box kbd {
    display: inline-block;
    padding: 1px 5px;
    background: #fff;
    border: 1px solid #cfe2ff;
    border-radius: 4px;
    font-size: 11px;
    font-family: monospace;
}

/* === Loading === */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    color: #6c757d;
}

.loading-state i {
    font-size: 24px;
    margin-bottom: 8px;
    color: #0d6efd;
}

/* === Linked List === */
.linked-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
}

.linked-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    background: #fff;
    transition: all 0.15s ease;
}

.linked-card:hover {
    border-color: #cfe2ff;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.08);
}

.linked-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    flex-shrink: 0;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.linked-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.linked-info {
    flex: 1;
    min-width: 0;
}

.linked-name {
    font-size: 15px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 4px;
}

.linked-meta {
    display: flex;
    align-items: center;
    gap: 8px;
}

.linked-label {
    padding: 2px 8px;
    background: #e7f1ff;
    color: #084298;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.linked-uuid {
    font-size: 11px;
    color: #adb5bd;
    font-family: monospace;
}

.linked-actions {
    display: flex;
    gap: 6px;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.linked-card:hover .linked-actions {
    opacity: 1;
}

.icon-btn {
    width: 34px;
    height: 34px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    transition: all 0.15s ease;
}

.icon-btn:hover {
    background: #e7f1ff;
    border-color: #0d6efd;
    color: #0d6efd;
}

.icon-btn.danger:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

/* === Empty State === */
.empty-linked {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 20px;
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
}

.empty-linked i {
    font-size: 40px;
    color: #adb5bd;
    margin-bottom: 12px;
}

.empty-linked p {
    font-size: 16px;
    font-weight: 600;
    color: #495057;
    margin: 0 0 4px 0;
}

.empty-linked span {
    font-size: 13px;
    color: #6c757d;
}

/* === Actions Bar === */
.linked-actions-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
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

.form-textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
}

.field-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    font-size: 12px;
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
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
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
    from { opacity: 0; transform: translateX(-8px); }
    to { opacity: 1; transform: translateX(0); }
}

.save-status.success { color: #198754; }
.save-status.error { color: #dc3545; }

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

.tool-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: #f1f3f5;
    color: #495057;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.tool-btn:hover {
    background: #e7f1ff;
    color: #0d6efd;
    border-color: #cfe2ff;
}

/* === Responsive === */
@media (max-width: 576px) {
    .settings-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .tab-label { display: none; }
    .tab-btn { padding: 10px 12px; }

    .action-buttons,
    .linked-actions-bar {
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

    .linked-actions {
        opacity: 1;
    }
}

/* === Визуальные настройки === */
.visual-settings-grid {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 24px;
    align-items: start;
}

.logo-upload-wrapper {
    width: 120px;
}

.logo-preview {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e9ecef;
    background: #f8f9fa;
}

.logo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-remove-logo {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    transition: all 0.15s ease;
}

.btn-remove-logo:hover {
    background: #dc3545;
    transform: scale(1.1);
}

.logo-upload-btn {
    width: 120px;
    height: 120px;
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
    color: #6c757d;
}

.logo-upload-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #f8f9ff;
}

.logo-upload-btn i {
    font-size: 24px;
}

.logo-upload-btn span {
    font-size: 12px;
    font-weight: 500;
}

.visual-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.color-picker {
    display: flex;
    gap: 8px;
}

.color-input {
    width: 44px;
    height: 40px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    padding: 2px;
}

.color-text {
    flex: 1;
}

/* === Превью workspace === */
.workspace-preview {
    margin-top: 20px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 12px;
}

.preview-card {
    display: flex;
    align-items: center;
    gap: 14px;
}

.preview-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    font-weight: 700;
    flex-shrink: 0;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.preview-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-info {
    flex: 1;
    min-width: 0;
}

.preview-name {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 4px;
}

.preview-desc {
    font-size: 13px;
    color: #6c757d;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* === Responsive === */
@media (max-width: 768px) {
    .visual-settings-grid {
        grid-template-columns: 1fr;
    }

    .visual-fields {
        grid-template-columns: 1fr;
    }
}
</style>
