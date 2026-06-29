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
                            type="text"
                            class="search-input"
                            placeholder="Поиск товара..."
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                            name="search-{{ Math.random().toString(36).slice(2) }}"
                            id="search-{{ store.uuid }}"
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

                    <!-- Фильтры -->
                    <div class="tool-group">
                        <button
                            class="filter-btn"
                            :class="{ active: store.showOnlyStopList }"
                            @click="store.toggleStopListFilter()"
                            :title="`Товары в стоп-листе (${store.stopListCount})`"
                        >
                            <i class="fa-solid fa-ban"></i>
                            <span class="btn-label">Стоп</span>
                            <span v-if="store.stopListCount > 0" class="filter-badge stop-badge">
                                {{ store.stopListCount }}
                            </span>
                        </button>

                        <button
                            class="filter-btn"
                            :class="{ active: store.showOnlyActive }"
                            @click="store.toggleActiveFilter()"
                            :title="`Активные товары (${store.activeCount})`"
                        >
                            <i class="fa-solid fa-check-circle"></i>
                            <span class="btn-label">Активные</span>
                            <span v-if="store.activeCount > 0" class="filter-badge active-badge">
                                {{ store.activeCount }}
                            </span>
                        </button>

                        <button
                            v-if="store.showOnlyStopList || store.showOnlyActive"
                            class="filter-btn filter-clear"
                            @click="store.clearFilters()"
                            title="Сбросить фильтры"
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

                        <button
                            class="tool-btn master-btn"
                            :class="masterButtonClass"
                            @click="handleMasterClick"
                            :title="masterButtonTitle"
                        >
                            <i class="fa-solid" :class="masterIcon"></i>
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
                            @click="showStopListAddModal = true"
                            title="Добавить в стоп-лист"
                        >
                            <i class="fa-solid fa-ban"></i>
                            <span class="btn-label">В стоп</span>
                        </button>

                        <button
                            class="bulk-btn success"
                            :disabled="!hasSelection"
                            @click="showStopListRemoveModal = true"
                            title="Убрать из стоп-листа"
                        >
                            <i class="fa-solid fa-circle-check"></i>
                            <span class="btn-label">Из стоп</span>
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

    <!-- Модалка удаления -->
    <ConfirmModal
        v-model:show="showDeleteModal"
        title="Удалить выбранные товары?"
        description="Это действие удалит все выбранные вами товары без возможности восстановления."
        @accept="deleteSelectedProduct"
        @reject="showDeleteModal = false"
    />

    <!-- Модалка добавления в стоп-лист -->
    <ConfirmModal
        v-model:show="showStopListAddModal"
        title="Отправить в стоп-лист?"
        :description="`${selectedCount} ${pluralize(selectedCount, 'товар будет помечен', 'товара будут помечены', 'товаров будут помечены')} как недоступные для показа.`"
        @accept="addSelectedToStopList"
        @reject="showStopListAddModal = false"
    />

    <!-- Модалка удаления из стоп-листа -->
    <ConfirmModal
        v-model:show="showStopListRemoveModal"
        title="Убрать из стоп-листа?"
        :description="`${selectedCount} ${pluralize(selectedCount, 'товар снова станет', 'товара снова станут', 'товаров снова станут')} активными и доступными для заказа.`"
        @accept="removeSelectedFromStopList"
        @reject="showStopListRemoveModal = false"
    />

    <!-- Модалка мастер-кода -->
    <MasterCodeModal
        ref="masterModal"
        @success="onMasterSuccess"
        @locked="onMasterLocked"
        @lock-session="onLockSession"
    />
</template>

<script>
import ConfirmModal from '@/Components/Layout/ConfirmModal.vue'
import { useWorkspaceStore } from '@/store/workspace.js'
import MasterCodeModal from '@/Components/Auth/MasterCodeModal.vue'

export default {
    name: 'TopMenu',

    components: {
        ConfirmModal,
        MasterCodeModal
    },

    props: {
        viewMode: {
            type: String,
            default: 'grid'
        }
    },

    data() {
        return {
            masterModalMode: 'verify',
            isExporting: false,
            needSidebar: false,
            isSyncing: false,
            store: useWorkspaceStore(),
            isOpen: false,
            search: '',
            showDeleteModal: false,
            showStopListAddModal: false,
            showStopListRemoveModal: false,
            debounceTimer: null,
            copyToast: false,
        }
    },

    computed: {
        hasMasterCode() {
            return this.store.hasMasterCode
        },

        isMasterLocked() {
            return this.store.isMasterLocked
        },

        isMasterUnlocked() {
            return this.store.isMasterUnlocked
        },

        masterIcon() {
            if (!this.hasMasterCode) return 'fa-lock-open'
            return this.isMasterLocked ? 'fa-lock' : 'fa-lock-open'
        },

        masterButtonClass() {
            return {
                'master-inactive': !this.hasMasterCode,
                'master-locked': this.isMasterLocked,
                'master-unlocked': this.isMasterUnlocked,
            }
        },

        masterButtonTitle() {
            if (!this.hasMasterCode) return 'Установить мастер-код'
            if (this.isMasterLocked) return 'Товары защищены. Нажмите для ввода кода'
            return 'Товары разблокированы. Нажмите для управления'
        },

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
        handleMasterClick() {
            if (!this.hasMasterCode) {
                this.$refs.masterModal.show('set')
                return
            }

            if (this.isMasterLocked) {
                this.$refs.masterModal.show('verify')
                return
            }

            this.$refs.masterModal.show('menu')
        },
        async syncAllWebhooks() {
            this.isSyncing = true
            try {
                await axios.post(`/api/workspaces/${this.store.uuid}/webhooks/sync-all`)
            } catch (error) {
                console.error('Sync failed:', error)
                this.$notify.error('Ошибка при синхронизации')
            } finally {
                this.isSyncing = false
            }
        },

        // === СТОП-ЛИСТ ===
        async addSelectedToStopList() {
            const selectedCount = this.store.selectedIds.length

            if (selectedCount === 0) {
                this.showStopListAddModal = false
                return
            }

            try {
                const result = await this.store.addSelectedToStopList()

                this.showStopListAddModal = false

                this.$notify.success({
                    title: 'Добавлено в стоп-лист',
                    message: `${result.count} ${this.pluralize(result.count, 'товар добавлен', 'товара добавлено', 'товаров добавлено')}`
                })
            } catch (error) {
                console.error('Add to stop list failed:', error)
                this.$notify.error('Ошибка при добавлении в стоп-лист')
            }
        },

        async removeSelectedFromStopList() {
            const selectedCount = this.store.selectedIds.length

            if (selectedCount === 0) {
                this.showStopListRemoveModal = false
                return
            }

            try {
                const result = await this.store.removeSelectedFromStopList()

                this.showStopListRemoveModal = false

                this.$notify.success({
                    title: 'Убрано из стоп-листа',
                    message: `${result.count} ${this.pluralize(result.count, 'товар убран', 'товара убрано', 'товаров убрано')}`
                })
            } catch (error) {
                console.error('Remove from stop list failed:', error)
                this.$notify.error('Ошибка при удалении из стоп-листа')
            }
        },

        async deleteSelectedProduct() {
            const selectedCount = this.store.selectedIds.length

            if (selectedCount === 0) {
                this.showDeleteModal = false
                return
            }

            try {
                await this.store.removeProductsByIds()

                this.showDeleteModal = false

                this.$notify.success(`${selectedCount} ${this.pluralize(selectedCount, 'товар удалён', 'товара удалено', 'товаров удалено')}`)
            } catch (error) {
                console.error('Delete products failed:', error)
                this.$notify.error('Ошибка при удалении товаров')
            }
        },

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



        onMasterSuccess({ mode }) {
            const messages = {
                set: 'Мастер-код установлен. Товары защищены.',
                verify: 'Товары разблокированы.',
                change: 'Мастер-код изменён.',
                reset: 'Мастер-код удалён. Защита снята.',
            }
            this.$notify?.success({
                title: 'Готово',
                message: messages[mode] || 'Действие выполнено'
            })
        },

        onMasterLocked() {
            this.$notify?.error({
                title: 'Заблокировано',
                message: 'Ввод заблокирован на 1 час'
            })
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

/* === Фильтры === */
.filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #495057;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.filter-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
}

.filter-btn.active {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.filter-btn.filter-clear {
    color: #dc3545;
    border-color: #f5c2c7;
    padding: 7px 10px;
}

.filter-btn.filter-clear:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

.filter-badge {
    padding: 1px 7px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(0, 0, 0, 0.08);
}

.filter-btn.active .filter-badge {
    background: rgba(255, 255, 255, 0.25);
}

.stop-badge {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.filter-btn.active .stop-badge {
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
}

.active-badge {
    background: rgba(25, 135, 84, 0.1);
    color: #198754;
}

.filter-btn.active .active-badge {
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
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

.bulk-btn.success {
    background: #d1e7dd;
    color: #0f5132;
}

.bulk-btn.success:hover:not(:disabled) {
    background: #a3cfbb;
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

.master-btn {
    transition: all 0.2s ease;
}

.master-btn.master-inactive {
    color: #6c757d;
}

.master-btn.master-inactive:hover {
    color: #0d6efd;
    background: rgba(13, 110, 253, 0.1);
}

.master-btn.master-locked {
    color: #dc3545;
    background: rgba(220, 53, 69, 0.1);
}

.master-btn.master-locked:hover {
    background: rgba(220, 53, 69, 0.2);
}

.master-btn.master-unlocked {
    color: #198754;
    background: rgba(25, 135, 84, 0.1);
}

.master-btn.master-unlocked:hover {
    background: rgba(25, 135, 84, 0.2);
}
</style>
