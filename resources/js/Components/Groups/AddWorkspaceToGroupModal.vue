<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="modelValue" class="modal-overlay" @click.self="close">
                <div class="modal-content-custom">
                    <!-- Header -->
                    <div class="modal-header-custom">
                        <h5 class="modal-title-custom">
                            <i class="fa-solid fa-plus"></i>
                            <span>Добавить доску в группу</span>
                        </h5>
                        <button class="btn-close-custom" @click="close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body-custom">
                        <!-- Инфо о группе -->
                        <div class="group-info-header" v-if="group">
                            <div class="group-icon" :style="{ background: group.color }">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div>
                                <div class="group-name">{{ group.name }}</div>
                                <div class="group-meta">
                                    Сейчас в группе: {{ group.workspaces?.length || 0 }}
                                    {{ pluralize(group.workspaces?.length || 0, 'доска', 'доски', 'досок') }}
                                </div>
                            </div>
                        </div>

                        <!-- Поиск (с исправленными стилями) -->
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="form-input search-input"
                                placeholder="Поиск доски..."
                            />
                        </div>

                        <!-- Список доступных досок (реактивно из computed) -->
                        <div class="workspace-list">
                            <label
                                v-for="ws in filteredWorkspaces"
                                :key="ws.id"
                                class="ws-checkbox"
                            >
                                <input
                                    type="checkbox"
                                    :value="ws.id"
                                    v-model="selectedIds"
                                />
                                <div class="ws-icon" :style="{ background: ws.color }">
                                    {{ ws.initials || ws.name?.substring(0, 2) }}
                                </div>
                                <div class="ws-info">
                                    <div class="ws-name">{{ ws.name }}</div>
                                    <div class="ws-label" v-if="ws.label">{{ ws.label }}</div>
                                </div>
                            </label>

                            <!-- Пустое состояние -->
                            <div v-if="filteredWorkspaces.length === 0" class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <p v-if="searchQuery">Ничего не найдено</p>
                                <p v-else>Все доступные доски уже в этой группе</p>
                            </div>
                        </div>

                        <!-- Подсказка -->
                        <small class="field-hint" v-if="selectedIds.length > 0">
                            <i class="fa-solid fa-circle-info"></i>
                            Выбрано: {{ selectedIds.length }}
                            {{ pluralize(selectedIds.length, 'доска', 'доски', 'досок') }}
                        </small>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer-custom">
                        <button class="btn-cancel" @click="close">Отмена</button>
                        <button
                            class="btn-save"
                            :disabled="selectedIds.length === 0 || isSaving"
                            @click="addWorkspaces"
                        >
                            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-check"></i>
                            Добавить {{ selectedIds.length > 0 ? `(${selectedIds.length})` : '' }}
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
    name: 'AddWorkspaceToGroupModal',

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

    emits: ['update:modelValue', 'added'],

    data() {
        return {
            store: useWorkspaceStore(),
            isSaving: false,
            searchQuery: '',
            selectedIds: []
        }
    },

    computed: {
        // 1. Получаем все доски, которых ЕЩЁ НЕТ в этой группе
        availableWorkspaces() {
            if (!this.group) return []
            const currentIds = (this.group.workspaces || []).map(w => Number(w.id))
            return (this.store.allWorkspaces || []).filter(ws => !currentIds.includes(Number(ws.id)))
        },

        // 2. Фильтруем их по поисковому запросу (как в вашем примере)
        filteredWorkspaces() {
            if (!this.searchQuery) return this.availableWorkspaces

            const q = this.searchQuery.toLowerCase()
            return this.availableWorkspaces.filter(ws =>
                ws.name?.toLowerCase().includes(q) ||
                ws.label?.toLowerCase().includes(q)
            )
        }
    },

    watch: {
        // Простой watch, как в примере GroupEditModal
        modelValue(val) {
            if (val) {
                this.selectedIds = []
                this.searchQuery = ''
                document.body.style.overflow = 'hidden'

                // Если список вдруг пуст, можно безопасно вызвать загрузку,
                // но computed свойства сами обновятся, когда store.allWorkspaces заполнится
                if (!this.store.allWorkspaces || this.store.allWorkspaces.length === 0) {
                    this.store.loadAllWorkspaces()
                }
            } else {
                document.body.style.overflow = ''
            }
        }
    },

    methods: {
        close() {
            this.$emit('update:modelValue', false)
        },

        async addWorkspaces() {
            if (this.selectedIds.length === 0 || this.isSaving) return

            this.isSaving = true
            try {
                const currentIds = (this.group.workspaces || []).map(w => Number(w.id))
                const newIds = [...new Set([...currentIds, ...this.selectedIds])]

                await this.store.updateGroupWorkspaces(this.group.id, newIds)
                this.$emit('added')
                this.close()
            } catch (e) {
                console.error('Add workspaces failed:', e)
                this.$notify?.error('Ошибка при добавлении')
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
/* === Overlay & Modal Base === */
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

/* === Header === */
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

.modal-title-custom i { color: #198754; }

.btn-close-custom {
    width: 32px; height: 32px; border: none; border-radius: 8px;
    background: #f1f3f5; color: #6c757d; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}
.btn-close-custom:hover { background: #e9ecef; color: #212529; }

/* === Body === */
.modal-body-custom {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.group-info-header {
    display: flex; align-items: center; gap: 12px;
    padding: 12px; background: #f8f9fa; border-radius: 10px; margin-bottom: 16px;
}

.group-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 14px; flex-shrink: 0;
}

.group-name { font-size: 15px; font-weight: 600; color: #212529; }
.group-meta { font-size: 12px; color: #6c757d; margin-top: 2px; }

/* === ✅ ИСПРАВЛЕННЫЙ ИНПУТ ПОИСКА === */
.search-box {
    position: relative;
    margin-bottom: 16px;
}

.search-box > i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 14px;
    pointer-events: none;
    z-index: 2;
}

.search-input {
    width: 100%;
    height: 42px; /* Явная высота для идеального центрирования */
    padding: 0 12px 0 38px !important; /* Отступ слева под иконку */
    box-sizing: border-box; /* Критически важно для сохранения размеров */
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    transition: all 0.15s ease;
}

.search-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

/* === Workspace List === */
.workspace-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 6px;
}

.ws-checkbox {
    display: flex; align-items: center; gap: 10px;
    padding: 8px; border-radius: 6px; cursor: pointer;
}
.ws-checkbox:hover { background: #f8f9fa; }
.ws-checkbox input { width: 16px; height: 16px; cursor: pointer; flex-shrink: 0; }

.ws-icon {
    width: 32px; height: 32px; border-radius: 6px; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; flex-shrink: 0;
}

.ws-info { flex: 1; min-width: 0; }
.ws-name {
    font-size: 13px; font-weight: 500; color: #212529;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.ws-label { font-size: 11px; color: #6c757d; margin-top: 1px; }

/* === Empty State === */
.empty-state {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 30px 20px; text-align: center; color: #adb5bd;
}
.empty-state i { font-size: 32px; margin-bottom: 8px; opacity: 0.5; }
.empty-state p { margin: 0; font-size: 13px; color: #6c757d; }

.field-hint {
    display: flex; align-items: center; gap: 6px;
    margin-top: 10px; font-size: 12px; color: #0d6efd;
}
.field-hint i { font-size: 11px; }

/* === Footer === */
.modal-footer-custom {
    padding: 14px 20px; border-top: 1px solid #e9ecef;
    display: flex; justify-content: flex-end; gap: 8px;
}

.btn-cancel {
    padding: 8px 16px; border: 1px solid #dee2e6; border-radius: 8px;
    background: #fff; color: #6c757d; cursor: pointer;
}
.btn-cancel:hover { background: #f8f9fa; }

.btn-save {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 20px; border: none; border-radius: 8px;
    background: #198754; color: #fff; font-weight: 500; cursor: pointer;
}
.btn-save:hover:not(:disabled) { background: #157347; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

/* === Transitions === */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }

/* === Responsive === */
@media (max-width: 576px) {
    .modal-overlay { padding: 0; }
    .modal-content-custom { max-width: 100%; max-height: 100%; border-radius: 0; }
}
</style>
