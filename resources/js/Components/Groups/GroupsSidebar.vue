<template>
    <Teleport to="body">
        <Transition name="sidebar">
            <div v-if="isOpen" class="groups-sidebar-wrapper">
                <!-- Overlay -->
                <div class="sidebar-overlay" @click="close"></div>

                <!-- Sidebar -->
                <div class="groups-sidebar">
                    <!-- Header -->
                    <div class="sidebar-header">
                        <div class="header-title">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Группы досок</span>
                            <span class="groups-count">{{ store.groups.length }}</span>
                        </div>
                        <div class="header-actions">
                            <button
                                type="button"
                                class="btn-create"
                                @click="openCreateModal"
                                title="Создать группу"
                            >
                                <i class="fa-solid fa-plus"></i>
                            </button>
                            <button
                                type="button"
                                class="btn-close-sidebar"
                                @click="close"
                                title="Закрыть"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="sidebar-body">
                        <!-- Список групп -->
                        <div v-if="!store.currentGroup" class="groups-list">
                            <div
                                v-for="group in store.groups"
                                :key="group.id"
                                class="group-card"
                                @click="selectGroup(group)"
                            >
                                <div class="group-icon" :style="{ background: group.color }">
                                    <i class="fa-solid fa-layer-group"></i>
                                </div>
                                <div class="group-info">
                                    <div class="group-name">{{ group.name }} </div>
                                    <div class="group-meta">
                                        {{ group.workspaces?.length || 0 }}
                                        {{ pluralize(group.workspaces?.length || 0, 'доска', 'доски', 'досок') }}
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right group-arrow"></i>
                            </div>

                            <!-- Пустое состояние -->
                            <div v-if="store.groups.length === 0" class="empty-state">
                                <i class="fa-solid fa-layer-group"></i>
                                <p>Нет групп</p>
                                <span>Создайте первую группу для объединения досок</span>
                                <button type="button" class="btn-create-first" @click="openCreateModal">
                                    <i class="fa-solid fa-plus"></i>
                                    Создать группу
                                </button>
                            </div>
                        </div>

                        <!-- Детали выбранной группы -->
                        <div v-else class="group-details">
                            <!-- Back button -->
                            <button type="button" class="btn-back" @click="store.clearCurrentGroup()">
                                <i class="fa-solid fa-arrow-left"></i>
                                <span>К списку групп</span>
                            </button>

                            <!-- Info -->
                            <div class="details-header">
                                <div class="group-icon large" :style="{ background: store.currentGroup.color }">
                                    <i class="fa-solid fa-layer-group"></i>
                                </div>
                                <div class="details-info">
                                    <div class="details-name">{{ store.currentGroup.name }}</div>
                                    <div class="details-meta">
                                        {{ store.currentGroup.workspaces?.length || 0 }}
                                        {{ pluralize(store.currentGroup.workspaces?.length || 0, 'доска', 'доски', 'досок') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Список досок в группе -->
                            <div class="details-section">
                                <div class="section-title">
                                    <i class="fa-solid fa-table-columns"></i>
                                    <span>Доски в группе</span>
                                </div>
                                <div class="workspaces-list">
                                    <div
                                        v-for="ws in currentGroup.workspaces"
                                        :key="ws.id"
                                        class="ws-item"
                                        :class="{ 'is-current': ws.id === currentWorkspaceId }"
                                    >
                                        <div class="ws-icon" :style="{ background: ws.color }">
                                            {{ ws.initials || ws.name?.substring(0, 2) }}
                                        </div>
                                        <div class="ws-info">
                                            <div class="ws-name">{{ ws.name }} ({{ws.id}})</div>
                                            <div class="ws-label" v-if="ws.label">{{ ws.label }}</div>
                                        </div>
                                        <div class="ws-actions">
                                            <button
                                                v-if="ws.id !== currentWorkspaceId"
                                                type="button"
                                                class="icon-btn"
                                                @click="goToWorkspace(ws)"
                                                title="Перейти"
                                            >
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="icon-btn danger"
                                                @click="removeFromGroup(ws)"
                                                title="Удалить из группы"
                                            >
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Кнопка добавления доски -->
                                    <button
                                        type="button"
                                        class="add-workspace-btn"
                                        @click="openAddWorkspaceModal"
                                    >
                                        <i class="fa-solid fa-plus"></i>
                                        <span>Добавить доску</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Действия -->
                            <div class="details-section">
                                <div class="section-title">
                                    <i class="fa-solid fa-bolt"></i>
                                    <span>Действия</span>
                                </div>
                                <div class="action-buttons">
                                    <button
                                        type="button"
                                        class="action-btn secondary"
                                        @click="openWebhookModal"
                                    >
                                        <i class="fa-solid fa-link"></i>
                                        <div class="action-info">
                                            <strong>Вебхуки</strong>
                                            <span>Настроить для всех досок</span>
                                        </div>
                                    </button>
                                    <button
                                        type="button"
                                        class="action-btn primary"
                                        @click="openSyncModal"
                                    >
                                        <i class="fa-solid fa-arrows-rotate"></i>
                                        <div class="action-info">
                                            <strong>Синхронизировать</strong>
                                            <span>Обновить данные</span>
                                        </div>
                                    </button>
                                    <button
                                        type="button"
                                        class="action-btn secondary"
                                        @click="openEditModal"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                        <div class="action-info">
                                            <strong>Редактировать</strong>
                                            <span>Название, цвет, состав</span>
                                        </div>
                                    </button>
                                    <button
                                        type="button"
                                        class="action-btn danger"
                                        @click="confirmDeleteGroup"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                        <div class="action-info">
                                            <strong>Удалить группу</strong>
                                            <span>Доски не будут удалены</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Модалки (всё ещё нужны для многошаговых процессов) -->
        <GroupCreateModal
            v-model="showCreateModal"
            @created="onGroupCreated"
        />
        <GroupEditModal
            v-model="showEditModal"
            :group="store.currentGroup"
            @saved="onGroupEdited"
        />
        <GroupWebhookModal
            v-model="showWebhookModal"
            :group="store.currentGroup"
            @saved="onWebhooksSaved"
        />
        <GroupSyncModal
            v-model="showSyncModal"
            :group="store.currentGroup"
            @synced="onGroupSynced"
        />
        <AddWorkspaceToGroupModal
            v-model="showAddWorkspaceModal"
            :group="store.currentGroup"
            @added="onWorkspaceAdded"
        />
        <ConfirmModal
            v-model:show="showDeleteConfirm"
            title="Удалить группу?"
            :description="`Удалить группу «${store.currentGroup?.name}»? Доски останутся, но связь между ними будет разорвана.`"
            type="danger"
            confirm-text="Удалить группу"
            @accept="deleteGroup"
        />
    </Teleport>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'
import GroupCreateModal from '@/Components/Groups/GroupCreateModal.vue'
import GroupEditModal from '@/Components/Groups/GroupEditModal.vue'
import GroupWebhookModal from '@/Components/Groups/GroupWebhookModal.vue'
import GroupSyncModal from '@/Components/Groups/GroupSyncModal.vue'
import AddWorkspaceToGroupModal from '@/Components/Groups/AddWorkspaceToGroupModal.vue'
import ConfirmModal from '@/Components/Layout/ConfirmModal.vue'

export default {
    name: 'GroupsSidebar',

    components: {
        GroupCreateModal,
        GroupEditModal,
        GroupWebhookModal,
        GroupSyncModal,
        AddWorkspaceToGroupModal,
        ConfirmModal
    },

    props: {
        modelValue: {
            type: Boolean,
            default: false
        }
    },

    emits: ['update:modelValue'],

    data() {
        return {
            store: useWorkspaceStore(),
            showCreateModal: false,
            showEditModal: false,
            showWebhookModal: false,
            showSyncModal: false,
            showAddWorkspaceModal: false,
            showDeleteConfirm: false,
        }
    },

    computed: {
        isOpen: {
            get() { return this.modelValue },
            set(val) { this.$emit('update:modelValue', val) }
        },
        currentWorkspaceId() {
            return this.store.currentWorkspace?.id
        },
        currentGroup(){
            return this.store.currentGroup || []
        }
    },

    watch: {
        isOpen(val) {
            if (val) {
                document.body.style.overflow = 'hidden'
                this.store.loadGroups()
            } else {
                document.body.style.overflow = ''
            }
        }
    },

    methods: {
        close() {
            this.isOpen = false
        },

        selectGroup(group) {
            this.store.selectGroup(group.id)
        },

        // === Модалки ===
        openCreateModal() { this.showCreateModal = true },
        openEditModal() { this.showEditModal = true },
        openWebhookModal() { this.showWebhookModal = true },
        openSyncModal() { this.showSyncModal = true },
        openAddWorkspaceModal() { this.showAddWorkspaceModal = true },

        // === Удаление доски из группы ===
        async removeFromGroup(workspace) {
            try {
                const newIds = this.currentGroup.workspaces
                    .filter(w => w.id !== workspace.id)
                    .map(w => w.id)

                await this.store.updateGroupWorkspaces(this.currentGroup.id, newIds)
                this.$notify?.success(`«${workspace.name}» удалена из группы`)
            } catch (e) {
                this.$notify?.error('Ошибка при удалении')
            }
        },

        // === Удаление группы ===
        confirmDeleteGroup() {
            this.showDeleteConfirm = true
        },

        async deleteGroup() {
            try {
                await this.store.deleteGroup(this.store.currentGroup.id)
                this.store.clearCurrentGroup()
                this.$notify?.success('Группа удалена')
            } catch (e) {
                this.$notify?.error('Ошибка при удалении')
            }
        },

        // === Переход на другую доску ===
        goToWorkspace(workspace) {
            window.location.href = `/workspace/${workspace.uuid}`
        },

        // === Callbacks ===
        async onGroupCreated(group) {
            this.store.selectGroup(group.id)
            this.$notify?.success({
                title: 'Группа создана',
                message: `«${group.name}»`
            })
        },

        async onGroupEdited() {
            this.$notify?.success('Группа обновлена')
        },

        async onWebhooksSaved() {
            this.$notify?.success('Вебхуки обновлены')
        },

        async onGroupSynced(results) {
            const successCount = results.filter(r => r.success).length
            const failCount = results.length - successCount

            // Считаем итоги (с фолбэком на старые поля, если они вдруг придут)
            const totalProducts = results.reduce((sum, r) => sum + (r.products_count || r.products_synced || 0), 0)
            const totalCollections = results.reduce((sum, r) => sum + (r.collections_count || 0), 0)

            if (failCount === 0) {
                this.$notify?.success({
                    title: 'Синхронизация завершена',
                    // ✅ Используем разделители для читаемости
                    message: `${successCount} досок • ${totalProducts} товаров • ${totalCollections} коллекций`
                })
            } else {
                this.$notify?.warning({
                    title: 'Синхронизация с ошибками',
                    message: `${successCount} успешно, ${failCount} с ошибками`
                })
            }
        },

        async onWorkspaceAdded() {
            this.$notify?.success('Доска добавлена в группу')
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
/* === Wrapper === */
.groups-sidebar-wrapper {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    justify-content: flex-end;
}

.sidebar-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
}

/* === Sidebar === */
.groups-sidebar {
    position: relative;
    width: 420px;
    max-width: 100%;
    height: 100%;
    background: #fff;
    box-shadow: -8px 0 24px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    animation: slideInRight 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideInRight {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

/* === Header === */
.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    flex-shrink: 0;
}

.header-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
    color: #212529;
}

.header-title i {
    color: #6f42c1;
    font-size: 18px;
}

.groups-count {
    padding: 2px 8px;
    background: #6f42c1;
    color: #fff;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.header-actions {
    display: flex;
    gap: 6px;
}

.btn-create,
.btn-close-sidebar {
    width: 34px;
    height: 34px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.btn-create:hover {
    background: #6f42c1;
    border-color: #6f42c1;
    color: #fff;
}

.btn-close-sidebar:hover {
    background: #f1f3f5;
    color: #212529;
}

/* === Body === */
.sidebar-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}

/* === Groups List === */
.groups-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.group-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.group-card:hover {
    border-color: #6f42c1;
    background: #faf8ff;
    transform: translateX(-2px);
}

.group-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
    flex-shrink: 0;
}

.group-icon.large {
    width: 56px;
    height: 56px;
    font-size: 20px;
}

.group-info {
    flex: 1;
    min-width: 0;
}

.group-name {
    font-size: 14px;
    font-weight: 600;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.group-meta {
    font-size: 12px;
    color: #6c757d;
    margin-top: 2px;
}

.group-arrow {
    color: #adb5bd;
    font-size: 12px;
    flex-shrink: 0;
}

/* === Empty State === */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    color: #adb5bd;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.4;
}

.empty-state p {
    margin: 0 0 4px 0;
    font-size: 16px;
    font-weight: 600;
    color: #495057;
}

.empty-state span {
    font-size: 13px;
    margin-bottom: 20px;
}

.btn-create-first {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    background: #6f42c1;
    color: #fff;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
}

.btn-create-first:hover {
    background: #5a32a3;
}

/* === Group Details === */
.group-details {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #6c757d;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    align-self: flex-start;
}

.btn-back:hover {
    background: #f1f3f5;
    color: #212529;
}

.details-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 12px;
}

.details-info {
    flex: 1;
    min-width: 0;
}

.details-name {
    font-size: 18px;
    font-weight: 700;
    color: #212529;
    margin-bottom: 4px;
}

.details-meta {
    font-size: 13px;
    color: #6c757d;
}

/* === Sections === */
.details-section {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.section-title i {
    color: #0d6efd;
    font-size: 12px;
}

/* === Workspaces List === */
.workspaces-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.ws-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.15s ease;
}

.ws-item:hover {
    background: #f8f9fa;
}

.ws-item.is-current {
    background: #e7f1ff;
    border-color: #cfe2ff;
}

.ws-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
}

.ws-info {
    flex: 1;
    min-width: 0;
}

.ws-name {
    font-size: 13px;
    font-weight: 500;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ws-label {
    font-size: 11px;
    color: #6c757d;
    margin-top: 1px;
}

.ws-actions {
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.ws-item:hover .ws-actions {
    opacity: 1;
}

.icon-btn {
    width: 28px;
    height: 28px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    transition: all 0.15s ease;
}

.icon-btn:hover {
    background: #e7f1ff;
    border-color: #0d6efd;
    color: #0d6efd;
}

.icon-btn.danger:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

.add-workspace-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    border: 1px dashed #dee2e6;
    border-radius: 8px;
    background: transparent;
    color: #6c757d;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    margin-top: 4px;
}

.add-workspace-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #f8f9ff;
}

/* === Action Buttons === */
.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
    border: 1px solid #e9ecef;
    background: #fff;
}

.action-btn i {
    font-size: 16px;
    flex-shrink: 0;
}

.action-info {
    flex: 1;
    min-width: 0;
}

.action-info strong {
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 2px;
}

.action-info span {
    font-size: 12px;
    color: #6c757d;
}

.action-btn.secondary {
    color: #495057;
}

.action-btn.secondary:hover {
    border-color: #0d6efd;
    background: #f8f9ff;
    color: #0d6efd;
}

.action-btn.primary {
    background: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
    color: #fff;
    border: none;
}

.action-btn.primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.action-btn.primary .action-info span {
    color: rgba(255, 255, 255, 0.8);
}

.action-btn.danger {
    color: #dc3545;
}

.action-btn.danger:hover {
    background: #dc3545;
    color: #fff;
    border-color: #dc3545;
}

.action-btn.danger .action-info span {
    color: #dc3545;
}

.action-btn.danger:hover .action-info span {
    color: rgba(255, 255, 255, 0.8);
}

/* === Transitions === */
.sidebar-enter-active,
.sidebar-leave-active {
    transition: opacity 0.3s ease;
}

.sidebar-enter-active .groups-sidebar,
.sidebar-leave-active .groups-sidebar {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.sidebar-enter-from,
.sidebar-leave-to {
    opacity: 0;
}

.sidebar-enter-from .groups-sidebar,
.sidebar-leave-to .groups-sidebar {
    transform: translateX(100%);
}

/* === Responsive === */
@media (max-width: 576px) {
    .groups-sidebar {
        width: 100%;
    }

    .action-btn {
        padding: 10px 12px;
    }

    .ws-actions {
        opacity: 1;
    }
}
</style>
