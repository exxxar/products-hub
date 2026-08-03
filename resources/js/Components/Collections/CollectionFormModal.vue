<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header builder-header">
                    <div class="header-left">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            {{ isEditing ? 'Редактировать коллекцию' : 'Создать коллекцию' }}
                        </h5>
                    </div>

                    <div class="header-center">
                        <input
                            v-model="form.name"
                            type="text"
                            class="collection-name-input"
                            placeholder="Название коллекции (например: Happy Meal)"
                            maxlength="100"
                        />
                    </div>

                    <div class="header-right">
                        <div class="collection-stats" v-if="totalProductsCount > 0">
                            <span class="stat-chip">
                                <i class="fa-solid fa-cube"></i>
                                {{ totalProductsCount }} товаров
                            </span>
                            <span class="stat-chip">
                                <i class="fa-solid fa-layer-group"></i>
                                {{ form.categories.length }} категорий
                            </span>
                        </div>
                        <button type="button" class="btn-close" @click="hide"></button>
                    </div>
                </div>

                <!-- Body: Two Panels -->
                <div class="modal-body builder-body">
                    <!-- LEFT PANEL -->
                    <div class="selector-panel">
                        <!-- === VIEW: Categories List === -->
                        <template v-if="leftView === 'categories'">
                            <div class="panel-header">
                                <h6>
                                    <i class="fa-solid fa-layer-group"></i>
                                    Категории
                                </h6>
                                <div class="panel-search">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input
                                        v-model="categorySearch"
                                        type="text"
                                        placeholder="Поиск..."
                                    />
                                </div>
                            </div>

                            <div class="categories-full-list">
                                <div
                                    v-for="cat in filteredCategories"
                                    :key="cat.id"
                                    class="category-row"
                                    :class="{ 'in-collection': isCategoryInCollection(cat.id) }"
                                    @click="openCategoryProducts(cat)"
                                >
                                    <div class="category-row-main">
                                        <div class="cat-icon">
                                            <i class="fa-solid fa-folder"></i>
                                        </div>
                                        <div class="cat-details">
                                            <span class="cat-title">{{ cat.name }}</span>
                                            <span class="cat-sub">
                                                {{ cat.products_count ?? 0 }} товаров
                                            </span>
                                        </div>
                                    </div>

                                    <div class="category-row-right">
                                        <span v-if="getCategoryFromForm(cat.id)" class="cat-collection-badge">
                                            <i class="fa-solid fa-check"></i>
                                            {{ getCategoryFromForm(cat.id).products.length }}
                                        </span>
                                        <i class="fa-solid fa-chevron-right chevron"></i>
                                    </div>
                                </div>

                                <div v-if="filteredCategories.length === 0" class="empty-panel">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <p>Нет категорий</p>
                                </div>
                            </div>
                        </template>

                        <!-- === VIEW: Products of Selected Category === -->
                        <template v-else-if="leftView === 'products' && selectedCategory">
                            <div class="panel-header">
                                <button class="btn-back-inline" @click="leftView = 'categories'">
                                    <i class="fa-solid fa-arrow-left"></i>
                                </button>
                                <h6 class="products-title">
                                    {{ selectedCategory.name }}
                                </h6>
                                <div class="panel-search">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input
                                        v-model="productSearch"
                                        type="text"
                                        placeholder="Поиск..."
                                    />
                                </div>
                            </div>

                            <div class="products-toolbar">
                                <span class="products-count-label">
                                    {{ filteredCategoryProducts.length }} товаров
                                </span>
                                <div class="products-toolbar-actions">
                                    <button class="btn-sm" @click="selectAllCategoryProducts">
                                        <i class="fa-solid fa-check-double"></i>
                                        Выбрать все
                                    </button>
                                    <button class="btn-sm" @click="clearCategorySelection">
                                        <i class="fa-solid fa-xmark"></i>
                                        Сброс
                                    </button>
                                </div>
                            </div>

                            <div v-if="loadingProducts" class="empty-panel">
                                <i class="fa-solid fa-spinner fa-spin"></i>
                                <p>Загрузка товаров...</p>
                            </div>

                            <div v-else class="products-full-list">
                                <div
                                    v-for="product in filteredCategoryProducts"
                                    :key="product.id"
                                    class="product-row"
                                    :class="{ selected: isProductInSelectedCategory(product.id) }"
                                    @click="toggleProduct(product)"
                                >
                                    <label class="product-row-label" @click.stop>
                                        <input
                                            type="checkbox"
                                            :checked="isProductInSelectedCategory(product.id)"
                                            @change="toggleProduct(product)"
                                            @click.stop
                                        />
                                    </label>

                                    <div class="product-row-img" v-if="product.images?.length">
                                        <img :src="product.images[0].url" :alt="product.name" />
                                    </div>
                                    <div class="product-row-img placeholder" v-else>
                                        <i class="fa-solid fa-image"></i>
                                    </div>

                                    <div class="product-row-info">
                                        <span class="product-row-name">{{ product.name }}</span>
                                        <span class="product-row-meta">
                                            <span v-if="product.sku" class="sku">{{ product.sku }}</span>
                                            <span class="price">{{ formatPrice(getNum(product.price)) }}</span>
                                        </span>
                                    </div>
                                </div>

                                <div v-if="filteredCategoryProducts.length === 0" class="empty-panel">
                                    <i class="fa-solid fa-box-open"></i>
                                    <p>Нет товаров</p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Divider -->
                    <div class="panel-divider">
                        <div class="divider-line"></div>
                        <span class="divider-icon">
                            <i class="fa-solid fa-arrow-right"></i>
                        </span>
                        <div class="divider-line"></div>
                    </div>

                    <!-- RIGHT PANEL -->
                    <div class="preview-panel">
                        <div class="panel-header">
                            <h6>
                                <i class="fa-solid fa-eye"></i>
                                Состав коллекции
                            </h6>
                            <button
                                v-if="form.categories.length > 0"
                                class="btn-sm btn-danger"
                                @click="clearCollection"
                            >
                                <i class="fa-solid fa-trash"></i>
                                Очистить
                            </button>
                        </div>

                        <!-- Empty State -->
                        <div v-if="form.categories.length === 0" class="preview-empty">
                            <i class="fa-solid fa-box-open"></i>
                            <h6>Коллекция пуста</h6>
                            <p>Выберите категорию слева,<br/>затем отметьте товары галочками</p>
                        </div>

                        <!-- Categories in Collection -->
                        <div v-else class="collection-categories">
                            <div
                                v-for="(catConfig, index) in form.categories"
                                :key="catConfig.temp_id"
                                class="collection-category-card"
                            >
                                <div class="cc-header">
                                    <div class="cc-reorder">
                                        <button
                                            class="reorder-btn"
                                            @click="moveCategoryUp(index)"
                                            :disabled="index === 0"
                                            title="Вверх"
                                        >
                                            <i class="fa-solid fa-chevron-up"></i>
                                        </button>
                                        <button
                                            class="reorder-btn"
                                            @click="moveCategoryDown(index)"
                                            :disabled="index === form.categories.length - 1"
                                            title="Вниз"
                                        >
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </button>
                                    </div>

                                    <div class="cc-info">
                                        <span class="cc-name">{{ catConfig.category_name }}</span>
                                        <span class="cc-count">{{ catConfig.products.length }} товаров</span>
                                    </div>

                                    <div class="cc-rule">
                                        <select v-model="catConfig.selection_rule" class="rule-select">
                                            <option value="one">Выбор 1 позиции</option>
                                            <option value="multiple">Выбор нескольких</option>
                                            <option value="all">Все товары</option>
                                        </select>
                                    </div>

                                    <button
                                        class="cc-remove"
                                        @click="removeCategory(index)"
                                        title="Удалить категорию"
                                    >
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                <div class="cc-products">
                                    <div
                                        v-for="p in catConfig.products"
                                        :key="p.id"
                                        class="cc-product-item"
                                    >
                                        <span class="cc-product-name">{{ p.name }}</span>
                                        <span class="cc-product-price">
                                            {{ formatPrice(getNum(p.price)) }}
                                        </span>
                                        <button
                                            class="cc-product-remove"
                                            @click="removeProductFromCategory(catConfig, p.id)"
                                            title="Убрать"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                    <div class="cc-subtotal">
                                        <span>Подытог:</span>
                                        <strong>{{ formatPrice(getCategorySubtotal(catConfig)) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Totals (always visible when there's data) -->
                        <div class="collection-totals" v-if="form.categories.length > 0">
                            <div class="total-row">
                                <span>Сумма товаров:</span>
                                <span>{{ formatPrice(totalProductsSum) }}</span>
                            </div>
                            <div class="total-row" v-if="form.pricing_type === 'fixed' && form.fixed_price">
                                <span>Фиксированная цена:</span>
                                <span>{{ formatPrice(getNum(form.fixed_price)) }}</span>
                            </div>
                            <div class="total-row" v-if="discountAmount > 0">
                                <span>Скидка {{ form.discount_percent }}%:</span>
                                <span class="discount-amount">-{{ formatPrice(discountAmount) }}</span>
                            </div>
                            <div class="total-row total-final">
                                <span>Итого:</span>
                                <strong>{{ formatPrice(finalPrice) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer builder-footer">
                    <!-- Pricing -->
                    <div class="footer-section pricing-section">
                        <h6><i class="fa-solid fa-tag"></i> Ценообразование</h6>
                        <div class="pricing-options">
                            <label class="pricing-radio" :class="{ active: form.pricing_type === 'sum' }">
                                <input type="radio" v-model="form.pricing_type" value="sum" />
                                <span>Сумма товаров</span>
                            </label>
                            <label class="pricing-radio" :class="{ active: form.pricing_type === 'fixed' }">
                                <input type="radio" v-model="form.pricing_type" value="fixed" />
                                <span>Фиксированная цена</span>
                            </label>
                        </div>
                        <div v-if="form.pricing_type === 'fixed'" class="fixed-price-row">
                            <label>Цена:</label>
                            <input
                                v-model="form.fixed_price"
                                type="number"
                                class="price-input"
                                placeholder="0"
                                min="0"
                                step="0.01"
                            />
                            <span>₽</span>
                        </div>
                        <div class="discount-row">
                            <label>Скидка на коллекцию:</label>
                            <div class="discount-inputs">
                                <input
                                    v-model="form.discount_percent"
                                    type="number"
                                    class="discount-input"
                                    placeholder="0"
                                    min="0"
                                    max="100"
                                    step="1"
                                />
                                <span>%</span>
                                <span v-if="discountAmount > 0" class="discount-preview">
                                    = -{{ formatPrice(discountAmount) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Image -->
                    <div class="footer-section image-section">
                        <h6><i class="fa-solid fa-image"></i> Изображение</h6>
                        <div v-if="form.image_url" class="image-preview-box">
                            <img :src="form.image_url" alt="Collection" />
                            <button class="image-remove" @click="removeImage">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <label v-else class="image-upload-box">
                            <input
                                type="file"
                                accept="image/*"
                                @change="handleImageUpload"
                                style="display: none"
                            />
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Загрузить</span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="footer-section actions-section">
                        <div class="actions-row">
                            <button type="button" class="btn-cancel" @click="hide">Отмена</button>
                            <button
                                type="button"
                                class="btn-save"
                                @click="save"
                                :disabled="isSaving || !isValid"
                            >
                                <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                                <i v-else class="fa-solid fa-check"></i>
                                {{ isSaving ? 'Сохранение...' : 'Сохранить' }}
                            </button>
                        </div>
                        <div class="validation-hint" v-if="!isValid">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            Укажите название и добавьте товары
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'
import { useWorkspaceStore } from '@/store/workspace.js'
import axios from 'axios'

export default {
    name: 'CollectionFormModal',

    emits: ['saved'],

    data() {
        return {
            store: useWorkspaceStore(),
            modal: null,
            isEditing: false,
            editingId: null,
            isSaving: false,

            leftView: 'categories',
            selectedCategory: null,
            categoryProducts: [],
            loadingProducts: false,

            categorySearch: '',
            productSearch: '',

            form: this.getEmptyForm(),
            imageFile: null,
            originalImageUrl: null,

            tempIdCounter: 0,
        }
    },

    computed: {
        isValid() {
            if (!this.form.name?.trim()) return false
            if (this.form.pricing_type === 'fixed' && !this.getNum(this.form.fixed_price)) return false
            if (this.form.categories.length === 0) return false
            const hasProducts = this.form.categories.some(c => c.products.length > 0)
            if (!hasProducts) return false
            return true
        },

        filteredCategories() {
            const q = this.categorySearch.toLowerCase().trim()
            const list = this.store.categories || []
            if (!q) return list
            return list.filter(c => c.name?.toLowerCase().includes(q))
        },

        filteredCategoryProducts() {
            const q = this.productSearch.toLowerCase().trim()
            if (!q) return this.categoryProducts
            return this.categoryProducts.filter(p =>
                p.name?.toLowerCase().includes(q) ||
                p.sku?.toLowerCase().includes(q)
            )
        },

        totalProductsCount() {
            return this.form.categories.reduce((sum, c) => sum + c.products.length, 0)
        },

        totalProductsSum() {
            return this.form.categories.reduce((sum, c) => sum + this.getCategorySubtotal(c), 0)
        },

        basePrice() {
            if (this.form.pricing_type === 'fixed') {
                return this.getNum(this.form.fixed_price)
            }
            return this.totalProductsSum
        },

        discountAmount() {
            const p = this.getNum(this.form.discount_percent)
            if (!p || p <= 0) return 0
            return Math.round(this.basePrice * p) / 100
        },

        finalPrice() {
            return Math.max(0, this.basePrice - this.discountAmount)
        },
    },

    methods: {
        getNum(value) {
            if (value === null || value === undefined || value === '') return 0
            const n = typeof value === 'number' ? value : parseFloat(String(value).replace(/[^\d.,-]/g, '').replace(',', '.'))
            return isNaN(n) ? 0 : n
        },

        formatPrice(price) {
            const n = this.getNum(price)
            return new Intl.NumberFormat('ru-RU', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            }).format(n) + ' ₽'
        },

        getEmptyForm() {
            return {
                name: '',
                short_description: '',
                description: '',
                categories: [],
                pricing_type: 'sum',
                fixed_price: null,
                discount_percent: 0,
                image_url: null,
            }
        },

        async show(collection = null) {
            if (!this.store.categories?.length) {
                await this.store.loadCategories?.()
            }
            if (!this.store.products?.length) {
                await this.store.loadProducts(true)
            }

            if (collection) {
                this.isEditing = true
                this.editingId = collection.id
                this.loadCollectionForEdit(collection)
            } else {
                this.isEditing = false
                this.editingId = null
                this.form = this.getEmptyForm()
            }

            this.resetLeftPanel()

            this.$nextTick(() => {
                if (this.modal) this.modal.show()
            })
        },

        loadCollectionForEdit(collection) {
            const storeProductsMap = new Map(
                (this.store.products || []).map(p => [p.id, p])
            )

            const restoredCategories = (collection.collection_categories || []).map((cc) => {
                let products = []

                if (Array.isArray(cc.products) && cc.products.length > 0) {
                    products = cc.products.map(p => ({
                        id: p.id,
                        name: p.name,
                        price: this.getNum(p.price),
                        old_price: this.getNum(p.old_price),
                        sku: p.sku,
                        images: p.images,
                    }))
                } else if (Array.isArray(cc.product_ids)) {
                    products = cc.product_ids
                        .map(id => storeProductsMap.get(id))
                        .filter(Boolean)
                        .map(p => ({
                            id: p.id,
                            name: p.name,
                            price: this.getNum(p.price),
                            old_price: this.getNum(p.old_price),
                            sku: p.sku,
                            images: p.images,
                        }))
                }

                return {
                    temp_id: `edit_${cc.id || this.tempIdCounter++}`,
                    category_id: cc.category_id,
                    category_name: cc.category_name,
                    selection_rule: cc.selection_rule || 'one',
                    products,
                }
            })

            this.imageFile = null
            this.originalImageUrl = collection.image_url || null

            this.form = {
                name: collection.name || '',
                short_description: collection.short_description || '',
                description: collection.description || '',
                categories: restoredCategories,
                pricing_type: collection.pricing_type || 'sum',
                fixed_price: collection.fixed_price,
                discount_percent: this.getNum(collection.discount_percent),
                image_url: collection.image_url || null,
            }
        },

        hide() {
            if (this.form.image_url?.startsWith('blob:')) {
                URL.revokeObjectURL(this.form.image_url)
            }
            if (this.modal) this.modal.hide()
        },

        resetLeftPanel() {
            this.leftView = 'categories'
            this.selectedCategory = null
            this.categoryProducts = []
            this.productSearch = ''
            this.categorySearch = ''
            this.loadingProducts = false
        },

        async openCategoryProducts(cat) {
            this.selectedCategory = cat
            this.productSearch = ''
            this.loadingProducts = true
            this.leftView = 'products'

            try {
                const response = await axios.get(
                    `/api/workspaces/${this.store.uuid}/categories/${cat.id}/products`
                )

                const res = response.data
                let raw = []

                if (Array.isArray(res)) {
                    raw = res
                } else if (Array.isArray(res?.data)) {
                    raw = res.data
                } else if (Array.isArray(res?.products)) {
                    raw = res.products
                } else if (Array.isArray(res?.data?.products)) {
                    raw = res.data.products
                }

                this.categoryProducts = raw.map(p => ({
                    ...p,
                    price: this.getNum(p.price),
                    old_price: this.getNum(p.old_price),
                }))
            } catch (error) {
                console.error('Failed to load category products:', error)
                this.categoryProducts = []
                this.$notify?.error('Не удалось загрузить товары')
            } finally {
                this.loadingProducts = false
            }
        },

        isCategoryInCollection(categoryId) {
            return this.form.categories.some(c => c.category_id === categoryId)
        },

        getCategoryFromForm(categoryId) {
            return this.form.categories.find(c => c.category_id === categoryId) || null
        },

        isProductInSelectedCategory(productId) {
            if (!this.selectedCategory) return false
            const catConfig = this.getCategoryFromForm(this.selectedCategory.id)
            return catConfig?.products.some(p => p.id === productId) || false
        },

        toggleProduct(product) {
            if (!this.selectedCategory) return

            let catConfig = this.getCategoryFromForm(this.selectedCategory.id)

            if (!catConfig) {
                catConfig = {
                    temp_id: `cat_${this.tempIdCounter++}`,
                    category_id: this.selectedCategory.id,
                    category_name: this.selectedCategory.name,
                    selection_rule: 'one',
                    products: [],
                }
                this.form.categories.push(catConfig)
            }

            const idx = catConfig.products.findIndex(p => p.id === product.id)
            if (idx > -1) {
                catConfig.products.splice(idx, 1)
                if (catConfig.products.length === 0) {
                    this.form.categories = this.form.categories.filter(c => c.temp_id !== catConfig.temp_id)
                }
            } else {
                catConfig.products.push({
                    id: product.id,
                    name: product.name,
                    price: this.getNum(product.price),
                    old_price: this.getNum(product.old_price),
                    sku: product.sku,
                    images: product.images,
                })
            }
        },

        selectAllCategoryProducts() {
            if (!this.selectedCategory) return

            let catConfig = this.getCategoryFromForm(this.selectedCategory.id)
            if (!catConfig) {
                catConfig = {
                    temp_id: `cat_${this.tempIdCounter++}`,
                    category_id: this.selectedCategory.id,
                    category_name: this.selectedCategory.name,
                    selection_rule: 'all',
                    products: [],
                }
                this.form.categories.push(catConfig)
            }

            const existingIds = new Set(catConfig.products.map(p => p.id))
            this.filteredCategoryProducts.forEach(p => {
                if (!existingIds.has(p.id)) {
                    catConfig.products.push({
                        id: p.id,
                        name: p.name,
                        price: this.getNum(p.price),
                        old_price: this.getNum(p.old_price),
                        sku: p.sku,
                        images: p.images,
                    })
                }
            })
            catConfig.selection_rule = 'all'
        },

        clearCategorySelection() {
            if (!this.selectedCategory) return
            this.form.categories = this.form.categories.filter(
                c => c.category_id !== this.selectedCategory.id
            )
        },

        moveCategoryUp(index) {
            if (index <= 0) return
            const arr = this.form.categories
            ;[arr[index - 1], arr[index]] = [arr[index], arr[index - 1]]
            this.form.categories = [...arr]
        },

        moveCategoryDown(index) {
            if (index >= this.form.categories.length - 1) return
            const arr = this.form.categories
            ;[arr[index + 1], arr[index]] = [arr[index], arr[index + 1]]
            this.form.categories = [...arr]
        },

        removeCategory(index) {
            this.form.categories.splice(index, 1)
        },

        removeProductFromCategory(catConfig, productId) {
            catConfig.products = catConfig.products.filter(p => p.id !== productId)
            if (catConfig.products.length === 0) {
                this.form.categories = this.form.categories.filter(c => c.temp_id !== catConfig.temp_id)
            }
        },

        clearCollection() {
            if (confirm('Очистить все товары из коллекции?')) {
                this.form.categories = []
            }
        },

        getCategorySubtotal(catConfig) {
            return catConfig.products.reduce((sum, p) => sum + this.getNum(p.price), 0)
        },

        handleImageUpload(event) {
            const file = event.target.files[0]
            if (!file) return

            this.imageFile = file

            if (this.form.image_url?.startsWith('blob:')) {
                URL.revokeObjectURL(this.form.image_url)
            }
            this.form.image_url = URL.createObjectURL(file)

            event.target.value = ''
        },

        removeImage() {
            if (this.form.image_url?.startsWith('blob:')) {
                URL.revokeObjectURL(this.form.image_url)
            }
            this.imageFile = null
            this.form.image_url = null
        },

        async save() {
            if (!this.isValid || this.isSaving) return

            this.isSaving = true

            try {
                const formData = new FormData()

                formData.append('name', this.form.name)
                formData.append('short_description', this.form.short_description || '')
                formData.append('description', this.form.description || '')
                formData.append('type', 'custom')
                formData.append('pricing_type', this.form.pricing_type)

                if (this.form.pricing_type === 'fixed') {
                    formData.append('fixed_price', this.getNum(this.form.fixed_price))
                }

                formData.append('discount_percent', this.getNum(this.form.discount_percent))

                const categoriesPayload = this.form.categories.map(c => ({
                    category_id: c.category_id,
                    selection_rule: c.selection_rule,
                    product_ids: c.products.map(p => p.id),
                }))
                formData.append('categories', JSON.stringify(categoriesPayload))

                if (this.imageFile) {
                    formData.append('image', this.imageFile)
                }

                const shouldRemoveImage = !this.form.image_url && this.originalImageUrl
                if (shouldRemoveImage) {
                    formData.append('remove_image', '1')
                }

                let response
                const baseUrl = `/api/workspaces/${this.store.uuid}/collections`

                if (this.isEditing) {
                    formData.append('_method', 'PUT')
                    response = await axios.post(`${baseUrl}/${this.editingId}`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    })

                    // 🔥 Обновляем существующую коллекцию в store
                    const index = this.store.collections.findIndex(c => c.id === this.editingId)
                    if (index > -1) {
                        this.store.collections[index] = response.data
                    }
                } else {
                    response = await axios.post(baseUrl, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    })

                    // 🔥 Добавляем новую коллекцию в начало списка
                    this.store.collections.unshift(response.data)
                }

                this.$emit('saved', response.data)
                this.hide()

                this.$notify?.success({
                    title: this.isEditing ? 'Коллекция обновлена' : 'Коллекция создана',
                    message: this.form.name,
                })
            } catch (error) {
                console.error('Save collection failed:', error)
                this.$notify?.error(error.response?.data?.message || 'Ошибка при сохранении')
            } finally {
                this.isSaving = false
            }
        },
    },

    mounted() {
        this.modal = new Modal(this.$refs.modal)
    },

    beforeUnmount() {
        if (this.modal) {
            this.modal.dispose()
            this.modal = null
        }
    },
}
</script>

<style scoped>
/* === Header === */
.builder-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 24px;
    border-bottom: 1px solid #e9ecef;
    background: #fff;
    gap: 16px;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    white-space: nowrap;
}

.modal-title i { color: #6f42c1; }

.header-center { flex: 1; max-width: 500px; }

.collection-name-input {
    width: 100%;
    padding: 8px 14px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 500;
    text-align: center;
    background: #f8f9fa;
    outline: none;
    transition: all 0.15s ease;
}

.collection-name-input:focus {
    border-color: #0d6efd;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.header-right { display: flex; align-items: center; gap: 12px; }

.collection-stats { display: flex; gap: 8px; }

.stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    background: #e7f1ff;
    color: #0d6efd;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

/* === Body === */
.builder-body {
    display: flex;
    flex: 1;
    overflow: hidden;
    padding: 0;
    min-height: 500px;
}

/* === Left Panel === */
.selector-panel {
    width: 420px;
    min-width: 360px;
    border-right: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    background: #fafbfc;
}

.panel-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    border-bottom: 1px solid #e9ecef;
    background: #fff;
}

.panel-header h6 {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    color: #212529;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.panel-header h6 i { color: #0d6efd; }

.products-title {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-back-inline {
    width: 32px;
    height: 32px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #495057;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.btn-back-inline:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #e7f1ff;
}

.panel-search {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 6px 10px;
}

.panel-search i { color: #adb5bd; font-size: 12px; }

.panel-search input {
    border: none;
    background: transparent;
    outline: none;
    font-size: 13px;
    width: 140px;
}

/* === Categories full list === */
.categories-full-list,
.products-full-list {
    flex: 1;
    overflow-y: auto;
}

.category-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
    transition: all 0.1s ease;
}

.category-row:hover {
    background: #e7f1ff;
}

.category-row.in-collection {
    background: #f0f9ff;
    border-left: 3px solid #0d6efd;
}

.category-row-main {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.cat-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #e7f1ff;
    color: #0d6efd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.category-row.in-collection .cat-icon {
    background: #0d6efd;
    color: #fff;
}

.cat-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    flex: 1;
}

.cat-title {
    font-size: 14px;
    font-weight: 500;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cat-sub {
    font-size: 12px;
    color: #6c757d;
}

.category-row-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.cat-collection-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    background: #0d6efd;
    color: #fff;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.chevron {
    color: #adb5bd;
    font-size: 11px;
}

.category-row:hover .chevron { color: #0d6efd; }

/* === Products toolbar === */
.products-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    background: #fff;
    border-bottom: 1px solid #e9ecef;
}

.products-count-label {
    font-size: 12px;
    color: #6c757d;
}

.products-toolbar-actions { display: flex; gap: 6px; }

.btn-sm {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 10px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: #fff;
    color: #495057;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.1s ease;
}

.btn-sm:hover {
    border-color: #0d6efd;
    color: #0d6efd;
}

.btn-sm.btn-danger:hover {
    border-color: #dc3545;
    color: #dc3545;
    background: #fff5f5;
}

/* === Products rows === */
.product-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
    transition: all 0.1s ease;
}

.product-row:hover { background: #f8f9fa; }

.product-row.selected {
    background: #e7f1ff;
    border-left: 3px solid #0d6efd;
}

.product-row-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.product-row-label input {
    width: 18px;
    height: 18px;
    accent-color: #0d6efd;
    cursor: pointer;
}

.product-row-img {
    width: 40px;
    height: 40px;
    border-radius: 6px;
    overflow: hidden;
    flex-shrink: 0;
    background: #f1f3f5;
}

.product-row-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-row-img.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
    font-size: 16px;
}

.product-row-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.product-row-name {
    font-size: 13px;
    font-weight: 500;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.product-row-meta {
    display: flex;
    gap: 8px;
    font-size: 12px;
    color: #6c757d;
}

.product-row-meta .sku { color: #adb5bd; }
.product-row-meta .price { color: #0d6efd; font-weight: 600; }

.empty-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 60px 20px;
    color: #adb5bd;
    text-align: center;
}

.empty-panel i { font-size: 32px; margin-bottom: 12px; opacity: 0.5; }
.empty-panel p { font-size: 13px; margin: 0; }

/* === Divider === */
.panel-divider {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 32px;
    background: #fff;
    border-right: 1px solid #e9ecef;
    border-left: 1px solid #e9ecef;
}

.divider-line { flex: 1; width: 1px; background: #e9ecef; }

.divider-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e7f1ff;
    color: #0d6efd;
    border-radius: 50%;
    font-size: 10px;
    margin: 8px 0;
}

/* === Right Panel === */
.preview-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #fff;
    overflow-y: auto;
}

.preview-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 60px 20px;
    text-align: center;
    color: #adb5bd;
}

.preview-empty i { font-size: 48px; margin-bottom: 16px; opacity: 0.3; }
.preview-empty h6 { font-size: 16px; font-weight: 600; color: #6c757d; margin: 0 0 6px 0; }
.preview-empty p { font-size: 13px; margin: 0; line-height: 1.5; }

.collection-categories {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    flex: 1;
}

.collection-category-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.15s ease;
}

.collection-category-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.08);
}

.cc-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.cc-reorder { display: flex; flex-direction: column; gap: 2px; }

.reorder-btn {
    width: 22px;
    height: 18px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    transition: all 0.1s ease;
}

.reorder-btn:hover:not(:disabled) {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #e7f1ff;
}

.reorder-btn:disabled { opacity: 0.3; cursor: not-allowed; }

.cc-info { flex: 1; display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.cc-name { font-size: 14px; font-weight: 600; color: #212529; }
.cc-count { font-size: 11px; color: #6c757d; }
.cc-rule { flex-shrink: 0; }

.rule-select {
    padding: 4px 8px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 12px;
    background: #fff;
    color: #495057;
    cursor: pointer;
    outline: none;
}

.rule-select:focus { border-color: #0d6efd; }

.cc-remove {
    width: 26px;
    height: 26px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #adb5bd;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.1s ease;
}

.cc-remove:hover { background: #fff5f5; color: #dc3545; }

.cc-products { padding: 4px 0; }

.cc-product-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 14px;
    border-bottom: 1px solid #f8f9fa;
}

.cc-product-item:last-child { border-bottom: none; }

.cc-product-name {
    flex: 1;
    font-size: 13px;
    color: #495057;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cc-product-price {
    font-size: 12px;
    color: #0d6efd;
    font-weight: 600;
    white-space: nowrap;
}

.cc-product-remove {
    width: 20px;
    height: 20px;
    border: none;
    border-radius: 4px;
    background: transparent;
    color: #adb5bd;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    transition: all 0.1s ease;
}

.cc-product-remove:hover { background: #fff5f5; color: #dc3545; }

.cc-subtotal {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 8px 14px;
    font-size: 12px;
    color: #6c757d;
    border-top: 1px dashed #e9ecef;
}

.cc-subtotal strong { color: #0d6efd; }

.collection-totals {
    padding: 16px;
    margin-top: auto;
    border-top: 2px solid #e9ecef;
    background: #f8f9fa;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 13px;
    color: #495057;
}

.total-final {
    padding-top: 10px;
    margin-top: 6px;
    border-top: 1px solid #dee2e6;
    font-size: 16px;
}

.total-final strong { color: #0d6efd; font-size: 18px; }

.discount-amount { color: #dc3545; font-weight: 600; }

/* === Footer === */
.builder-footer {
    display: flex;
    gap: 20px;
    padding: 16px 24px;
    border-top: 1px solid #e9ecef;
    background: #fff;
    align-items: flex-start;
    flex-wrap: wrap;
}

.footer-section { display: flex; flex-direction: column; gap: 8px; }

.footer-section h6 {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #495057;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.footer-section h6 i { color: #0d6efd; }

.pricing-section { flex: 2; min-width: 280px; }
.image-section { flex: 0 0 auto; align-items: center; }
.actions-section { flex: 0 0 auto; margin-left: auto; }

.pricing-options { display: flex; gap: 8px; flex-wrap: wrap; }

.pricing-radio {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.1s ease;
}

.pricing-radio input { accent-color: #0d6efd; }

.pricing-radio.active {
    border-color: #0d6efd;
    background: #e7f1ff;
    color: #0d6efd;
}

.fixed-price-row,
.discount-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.fixed-price-row label,
.discount-row label {
    font-size: 12px;
    color: #495057;
    white-space: nowrap;
}

.price-input,
.discount-input {
    width: 110px;
    padding: 5px 8px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 13px;
    outline: none;
}

.price-input:focus,
.discount-input:focus { border-color: #0d6efd; }

.discount-inputs {
    display: flex;
    align-items: center;
    gap: 6px;
}

.discount-preview {
    font-size: 12px;
    color: #dc3545;
    font-weight: 600;
    white-space: nowrap;
}

.image-preview-box {
    position: relative;
    width: 70px;
    height: 70px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.image-preview-box img { width: 100%; height: 100%; object-fit: cover; }

.image-remove {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 20px;
    height: 20px;
    border: none;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.image-upload-box {
    width: 70px;
    height: 70px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    cursor: pointer;
    color: #6c757d;
    transition: all 0.15s ease;
}

.image-upload-box:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #e7f1ff;
}

.image-upload-box i { font-size: 18px; }
.image-upload-box span { font-size: 10px; }

.actions-row { display: flex; gap: 8px; }

.btn-cancel {
    padding: 8px 18px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.1s ease;
}

.btn-cancel:hover { background: #f8f9fa; }

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 24px;
    border: none;
    border-radius: 8px;
    background: #0d6efd;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.1s ease;
}

.btn-save:hover:not(:disabled) { background: #0b5ed7; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

.validation-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #dc3545;
    margin-top: 4px;
}

/* === Responsive === */
@media (max-width: 992px) {
    .builder-body { flex-direction: column; }

    .selector-panel {
        width: 100%;
        min-width: auto;
        max-height: 50vh;
        border-right: none;
        border-bottom: 1px solid #e9ecef;
    }

    .panel-divider { display: none; }
    .builder-footer { flex-direction: column; }
}
</style>
