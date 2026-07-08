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
                        <div class="name-input-wrapper">
                            <EmojiPicker v-model="emoji"/>
                            <input
                                v-model="form.name"
                                type="text"
                                class="form-input"
                                :placeholder="emoji ? 'Название категории...' : 'Например: Одежда'"
                            />
                        </div>
                        <small class="form-hint" v-if="emoji">
                            <i class="fa-solid fa-circle-info"></i>
                            Эмодзи будет отображаться в начале названия
                        </small>
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
import {Modal} from 'bootstrap'
import {useWorkspaceStore} from '@/store/workspace.js'
import EmojiPicker from '@/Components/Layout/EmojiPicker.vue'

export default {
    name: 'CategoryModal',

    components: {
        EmojiPicker
    },

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
            emoji: '',
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
            return this.store.categories.filter(c => c.id !== this.category?.id)
        },

        // ✅ Полное название с эмодзи
        fullName() {
            const name = this.form.name.trim()
            if (!name) return ''
            return this.emoji ? `${this.emoji} ${name}` : name
        }
    },

    methods: {
        show() {

            this.$nextTick(() => {
                if (this.modal) {

                    if (this.category) {
                        // ✅ Извлекаем эмодзи из начала названия
                        const name = this.category.name || ''
                        const emojiMatch = name.match(/^(\p{Emoji_Presentation}|\p{Emoji}\uFE0F)\s*/u)

                        if (emojiMatch) {
                            this.emoji = emojiMatch[1]
                            this.form.name = name.replace(emojiMatch[0], '').trim()
                        } else {
                            this.emoji = ''
                            this.form.name = name
                        }

                        this.form.description = this.category.description || ''
                        this.form.parent_id = this.category.parent_id || null
                    } else {
                        this.emoji = ''
                        this.form = {
                            name: '',
                            description: '',
                            parent_id: null
                        }
                    }


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
                // ✅ Отправляем полное название с эмодзи
                const data = {
                    ...this.form,
                    name: this.fullName
                }

                await this.$emit('save', data, this.category?.id)
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

.name-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}

.name-input-wrapper .form-input {
    flex: 1;
}

.form-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    font-size: 12px;
    color: #6c757d;
}

.form-hint i {
    color: #0d6efd;
    font-size: 11px;
}
</style>
