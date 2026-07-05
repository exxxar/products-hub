<template>
    <div class="workspace-switcher">
        <!-- Текущая доска -->
        <button type="button" class="current-workspace" @click="togglePanel">
            <div class="workspace-icon" :style="{ background: store.workspaceColor }">
                <img v-if="store.workspaceLogo" :src="store.workspaceLogo" alt="" />
                <span v-else>{{ store.workspaceInitials }}</span>
            </div>
            <div class="workspace-info">
                <div class="workspace-name">{{ store.workspaceName }}</div>
                <div class="workspace-count" v-if="store.linkedWorkspaces.length > 0">
                    {{ store.linkedWorkspaces.length }} {{ pluralize(store.linkedWorkspaces.length, 'доска', 'доски', 'досок') }}
                </div>
            </div>
            <i class="fa-solid fa-chevron-down toggle-icon"></i>
        </button>

        <!-- Панель -->
        <Transition name="panel">
            <div v-if="isOpen" class="switcher-panel" @click.stop>
                <!-- Поиск -->
                <div class="panel-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Найти доску..."
                        ref="searchInput"
                    />
                </div>

                <!-- Быстрые действия -->
                <div class="panel-actions">
                    <button type="button" class="action-btn" @click="openCreateModal">
                        <i class="fa-solid fa-plus"></i>
                        <span>Новая доска</span>
                    </button>
                    <button type="button" class="action-btn" @click="openAddModal">
                        <i class="fa-solid fa-link"></i>
                        <span>Добавить</span>
                    </button>
                </div>

                <!-- Список досок -->
                <div class="workspaces-section">
                    <!-- Текущая доска -->
                    <div class="workspace-item is-current">
                        <div class="workspace-icon" :style="{ background: store.workspaceColor }">
                            <img v-if="store.workspaceLogo" :src="store.workspaceLogo" alt="" />
                            <span v-else>{{ store.workspaceInitials }}</span>
                        </div>
                        <div class="workspace-info">
                            <div class="workspace-name">{{ store.workspaceName }}</div>
                            <div class="workspace-badge">Текущая</div>
                        </div>
                        <i class="fa-solid fa-check current-icon"></i>
                    </div>

                    <!-- Связанные доски -->
                    <div v-if="filteredWorkspaces.length > 0" class="linked-section">
                        <div
                            v-for="workspace in filteredWorkspaces"
                            :key="workspace.id"
                            class="workspace-item"
                            @click="selectWorkspace(workspace)"
                        >
                            <div class="workspace-icon" :style="{ background: workspace.color }">
                                <img v-if="workspace.logo_url" :src="workspace.logo_url" alt="" />
                                <span v-else>{{ workspace.initials }}</span>
                            </div>
                            <div class="workspace-info">
                                <div class="workspace-name">{{ workspace.name }}</div>
                                <div class="workspace-label" v-if="workspace.label">{{ workspace.label }}</div>
                            </div>
                            <button
                                type="button"
                                class="unlink-btn"
                                @click.stop="confirmUnlink(workspace)"
                                title="Удалить из списка"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Пустое состояние -->
                    <div v-else-if="searchQuery" class="empty-hint">
                        Ничего не найдено
                    </div>
                    <div v-else class="empty-hint">
                        <i class="fa-solid fa-layer-group"></i>
                        <p>Нет связанных досок</p>
                        <button type="button" class="btn-add-first" @click="openAddModal">
                            <i class="fa-solid fa-plus"></i>
                            Добавить первую доску
                        </button>
                    </div>
                </div>

                <!-- Подсказка -->
                <div class="panel-hint">
                    <kbd>Ctrl</kbd> + <kbd>K</kbd> — быстрый поиск
                </div>
            </div>
        </Transition>

        <!-- Overlay -->
        <div v-if="isOpen" class="switcher-overlay" @click="isOpen = false"></div>

        <!-- Модалки -->
        <CreateWorkspaceModal
            ref="createModal"
            @created="onWorkspaceCreated"
        />

        <AddWorkspaceModal
            ref="addModal"
            @added="onWorkspaceAdded"
        />

        <ConfirmModal
            v-model:show="showUnlinkConfirm"
            title="Удалить из списка?"
            :description="`Удалить доску ${workspaceToUnlink?.name} из списка связанных?`"
        @accept="unlinkWorkspace"
        @reject="showUnlinkConfirm = false"
        />
    </div>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'
import CreateWorkspaceModal from '@/Components/Groups/WorkspaceCreateModal.vue'
import AddWorkspaceModal from '@/Components/Groups/AddWorkspaceModal.vue'
import ConfirmModal from '@/Components/Layout/ConfirmModal.vue'

export default {
    name: 'WorkspaceSwitcher',

    components: {
        CreateWorkspaceModal,
        AddWorkspaceModal,
        ConfirmModal
    },

    data() {
        return {
            store: useWorkspaceStore(),
            isOpen: false,
            searchQuery: '',
            showUnlinkConfirm: false,
            workspaceToUnlink: null,
        }
    },

    computed: {
        filteredWorkspaces() {
            if (!this.searchQuery) return this.store.linkedWorkspaces
            const q = this.searchQuery.toLowerCase()
            return this.store.linkedWorkspaces.filter(w =>
                w.name.toLowerCase().includes(q) ||
                w.label?.toLowerCase().includes(q)
            )
        }
    },

    watch: {
        isOpen(val) {
            if (val) {
                this.$nextTick(() => {
                    this.$refs.searchInput?.focus()
                })
            }
        }
    },

    methods: {
        async togglePanel() {
            this.isOpen = !this.isOpen
            if (this.isOpen) {
                await this.store.loadLinkedWorkspaces()
            }
        },

        selectWorkspace(workspace) {
            this.store.switchWorkspace(workspace.uuid)
        },

        openCreateModal() {
            this.$refs.createModal.show()
            this.isOpen = false
        },

        openAddModal() {
            this.$refs.addModal.show()
            this.isOpen = false
        },

        confirmUnlink(workspace) {
            this.workspaceToUnlink = workspace
            this.showUnlinkConfirm = true
        },

        async unlinkWorkspace() {
            if (!this.workspaceToUnlink) return

            try {
                await this.store.unlinkWorkspace(this.workspaceToUnlink.uuid)
                this.$notify?.success('Доска удалена из списка')
                this.showUnlinkConfirm = false
                this.workspaceToUnlink = null
            } catch (error) {
                this.$notify?.error('Ошибка при удалении')
            }
        },

        async onWorkspaceCreated(workspace) {
            await this.store.loadLinkedWorkspaces()
            this.store.switchWorkspace(workspace.uuid)
        },

        async onWorkspaceAdded() {
            await this.store.loadLinkedWorkspaces()
        },

        pluralize(count, one, two, five) {
            let n = Math.abs(count) % 100
            if (n >= 5 && n <= 20) return five
            n %= 10
            if (n === 1) return one
            if (n >= 2 && n <= 4) return two
            return five
        },

        handleClickOutside(e) {
            if (!this.$el.contains(e.target)) {
                this.isOpen = false
            }
        },

        handleKeydown(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault()
                this.togglePanel()
            }
            if (e.key === 'Escape' && this.isOpen) {
                this.isOpen = false
            }
        }
    },

    mounted() {
        document.addEventListener('click', this.handleClickOutside)
        document.addEventListener('keydown', this.handleKeydown)
        this.store.loadLinkedWorkspaces()
    },

    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside)
        document.removeEventListener('keydown', this.handleKeydown)
    }
}
</script>

<style scoped>
.workspace-switcher {
    position: relative;
}

.current-workspace {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s ease;
    min-width: 200px;
    max-width: 320px;
}

.current-workspace:hover {
    border-color: #0d6efd;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
}

.workspace-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    flex-shrink: 0;
    overflow: hidden;
}

.workspace-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.workspace-info {
    flex: 1;
    min-width: 0;
    text-align: left;
}

.workspace-name {
    font-size: 14px;
    font-weight: 600;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.workspace-count {
    font-size: 11px;
    color: #6c757d;
    margin-top: 2px;
}

.toggle-icon {
    font-size: 11px;
    color: #adb5bd;
    transition: transform 0.2s ease;
}

.current-workspace:hover .toggle-icon {
    color: #0d6efd;
}

/* === Панель === */
.switcher-panel {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    width: 360px;
    max-height: 560px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.switcher-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.2);
    z-index: 999;
}

/* === Поиск === */
.panel-search {
    position: relative;
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
}

.panel-search > i {
    position: absolute;
    left: 24px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 13px;
}

.panel-search input {
    width: 100%;
    padding: 9px 12px 9px 36px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    background: #f8f9fa;
}

.panel-search input:focus {
    background: #fff;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

/* === Быстрые действия === */
.panel-actions {
    display: flex;
    gap: 6px;
    padding: 10px 12px;
    border-bottom: 1px solid #f1f3f5;
    background: #f8f9fa;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px;
    border: 1px dashed #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #495057;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.action-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    border-style: solid;
}

.action-btn i {
    font-size: 11px;
}

/* === Секция досок === */
.workspaces-section {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.workspace-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.workspace-item:hover {
    background: #f8f9fa;
}

.workspace-item.is-current {
    background: #e7f1ff;
    cursor: default;
}

.workspace-item .workspace-icon {
    width: 32px;
    height: 32px;
    font-size: 12px;
}

.workspace-item .workspace-info {
    flex: 1;
}

.workspace-item .workspace-name {
    font-size: 13px;
}

.workspace-label {
    font-size: 11px;
    color: #6c757d;
    margin-top: 1px;
}

.workspace-badge {
    font-size: 10px;
    padding: 1px 6px;
    background: #0d6efd;
    color: #fff;
    border-radius: 8px;
    font-weight: 500;
    display: inline-block;
    margin-top: 2px;
}

.current-icon {
    color: #198754;
    font-size: 12px;
}

.unlink-btn {
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #adb5bd;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    opacity: 0;
    transition: all 0.15s ease;
}

.workspace-item:hover .unlink-btn {
    opacity: 1;
}

.unlink-btn:hover {
    background: #dc3545;
    color: #fff;
}

/* === Пустое состояние === */
.empty-hint {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px 20px;
    text-align: center;
    color: #adb5bd;
}

.empty-hint i {
    font-size: 32px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.empty-hint p {
    margin: 0 0 12px 0;
    font-size: 13px;
    color: #6c757d;
}

.btn-add-first {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1px solid #0d6efd;
    border-radius: 8px;
    background: #fff;
    color: #0d6efd;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
}

.btn-add-first:hover {
    background: #0d6efd;
    color: #fff;
}

/* === Подсказка === */
.panel-hint {
    padding: 8px 12px;
    border-top: 1px solid #f1f3f5;
    background: #f8f9fa;
    font-size: 11px;
    color: #6c757d;
    text-align: center;
}

.panel-hint kbd {
    display: inline-block;
    padding: 2px 6px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 10px;
    font-family: monospace;
    color: #495057;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* === Transition === */
.panel-enter-active,
.panel-leave-active {
    transition: all 0.2s ease;
}

.panel-enter-from,
.panel-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

/* === Responsive === */
@media (max-width: 576px) {
    .switcher-panel {
        width: calc(100vw - 24px);
        left: -12px;
    }

    .current-workspace {
        min-width: auto;
    }

    .workspace-info {
        display: none;
    }
}
</style>
