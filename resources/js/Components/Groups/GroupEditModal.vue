<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="modelValue" class="modal-overlay" @click.self="close">
                <div class="modal-content-custom">
                    <!-- Header -->
                    <div class="modal-header-custom">
                        <h5 class="modal-title-custom">
                            <i class="fa-solid fa-pen"></i>
                            <span>Редактировать группу</span>
                        </h5>
                        <button class="btn-close-custom" @click="close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body-custom">
                        <!-- Название -->
                        <div class="field-group">
                            <label class="field-label">Название группы</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="form-input"
                                placeholder="Например: Сеть кофеен"
                                ref="nameInput"
                            />
                        </div>

                        <!-- Цвет -->
                        <div class="field-group">
                            <label class="field-label">Цвет группы</label>
                            <div class="color-picker">
                                <input
                                    v-model="form.color"
                                    type="color"
                                    class="color-input"
                                />
                                <input
                                    v-model="form.color"
                                    type="text"
                                    class="form-input color-text"
                                    maxlength="7"
                                    placeholder="#0d6efd"
                                />
                            </div>
                        </div>

                        <!-- Состав группы -->
                        <div class="field-group">
                            <label class="field-label">
                                Состав группы
                                <span class="field-hint-inline">
                                    ({{ form.workspace_ids.length }}
                                    {{ pluralize(form.workspace_ids.length, 'доска', 'доски', 'досок') }})
                                </span>
                            </label>
                            <div class="workspace-list">
                                <label
                                    v-for="ws in filteredWorkspaces"
                                    :key="ws.id"
                                    class="ws-checkbox"
                                >
                                    <input
                                        type="checkbox"
                                        :value="ws.id"
                                        v-model="form.workspace_ids"
                                    />
                                    <div class="ws-icon" :style="{ background: ws.color }">
                                        {{ ws.initials || ws.name?.substring(0, 2) }}
                                    </div>
                                    <span class="ws-name">{{ ws.name }}</span>
                                    <span v-if="ws.label" class="ws-label">{{ ws.label }}</span>
                                </label>
                            </div>
                            <small class="field-hint" v-if="form.workspace_ids.length < 2">
                                <i class="fa-solid fa-circle-info"></i>
                                В группе должно быть минимум 2 доски
                            </small>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer-custom">
                        <button class="btn-cancel" @click="close">Отмена</button>
                        <button
                            class="btn-save"
                            :disabled="!isValid || isSaving"
                            @click="save"
                        >
                            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-check"></i>
                            Сохранить
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
    name: 'GroupEditModal',

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

    emits: ['update:modelValue', 'saved'],

    data() {
        return {
            searchQuery:'',
            store: useWorkspaceStore(),
            isSaving: false,
            form: {
                name: '',
                color: '#0d6efd',
                workspace_ids: []
            }
        }
    },

    computed: {
        filteredWorkspaces() {
            if (!this.searchQuery) return this.store.linkedWorkspaces
            const q = this.searchQuery.toLowerCase()
            return this.store.linkedWorkspaces.filter(w =>
                w.name.toLowerCase().includes(q) ||
                w.label?.toLowerCase().includes(q)
            )
        },

        isValid() {
            return this.form.name.trim() && this.form.workspace_ids.length >= 2
        }
    },

    watch: {
        modelValue(val) {
            if (val && this.group) {
                // Инициализируем форму из группы
                this.form = {
                    name: this.group.name || '',
                    color: this.group.color || '#0d6efd',
                    workspace_ids: (this.group.workspaces || []).map(w => w.id)
                }
                document.body.style.overflow = 'hidden'

                this.$nextTick(() => {
                    this.$refs.nameInput?.focus()
                })
            } else {
                document.body.style.overflow = ''
            }
        }
    },

    methods: {
        close() {
            this.$emit('update:modelValue', false)
        },

        async save() {
            if (!this.isValid || this.isSaving) return

            this.isSaving = true

            try {
                await this.store.updateGroup(this.group.id, {
                    name: this.form.name,
                    color: this.form.color,
                    workspace_ids: this.form.workspace_ids
                })

                this.$emit('saved')
                this.close()
            } catch (e) {
                console.error('Save group failed:', e)
                this.$notify?.error('Ошибка при сохранении')
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
    color: #6f42c1;
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

.field-group {
    margin-bottom: 16px;
}

.field-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #495057;
    margin-bottom: 8px;
}

.field-hint-inline {
    font-size: 11px;
    color: #6c757d;
    font-weight: 400;
}

.form-input {
    width: 100%;
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

.workspace-list {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 6px;
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
    flex-shrink: 0;
}

.ws-icon {
    width: 90px;
    min-height: 25px;
    border-radius: 6px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    font-weight: 700;
    flex-shrink: 0;
    text-align: center;
    padding: 7px;

}


.ws-name {
    flex: 1;
    font-size: 13px;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ws-label {
    font-size: 11px;
    color: #6c757d;
    padding: 1px 6px;
    background: #f1f3f5;
    border-radius: 6px;
    flex-shrink: 0;
}

.field-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    font-size: 12px;
    color: #dc3545;
}

.field-hint i {
    font-size: 11px;
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
    font-weight: 500;
    cursor: pointer;
}

.btn-save:hover:not(:disabled) {
    background: #0b5ed7;
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
}
</style>
