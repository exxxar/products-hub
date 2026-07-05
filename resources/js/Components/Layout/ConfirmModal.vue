<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="confirm-modal-overlay"
                @click.self="reject"
            >
                <div class="confirm-modal" :class="[`type-${type}`]">
                    <!-- Декоративный фон -->
                    <div class="modal-bg-decoration"></div>

                    <!-- Header -->
                    <div class="modal-header-custom">
                        <div class="modal-icon-wrapper">
                            <i :class="iconClass"></i>
                        </div>
                        <div class="modal-header-content">
                            <h5 class="modal-title-custom">{{ title }}</h5>
                            <p class="modal-subtitle">{{ subtitle }}</p>
                        </div>
                        <button class="btn-close-custom" @click="reject" title="Закрыть">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body-custom">
                        <div class="description-text">
                            {{ description }}
                        </div>

                        <!-- Предупреждение -->
                        <div v-if="warning" class="warning-block">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>{{ warning }}</span>
                        </div>

                        <!-- Кастомный контент -->
                        <slot name="body"></slot>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer-custom">
                        <slot name="buttons">
                            <button
                                type="button"
                                class="btn-cancel-custom"
                                @click="reject"
                                :disabled="isLoading"
                            >
                                <i class="fa-solid fa-xmark"></i>
                                {{ cancelText }}
                            </button>

                            <button
                                type="button"
                                class="btn-confirm-custom"
                                :class="[`btn-${type}`]"
                                @click="accept"
                                :disabled="isLoading"
                            >
                                <i v-if="isLoading" class="fa-solid fa-spinner fa-spin"></i>
                                <i v-else :class="confirmIcon"></i>
                                {{ confirmText }}
                            </button>
                        </slot>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script>
export default {
    name: "ConfirmModal",

    props: {
        show: {
            type: Boolean,
            required: true
        },
        title: {
            type: String,
            default: "Подтверждение"
        },
        description: {
            type: String,
            default: ""
        },
        subtitle: {
            type: String,
            default: "Это действие требует подтверждения"
        },
        warning: {
            type: String,
            default: ""
        },
        type: {
            type: String,
            default: "danger",
            validator: (v) => ['danger', 'warning', 'info', 'success'].includes(v)
        },
        confirmText: {
            type: String,
            default: "Подтвердить"
        },
        cancelText: {
            type: String,
            default: "Отмена"
        },
        isLoading: {
            type: Boolean,
            default: false
        }
    },

    emits: ["update:show", "accept", "reject"],

    computed: {
        iconClass() {
            const icons = {
                danger: 'fa-solid fa-triangle-exclamation',
                warning: 'fa-solid fa-triangle-exclamation',
                info: 'fa-solid fa-circle-info',
                success: 'fa-solid fa-circle-check'
            }
            return icons[this.type]
        },

        confirmIcon() {
            const icons = {
                danger: 'fa-solid fa-trash',
                warning: 'fa-solid fa-triangle-exclamation',
                info: 'fa-solid fa-check',
                success: 'fa-solid fa-check'
            }
            return icons[this.type]
        }
    },

    methods: {
        accept() {
            this.$emit("accept")
            this.$emit("update:show", false)
        },
        reject() {
            this.$emit("reject")
            this.$emit("update:show", false)
        },
        handleEsc(e) {
            if (e.key === 'Escape' && this.show) {
                this.reject()
            }
        }
    },

    watch: {
        show(val) {
            if (val) {
                document.body.style.overflow = 'hidden'
                document.addEventListener('keydown', this.handleEsc)
            } else {
                document.body.style.overflow = ''
                document.removeEventListener('keydown', this.handleEsc)
            }
        }
    },

    beforeUnmount() {
        document.body.style.overflow = ''
        document.removeEventListener('keydown', this.handleEsc)
    }
}
</script>

<style scoped>
/* === Overlay === */
.confirm-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

/* === Modal === */
.confirm-modal {
    position: relative;
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 440px;
    box-shadow:
        0 20px 60px rgba(0, 0, 0, 0.2),
        0 0 0 1px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    animation: modalSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* === Декоративный фон === */
.modal-bg-decoration {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 140px;
    background: linear-gradient(135deg, var(--modal-color-1, #dc3545) 0%, var(--modal-color-2, #fd7e14) 100%);
    opacity: 0.08;
    pointer-events: none;
}

/* === Типы модалки === */
.type-danger {
    --modal-color-1: #dc3545;
    --modal-color-2: #fd7e14;
}

.type-warning {
    --modal-color-1: #ffc107;
    --modal-color-2: #fd7e14;
}

.type-info {
    --modal-color-1: #0d6efd;
    --modal-color-2: #6f42c1;
}

.type-success {
    --modal-color-1: #198754;
    --modal-color-2: #0dcaf0;
}

/* === Header === */
.modal-header-custom {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 24px 24px 16px;
}

.modal-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--modal-color-1) 0%, var(--modal-color-2) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.modal-header-content {
    flex: 1;
    min-width: 0;
    padding-top: 2px;
}

.modal-title-custom {
    font-size: 18px;
    font-weight: 700;
    color: #212529;
    margin: 0 0 4px 0;
    line-height: 1.3;
}

.modal-subtitle {
    font-size: 12px;
    color: #6c757d;
    margin: 0;
}

.btn-close-custom {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: #adb5bd;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.btn-close-custom:hover {
    background: #f1f3f5;
    color: #495057;
}

/* === Body === */
.modal-body-custom {
    position: relative;
    padding: 0 24px 20px;
}

.description-text {
    font-size: 14px;
    color: #495057;
    line-height: 1.6;
    margin: 0;
}

.warning-block {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    background: #fff3cd;
    border-left: 3px solid #ffc107;
    border-radius: 8px;
    margin-top: 14px;
    font-size: 13px;
    color: #664d03;
}

.warning-block i {
    font-size: 14px;
    color: #cc9a06;
    margin-top: 2px;
    flex-shrink: 0;
}

/* === Footer === */
.modal-footer-custom {
    position: relative;
    padding: 16px 24px;
    border-top: 1px solid #f1f3f5;
    background: #fafbfc;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-cancel-custom {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    background: #fff;
    color: #495057;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-cancel-custom:hover:not(:disabled) {
    background: #f8f9fa;
    border-color: #adb5bd;
    color: #212529;
}

.btn-cancel-custom:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-confirm-custom {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-confirm-custom:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}

.btn-confirm-custom:active:not(:disabled) {
    transform: translateY(0);
}

.btn-confirm-custom:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Цвета кнопок по типу */
.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.btn-danger:hover:not(:disabled) {
    background: linear-gradient(135deg, #c82333 0%, #b21f2d 100%);
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: #212529;
}

.btn-warning:hover:not(:disabled) {
    background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%);
    color: #fff;
}

.btn-info {
    background: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
}

.btn-info:hover:not(:disabled) {
    background: linear-gradient(135deg, #0b5ed7 0%, #5f37ad 100%);
}

.btn-success {
    background: linear-gradient(135deg, #198754 0%, #0dcaf0 100%);
}

.btn-success:hover:not(:disabled) {
    background: linear-gradient(135deg, #157347 0%, #0bb5d6 100%);
}

/* === Transitions === */
.modal-enter-active {
    transition: opacity 0.2s ease;
}

.modal-enter-active .confirm-modal {
    animation: modalSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-leave-active {
    transition: opacity 0.15s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

/* === Responsive === */
@media (max-width: 480px) {
    .confirm-modal {
        max-width: 100%;
    }

    .modal-header-custom {
        padding: 20px 20px 14px;
    }

    .modal-body-custom {
        padding: 0 20px 16px;
    }

    .modal-footer-custom {
        padding: 14px 20px;
        flex-direction: column-reverse;
    }

    .btn-cancel-custom,
    .btn-confirm-custom {
        width: 100%;
        justify-content: center;
    }
}
</style>
