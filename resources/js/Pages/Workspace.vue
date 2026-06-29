<template>
    <div class="workspace-container container">
        <!-- Верхнее меню -->

        <NotifyContainer/>

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
                <!-- Режим сетки -->
                <template v-if="viewMode === 'grid'">
                    <ProductGrid
                        :selectedIds="store.selectedIds"
                        @create-product="openCreateProduct"
                        @edit-product="openEditProduct"
                        @toggle-select="toggleSelect"
                        @toggle-stop-list="handleToggleStopList"
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
                                {{ getCategoryProductCount(cat.id) }} товаров
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

                <!-- Пустое состояние -->
                <div v-if="store.products.length === 0 && !needPassword" class="empty-state-main ">
                    <i class="fa-solid fa-box-open"></i>
                    <h5>Нет товаров</h5>
                    <p>Добавьте первый товар или импортируйте из другого источника</p>

                </div>
            </div>

        </div>
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

        <ImportModal
            ref="importModal"
            @import="importProducts"
        />

        <WebhookSettingsModal
            ref="webhookModal"
            :modelValue="webhook"
            @save="saveWebhook"
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

        <MenuConfiguratorModal ref="menuGeneratorModal" />

        <!-- PWA установка -->
        <div class="modal fade" id="installPwaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-download me-2"></i>Установить приложение
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Вы можете установить Агрегатор товаров как приложение и запускать его прямо с рабочего стола.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Позже</button>
                        <button class="btn btn-primary" @click="installPWA">
                            <i class="fa-solid fa-download me-2"></i>Установить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import TopMenu from '../Components/Layout/TopMenu.vue'
import WorkspaceSidebar from '../components/Sidebar/WorkspaceSidebar.vue'
import ProductGrid from '../Components/Products/ProductGrid.vue'
import ProductTable from '../Components/Products/ProductTable.vue'
import ProductModal from '../Components/Products/ProductModal.vue'
import CollectionModal from '../Components/Collections/CollectionModal.vue'
import ImportModal from '../Components/Import/ImportModal.vue'
import WebhookSettingsModal from '../Components/Layout/SettingsModal.vue'

import PasswordModal from '../Components/Auth/PasswordModal.vue'
import {useWorkspaceStore} from '@/store/workspace.js'

import CategoryModal from '../components/categories/CategoryModal.vue'
import CategoryPresetsModal from '../components/categories/CategoryPresetsModal.vue'
import NotifyContainer from "@/notify/NotifyContainer.vue";
import MenuConfiguratorModal from '../components/Menu/MenuConfiguratorModal.vue'
import ActivityLogPanel from '@/Components/Layout/ActivityLogPanel.vue'


export default {
    name: 'Workspace',

    components: {
        NotifyContainer,
        TopMenu,
        MenuConfiguratorModal,
        CategoryPresetsModal,
        ProductGrid,
        ProductTable,
        ProductModal,
        CollectionModal,
        WorkspaceSidebar,
        ImportModal,
        CategoryModal,
        WebhookSettingsModal,
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
            showActivityLog: false,
            workspace: null,
            needPassword: false,
            needSidebar: false,
            viewMode: 'grid',
            store: useWorkspaceStore(),
            selectedCollection: null,
            selectedCategory: null,
            webhook: null
        }
    },
    watch: {
        viewMode() {
            this.selectedCategory = null
        }
    },
    computed: {
        store() {
            return useWorkspaceStore()
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
    },
    methods: {
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

            console.log("Workspace", this.workspace)
            // Инициализируем store
            this.store.setUuid(this.item.uuid)
            this.store.setAccessToken(this.item.access_token)
            this.store.setSettings(this.item.settings)
            this.store.setProducts(this.item.products || [])

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

        // === PWA ===
        installPWA() {
            if (typeof window.installPWA === 'function') {
                window.installPWA()
            } else {
                console.warn('PWA installation not available')
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

        async fastCreateNewCategory(name){
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

            console.log("Test", categoryId)
            return this.store.products.filter(p => p.category_id === categoryId)
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

        async saveCollection(data) {
            console.log("data", data)
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

        async saveWebhook(data) {
            try {
                await this.store.saveWebhook(data)
                this.webhook = data
            } catch (error) {
                console.error('Save webhook failed:', error)
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
    padding: 24px;
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
    from { opacity: 0; }
    to { opacity: 1; }
}

.slide-right-enter-active,
.slide-right-leave-active {
    transition: transform 0.25s ease;
}

.slide-right-enter-from,
.slide-right-leave-to {
    transform: translateX(100%);
}
</style>
