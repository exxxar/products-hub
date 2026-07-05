<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-link"></i>
                        Добавить доску по ссылке
                    </h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">
                    <div class="info-box">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            <strong>Вставьте ссылку на доску</strong>
                            <p>Формат: <code>http://domain/workspace/uuid</code></p>
                        </div>
                    </div>

                    <!-- Поле ввода -->
                    <div class="field-group">
                        <label class="field-label">Ссылка на доску</label>
                        <div class="input-with-button">
                            <input
                                v-model="workspaceUrl"
                                type="text"
                                class="form-input"
                                placeholder="http://127.0.0.1:8000/workspace/66e0c432-..."
                                @keyup.enter="findWorkspace"
                                ref="urlInput"
                            />
                            <button
                                type="button"
                                class="btn-find"
                                @click="findWorkspace"
                                :disabled="isSearching || !workspaceUrl.trim()"
                            >
                                <i v-if="isSearching" class="fa-solid fa-spinner fa-spin"></i>
                                <i v-else class="fa-solid fa-magnifying-glass"></i>
                                Найти
                            </button>
                        </div>
                        <div v-if="urlError" class="field-error">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            {{ urlError }}
                        </div>
                    </div>

                    <!-- Результат поиска -->
                    <div v-if="foundWorkspace" class="found-workspace">
                        <div class="found-card">
                            <div class="found-icon" :style="{ background: foundWorkspace.color }">
                                <img v-if="foundWorkspace.logo_url" :src="foundWorkspace.logo_url" alt="" />
                                <span v-else>{{ foundWorkspace.initials }}</span>
                            </div>
                            <div class="found-info">
                                <div class="found-name">{{ foundWorkspace.name }}</div>
                                <div class="found-meta">
                                    <span v-if="foundWorkspace.label" class="found-label">{{ foundWorkspace.label }}</span>
                                    <span class="found-uuid">{{ foundWorkspace.uuid.substring(0, 8) }}...</span>
                                </div>
                            </div>
                            <div v-if="isAlreadyLinked" class="already-linked">
                                <i class="fa-solid fa-check-circle"></i>
                                Уже связана
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" @click="hide">Отмена</button>
                    <button
                        type="button"
                        class="btn-save"
                        @click="addWorkspace"
                        :disabled="isSaving || !foundWorkspace || isAlreadyLinked"
                    >
                        <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        Добавить
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
    name: 'AddWorkspaceModal',

    emits: ['added'],

    data() {
        return {
            store: useWorkspaceStore(),
            modal: null,
            workspaceUrl: '',
            foundWorkspace: null,
            isAlreadyLinked: false,
            isSearching: false,
            isSaving: false,
            urlError: '',
        }
    },

    methods: {
        show() {
            this.resetForm()
            this.$nextTick(() => {
                if (this.modal) this.modal.show()
                this.$refs.urlInput?.focus()
            })
        },

        hide() {
            if (this.modal) this.modal.hide()
        },

        resetForm() {
            this.workspaceUrl = ''
            this.foundWorkspace = null
            this.isAlreadyLinked = false
            this.urlError = ''
        },

        extractUuid(url) {
            // Парсим UUID из ссылки
            const match = url.match(/workspace\/([a-f0-9-]{36})/i)
            if (match) {
                return match[1]
            }

            // Если ввели просто UUID
            if (/^[a-f0-9-]{36}$/i.test(url.trim())) {
                return url.trim()
            }

            return null
        },

        async findWorkspace() {
            if (!this.workspaceUrl.trim()) {
                this.urlError = 'Введите ссылку на доску'
                return
            }

            const uuid = this.extractUuid(this.workspaceUrl)
            if (!uuid) {
                this.urlError = 'Неверный формат ссылки. Ожидается UUID в конце URL'
                return
            }

            this.isSearching = true
            this.urlError = ''
            this.foundWorkspace = null

            try {
                const result = await this.store.findWorkspaceByUuid(uuid)

                if (result.success) {
                    this.foundWorkspace = result.workspace
                    this.isAlreadyLinked = result.is_already_linked
                } else {
                    this.urlError = result.error || 'Доска не найдена'
                }
            } catch (error) {
                if (error.response?.status === 404) {
                    this.urlError = 'Доска не найдена'
                } else {
                    this.urlError = 'Ошибка при поиске доски'
                }
            } finally {
                this.isSearching = false
            }
        },

        async addWorkspace() {
            if (!this.foundWorkspace || this.isAlreadyLinked || this.isSaving) return

            this.isSaving = true

            try {
                await this.store.linkWorkspace(this.foundWorkspace.uuid)

                this.$emit('added')
                this.hide()

                this.$notify?.success({
                    title: 'Доска добавлена',
                    message: `«${this.foundWorkspace.name}» теперь в списке связанных`
                })
            } catch (error) {
                this.$notify?.error('Ошибка при добавлении')
            } finally {
                this.isSaving = false
            }
        }
    },

    mounted() {
        this.modal = new Modal(this.$refs.modal)
    },

    beforeUnmount() {
        if (this.modal) this.modal.dispose()
    }
}
</script>

<style scoped>
.modal-content {
    border: none;
    border-radius: 14px;
}

.modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e9ecef;
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 17px;
    font-weight: 600;
}

.modal-title i {
    color: #0d6efd;
}

.modal-body {
    padding: 20px;
}

.info-box {
    display: flex;
    gap: 12px;
    padding: 12px 16px;
    background: #e7f1ff;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info-box i {
    font-size: 18px;
    color: #0d6efd;
    flex-shrink: 0;
    margin-top: 2px;
}

.info-box strong {
    display: block;
    font-size: 14px;
    color: #084298;
    margin-bottom: 4px;
}

.info-box p {
    margin: 0;
    font-size: 12px;
    color: #084298;
}

.info-box code {
    padding: 2px 6px;
    background: #fff;
    border: 1px solid #cfe2ff;
    border-radius: 4px;
    font-size: 11px;
    font-family: monospace;
}

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

.input-with-button {
    display: flex;
    gap: 8px;
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

.btn-find {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    background: #0d6efd;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.btn-find:hover:not(:disabled) {
    background: #0b5ed7;
}

.btn-find:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.field-error {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    font-size: 13px;
    color: #dc3545;
}

.field-error i {
    font-size: 12px;
}

/* === Found Workspace === */
.found-workspace {
    animation: slideIn 0.2s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.found-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 2px solid #198754;
    border-radius: 10px;
    background: #f8fffe;
}

.found-icon {
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

.found-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.found-info {
    flex: 1;
    min-width: 0;
}

.found-name {
    font-size: 15px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 4px;
}

.found-meta {
    display: flex;
    align-items: center;
    gap: 8px;
}

.found-label {
    padding: 2px 8px;
    background: #e7f1ff;
    color: #084298;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.found-uuid {
    font-size: 11px;
    color: #adb5bd;
    font-family: monospace;
}

.already-linked {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #d1e7dd;
    color: #0f5132;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
}

.already-linked i {
    font-size: 14px;
}

.modal-footer {
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
</style>
