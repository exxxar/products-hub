<template>
    <div class="workspace-switcher">
        <!-- Триггер -->
        <button type="button" class="current-workspace" @click="togglePanel">
            <div class="workspace-icon" :style="{ background: store.workspaceColor }">
                <img v-if="store.workspaceLogo" v-lazy="store.workspaceLogo" alt="" />
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

        <!-- ✅ Bottom Sheet для мобильных -->
        <Transition name="sheet">
            <div v-if="isOpen" class="sheet-overlay" @click="isOpen = false"></div>
        </Transition>

        <Transition name="sheet">
            <div v-if="isOpen" class="workspace-sheet" :class="{ 'is-mobile': isMobile }">
                <!-- Ручка для мобильных -->
                <div v-if="isMobile" class="sheet-handle" @click="isOpen = false"></div>

                <!-- Header -->
                <div class="sheet-header">
                    <div class="sheet-title">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Мои доски</span>
                        <span class="sheet-count">{{ store.linkedWorkspaces.length + 1 }}</span>
                    </div>
                    <button type="button" class="sheet-close" @click="isOpen = false">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Поиск -->
                <div class="sheet-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Найти доску..."
                        ref="searchInput"
                    />
                </div>

                <!-- Быстрые действия -->
                <div class="sheet-actions">
                    <button type="button" class="sheet-action-btn" @click="openCreateModal">
                        <i class="fa-solid fa-plus"></i>
                        <span>Новая</span>
                    </button>
                    <button type="button" class="sheet-action-btn" @click="openAddModal">
                        <i class="fa-solid fa-link"></i>
                        <span>Добавить</span>
                    </button>
                </div>

                <!-- Список -->
                <div class="sheet-content">
                    <!-- Текущая -->
                    <div class="ws-item is-current">
                        <div class="ws-icon" :style="{ background: store.workspaceColor }">
                            <img v-if="store.workspaceLogo" v-lazy="store.workspaceLogo" alt="" />
                            <span v-else>{{ store.workspaceInitials }}</span>
                        </div>
                        <div class="ws-info">
                            <div class="ws-name">{{ store.workspaceName }}</div>
                            <div class="ws-badge">Текущая</div>
                        </div>
                        <i class="fa-solid fa-check ws-check"></i>
                    </div>

                    <!-- Связанные -->
                    <div v-if="filteredWorkspaces.length > 0" class="ws-list">
                        <div
                            v-for="workspace in filteredWorkspaces"
                            :key="workspace.id"
                            class="ws-item"
                        >
                            <div class="ws-icon" :style="{ background: workspace.color }">
                                <img v-if="workspace.logo_url" v-lazy="workspace.logo_url" alt="" />
                                <span v-else>{{ workspace.initials }}</span>
                            </div>
                            <div class="ws-info" @click="selectWorkspace(workspace)">
                                <div class="ws-name">{{ workspace.name }}</div>
                                <div class="ws-label" v-if="workspace.label">{{ workspace.label }}</div>
                            </div>
                            <div class="ws-actions">
                                <button
                                    type="button"
                                    class="ws-btn"
                                    @click="selectWorkspace(workspace)"
                                    title="Перейти"
                                >
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                                <button
                                    type="button"
                                    class="ws-btn"
                                    @click="openInNewTab(workspace)"
                                    title="В новом окне"
                                >
                                    <i class="fa-solid fa-up-right-from-square"></i>
                                </button>
                                <button
                                    type="button"
                                    class="ws-btn danger"
                                    @click.stop="confirmUnlink(workspace)"
                                    title="Удалить"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Пустое -->
                    <div v-else-if="searchQuery" class="empty-hint">Ничего не найдено</div>
                    <div v-else class="empty-hint">
                        <i class="fa-solid fa-layer-group"></i>
                        <p>Нет связанных досок</p>
                        <button type="button" class="btn-add-first" @click="openAddModal">
                            <i class="fa-solid fa-plus"></i>
                            Добавить
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Модалки -->
        <CreateWorkspaceModal ref="createModal" @created="onWorkspaceCreated" />
        <AddWorkspaceModal ref="addModal" @added="onWorkspaceAdded" />
        <ConfirmModal
            v-model:show="showUnlinkConfirm"
            title="Удалить из списка?"
            :description="`Удалить доску «${workspaceToUnlink?.name}» из связанных?`"
            @accept="unlinkWorkspace"
            @reject="showUnlinkConfirm = false"
        />
    </div>
</template>

<script>import { useWorkspaceStore } from '@/store/workspace.js'

import CreateWorkspaceModal from './WorkspaceCreateModal.vue'
import AddWorkspaceModal from './AddWorkspaceModal.vue'
import ConfirmModal from '@/Components/Layout/ConfirmModal.vue'

export default {
    name: 'WorkspaceSwitcher',
    components: { CreateWorkspaceModal, AddWorkspaceModal, ConfirmModal },

    data() {
        return {
            store: useWorkspaceStore(),
            isOpen: false,
            searchQuery: '',
            showUnlinkConfirm: false,
            workspaceToUnlink: null,
            isMobile: window.innerWidth < 768,
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

    methods: {
        togglePanel() {
            this.isOpen = !this.isOpen
            if (this.isOpen) {
                this.store.loadLinkedWorkspaces()
                this.$nextTick(() => this.$refs.searchInput?.focus())
            }
        },

        selectWorkspace(workspace) {
            this.store.switchWorkspace(workspace.uuid)
        },

        openInNewTab(workspace) {
            window.open(`/workspace/${workspace.uuid}`, '_blank')
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

        handleResize() {
            this.isMobile = window.innerWidth < 768
        }
    },

    mounted() {
        window.addEventListener('resize', this.handleResize)
        this.store.loadLinkedWorkspaces()
    },

    beforeUnmount() {
        window.removeEventListener('resize', this.handleResize)
    }
}
</script>

<style scoped>
.workspace-switcher {
    position: relative;
}

/* === Trigger === */
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

/* === Overlay === */
.sheet-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

/* === Sheet (общий) === */
.workspace-sheet {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #fff;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Десктоп — по центру */
@media (min-width: 768px) {
    .workspace-sheet:not(.is-mobile) {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        right: auto;
        bottom: auto;
        width: 380px;
        max-height: 560px;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
    }
}

/* Мобильный — снизу */
.workspace-sheet.is-mobile {
    border-radius: 20px 20px 0 0;
    top: auto;
    max-height: 85vh;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15);
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

/* Ручка для свайпа */
.sheet-handle {
    width: 40px;
    height: 4px;
    background: #dee2e6;
    border-radius: 2px;
    margin: 10px auto 4px;
    cursor: pointer;
    flex-shrink: 0;
}

/* === Header === */
.sheet-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid #e9ecef;
    flex-shrink: 0;
}

.sheet-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #212529;
}

.sheet-title i {
    color: #0d6efd;
    font-size: 15px;
}

.sheet-count {
    padding: 2px 8px;
    background: #e7f1ff;
    color: #0d6efd;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
}

.sheet-close {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: #f1f3f5;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.sheet-close:hover {
    background: #e9ecef;
    color: #212529;
}

/* === Search === */
.sheet-search {
    position: relative;
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    flex-shrink: 0;
}

.sheet-search > i {
    position: absolute;
    left: 24px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 13px;
}

.sheet-search input {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    background: #f8f9fa;
}

.sheet-search input:focus {
    background: #fff;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

/* === Actions === */
.sheet-actions {
    display: flex;
    gap: 8px;
    padding: 10px 12px;
    border-bottom: 1px solid #f1f3f5;
    background: #fafbfc;
    flex-shrink: 0;
}

.sheet-action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px;
    border: 1px dashed #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #495057;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.sheet-action-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    border-style: solid;
}

.sheet-action-btn i {
    font-size: 11px;
}

/* === Content === */
.sheet-content {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
    -webkit-overflow-scrolling: touch;
}

/* === Workspace Items === */
.ws-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 10px;
    transition: background 0.15s ease;
    margin-bottom: 4px;
}

.ws-item:hover {
    background: #f8f9fa;
}

.ws-item.is-current {
    background: #e7f1ff;
}

.ws-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    flex-shrink: 0;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.ws-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ws-info {
    flex: 1;
    min-width: 0;
    cursor: pointer;
}

.ws-name {
    font-size: 14px;
    font-weight: 600;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ws-label {
    font-size: 11px;
    color: #6c757d;
    margin-top: 2px;
}

.ws-badge {
    font-size: 10px;
    padding: 2px 8px;
    background: #0d6efd;
    color: #fff;
    border-radius: 8px;
    font-weight: 500;
    display: inline-block;
    margin-top: 3px;
}

.ws-check {
    color: #198754;
    font-size: 14px;
    flex-shrink: 0;
}

/* === Actions === */
.ws-actions {
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.15s ease;
    flex-shrink: 0;
}

.ws-item:hover .ws-actions {
    opacity: 1;
}

/* На мобильном кнопки всегда видны */
@media (max-width: 767px) {
    .ws-actions {
        opacity: 1;
    }
}

.ws-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.15s ease;
}

.ws-btn:hover {
    background: #e7f1ff;
    border-color: #0d6efd;
    color: #0d6efd;
}

.ws-btn.danger:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

/* === Empty === */
.empty-hint {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: #adb5bd;
}

.empty-hint i {
    font-size: 36px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.empty-hint p {
    margin: 0 0 12px 0;
    font-size: 14px;
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
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
}

.btn-add-first:hover {
    background: #0d6efd;
    color: #fff;
}

/* === Transitions === */
.sheet-enter-active,
.sheet-leave-active {
    transition: opacity 0.2s ease;
}

.sheet-enter-from,
.sheet-leave-to {
    opacity: 0;
}

/* Мобильная анимация для sheet */
.sheet-enter-active .workspace-sheet.is-mobile {
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.sheet-leave-active .workspace-sheet.is-mobile {
    animation: slideDown 0.25s ease forwards;
}

@keyframes slideDown {
    from { transform: translateY(0); }
    to { transform: translateY(100%); }
}
</style>
