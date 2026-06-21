<template>
    <div class="access-link-section">
        <h6 class="section-title">
            <i class="fa-solid fa-link"></i>
            Ссылка для доступа
        </h6>

        <div v-if="isLoading" class="loading">
            <i class="fa-solid fa-spinner fa-spin"></i>
        </div>

        <div v-else>
            <div class="link-display">
                <input
                    type="text"
                    :value="store.accessUrl"
                    readonly
                    class="form-input readonly"
                    @click="selectInput"
                    ref="linkInput"
                />
                <button
                    type="button"
                    class="btn-copy"
                    @click="copyLink"
                    :title="copied ? 'Скопировано!' : 'Копировать'"
                >
                    <i :class="copied ? 'fa-solid fa-check' : 'fa-solid fa-copy'"></i>
                </button>
            </div>

            <div class="link-actions">
                <button
                    type="button"
                    class="btn-regenerate"
                    @click="confirmRegenerate = true"
                >
                    <i class="fa-solid fa-arrows-rotate"></i>
                    Обновить ссылку
                </button>
                <p class="hint">
                    <i class="fa-solid fa-circle-info"></i>
                    После обновления старая ссылка перестанет работать
                </p>
            </div>
        </div>

        <!-- Подтверждение перегенерации -->
        <div v-if="confirmRegenerate" class="confirm-overlay" @click="confirmRegenerate = false">
            <div class="confirm-dialog" @click.stop>
                <h6>Обновить ссылку доступа?</h6>
                <p>Все, у кого есть старая ссылка, потеряют доступ к workspace.</p>
                <div class="confirm-actions">
                    <button @click="confirmRegenerate = false">Отмена</button>
                    <button @click="regenerateToken" class="btn-danger">Обновить</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'AccessLinkManager',

    data() {
        return {
            store: useWorkspaceStore(),
            isLoading: false,
            copied: false,
            confirmRegenerate: false
        }
    },

    mounted() {
        this.loadToken()
    },

    methods: {
        async loadToken() {
            // Если токен уже загружен в store - не дублируем запрос
            if (this.store.accessToken) return

            this.isLoading = true
            try {
                await this.store.loadAccessToken()
            } catch (error) {
                console.error('Failed to load token:', error)
            } finally {
                this.isLoading = false
            }
        },

        async regenerateToken() {
            this.isLoading = true
            this.confirmRegenerate = false

            try {
                await this.store.regenerateAccessToken()
            } catch (error) {
                console.error('Failed to regenerate token:', error)
                alert('Ошибка при обновлении ссылки')
            } finally {
                this.isLoading = false
            }
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.store.accessUrl)
                this.copied = true
                setTimeout(() => {
                    this.copied = false
                }, 2000)
            } catch (error) {
                console.error('Failed to copy:', error)
            }
        },

        selectInput() {
            this.$refs.linkInput.select()
        }
    }
}
</script>


<style scoped>
.access-link-section {
    margin-bottom: 24px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin: 0 0 16px 0;
}

.section-title i {
    color: #0d6efd;
}

.loading {
    text-align: center;
    padding: 20px;
    color: #6c757d;
}

.link-display {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.form-input.readonly {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
    font-size: 13px;
    color: #495057;
    cursor: pointer;
}

.btn-copy {
    padding: 10px 16px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-copy:hover {
    background: #f8f9fa;
    color: #0d6efd;
    border-color: #0d6efd;
}

.link-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.btn-regenerate {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1px solid #ffc107;
    border-radius: 8px;
    background: #fff3cd;
    color: #856404;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-regenerate:hover {
    background: #ffe69c;
    border-color: #ffc107;
}

.hint {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    font-size: 12px;
    color: #6c757d;
}

/* === Confirm dialog === */
.confirm-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.confirm-dialog {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    max-width: 400px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.confirm-dialog h6 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 12px 0;
}

.confirm-dialog p {
    font-size: 14px;
    color: #6c757d;
    margin: 0 0 20px 0;
}

.confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.confirm-actions button {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.confirm-actions button:first-child {
    border: 1px solid #dee2e6;
    background: #fff;
    color: #6c757d;
}

.confirm-actions button:first-child:hover {
    background: #f8f9fa;
}

.confirm-actions .btn-danger {
    border: none;
    background: #dc3545;
    color: #fff;
}

.confirm-actions .btn-danger:hover {
    background: #bb2d3b;
}
</style>
