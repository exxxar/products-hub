<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-plus"></i>
                        Новая доска
                    </h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">
                    <div class="field-group">
                        <label class="field-label">Название *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="form-input"
                            placeholder="Например: Магазин одежды"
                            ref="nameInput"
                        />
                    </div>

                    <div class="field-group">
                        <label class="field-label">Метка (2-3 символа)</label>
                        <input
                            v-model="form.label"
                            type="text"
                            class="form-input"
                            placeholder="МС"
                            maxlength="3"
                        />
                    </div>

                    <div class="field-group">
                        <label class="field-label">Цвет</label>
                        <div class="color-picker">
                            <input v-model="form.color" type="color" class="color-input" />
                            <input v-model="form.color" type="text" class="form-input" />
                        </div>
                    </div>

                    <!-- Превью -->
                    <div class="preview-section">
                        <div class="preview-card">
                            <div class="preview-icon" :style="{ background: form.color }">
                                {{ previewInitials }}
                            </div>
                            <div class="preview-info">
                                <div class="preview-name">{{ form.name || 'Название доски' }}</div>
                                <div class="preview-label" v-if="form.label">{{ form.label }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" @click="hide">Отмена</button>
                    <button
                        type="button"
                        class="btn-save"
                        @click="create"
                        :disabled="isCreating || !isValid"
                    >
                        <i v-if="isCreating" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        Создать
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
    name: 'CreateWorkspaceModal',

    emits: ['created'],

    data() {
        return {
            store: useWorkspaceStore(),
            modal: null,
            isCreating: false,
            form: {
                name: '',
                label: '',
                color: '#0d6efd',
            }
        }
    },

    computed: {
        isValid() {
            return this.form.name.trim().length > 0
        },

        previewInitials() {
            if (this.form.label) return this.form.label.toUpperCase()
            if (this.form.name) return this.form.name.substring(0, 2).toUpperCase()
            return 'WS'
        }
    },

    methods: {
        show() {
            this.resetForm()
            this.$nextTick(() => {
                if (this.modal) this.modal.show()
                this.$refs.nameInput?.focus()
            })
        },

        hide() {
            if (this.modal) this.modal.hide()
        },

        resetForm() {
            this.form = { name: '', label: '', color: '#0d6efd' }
        },

        async create() {
            if (!this.isValid || this.isCreating) return

            this.isCreating = true

            try {
                const result = await this.store.createAndLinkWorkspace(this.form)
                this.$emit('created', result.workspace)
                this.hide()
                this.$notify?.success('Доска создана')
            } catch (error) {
                this.$notify?.error('Ошибка при создании')
            } finally {
                this.isCreating = false
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
    color: #198754;
}

.modal-body {
    padding: 20px;
}

.field-group {
    margin-bottom: 16px;
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
    padding: 9px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
}

.form-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
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
}

.preview-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.preview-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 10px;
}

.preview-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
    font-weight: 700;
}

.preview-name {
    font-size: 14px;
    font-weight: 600;
    color: #212529;
}

.preview-label {
    font-size: 12px;
    color: #6c757d;
    margin-top: 2px;
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
