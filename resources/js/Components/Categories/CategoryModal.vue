<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-tag"></i>
                        {{ isEdit ? 'Редактировать категорию' : 'Создать категорию' }}
                    </h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Название</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="form-input"
                            placeholder="Например: Одежда"
                        />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Описание (необязательно)</label>
                        <textarea
                            v-model="form.description"
                            class="form-input"
                            placeholder="Описание категории..."
                            rows="3"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Родительская категория</label>
                        <select v-model="form.parent_id" class="form-input">
                            <option :value="null">Нет (корневая категория)</option>
                            <option
                                v-for="cat in availableParents"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" @click="hide">Отмена</button>
                    <button
                        type="button"
                        class="btn-save"
                        @click="save"
                        :disabled="!form.name.trim()"
                    >
                        <i class="fa-solid fa-check"></i>
                        {{ isEdit ? 'Сохранить' : 'Создать' }}
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
    name: 'CategoryModal',

    props: {
        category: {
            type: Object,
            default: null
        }
    },

    emits: ['save'],

    data() {
        return {
            store: useWorkspaceStore(),
            modal: null,
            form: {
                name: '',
                description: '',
                parent_id: null
            }
        }
    },

    computed: {
        isEdit() {
            return !!this.category
        },

        availableParents() {
            // Исключаем текущую категорию из списка родителей
            return this.store.categories.filter(c => c.id !== this.category?.id)
        }
    },

    methods: {
        show() {
            if (this.category) {
                this.form = {
                    name: this.category.name || '',
                    description: this.category.description || '',
                    parent_id: this.category.parent_id || null
                }
            } else {
                this.form = {
                    name: '',
                    description: '',
                    parent_id: null
                }
            }

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

        async save() {
            if (!this.form.name.trim()) return

            try {
                await this.$emit('save', { ...this.form }, this.category?.id)
                this.hide()
            } catch (error) {
                console.error('Save category failed:', error)
            }
        }
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
    color: #212529;
}

.modal-title i {
    color: #0d6efd;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 16px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
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
    font-family: inherit;
}

.form-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

textarea.form-input {
    resize: vertical;
}

select.form-input {
    cursor: pointer;
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

.btn-save:hover:not(:disabled) {
    background: #0b5ed7;
}

.btn-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
