<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="modelValue" class="modal-overlay" @click.self="close">
                <div class="modal-content-custom">
                    <div class="modal-header-custom">
                        <h5 class="modal-title-custom">
                            <i class="fa-solid fa-layer-group"></i>
                            Создать группу досок
                        </h5>
                        <button class="btn-close-custom" @click="close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="modal-body-custom">
                        <div class="field-group">
                            <label class="field-label">Название группы</label>
                            <input
                                v-model="form.name"
                                class="form-input"
                                placeholder="Например: Сеть кофеен"
                                ref="nameInput"
                            />
                        </div>

                        <div class="field-group">
                            <label class="field-label">
                                Выберите доски для объединения (минимум 2)
                            </label>

                            <!-- Поиск -->
                            <div class="sheet-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Найти доску..."
                                    ref="searchInput"
                                />
                            </div>

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
                                        {{ ws.initials }}
                                    </div>
                                    <span>{{ ws.name }}</span>
                                </label>
                            </div>
                            <small class="field-hint" v-if="form.workspace_ids.length < 2">
                                <i class="fa-solid fa-circle-info"></i>
                                Выберите минимум 2 доски
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer-custom">
                        <button class="btn-cancel" @click="close">Отмена</button>
                        <button
                            class="btn-save"
                            :disabled="!isValid || isCreating"
                            @click="create"
                        >
                            <i v-if="isCreating" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-check"></i>
                            Создать группу
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
    name: 'GroupCreateModal',

    props: {
        modelValue: {
            type: Boolean,
            default: false
        }
    },

    emits: ['update:modelValue', 'created'],

    data() {
        return {
            searchQuery:'',
            store: useWorkspaceStore(),
            isCreating: false,
            form: {
                name: '',
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
            if (val) {
                // Инициализируем форму при открытии
                this.form = {
                    name: '',
                    workspace_ids: [this.store.currentWorkspace?.id].filter(Boolean)
                }
                document.body.style.overflow = 'hidden'

                // Автофокус на поле названия
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

        async create() {
            if (!this.isValid || this.isCreating) return

            this.isCreating = true

            try {
                const group = await this.store.createGroup(this.form)
                this.$emit('created', group)
                this.close()
            } catch (e) {
                this.$notify?.error('Ошибка при создании группы')
            } finally {
                this.isCreating = false
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
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #495057;
    margin-bottom: 8px;
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

.workspace-list {
    max-height: 250px;
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
}

/* === Search === */
.sheet-search {
    position: relative;
    padding: 12px 0px;
    border-bottom: 1px solid #e9ecef;
    flex-shrink: 0;
}

.sheet-search > i {
    position: absolute;
    left: 24px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 13px;
}

.sheet-search input {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    background: #f8f9fa;
}

.sheet-search input:focus {
    background: #fff;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

</style>
