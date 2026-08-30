<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="modelValue" class="modal-overlay" @click.self="close">
                <div class="modal-content-custom">
                    <div class="modal-header-custom">
                        <h5 class="modal-title-custom">
                            <i class="fa-regular fa-copy"></i>
                            Копирование доски
                        </h5>
                        <button class="btn-close-custom" @click="close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="modal-body-custom">
                        <p class="modal-description">
                            Укажите название и описание для новой копии доски. Все товары, категории и коллекции будут скопированы.
                        </p>

                        <div class="field-group">
                            <label class="field-label">Название доски *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="form-input"
                                placeholder="Например: Чача Пури (Филиал)"
                                ref="nameInput"
                                @keyup.enter="confirm"
                            />
                        </div>

                        <div class="field-group">
                            <label class="field-label">Описание</label>
                            <textarea
                                v-model="form.description"
                                class="form-input form-textarea"
                                placeholder="Краткое описание новой доски..."
                                rows="3"
                            ></textarea>
                        </div>
                    </div>

                    <div class="modal-footer-custom">
                        <button class="btn-cancel" @click="close" :disabled="isConfirming">Отмена</button>
                        <button
                            class="btn-save"
                            @click="confirm"
                            :disabled="!isFormValid || isConfirming"
                        >
                            <i v-if="isConfirming" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-copy"></i>
                            {{ isConfirming ? 'Копирование...' : 'Скопировать' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script>
export default {
    name: 'DuplicateWorkspaceModal',
    props: {
        modelValue: { type: Boolean, default: false },
        workspace: { type: Object, default: null }
    },
    emits: ['update:modelValue', 'confirm'],
    data() {
        return {
            form: { name: '', description: '' },
            isConfirming: false
        }
    },
    computed: {
        isFormValid() {
            return this.form.name.trim().length > 0
        }
    },
    watch: {
        modelValue(val) {
            if (val && this.workspace) {
                // Предзаполняем данными исходной доски для удобства
                this.form = {
                    name: (this.workspace.name || '') + ' (Копия)',
                    description: this.workspace.description || ''
                }
                document.body.style.overflow = 'hidden'
                this.$nextTick(() => this.$refs.nameInput?.focus())
            } else {
                document.body.style.overflow = ''
            }
        }
    },
    methods: {
        close() {
            if (!this.isConfirming) {
                this.$emit('update:modelValue', false)
            }
        },
        async confirm() {
            if (!this.isFormValid || this.isConfirming) return

            this.isConfirming = true
            try {
                await this.$emit('confirm', { ...this.form })
            } finally {
                this.isConfirming = false
            }
        }
    },
    beforeUnmount() {
        document.body.style.overflow = ''
    }
}
</script>

<style scoped>
/* Используйте те же стили модалок, что и в AddWorkspaceToGroupModal */
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px; }
.modal-content-custom { background: #fff; border-radius: 14px; width: 100%; max-width: 500px; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2); animation: modalSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes modalSlideIn { from { opacity: 0; transform: translateY(-20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
.modal-header-custom { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e9ecef; }
.modal-title-custom { display: flex; align-items: center; gap: 10px; font-size: 17px; font-weight: 600; margin: 0; }
.modal-title-custom i { color: #0d6efd; }
.btn-close-custom { width: 32px; height: 32px; border: none; border-radius: 8px; background: #f1f3f5; color: #6c757d; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.modal-body-custom { padding: 20px; }
.modal-description { font-size: 13px; color: #6c757d; margin: 0 0 16px 0; line-height: 1.5; }
.field-group { margin-bottom: 16px; }
.field-label { display: block; font-size: 13px; font-weight: 500; color: #495057; margin-bottom: 6px; }
.form-input { width: 100%; padding: 10px 12px; border: 1px solid #dee2e6; border-radius: 8px; font-size: 14px; outline: none; font-family: inherit; }
.form-input:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); }
.form-textarea { resize: vertical; min-height: 80px; }
.modal-footer-custom { padding: 14px 20px; border-top: 1px solid #e9ecef; display: flex; justify-content: flex-end; gap: 8px; }
.btn-cancel { padding: 8px 16px; border: 1px solid #dee2e6; border-radius: 8px; background: #fff; color: #6c757d; cursor: pointer; }
.btn-cancel:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-save { display: inline-flex; align-items: center; gap: 6px; padding: 8px 20px; border: none; border-radius: 8px; background: #0d6efd; color: #fff; font-weight: 500; cursor: pointer; }
.btn-save:hover:not(:disabled) { background: #0b5ed7; }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
