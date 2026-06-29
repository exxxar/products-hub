<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid" :class="modalIcon"></i>
                        {{ modalTitle }}
                    </h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">
                    <!-- Блокировка после 5 попыток -->
                    <div v-if="isLocked" class="lock-warning">
                        <i class="fa-solid fa-hourglass-half"></i>
                        <div>
                            <strong>Ввод заблокирован</strong>
                            <p>Повторите через <strong>{{ lockTimeLeft }}</strong></p>
                        </div>
                    </div>

                    <template v-else>
                        <!-- Режим: меню действий -->
                        <template v-if="currentMode === 'menu'">
                            <p class="modal-hint">
                                Товары разблокированы. Выберите действие:
                            </p>

                            <div class="action-menu">
                                <button
                                    type="button"
                                    class="action-item"
                                    @click="switchMode('change')"
                                >
                                    <i class="fa-solid fa-key"></i>
                                    <div class="action-content">
                                        <strong>Сменить код</strong>
                                        <small>Установить новый мастер-код</small>
                                    </div>
                                    <i class="fa-solid fa-chevron-right action-arrow"></i>
                                </button>

                                <button
                                    type="button"
                                    class="action-item"
                                    @click="switchMode('reset')"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                    <div class="action-content">
                                        <strong>Удалить код</strong>
                                        <small>Снять защиту с товаров</small>
                                    </div>
                                    <i class="fa-solid fa-chevron-right action-arrow"></i>
                                </button>

                                <button
                                    type="button"
                                    class="action-item action-danger"
                                    @click="lockSession"
                                >
                                    <i class="fa-solid fa-lock"></i>
                                    <div class="action-content">
                                        <strong>Заблокировать</strong>
                                        <small>Защитить товары (без удаления кода)</small>
                                    </div>
                                    <i class="fa-solid fa-chevron-right action-arrow"></i>
                                </button>
                            </div>
                        </template>

                        <!-- Режим: установка нового кода -->
                        <template v-else-if="currentMode === 'set'">
                            <p class="modal-hint">
                                Придумайте мастер-код для защиты товаров от случайного удаления и редактирования.
                                Минимум 4 символа.
                            </p>

                            <div class="form-group">
                                <label>Новый мастер-код</label>
                                <input
                                    v-model="newCode"
                                    type="password"
                                    class="form-input"
                                    placeholder="Введите код"
                                    @keyup.enter="$refs.confirmInput?.focus()"
                                    autofocus
                                />
                            </div>

                            <div class="form-group">
                                <label>Подтверждение кода</label>
                                <input
                                    ref="confirmInput"
                                    v-model="confirmCode"
                                    type="password"
                                    class="form-input"
                                    :class="{ 'is-invalid': confirmError }"
                                    placeholder="Повторите код"
                                    @keyup.enter="handleSubmit"
                                />
                                <div v-if="confirmError" class="invalid-feedback">
                                    {{ confirmError }}
                                </div>
                            </div>
                        </template>

                        <!-- Режим: верификация (разблокировка) -->
                        <template v-else-if="currentMode === 'verify'">
                            <p class="modal-hint">
                                Введите мастер-код для разблокировки редактирования товаров.
                            </p>

                            <div class="form-group">
                                <label>Мастер-код</label>
                                <input
                                    v-model="code"
                                    type="password"
                                    class="form-input"
                                    :class="{ 'is-invalid': errorMessage }"
                                    placeholder="Введите код"
                                    @keyup.enter="handleSubmit"
                                    autofocus
                                />
                                <div v-if="errorMessage" class="invalid-feedback">
                                    {{ errorMessage }}
                                </div>
                                <small v-if="attemptsLeft !== null && attemptsLeft < 5" class="form-hint warning">
                                    Осталось попыток: {{ attemptsLeft }}
                                </small>
                            </div>
                        </template>

                        <!-- Режим: смена кода -->
                        <template v-else-if="currentMode === 'change'">
                            <p class="modal-hint">
                                Введите текущий код и новый мастер-код.
                            </p>

                            <div class="form-group">
                                <label>Текущий мастер-код</label>
                                <input
                                    v-model="currentCode"
                                    type="password"
                                    class="form-input"
                                    placeholder="Текущий код"
                                    @keyup.enter="$refs.newCodeInput?.focus()"
                                    autofocus
                                />
                            </div>

                            <div class="form-group">
                                <label>Новый мастер-код</label>
                                <input
                                    ref="newCodeInput"
                                    v-model="newCode"
                                    type="password"
                                    class="form-input"
                                    placeholder="Новый код"
                                    @keyup.enter="$refs.confirmNewInput?.focus()"
                                />
                            </div>

                            <div class="form-group">
                                <label>Подтверждение нового кода</label>
                                <input
                                    ref="confirmNewInput"
                                    v-model="confirmNewCode"
                                    type="password"
                                    class="form-input"
                                    :class="{ 'is-invalid': confirmError }"
                                    placeholder="Повторите новый код"
                                    @keyup.enter="handleSubmit"
                                />
                                <div v-if="confirmError" class="invalid-feedback">
                                    {{ confirmError }}
                                </div>
                            </div>
                        </template>

                        <!-- Режим: сброс кода -->
                        <template v-else-if="currentMode === 'reset'">
                            <div class="danger-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <div>
                                    <strong>Удаление мастер-кода</strong>
                                    <p>После удаления товары больше не будут защищены.</p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Введите мастер-код для подтверждения</label>
                                <input
                                    v-model="code"
                                    type="password"
                                    class="form-input"
                                    :class="{ 'is-invalid': errorMessage }"
                                    placeholder="Мастер-код"
                                    @keyup.enter="handleSubmit"
                                    autofocus
                                />
                                <div v-if="errorMessage" class="invalid-feedback">
                                    {{ errorMessage }}
                                </div>
                            </div>
                        </template>
                    </template>
                </div>

                <div class="modal-footer">
                    <!-- Кнопка "Назад" для режимов change/reset -->
                    <button
                        v-if="currentMode === 'change' || currentMode === 'reset'"
                        type="button"
                        class="btn-back"
                        @click="switchMode('menu')"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                        Назад
                    </button>

                    <button
                        v-if="currentMode !== 'menu'"
                        type="button"
                        class="btn-cancel"
                        @click="hide"
                    >
                        Отмена
                    </button>

                    <button
                        v-if="!isLocked && currentMode !== 'menu'"
                        type="button"
                        class="btn-primary-action"
                        :class="{ 'btn-danger': currentMode === 'reset' }"
                        @click="handleSubmit"
                        :disabled="isSubmitting || !canSubmit"
                    >
                        <i v-if="isSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid" :class="submitIcon"></i>
                        {{ submitLabel }}
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
    name: 'MasterCodeModal',

    emits: ['success', 'locked', 'lock-session'],

    // ❌ Убираем prop currentMode
    // props: { currentMode: { ... } },

    data() {
        return {
            store: useWorkspaceStore(),
            modal: null,

            // ✅ Локальное состояние вместо prop
            currentMode: 'verify',

            code: '',
            newCode: '',
            confirmCode: '',
            currentCode: '',
            confirmNewCode: '',
            errorMessage: '',
            confirmError: '',
            isSubmitting: false,
            lockTimer: null,
            lockTimeLeft: '',
        }
    },

    computed: {
        modalTitle() {
            const titles = {
                menu: 'Мастер-код',
                set: 'Установка мастер-кода',
                verify: 'Ввод мастер-кода',
                change: 'Смена мастер-кода',
                reset: 'Удаление мастер-кода',
            }
            return titles[this.currentMode] || 'Мастер-код'
        },

        modalIcon() {
            const icons = {
                menu: 'fa-shield-halved',
                set: 'fa-lock',
                verify: 'fa-lock-open',
                change: 'fa-key',
                reset: 'fa-unlock',
            }
            return icons[this.currentMode] || 'fa-lock'
        },

        submitLabel() {
            const labels = {
                set: 'Установить код',
                verify: 'Разблокировать',
                change: 'Сменить код',
                reset: 'Удалить код',
            }
            return labels[this.currentMode] || 'Подтвердить'
        },

        submitIcon() {
            const icons = {
                set: 'fa-lock',
                verify: 'fa-lock-open',
                change: 'fa-key',
                reset: 'fa-trash',
            }
            return icons[this.currentMode] || 'fa-check'
        },

        canSubmit() {
            switch (this.currentMode) {
                case 'set':
                    return this.newCode.length >= 4 && this.newCode === this.confirmCode
                case 'verify':
                case 'reset':
                    return this.code.length > 0
                case 'change':
                    return this.currentCode.length > 0 &&
                        this.newCode.length >= 4 &&
                        this.newCode === this.confirmNewCode
                default:
                    return false
            }
        },

        isLocked() {
            return this.store.masterStatus?.is_locked || false
        },

        attemptsLeft() {
            return this.store.masterAttemptsLeft
        },
    },

    watch: {
        isLocked: {
            immediate: true,
            handler(locked) {
                if (locked) {
                    this.startLockTimer()
                } else {
                    this.stopLockTimer()
                }
            }
        },

        confirmCode(val) {
            this.confirmError = (val && val !== this.newCode) ? 'Коды не совпадают' : ''
        },

        confirmNewCode(val) {
            this.confirmError = (val && val !== this.newCode) ? 'Коды не совпадают' : ''
        },
    },

    methods: {
        // ✅ Публичный метод для установки режима из родителя
        setMode(currentMode) {
            const validModes = ['menu', 'set', 'verify', 'change', 'reset']
            if (!validModes.includes(currentMode)) {
                console.warn(`Invalid currentMode: ${currentMode}`)
                return
            }
            this.currentMode = currentMode
            this.resetForm()
        },

        // ✅ Публичный метод для показа модалки с нужным режимом
        show(currentMode = null) {
            if (currentMode) {
                this.setMode(currentMode)
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

        resetForm() {
            this.code = ''
            this.newCode = ''
            this.confirmCode = ''
            this.currentCode = ''
            this.confirmNewCode = ''
            this.errorMessage = ''
            this.confirmError = ''
            this.isSubmitting = false
        },

        // ✅ Переключение режима — теперь работает с локальным состоянием
        switchMode(newMode) {
            this.setMode(newMode)
        },

        async lockSession() {
            this.store.lockMaster()
            this.store.clearUnlockToken()
            this.$emit('lock-session')
            this.hide()
        },

        async handleSubmit() {
            if (!this.canSubmit || this.isSubmitting) return

            this.isSubmitting = true
            this.errorMessage = ''

            try {
                let result

                switch (this.currentMode) {
                    case 'set':
                        result = await this.store.setMasterCode(this.newCode, this.confirmCode)
                        break
                    case 'verify':
                        result = await this.store.verifyMasterCode(this.code)
                        break
                    case 'change':
                        result = await this.store.changeMasterCode(
                            this.currentCode,
                            this.newCode,
                            this.confirmNewCode
                        )
                        break
                    case 'reset':
                        result = await this.store.resetMasterCode(this.code)
                        break
                }

                if (result?.success || result?.has_code === false) {
                    this.$emit('success', { mode: this.currentMode })
                    this.hide()
                } else if (result?.locked) {
                    this.$emit('locked')
                    this.hide()
                } else if (result?.error) {
                    this.errorMessage = result.error

                    if (result.error.includes('уже установлен')) {
                        setTimeout(() => {
                            this.switchMode('verify')
                        }, 2000)
                    }
                } else {
                    this.errorMessage = result?.message || 'Неверный код'
                }
            } catch (error) {
                console.error('Master code action failed:', error)
                this.errorMessage = 'Произошла ошибка при выполнении операции'
            } finally {
                this.isSubmitting = false
            }
        },

        startLockTimer() {
            this.stopLockTimer()
            this.updateLockTime()
            this.lockTimer = setInterval(() => {
                this.updateLockTime()
            }, 1000)
        },

        stopLockTimer() {
            if (this.lockTimer) {
                clearInterval(this.lockTimer)
                this.lockTimer = null
            }
        },

        updateLockTime() {
            const lockedUntil = this.store.masterLockedUntil
            if (!lockedUntil) {
                this.lockTimeLeft = ''
                return
            }

            const diff = Math.max(0, Math.floor((new Date(lockedUntil) - new Date()) / 1000))

            if (diff <= 0) {
                this.lockTimeLeft = ''
                this.stopLockTimer()
                this.store.loadWorkspace()
                return
            }

            const minutes = Math.floor(diff / 60)
            const seconds = diff % 60
            this.lockTimeLeft = `${minutes}:${seconds.toString().padStart(2, '0')}`
        },
    },

    mounted() {
        this.modal = new Modal(this.$refs.modal, {
            backdrop: 'static',
            keyboard: false,
        })
    },

    beforeUnmount() {
        this.stopLockTimer()
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

.modal-hint {
    font-size: 14px;
    color: #6c757d;
    margin: 0 0 20px 0;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
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

.form-input.is-invalid {
    border-color: #dc3545;
}

.form-input.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.invalid-feedback {
    color: #dc3545;
    font-size: 12px;
    margin-top: 6px;
}

.form-hint {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #6c757d;
}

.form-hint.warning {
    color: #dc3545;
    font-weight: 500;
}

/* === Action Menu === */
.action-menu {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
    width: 100%;
}

.action-item:hover {
    border-color: #0d6efd;
    background: #f8f9ff;
    transform: translateX(2px);
}

.action-item > i:first-child {
    font-size: 20px;
    color: #0d6efd;
    width: 24px;
    text-align: center;
    flex-shrink: 0;
}

.action-content {
    flex: 1;
    min-width: 0;
}

.action-content strong {
    display: block;
    font-size: 14px;
    color: #212529;
    margin-bottom: 2px;
}

.action-content small {
    font-size: 12px;
    color: #6c757d;
}

.action-arrow {
    color: #adb5bd;
    font-size: 12px;
    flex-shrink: 0;
}

.action-item.action-danger > i:first-child {
    color: #dc3545;
}

.action-item.action-danger:hover {
    border-color: #dc3545;
    background: #fff5f5;
}

/* === Lock Warning === */
.lock-warning {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: #f8d7da;
    border: 1px solid #f5c2c7;
    border-radius: 8px;
    color: #842029;
}

.lock-warning i {
    font-size: 24px;
    flex-shrink: 0;
}

.lock-warning strong {
    display: block;
    margin-bottom: 4px;
}

.lock-warning p {
    margin: 0;
    font-size: 14px;
}

/* === Danger Warning === */
.danger-warning {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: #fff3cd;
    border: 1px solid #ffecb5;
    border-radius: 8px;
    color: #664d03;
    margin-bottom: 20px;
}

.danger-warning i {
    font-size: 24px;
    flex-shrink: 0;
}

.danger-warning strong {
    display: block;
    margin-bottom: 4px;
}

.danger-warning p {
    margin: 0;
    font-size: 13px;
}

/* === Footer === */
.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 16px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 14px;
    cursor: pointer;
    margin-right: auto;
}

.btn-back:hover {
    background: #f8f9fa;
    color: #0d6efd;
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
}

.btn-cancel:hover {
    background: #f8f9fa;
}

.btn-primary-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
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

.btn-primary-action:hover:not(:disabled) {
    background: #0b5ed7;
}

.btn-primary-action.btn-danger {
    background: #dc3545;
}

.btn-primary-action.btn-danger:hover:not(:disabled) {
    background: #bb2d3b;
}

.btn-primary-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
