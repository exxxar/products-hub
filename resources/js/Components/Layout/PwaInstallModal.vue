<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <!-- Header с градиентом -->
                <div class="modal-header-custom">
                    <button type="button" class="btn-close-custom" @click="hide">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                    <div class="header-content">
                        <div class="app-icon-large">
                            <i class="fa-solid fa-cube"></i>
                        </div>
                        <h4 class="modal-title-custom">
                            Установить приложение
                        </h4>
                        <p class="modal-subtitle">
                            Агрегатор товаров на вашем устройстве
                        </p>
                    </div>
                </div>

                <!-- Body -->
                <div class="modal-body-custom">
                    <!-- Преимущества -->
                    <div class="benefits-section">
                        <h6 class="section-title-custom">
                            <i class="fa-solid fa-sparkles"></i>
                            Преимущества установки
                        </h6>

                        <div class="benefits-grid">
                            <div class="benefit-card">
                                <div class="benefit-icon" style="background: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%)">
                                    <i class="fa-solid fa-bolt"></i>
                                </div>
                                <div class="benefit-info">
                                    <strong>Быстрый запуск</strong>
                                    <span>Открывается мгновенно с рабочего стола</span>
                                </div>
                            </div>

                            <div class="benefit-card">
                                <div class="benefit-icon" style="background: linear-gradient(135deg, #198754 0%, #0dcaf0 100%)">
                                    <i class="fa-solid fa-wifi"></i>
                                </div>
                                <div class="benefit-info">
                                    <strong>Работает офлайн</strong>
                                    <span>Доступ к данным без интернета</span>
                                </div>
                            </div>

                            <div class="benefit-card">
                                <div class="benefit-icon" style="background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%)">
                                    <i class="fa-solid fa-bell"></i>
                                </div>
                                <div class="benefit-info">
                                    <strong>Уведомления</strong>
                                    <span>Получайте важные обновления</span>
                                </div>
                            </div>

                            <div class="benefit-card">
                                <div class="benefit-icon" style="background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%)">
                                    <i class="fa-solid fa-mobile-screen"></i>
                                </div>
                                <div class="benefit-info">
                                    <strong>Полный экран</strong>
                                    <span>Без адресной строки браузера</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Превью -->
                    <div class="preview-section">
                        <h6 class="section-title-custom">
                            <i class="fa-solid fa-eye"></i>
                            Как это будет выглядеть
                        </h6>

                        <div class="preview-container">
                            <div class="desktop-preview">
                                <div class="desktop-screen">
                                    <div class="desktop-icons">
                                        <div class="desktop-icon installed">
                                            <div class="icon-app">
                                                <i class="fa-solid fa-cube"></i>
                                            </div>
                                            <span>Агрегатор</span>
                                        </div>
                                        <div class="desktop-icon">
                                            <div class="icon-app" style="background: #e9ecef">
                                                <i class="fa-solid fa-folder" style="color: #adb5bd"></i>
                                            </div>
                                            <span>Документы</span>
                                        </div>
                                        <div class="desktop-icon">
                                            <div class="icon-app" style="background: #e9ecef">
                                                <i class="fa-solid fa-trash" style="color: #adb5bd"></i>
                                            </div>
                                            <span>Корзина</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="desktop-label">
                                    <i class="fa-solid fa-desktop"></i>
                                    Рабочий стол
                                </div>
                            </div>

                            <div class="mobile-preview">
                                <div class="mobile-screen">
                                    <div class="mobile-icons">
                                        <div class="mobile-icon installed">
                                            <div class="icon-app">
                                                <i class="fa-solid fa-cube"></i>
                                            </div>
                                        </div>
                                        <div class="mobile-icon">
                                            <div class="icon-app" style="background: #e9ecef">
                                                <i class="fa-solid fa-camera" style="color: #adb5bd"></i>
                                            </div>
                                        </div>
                                        <div class="mobile-icon">
                                            <div class="icon-app" style="background: #e9ecef">
                                                <i class="fa-solid fa-gear" style="color: #adb5bd"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mobile-label">
                                    <i class="fa-solid fa-mobile-screen"></i>
                                    Домашний экран
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Информационный блок -->
                    <div class="info-section">
                        <div class="info-box-custom">
                            <i class="fa-solid fa-circle-info"></i>
                            <div>
                                <strong>Что такое PWA?</strong>
                                <p>
                                    Progressive Web App — это современная технология, которая позволяет
                                    использовать веб-приложение как обычное приложение. Установка занимает
                                    несколько секунд и не требует магазинов приложений.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer-custom">
                    <button type="button" class="btn-later" @click="hide">
                        <i class="fa-solid fa-clock"></i>
                        Позже
                    </button>
                    <button type="button" class="btn-install" @click="install">
                        <i class="fa-solid fa-download"></i>
                        Установить приложение
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'

export default {
    name: 'PwaInstallModal',

    emits: ['install'],

    data() {
        return {
            modal: null,
        }
    },

    methods: {
        show() {
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

        install() {
            this.$emit('install')
            this.hide()
        }
    },

    mounted() {
        this.modal = new Modal(this.$refs.modal, {
            backdrop: 'static',
            keyboard: false,
        })
    },

    beforeUnmount() {
        if (this.modal) {
            this.modal.dispose()
        }
    }
}
</script>

<style scoped>
/* === Modal Content === */
.modal-content {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

/* === Header === */
.modal-header-custom {
    position: relative;
    background: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
    padding: 40px 24px 32px;
    text-align: center;
    color: #fff;
}

.btn-close-custom {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.15s ease;
    z-index: 10;
}

.btn-close-custom:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

.header-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.app-icon-large {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.modal-title-custom {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.modal-subtitle {
    font-size: 14px;
    opacity: 0.9;
    margin: 0;
}

/* === Body === */
.modal-body-custom {
    padding: 28px 24px;
    max-height: 60vh;
    overflow-y: auto;
}

/* === Section Title === */
.section-title-custom {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 600;
    color: #212529;
    margin: 0 0 16px 0;
}

.section-title-custom i {
    color: #0d6efd;
    font-size: 14px;
}

/* === Benefits === */
.benefits-section {
    margin-bottom: 28px;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.benefit-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: #f8f9fa;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.benefit-card:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.benefit-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.benefit-info {
    flex: 1;
    min-width: 0;
}

.benefit-info strong {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 2px;
}

.benefit-info span {
    font-size: 12px;
    color: #6c757d;
}

/* === Preview === */
.preview-section {
    margin-bottom: 28px;
}

.preview-container {
    display: flex;
    gap: 20px;
    justify-content: center;
}

.desktop-preview,
.mobile-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.desktop-screen,
.mobile-screen {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.desktop-screen {
    width: 200px;
    height: 140px;
}

.mobile-screen {
    width: 100px;
    height: 160px;
}

.desktop-icons,
.mobile-icons {
    display: grid;
    gap: 12px;
}

.desktop-icons {
    grid-template-columns: repeat(3, 1fr);
}

.mobile-icons {
    grid-template-columns: repeat(2, 1fr);
}

.desktop-icon,
.mobile-icon {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.icon-app {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.desktop-icon span {
    font-size: 10px;
    color: #495057;
    text-align: center;
}

.installed .icon-app {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
    }
    50% {
        box-shadow: 0 2px 16px rgba(13, 110, 253, 0.6);
    }
}

.desktop-label,
.mobile-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
}

.desktop-label i,
.mobile-label i {
    font-size: 11px;
}

/* === Info Section === */
.info-section {
    margin-bottom: 8px;
}

.info-box-custom {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    background: #e7f1ff;
    border-radius: 10px;
    border-left: 4px solid #0d6efd;
}

.info-box-custom i {
    font-size: 18px;
    color: #0d6efd;
    flex-shrink: 0;
    margin-top: 2px;
}

.info-box-custom strong {
    display: block;
    font-size: 14px;
    color: #084298;
    margin-bottom: 4px;
}

.info-box-custom p {
    margin: 0;
    font-size: 13px;
    color: #084298;
    line-height: 1.5;
}

/* === Footer === */
.modal-footer-custom {
    padding: 16px 24px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #f8f9fa;
}

.btn-later {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-later:hover {
    background: #e9ecef;
    color: #495057;
}

.btn-install {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.btn-install:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
}

.btn-install i {
    font-size: 14px;
}

/* === Responsive === */
@media (max-width: 576px) {
    .modal-header-custom {
        padding: 32px 20px 24px;
    }

    .app-icon-large {
        width: 64px;
        height: 64px;
        font-size: 28px;
    }

    .modal-title-custom {
        font-size: 20px;
    }

    .modal-body-custom {
        padding: 24px 20px;
    }

    .benefits-grid {
        grid-template-columns: 1fr;
    }

    .preview-container {
        flex-direction: column;
        align-items: center;
    }

    .desktop-screen {
        width: 180px;
        height: 120px;
    }

    .mobile-screen {
        width: 90px;
        height: 140px;
    }

    .modal-footer-custom {
        flex-direction: column-reverse;
    }

    .btn-later,
    .btn-install {
        width: 100%;
        justify-content: center;
    }
}
</style>
