<template>
    <div class="top-menu">
        <!-- Кнопка открытия/закрытия -->
        <div class="toggle-wrapper">
            <button
                class="toggle-btn"
                :class="{ 'is-open': isOpen }"
                @click="isOpen = !isOpen"
                :aria-label="isOpen ? 'Закрыть панель' : 'Открыть панель'"
                :title="isOpen ? 'Свернуть' : 'Развернуть панель'"
            >
                <i :class="isOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-sliders'"></i>
            </button>
        </div>

        <!-- Панель с анимацией -->
        <Transition name="slide-fade">
            <div v-if="isOpen" class="panel">
                <!-- Верхний ряд: инструменты -->
                <div class="panel-row">
                    <!-- Вид -->
                    <div class="tool-group">
                        <div class="dropdown">
                            <button
                                class="tool-btn dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                :title="viewLabel"
                            >
                                <i :class="viewIcon"></i>
                                <span class="btn-label">{{ viewLabel }}</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#" @click.prevent="$emit('change-view', 'grid')">
                                        <i class="fa-solid fa-grip me-2"></i> Сетка
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" @click.prevent="$emit('change-view', 'table')">
                                        <i class="fa-solid fa-table me-2"></i> Таблица
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" @click.prevent="$emit('change-view', 'categories')">
                                        <i class="fa-solid fa-list me-2"></i> По категориям
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Импорт / Экспорт -->
                    <div class="tool-group">
                        <button class="tool-btn" @click="$emit('open-import')" title="Импорт">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </button>
                        <button
                            class="tool-btn"
                            @click="exportToExcel"
                            :disabled="isExporting"
                            title="Экспорт всех данных в Excel"
                        >
                            <i class="fa-solid fa-file-excel" :class="{ 'rotating': isExporting }"></i>
                        </button>
                        <button
                            class="tool-btn"
                            @click="handleExport('vk')"
                            title="Экспорт в VK"
                        >
                            <i class="fa-brands fa-vk"></i>
                        </button>
                    </div>

                    <div class="divider"></div>

                    <!-- Поиск -->
                    <div class="search-wrapper">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input
                            v-model="search"
                            type="search"
                            class="search-input"
                            placeholder="Поиск товара..."
                        />
                        <button
                            v-if="search"
                            class="search-clear"
                            @click="search = ''"
                            title="Очистить"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="divider"></div>

                    <!-- Действия -->
                    <div class="tool-group">
                        <button
                            v-if="hasSelection"
                            class="tool-btn accent"
                            @click="$emit('open-collection')"
                            title="Добавить в коллекцию"
                        >
                            <i class="fa-solid fa-folder-plus"></i>
                            <span class="badge-count">{{ selectedCount }}</span>
                        </button>

                        <button class="tool-btn" @click="copyCurrentUrl" title="Скопировать ссылку">
                            <i class="fa-solid fa-link"></i>
                        </button>

                        <button class="tool-btn" @click="$emit('open-webhook')" title="Настройки / Webhooks">
                            <i class="fa-solid fa-gears"></i>
                        </button>

                        <button
                            class="tool-btn bg-success-subtle"
                            @click="syncAllWebhooks"
                            :disabled="isSyncing"
                            title="Синхронизировать все вебхуки"
                        >
                            <i class="fa-solid fa-arrows-rotate" :class="{ 'rotating': isSyncing }"></i>
                        </button>

                        <button
                            class="tool-btn"
                            @click="$emit('open-menu-generator')"
                            title="Генератор PDF-меню"
                        >
                            <i class="fa-solid fa-file-pdf"></i>
                        </button>
                    </div>
                </div>

                <!-- Нижний ряд: статистика и массовые действия -->
                <div class="panel-row bottom-row">
                    <div class="stats">
                        <span class="stat-badge">
                            <strong>{{ selectedCount }}</strong>
                            <span class="stat-sep">/</span>
                            {{ totalCount }}
                        </span>

                        <button class="link-btn" @click="selectAll">
                            Выбрать все
                        </button>

                        <button
                            v-if="hasSelection"
                            class="link-btn danger"
                            @click="clearSelection"
                        >
                            Сбросить
                        </button>
                    </div>

                    <div class="switch-sidebar">
                        <div class="form-check form-switch small">
                            <input class="form-check-input"
                                   v-model="needSidebar"
                                   type="checkbox" role="switch" id="switchCheckDefault">
                            <label class="form-check-label" for="switchCheckDefault">Категории \ коллекции</label>
                        </div>
                    </div>

                    <div class="bulk-actions">
                        <button
                            class="bulk-btn warning"
                            :disabled="!hasSelection"
                            @click="showStopListModal = true"
                            title="В стоп-лист"
                        >
                            <i class="fa-solid fa-hand"></i>
                            <span class="btn-label">Стоп</span>
                        </button>
                        <button
                            class="bulk-btn danger"
                            :disabled="!hasSelection"
                            @click="showDeleteModal = true"
                            title="Удалить"
                        >
                            <i class="fa-solid fa-trash-can"></i>
                            <span class="btn-label">Удалить</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Toast-уведомление -->
        <Transition name="toast">
            <div v-if="copyToast" class="copy-toast">
                <i class="fa-solid fa-check"></i> Ссылка скопирована
            </div>
        </Transition>
    </div>

    <ConfirmModal
        v-model:show="showDeleteModal"
        title="Удалить выбранные товары?"
        description="Это действие удалит все выбранные вами товары без возможности восстановления."
        @accept="deleteSelectedProduct"
        @reject="showDeleteModal = false"
    />

    <ConfirmModal
        v-model:show="showStopListModal"
        title="Отправить в стоп-лист?"
        description="Выбранные товары будут помечены как недоступные для показа."
        @accept="addSelectedToStopList"
        @reject="showStopListModal = false"
    />
</template>

<script>
import ConfirmModal from '@/Components/Layout/ConfirmModal.vue'
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'TopMenu',

    components: {
        ConfirmModal
    },

    props: {
        viewMode: {
            type: String,
            default: 'grid'
        }
    },

    data() {
        return {
            isExporting: false,
            needSidebar: false,
            isSyncing: false,
            store: useWorkspaceStore(),
            isOpen: false,
            search: '',
            showDeleteModal: false,
            showStopListModal: false,
            debounceTimer: null,
            copyToast: false,
        }
    },

    computed: {
        selectedCount() {
            return this.store.selectedIds.length
        },

        totalCount() {
            return this.store.filteredProducts.length
        },

        hasSelection() {
            return this.selectedCount > 0
        },

        viewIcon() {
            const icons = {
                grid: 'fa-solid fa-grip',
                table: 'fa-solid fa-table',
                categories: 'fa-solid fa-list'
            }
            return icons[this.viewMode] || icons.grid
        },

        viewLabel() {
            const labels = {
                grid: 'Сетка',
                table: 'Таблица',
                categories: 'По категориям'
            }
            return labels[this.viewMode] || 'Вид'
        }
    },

    watch: {
        search(val) {
            clearTimeout(this.debounceTimer)
            this.debounceTimer = setTimeout(() => {
                this.store.setSearch(val)
            }, 300)
        },
        needSidebar(){
            this.$emit("toggle-sidebar", this.needSidebar)
        }
    },

    beforeUnmount() {
        // Чистим таймер при уничтожении компонента
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer)
        }
    },

    methods: {
        handleExport(type) {
            if (type === 'vk') {
                this.$emit('export-vk')
            }
        },
        async syncAllWebhooks() {
            this.isSyncing = true
            try {
                await axios.post(`/api/workspaces/${this.store.uuid}/webhooks/sync-all`)
                // Можно показать уведомление об успехе
            } catch (error) {
                console.error('Sync failed:', error)
                alert('Ошибка при синхронизации')
            } finally {
                this.isSyncing = false
            }
        },
        addSelectedToStopList() {
            // TODO: логика стоп-листа
            this.showStopListModal = false
        },

        async deleteSelectedProduct() {
            const selectedCount = this.store.selectedIds.length

            if (selectedCount === 0) {
                this.showDeleteModal = false
                return
            }

            try {
                // Вызываем метод store для удаления
                await this.store.removeProductsByIds()

                // Закрываем модалку
                this.showDeleteModal = false

                // Показываем уведомление
                this.$notify.success(`${selectedCount} ${this.pluralize(selectedCount, 'товар удалён', 'товара удалено', 'товаров удалено')}`)

            } catch (error) {
                console.error('Delete products failed:', error)
                this.$notify.error('Ошибка при удалении товаров')
            }
        },

        // Утилита для склонения
        pluralize(count, one, two, five) {
            let n = Math.abs(count)
            n %= 100
            if (n >= 5 && n <= 20) return five
            n %= 10
            if (n === 1) return one
            if (n >= 2 && n <= 4) return two
            return five
        },

        async exportToExcel() {
            this.isExporting = true

            try {
                await this.store.exportWorkspace()

                this.$notify.success({
                    title: 'Экспорт завершён',
                    message: 'Файл успешно скачан'
                })
            } catch (error) {
                console.error('Export failed:', error)
                this.$notify.error('Ошибка при экспорте')
            } finally {
                this.isExporting = false
            }
        },

        async copyCurrentUrl() {
            try {
                await navigator.clipboard.writeText(window.location.href)
                this.copyToast = true
                setTimeout(() => {
                    this.copyToast = false
                }, 2000)
            } catch (e) {
                console.error('Ошибка копирования:', e)
            }
        },

        selectAll() {
            this.store.selectedIds = this.store.filteredProducts.map(p => p.id)
        },

        clearSelection() {
            this.store.selectedIds = []
        },

    }
}
</script>

<style scoped>

.rotating {
    animation: rotate 1s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.top-menu {
    position: sticky;
    top: 8px;
    z-index: 100;
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-family: inherit;
}

/* === Кнопка-тогл === */
.toggle-wrapper {
    flex-shrink: 0;
}

.toggle-btn {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 10px;
    background: #ffffff;
    color: #6c757d;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.toggle-btn:hover {
    background: #f8f9fa;
    color: #0d6efd;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
}

.toggle-btn.is-open {
    background: #0d6efd;
    color: #fff;
}

.toggle-btn.is-open:hover {
    background: #0b5ed7;
    color: #fff;
}

/* === Основная панель === */
.panel {
    background: #ffffff;
    border-radius: 12px;
    padding: 12px 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 600px;
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.panel-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.bottom-row {
    padding-top: 10px;
    border-top: 1px dashed #e9ecef;
    justify-content: space-between;
}

/* === Разделители между группами === */
.divider {
    width: 1px;
    height: 24px;
    background: #e9ecef;
    flex-shrink: 0;
}

/* === Группы инструментов === */
.tool-group {
    display: flex;
    gap: 4px;
    align-items: center;
}

/* === Кнопки инструментов === */
.tool-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: #f1f3f5;
    color: #495057;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.tool-btn:hover {
    background: #e7f1ff;
    color: #0d6efd;
    border-color: #cfe2ff;
}

.tool-btn.accent {
    background: #e7f1ff;
    color: #0d6efd;
    border-color: #cfe2ff;
}

.tool-btn .btn-label {
    font-weight: 500;
    font-size: 13px;
}

.badge-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: #0d6efd;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
}

/* === Поиск === */
.search-wrapper {
    position: relative;
    flex: 1;
    min-width: 200px;
    max-width: 320px;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 13px;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 8px 32px 8px 34px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
    font-size: 14px;
    color: #212529;
    transition: all 0.15s ease;
    outline: none;
}

.search-input:focus {
    background: #fff;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.search-input::placeholder {
    color: #adb5bd;
}

.search-clear {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    width: 22px;
    height: 22px;
    border: none;
    border-radius: 50%;
    background: transparent;
    color: #adb5bd;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.15s ease;
}

.search-clear:hover {
    background: #e9ecef;
    color: #495057;
}

/* === Статистика === */
.stats {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stat-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: #e7f1ff;
    color: #0d6efd;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
}

.stat-badge strong {
    font-weight: 700;
}

.stat-sep {
    color: #adb5bd;
}

.link-btn {
    background: none;
    border: none;
    padding: 0;
    color: #6c757d;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
    transition: color 0.15s ease;
}

.link-btn:hover {
    color: #0d6efd;
    text-decoration: underline;
}

.link-btn.danger:hover {
    color: #dc3545;
}

/* === Массовые действия === */
.bulk-actions {
    display: flex;
    gap: 6px;
}

.bulk-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.bulk-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.bulk-btn.warning {
    background: #fff3cd;
    color: #856404;
}

.bulk-btn.warning:hover:not(:disabled) {
    background: #ffe69c;
}

.bulk-btn.danger {
    background: #f8d7da;
    color: #842029;
}

.bulk-btn.danger:hover:not(:disabled) {
    background: #f1aeb5;
}

/* === Toast === */
.copy-toast {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    padding: 10px 18px;
    background: #198754;
    color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(25, 135, 84, 0.3);
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    z-index: 1000;
}

/* === Анимации === */
.slide-fade-enter-active {
    transition: all 0.25s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s ease-in;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateX(-12px);
}

.toast-enter-active {
    transition: all 0.3s ease-out;
}

.toast-leave-active {
    transition: all 0.2s ease-in;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translate(-50%, 12px);
}

.rotating {
    animation: rotate 1s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* === Адаптив === */
@media (max-width: 768px) {
    .panel {
        min-width: auto;
        width: calc(100vw - 70px);
    }

    .btn-label {
        display: none;
    }

    .search-wrapper {
        order: 10;
        width: 100%;
        max-width: none;
    }

    .divider {
        display: none;
    }
}
</style>
