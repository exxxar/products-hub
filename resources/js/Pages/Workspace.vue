<template>
    <div class="workspace-container container">
        <!-- Верхнее меню -->

        <NotifyContainer/>


        <div class="workspace-topbar">
            <WorkspaceSwitcher
                @open-create-group="openCreateGroup"
                @open-create-workspace="openCreateWorkspace"
            />
            <TopMenu
                :viewMode="viewMode"
                @change-view="viewMode = $event"
                @open-import="openImport"
                @open-collection="openCollection"
                @open-webhook="openWebhook"
                @export-vk="exportToVk"
                @toggle-sidebar="needSidebar = $event"
                @open-menu-generator="openMenuGenerator"
            />


        </div>


        <!-- Модалка авторизации -->
        <PasswordModal
            v-if="needPassword"
            @submit="authWorkspace"
        />
        <div class="workspace-layout">
            <!-- Единый sidebar с переключателем -->
            <WorkspaceSidebar
                v-if="needSidebar"
                :selectedCollection="selectedCollection"
                :selectedCategory="selectedCategory"
                @select-collection="onSelectCollection"
                @select-category="onSelectCategory"
                @create-category="openCreateCategory"
                @edit-category="openEditCategory"
                @open-presets="openCategoryPresets"
            />


            <!-- Основной контент -->
            <div class="workspace-content">

                <!-- ✅ РЕЖИМ АГРЕГАТОРА -->
                <template v-if="store.isWorkspaceAggregator">
                    <WorkspaceCardsGrid
                        :workspaces="allWorkspaces"
                        @select="goToWorkspace"
                    />


                    <!-- ✅ Кнопка открытия сайдбара групп -->
                    <button
                        type="button"
                        class="btn-open-groups"
                        @click="showGroupsSidebar = true"
                        title="Группы досок"
                    >
                        <i class="fa-solid fa-layer-group"></i>
                        <span class="btn-label">Группы</span>
                        <span v-if="store.groupsCount > 0" class="groups-badge">{{ store.groupsCount }}</span>
                    </button>

                    <!-- ✅ Сайдбар групп -->
                    <GroupsSidebar v-model="showGroupsSidebar" />
                </template>

                <!-- РЕЖИМ ТОВАРОВ (по умолчанию) -->
                <template v-else>
                    <!-- Режим сетки -->
                    <template v-if="viewMode === 'grid'">
                        <ProductGrid
                            :selectedIds="store.selectedIds"
                            @create-product="openCreateProduct"
                            @edit-product="openEditProduct"
                            @toggle-select="toggleSelect"
                            @toggle-stop-list="handleToggleStopList"
                            @edit-images="openImagesModal"
                        />
                    </template>

                    <!-- Режим таблицы -->
                    <template v-else-if="viewMode === 'table'">
                        <ProductTable
                            :products="store.products"
                            :selectedIds="store.selectedIds"
                            @edit-product="openEditProduct"
                            @toggle-select="toggleSelect"
                            @clear-selection="clearSelection"
                            @select-many="selectMany"
                        />
                    </template>

                    <!-- Режим категорий -->
                    <template v-else-if="viewMode === 'categories'">
                        <div class="categories-view">
                            <h5 class="categories-title">Группировка по категориям</h5>
                            <template v-if="selectedCategory">
                                <div class="category-header-with-back">
                                    <button
                                        type="button"
                                        class="btn-back"
                                        @click="selectedCategory = null"
                                    >
                                        <i class="fa-solid fa-arrow-left"></i>
                                        Все категории
                                    </button>
                                </div>

                                <!-- Индикатор загрузки -->
                                <div v-if="store.categoryProductsLoading" class="loading-state">
                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                    <p>Загрузка товаров...</p>
                                </div>

                                <!-- Пустое состояние -->
                                <div v-else-if="store.selectedCategoryProducts.length === 0" class="empty-category">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <h6>В категории "{{ selectedCategory.name }}" нет товаров</h6>
                                    <p>Добавьте товары в эту категорию или выберите другую</p>
                                </div>

                                <!-- Товары -->
                                <ProductGrid
                                    v-else
                                    :products="store.selectedCategoryProducts"
                                    :selectedIds="store.selectedIds"
                                    @edit-product="openEditProduct"
                                    @toggle-select="toggleSelect"
                                    @toggle-stop-list="handleToggleStopList"
                                />
                            </template>

                            <template v-else>
                                <div v-if="store.categories.length === 0" class="empty-state">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <p>Категории не созданы</p>
                                </div>

                                <div v-else v-for="cat in store.categories" :key="cat.id" class="category-group">
                                    <div class="category-header">
                                        <h6 class="category-name">{{ cat.name }}</h6>
                                        <span class="category-count">

                                  {{ cat.products_count }} товаров
                            </span>
                                    </div>

                                    <ProductGrid
                                        :products="getProductsByCategory(cat.id)"
                                        :selectedIds="store.selectedIds"
                                        @create-product="openCreateProduct"
                                        @edit-product="openEditProduct"
                                        @toggle-select="toggleSelect"
                                        @toggle-stop-list="handleToggleStopList"
                                    />
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Загрузка -->
                    <div v-if="store.productsLoading" class="loading-state">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <p>Загрузка товаров...</p>
                    </div>

                    <!-- Пустое состояние -->
                    <div v-else-if="store.products.length === 0 && !needPassword" class="empty-state-main ">
                        <i class="fa-solid fa-box-open"></i>
                        <h5>Нет товаров</h5>
                        <p>Добавьте первый товар или импортируйте из другого источника</p>

                    </div>

                    <LoadMoreButton />
                </template>
            </div>

        </div>

        <!-- ✅ Footer -->
        <WorkspaceFooter/>
        <!-- Модалки -->
        <ProductModal
            ref="productModal"
            :categories="store.categories"
            :product="store.editingProduct"
            @create-category="fastCreateNewCategory"
            @save="saveProduct"
            @delete="deleteProduct"
        />

        <CollectionModal
            ref="collectionModal"
            :collections="store.collections"
            :productIds="store.selectedIds"
            @save="saveCollection"
        />

        <WorkspaceCreateModal
            ref="workspaceCreateModal"
            @created="onWorkspaceCreated"
        />


        <!-- Модалка товаров коллекции -->
        <CollectionProductsModal
            ref="collectionProductsModal"
            @edit-collection="openEditCollection"
        />


        <ImportModal
            ref="importModal"
            @import="importProducts"
        />

        <SettingsModal
            ref="webhookModal"
            :modelValue="settingsData"
            @save="handleSaveSettings"
            @open-activity-log="openActivityLog"
            @test="testWebhook"
        />

        <CategoryPresetsModal
            ref="categoryPresetsModal"
            @applied="onPresetApplied"
        />

        <!-- Добавляем CategoryModal -->
        <CategoryModal
            ref="categoryModal"
            :category="store.editingCategory"
            @save="saveCategory"
        />

        <Transition name="slide-right">
            <ActivityLogPanel
                v-if="showActivityLog"
                @close="showActivityLog = false"
            />
        </Transition>

        <MenuConfiguratorModal ref="menuGeneratorModal"/>

        <div class="online-badge-fixed-container">
            <OnlineBadge/>
        </div>

        <!-- Модалка -->
        <PwaInstallModal
            ref="pwaInstallModal"
            @install="handleInstall"
        />

       ced="onGroupSynced" />

        <ProductImagesModal   v-model="showImagesModal"
                              :product="productForImages" />
    </div>
</template>

<script>
import TopMenu from '../Components/Layout/TopMenu.vue'
import OnlineBadge from '@/Components/Layout/OnlineBadge.vue'
import WorkspaceSidebar from '../components/Sidebar/WorkspaceSidebar.vue'
import ProductGrid from '../Components/Products/ProductGrid.vue'
import ProductTable from '../Components/Products/ProductTable.vue'
import ProductModal from '../Components/Products/ProductModal.vue'
import CollectionModal from '../Components/Collections/CollectionFormModal.vue'
import ImportModal from '../Components/Import/ImportModal.vue'
import SettingsModal from '../Components/Layout/SettingsModal.vue'

import PasswordModal from '../Components/Auth/PasswordModal.vue'
import {useWorkspaceStore} from '@/store/workspace.js'

import CategoryModal from '../components/categories/CategoryModal.vue'
import CategoryPresetsModal from '../components/categories/CategoryPresetsModal.vue'
import NotifyContainer from "@/notify/NotifyContainer.vue";
import MenuConfiguratorModal from '../components/Menu/MenuConfiguratorModal.vue'
import ActivityLogPanel from '@/Components/Layout/ActivityLogPanel.vue'
import CollectionProductsModal from '@/Components/Collections/CollectionProductsModal.vue'

import WorkspaceSwitcher from '@/Components/Groups/WorkspaceSwitcher.vue'
import WorkspaceCreateModal from '@/Components/Groups/WorkspaceCreateModal.vue'
import PwaInstallModal from '@/Components/Layout/PwaInstallModal.vue'
import WorkspaceFooter from '@/Components/Layout/WorkspaceFooter.vue'

import WorkspaceCardsGrid from '@/Components/Groups/WorkspaceCardsGrid.vue'
import LoadMoreButton from '@/Components/Products/LoadMoreButton.vue'
import ProductImagesModal from '@/Components/Products/ProductImagesModal.vue'

import GroupsSidebar from '@/Components/Groups/GroupsSidebar.vue'

export default {
    name: 'Workspace',

    components: {
        GroupsSidebar,
        ProductImagesModal,
        LoadMoreButton,
        WorkspaceCardsGrid,
        PwaInstallModal,
        NotifyContainer,
        WorkspaceSwitcher,
        TopMenu,
        WorkspaceFooter,
        OnlineBadge,
        MenuConfiguratorModal,
        WorkspaceCreateModal,
        CategoryPresetsModal,
        ProductGrid,
        ProductTable,
        ProductModal,
        CollectionModal,
        WorkspaceSidebar,
        CollectionProductsModal,
        ImportModal,
        CategoryModal,
        SettingsModal,
        PasswordModal,
        ActivityLogPanel

    },

    props: {
        item: {
            type: Object,
            required: true,
            validator: (value) => {
                return value.uuid && value.settings
            }
        }
    },

    data() {
        return {
            currentGroup: null,
            deferredPrompt: null,
            showActivityLog: false,
            workspace: null,
            needPassword: false,
            needSidebar: false,
            viewMode: 'grid',
            store: useWorkspaceStore(),
            selectedCollection: null,
            selectedCategory: null,
            webhook: null,
            showImagesModal: false,
            productForImages: null,

            showGroupsSidebar: false,
        }
    },
    watch: {
        viewMode() {
            this.selectedCategory = null
        }
    },
    computed: {

        allWorkspaces() {
            // Текущий workspace + связанные
            const current = {
                id: this.store.id,
                uuid: this.store.uuid,
                name: this.store.name,
                label: this.store.settings?.visual?.label,
                color: this.store.color,
                logo_url: this.store.logo_url,
                is_current: true
            }
            return [current, ...this.store.linkedWorkspaces]
        },
        store() {
            return useWorkspaceStore()
        },
        settingsData() {

            return {
                name: this.store.name || '',
                label: this.store.name || '',
                description: this.store.description || '',
                url: this.store.url || this.store.settings?.url || '',
                display_mode: this.store.settings?.display_mode || 'products',
                visual: {
                    label: this.store.settings?.visual?.label || '',
                    color: this.store.color || '#0d6efd',
                    logo_url: this.store.logo_url || null
                },
                vk_shop_links: this.store.settings?.vk_shop_links || '',
                iiko: this.store.settings?.iiko || {
                    api_login: '',
                    organization_id: '',
                    terminal_group_id: ''
                },
                frontpad: this.store.settings?.frontpad || {
                    secret: ''
                }
            }
        },

        displayProducts() {
            if (this.selectedCollection) {
                return this.selectedCollection.products || []
            }
            if (this.viewMode === 'categories' && this.selectedCategory) {
                return this.store.selectedCategoryProducts
            }
            return this.store.filteredProducts
        }
    },

    created() {
        this.initWorkspace()

    },
    mounted() {

        // Проверяем есть ли callback от VK
        this.handleVKCallback()

        this.store.startPresenceTracking()

        if (this.store.groupsEnabled) {
            Promise.all([
                this.store.loadAllWorkspaces(),
                this.store.loadWorkspaceGroups(),
            ])
        }

        if (this.store.isWorkspaceAggregator) {
            this.store.loadLinkedWorkspaces()
        }

        this.store.loadProducts(true)
        this.loadGroups()

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault()
            this.deferredPrompt = e

            //this.showInstallModal()
            // Показываем модалку автоматически через 5 секунд
            setTimeout(() => {
                if (!localStorage.getItem('pwa_install_dismissed')) {
                    this.showInstallModal()
                }
            }, 5000)
        })
    },
    beforeUnmount() {
        this.store.stopPresenceTracking()
    },
    methods: {
        async loadGroups() {
            try {
                await this.store.loadGroups()
            } catch (error) {
                console.error('Failed to load groups:', error)
            }
        },


        // ✅ Выбор группы из мини-списка
        selectGroup(group) {
            this.store.selectGroup(group.id)
        },

        // ✅ После создания группы
        async onGroupCreated(group) {
            // Список уже перезагружен в store.createGroup()
            // Автоматически открывается панель текущей группы

            this.$notify?.success({
                title: 'Группа создана',
                message: `«${group.name}» с ${group.workspaces?.length || 0} досками`
            })
        },

        // ✅ Открытие модалки вебхуков
        openWebhookModal() {
            if (!this.store.currentGroup) return
            this.$refs.webhookModal.show(this.store.currentGroup)
        },

        // ✅ После сохранения вебхуков
        async onWebhooksSaved() {
            // Группы уже перезагружены в store.updateGroupWebhooks()

            this.$notify?.success({
                title: 'Вебхуки обновлены',
                message: `Для ${this.store.currentGroup?.workspaces?.length || 0} досок группы`
            })
        },

        // ✅ Открытие модалки синхронизации
        openSyncModal() {
            if (!this.store.currentGroup) return
            this.$refs.syncModal.show(this.store.currentGroup)
        },

        // ✅ После синхронизации
        async onGroupSynced(results) {
            // Группы уже перезагружены в store.syncGroup()

            const successCount = results.filter(r => r.success).length
            const failCount = results.length - successCount
            const totalProducts = results.reduce((sum, r) => sum + (r.products_synced || 0), 0)

            if (failCount === 0) {
                this.$notify?.success({
                    title: 'Синхронизация завершена',
                    message: `${successCount} досок, ${totalProducts} товаров`
                })
            } else {
                this.$notify?.warning({
                    title: 'Синхронизация с ошибками',
                    message: `${successCount} успешно, ${failCount} с ошибками`
                })
            }
        },



        openImagesModal(product) {
            this.productForImages = product
            this.showImagesModal = true
        },
        goToWorkspace(workspace) {
            if (workspace.is_current) return
            this.store.switchWorkspace(workspace.uuid)
        },
        openCreateGroup() {
            this.$refs.workspaceGroupModal.show()
        },

        openCreateWorkspace() {
            this.$refs.workspaceCreateModal.show()
        },

        async onGroupSaved() {
            await this.store.loadWorkspaceGroups()
            await this.store.loadAllWorkspaces()
        },

        async onWorkspaceCreated(workspace) {
            // Переход на новую доску
            this.store.switchWorkspace(workspace.uuid)
        },
        openActivityLog() {
            this.showActivityLog = true
        },
        async handleToggleStopList(productId) {
            try {
                const result = await this.store.toggleProductStopList(productId)

                if (result.success) {
                    this.$notify?.success({
                        title: result.in_stop_list ? 'Добавлено в стоп-лист' : 'Убрано из стоп-листа',
                        message: result.in_stop_list ? 'Товар скрыт из меню' : 'Товар снова активен'
                    })
                }
            } catch (error) {
                this.$notify?.error('Ошибка при изменении статуса')
            }
        },
        openMenuGenerator() {
            if (this.$refs.menuGeneratorModal) {
                this.$refs.menuGeneratorModal.show()
            }
        },
        openCategoryPresets() {
            if (this.$refs.categoryPresetsModal) {
                this.$refs.categoryPresetsModal.show()
            }
        },

        onPresetApplied(result) {
            // Можно добавить дополнительную логику после применения пресета
            console.log('Preset applied:', result)
        },
        async exportToVk() {
            try {
                await this.store.exportToVk()
            } catch (error) {
                console.error('VK export failed:', error)
            }
        },

        // Обработка callback от VK после авторизации
        async handleVKCallback() {
            const urlParams = new URLSearchParams(window.location.search)
            const code = urlParams.get('code')
            const state = urlParams.get('state')

            if (code && state) {
                // Это callback от VK
                try {
                    // Показываем уведомление о начале импорта
                    this.$notify.info('Начинается импорт товаров из VK. Это может занять несколько минут...')

                    // Отправляем callback на backend
                    const response = await axios.get('/workspace/vk-callback', {
                        params: {code, state}
                    })

                    if (response.data.message === 'ok') {
                        this.$notify.success(`Импорт завершён! Импортировано товаров: ${response.data.imported_count || 0}`)

                        // Перезагружаем workspace чтобы показать новые товары
                        await this.store.loadWorkspace()

                        // Очищаем URL от параметров VK
                        window.history.replaceState({}, document.title, window.location.pathname)
                    }
                } catch (error) {
                    console.error('VK callback failed:', error)
                    this.$notify.error('Ошибка при импорте товаров из VK')
                }
            }
        },

        async deleteCategory(id) {
            try {
                await this.store.deleteCategory(id)
            } catch (error) {
                console.error('Delete category failed:', error)
                this.$notify.error('Ошибка при удалении категории')
            }
        },
        // === Инициализация ===
        async initWorkspace() {
            this.workspace = this.item

            // Инициализируем store
            this.store.setName(this.item.name)
            this.store.setDescription(this.item.description)
            this.store.setLogoUrl(this.item.logo_url)
            this.store.setUuid(this.item.uuid)
            this.store.setColor(this.item.color)
            this.store.setAccessToken(this.item.access_token)
            this.store.setSettings(this.item.settings)
            this.store.setDisplayMode(this.item.settings?.display_mode || 'products')
          //  this.store.setDisplayMode( 'workspaces')
           // this.store.setProducts(this.item.products || [])

            this.store.setCollections(this.item.collections || [])
            this.store.setCategories(this.item.categories || [])
            this.store.setWebhooks(this.item.webhooks || [])

            this.store.syncMasterFromWorkspace(this.workspace)
            // === Инициализируем токен из localStorage (если есть) ===
            await this.store.initFromUrl()

            this.store.initMasterUnlock()


            // Загружаем коллекции С товарами
            await this.store.loadCollections()

            // Если токена нет - создаём новый
            if (!this.store.accessToken) {
                await this.store.initializeAccessToken()
            } else {
                // Если токен есть, но accessUrl не сформирован - формируем
                if (!this.store.accessUrl) {
                    this.store.accessUrl = `${window.location.origin}/w/${this.item.uuid}?token=${this.store.accessToken}`
                }
            }

        },

        async onCollectionSaved() {
            await this.store.loadCollections()
        },
        showInstallModal() {
            this.$refs.pwaInstallModal.show()
        },

        async handleInstall() {
            if (this.deferredPrompt) {
                this.deferredPrompt.prompt()
                const {outcome} = await this.deferredPrompt.userChoice

                if (outcome === 'accepted') {
                    this.$notify?.success({
                        title: 'Приложение установлено',
                        message: 'Теперь вы можете запускать его с рабочего стола'
                    })
                }

                this.deferredPrompt = null
            }
        },

        // === Авторизация ===
        async authWorkspace(password) {
            try {
                await this.store.authWorkspace(password)
                this.needPassword = false
            } catch (error) {
                console.error('Auth failed:', error)
                // Можно показать ошибку пользователю
            }
        },

        // === Выбор товаров ===
        toggleSelect(id) {
            this.store.toggleSelect(id)
        },

        clearSelection() {
            this.store.selectedIds = []
        },

        selectMany(ids) {
            this.store.selectedIds = ids
        },

        // === Товары ===
        openCreateProduct() {
            this.store.editingProduct = null
            if (this.$refs.productModal) {
                this.$refs.productModal.show()
            }
        },

        openEditProduct(product) {
            this.store.editingProduct = product
            if (this.$refs.productModal) {
                this.$refs.productModal.show()
            }
        },

        async saveProduct(formData, id) {
            try {
                await this.store.saveProduct(formData, id)
            } catch (error) {
                console.error('Save product failed:', error)
            }
        },

        async deleteProduct(id) {
            try {
                await this.store.deleteProduct(id)
            } catch (error) {
                console.error('Delete product failed:', error)
            }
        },

        // === Collections ===
        onSelectCollection(collection) {

            this.selectedCollection = collection
            this.handleSelectCollection(collection)
            this.selectedCategory = null
            this.store.clearSelection()
        },

        async onSelectCategory(category) {
            this.selectedCategory = category
            this.selectedCollection = null
            this.store.clearSelection()


            if (category) {
                this.viewMode = 'categories'
                this.store.categoryProductsLoading = true
                try {
                    await this.store.selectCategoryWithProducts(category)
                } catch (error) {
                    this.$notify.error('Ошибка при загрузке товаров категории')
                } finally {
                    this.store.categoryProductsLoading = false
                }
            } else {
                this.store.selectedCategoryProducts = []
            }
        },


        openCreateCategory() {
            this.store.editingCategory = null
            if (this.$refs.categoryModal) {
                this.$refs.categoryModal.show()
            }
        },

        openEditCategory(category) {
            this.store.editingCategory = category
            if (this.$refs.categoryModal) {
                this.$refs.categoryModal.show()
            }
        },

        async fastCreateNewCategory(name) {
            await this.saveCategory({
                name: name
            })
        },
        async saveCategory(categoryData, id) {
            try {
                await this.store.saveCategory(categoryData, id)
            } catch (error) {
                console.error('Save category failed:', error)
                this.$notify.error('Ошибка при сохранении категории')
            }
        },


        // === Категории ===
        getProductsByCategory(categoryId) {
            if (!categoryId)
                return null;

            return this.store.products.filter(p => p.categories.findIndex(sc => sc.id === categoryId) !== -1)
        },

        getCategoryProductCount(categoryId) {
            return this.getProductsByCategory(categoryId).length
        },

        // === Коллекции ===
        openCollection() {
            if (this.$refs.collectionModal) {
                this.$refs.collectionModal.show()
            }
        },

        async handleSelectCollection(collection) {
            this.selectedCollection = collection
            this.selectedCategory = null // Сбрасываем категорию

            if (collection) {
                // Открываем модалку с товарами коллекции
                await this.$refs.collectionProductsModal.show(collection)
            }
        },


        // === Обработка выбора категории ===
        handleSelectCategory(category) {
            this.selectedCategory = category
            this.selectedCollection = null // Сбрасываем коллекцию
            this.store.exitCollectionView()
            // ... остальная логика выбора категории
        },

        // === Выход из просмотра коллекции ===
        exitCollectionView() {
            this.store.exitCollectionView()
            this.selectedCollection = null
        },

        // === Редактирование коллекции ===
        openEditCollection(collection) {
            this.$refs.collectionModal?.show(collection)
        },

        async saveCollection(data) {
            try {
                await this.store.saveCollection(data)
            } catch (error) {
                console.error('Save collection failed:', error)
            }
        },

        // === Импорт ===
        openImport() {
            if (this.$refs.importModal) {
                this.$refs.importModal.show()
            }
        },

        async importProducts(payload) {
            try {
                await this.store.importProducts(payload)
            } catch (error) {
                console.error('Import failed:', error)
            }
        },

        // === Webhook ===
        openWebhook() {
            if (this.$refs.webhookModal) {
                this.$refs.webhookModal.show()
            }
        },

        pluralize(count, one, two, five) {
            let n = Math.abs(count) % 100
            if (n >= 5 && n <= 20) return five
            n %= 10
            if (n === 1) return one
            if (n >= 2 && n <= 4) return two
            return five
        },

        async handleSaveSettings(formData) {
            console.log("handleSaveSettings", formData)
            try {
                // Сохраняем базовую информацию
                await axios.put(`/api/workspaces/${this.store.uuid}`, {
                    name: formData.name,
                    description: formData.description,
                    url: formData.url,
                    color: formData.visual.color,
                    settings: {
                        ...this.store.settings,
                        visual: formData.visual,
                        vk_shop_links: formData.vk_shop_links,
                        iiko: formData.iiko,
                        frontpad: formData.frontpad,
                        display_mode: formData.display_mode || 'products',
                    }
                })

                this.store.setDisplayMode(formData.display_mode || 'products')

                // Обновляем локальное состояние
                this.store.name = formData.name
                this.store.description = formData.description
                this.store.color = formData.visual.color
                this.store.settings = {
                    ...this.store.settings,
                    visual: formData.visual,
                    vk_shop_links: formData.vk_shop_links,
                    iiko: formData.iiko,
                    frontpad: formData.frontpad,
                    display_mode: formData.display_mode || 'products',
                }

                this.$notify?.success('Настройки сохранены')
            } catch (error) {
                console.error('Save settings failed:', error)
                this.$notify?.error('Ошибка при сохранении настроек')
            }

        },

        async testWebhook(data) {
            try {
                await this.store.testWebhook(data)
            } catch (error) {
                console.error('Test webhook failed:', error)
            }
        }
    }
}
</script>

<style scoped>

.workspace-topbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #fff;
    border-bottom: 1px solid #e9ecef;
}

/* ✅ Мобильная адаптация */
@media (max-width: 768px) {
    .workspace-topbar {
        padding: 8px 12px;
        gap: 8px;
    }

    /* Компактный WorkspaceSwitcher */
    .workspace-switcher .current-workspace {
        min-width: auto;
        padding: 6px 10px;
    }

    .workspace-switcher .workspace-info {
        display: none;
    }

    .workspace-switcher .toggle-icon {
        display: none;
    }

    .workspace-switcher .workspace-icon {
        width: 32px;
        height: 32px;
        font-size: 12px;
    }
}

.workspace-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.workspace-layout {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.workspace-content {
    flex: 1;
    overflow-y: auto;
    padding: 24px 24px 70px 24px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e9ecef;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 600;
    color: #212529;
    margin: 0;
}

.section-title i {
    color: #0d6efd;
}

.product-count {
    font-size: 14px;
    color: #6c757d;
    background: #f8f9fa;
    padding: 6px 12px;
    border-radius: 8px;
}

/* === Responsive === */
@media (max-width: 768px) {
    .workspace-layout {
        flex-direction: column;
    }
}

/* === Категории === */
.categories-view {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.categories-title {
    font-size: 20px;
    font-weight: 600;
    color: #212529;
    margin: 0;
}

.category-group {
    background: #ffffff;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e9ecef;
}

.category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f3f5;
}

.category-name {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin: 0;
}

.category-count {
    font-size: 13px;
    color: #6c757d;
    background: #f8f9fa;
    padding: 4px 10px;
    border-radius: 6px;
}

/* === Пустые состояния === */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

.empty-state-main {
    text-align: center;
    padding: 80px 20px;
    color: #6c757d;
}

.empty-state-main i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.2;
}

.empty-state-main h5 {
    font-size: 20px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.empty-state-main p {
    font-size: 14px;
    margin-bottom: 24px;
}

/* === Адаптив === */
@media (max-width: 768px) {
    .category-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .empty-state-main {
        padding: 60px 20px;
    }

    .empty-state-main i {
        font-size: 48px;
    }
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: #6c757d;
}

.loading-state i {
    font-size: 32px;
    color: #0d6efd;
    margin-bottom: 16px;
}

.loading-state p {
    margin: 0;
    font-size: 14px;
}

.category-header-with-back {
    margin-bottom: 20px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #495057;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-back:hover {
    background: #f8f9fa;
    border-color: #0d6efd;
    color: #0d6efd;
}

.category-info {
    cursor: pointer;
    transition: all 0.15s ease;
}

.category-info:hover .category-name {
    color: #0d6efd;
}

.empty-category,
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-category i,
.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-category h6,
.empty-state h6 {
    font-size: 16px;
    font-weight: 600;
    color: #495057;
    margin: 0 0 8px 0;
}

.empty-category p,
.empty-state p {
    font-size: 14px;
    margin: 0 0 20px 0;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    background: #0d6efd;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-primary:hover {
    background: #0b5ed7;
}

.categories-view {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.category-group {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e9ecef;
}

.category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f3f5;
}

.category-name {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin: 0;
    transition: color 0.15s ease;
}

.category-count {
    font-size: 13px;
    color: #6c757d;
    background: #f8f9fa;
    padding: 4px 10px;
    border-radius: 6px;
}

.category-empty {
    text-align: center;
    padding: 30px 20px;
    color: #adb5bd;
    background: #f8f9fa;
    border-radius: 8px;
}

.category-empty i {
    font-size: 32px;
    margin-bottom: 8px;
    opacity: 0.5;
}

.category-empty p {
    margin: 0;
    font-size: 13px;
}

@media (max-width: 768px) {
    .workspace-layout {
        flex-direction: column;
    }
}

.activity-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    z-index: 999;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.slide-right-enter-active,
.slide-right-leave-active {
    transition: transform 0.25s ease;
}

.slide-right-enter-from,
.slide-right-leave-to {
    transform: translateX(100%);
}

.online-badge-fixed-container {
    position: fixed;
    bottom: 40px;
    right: 5px;
}

.group-panel {
    position: fixed;
    bottom: 60px; /* Над футером */
    right: 20px;
    width: 320px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    z-index: 100;
    overflow: hidden;
}

.group-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    border-bottom: 1px solid #e9ecef;
}

.group-info {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    min-width: 0;
}

.group-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
    flex-shrink: 0;
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
    font-size: 11px;
    color: #6c757d;
    margin-top: 2px;
}

.btn-close-group {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #adb5bd;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.btn-close-group:hover {
    background: #f1f3f5;
    color: #495057;
}

/* === Список досок в группе === */
.group-workspaces {
    max-height: 200px;
    overflow-y: auto;
    padding: 8px;
    border-bottom: 1px solid #e9ecef;
}

.group-ws-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s ease;
}

.group-ws-item:hover {
    background: #f8f9fa;
}

.group-ws-item.is-current {
    background: #e7f1ff;
}

.ws-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 700;
    flex-shrink: 0;
}

.ws-name {
    flex: 1;
    font-size: 13px;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ws-current {
    color: #198754;
    font-size: 12px;
}

/* === Кнопки действий === */
.group-actions {
    display: flex;
    gap: 6px;
    padding: 10px;
    background: #fafbfc;
}

.btn-action {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    border: none;
}

.btn-action.primary {
    background: #0d6efd;
    color: #fff;
}

.btn-action.primary:hover {
    background: #0b5ed7;
}

.btn-action.secondary {
    background: #fff;
    color: #495057;
    border: 1px solid #dee2e6;
}

.btn-action.secondary:hover {
    background: #f8f9fa;
    border-color: #0d6efd;
    color: #0d6efd;
}

.btn-open-groups {
    position: fixed;
    bottom: 60px;
    left: 20px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    color: #495057;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.15s ease;
    z-index: 99;
}

.btn-open-groups:hover {
    border-color: #6f42c1;
    color: #6f42c1;
    box-shadow: 0 4px 12px rgba(111, 66, 193, 0.15);
}

.btn-open-groups i {
    color: #6f42c1;
}

.groups-badge {
    padding: 2px 7px;
    background: #6f42c1;
    color: #fff;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

@media (max-width: 576px) {
    .btn-open-groups {
        bottom: 20px;
        left: 90px; /* Правее FAB кнопки */
    }

    .btn-label {
        display: none;
    }
}
</style>
