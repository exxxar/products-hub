<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header collection-header">
                    <div class="header-info">
                        <div class="header-icon">
                            <img
                                v-if="collection?.image_url"
                                v-lazy="collection.image_url"
                                :alt="collection.name"
                            />
                            <i v-else class="fa-solid fa-box-open"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="modal-title">{{ collection?.name || 'Коллекция' }}</h5>
                            <div class="header-meta">
                                <span class="meta-type">
                                    <i :class="getTypeIcon(collection?.type)"></i>
                                    {{ collection?.type_label }}
                                </span>
                                <span class="meta-sep">•</span>
                                <span class="meta-count">
                                    {{ groupedProducts.length }} категорий,
                                    {{ flatProducts.length }} товаров
                                </span>
                                <span v-if="isUpdating" class="meta-updating">
                                    <i class="fa-solid fa-arrows-rotate fa-spin"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="header-price">
                        <div class="price-info">
                            <span
                                v-if="collection?.discount_percent > 0 && basePrice > 0"
                                class="old-price"
                            >
                                {{ formatPrice(basePrice) }}
                            </span>
                            <span class="current-price">
                                {{ formatPrice(finalPrice) }}
                            </span>
                        </div>
                        <div v-if="collection?.discount_percent > 0" class="discount-badge">
                            -{{ collection.discount_percent }}%
                        </div>
                    </div>

                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <!-- Описание -->
                    <div v-if="collection?.description" class="view-description">
                        <p>{{ collection.description }}</p>
                    </div>

                    <!-- Статистика -->
                    <div class="view-stats">
                        <div class="stat-item">
                            <i class="fa-solid fa-layer-group"></i>
                            <div>
                                <strong>{{ groupedProducts.length }}</strong>
                                <span>категорий</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fa-solid fa-box"></i>
                            <div>
                                <strong>{{ flatProducts.length }}</strong>
                                <span>товаров</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fa-solid fa-ruble-sign"></i>
                            <div>
                                <strong>{{ formatPrice(totalPrice) }}</strong>
                                <span>сумма товаров</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fa-solid fa-ban"></i>
                            <div>
                                <strong>{{ stopListProductsCount }}</strong>
                                <span>в стоп-листе</span>
                            </div>
                        </div>
                    </div>

                    <!-- Инфо о правиле -->
                    <div v-if="collection?.type === 'custom'" class="rule-info">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            <strong>Набор по категориям</strong>
                            <p>Клиент выбирает товары согласно правилам каждой категории</p>
                        </div>
                    </div>

                    <!-- Пустое состояние -->
                    <div v-if="groupedProducts.length === 0 && !isUpdating" class="empty-state">
                        <i class="fa-solid fa-box-open"></i>
                        <p>В коллекции нет товаров</p>
                        <button
                            v-if="canEditProducts"
                            type="button"
                            class="btn-edit"
                            @click="editCollection"
                        >
                            <i class="fa-solid fa-pen"></i>
                            Добавить товары
                        </button>
                    </div>

                    <!-- Skeleton -->
                    <div v-else-if="groupedProducts.length === 0 && isUpdating" class="groups-list">
                        <div v-for="n in 2" :key="'sk-g-'+n" class="category-group skeleton">
                            <div class="category-group-header">
                                <div class="skeleton-line w-50"></div>
                            </div>
                            <div class="products-grid">
                                <div v-for="m in 3" :key="'sk-p-'+m" class="product-card skeleton">
                                    <div class="product-image skeleton-img"></div>
                                    <div class="product-info">
                                        <div class="skeleton-line w-75"></div>
                                        <div class="skeleton-line w-40"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 🔥 ГРУППИРОВКА ПО КАТЕГОРИЯМ -->
                    <div v-else class="groups-list">
                        <div
                            v-for="group in groupedProducts"
                            :key="group.category_id"
                            class="category-group"
                        >
                            <!-- Заголовок категории -->
                            <div class="category-group-header">
                                <div class="category-group-left">
                                    <div class="category-icon">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                    <div class="category-title-block">
                                        <h6 class="category-title">{{ group.category_name }}</h6>
                                        <span class="category-count">
                                            {{ group.products.length }} {{ pluralize(group.products.length, ['товар', 'товара', 'товаров']) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="category-group-right">
                                    <span class="rule-badge" :class="'rule-' + group.selection_rule">
                                        <i class="fa-solid fa-circle-info"></i>
                                        {{ group.rule_label || getRuleLabel(group.selection_rule) }}
                                    </span>
                                    <span class="category-subtotal">
                                        {{ formatPrice(getGroupSubtotal(group)) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Товары категории -->
                            <div class="products-grid">
                                <div
                                    v-for="product in group.products"
                                    :key="product.id"
                                    class="product-card"
                                    :class="{
                                        'in-stop-list': product.in_stop_list,
                                        'inactive': !product.is_active
                                    }"
                                >
                                    <div class="product-image">
                                        <img
                                            v-if="product.images?.length"
                                            v-lazy="product.images[0].url"
                                            :alt="product.name"
                                        />
                                        <div v-else class="image-placeholder">
                                            <i class="fa-solid fa-image"></i>
                                        </div>

                                        <div v-if="product.in_stop_list" class="product-badge stop">
                                            <i class="fa-solid fa-ban"></i>
                                        </div>
                                        <div
                                            v-if="product.old_price && product.old_price > product.price"
                                            class="product-badge discount"
                                        >
                                            -{{ Math.round((1 - product.price / product.old_price) * 100) }}%
                                        </div>
                                    </div>

                                    <div class="product-info">
                                        <div class="product-name">{{ product.name }}</div>

                                        <div v-if="product.sku" class="product-sku">
                                            <i class="fa-solid fa-barcode"></i>
                                            {{ product.sku }}
                                        </div>

                                        <div class="product-price">
                                            <span
                                                v-if="product.old_price && product.old_price > product.price"
                                                class="old-price"
                                            >
                                                {{ formatPrice(product.old_price) }}
                                            </span>
                                            <span class="current-price">
                                                {{ formatPrice(product.price) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div v-if="flatProducts.length > 0" class="modal-footer">
                    <div class="footer-info">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Общая стоимость товаров: <strong>{{ formatPrice(totalPrice) }}</strong></span>
                    </div>

                    <button
                        v-if="canEditProducts"
                        type="button"
                        class="btn-edit-collection"
                        @click="editCollection"
                    >
                        <i class="fa-solid fa-pen"></i>
                        Редактировать
                    </button>
                    <button type="button" class="btn-close-modal" @click="hide">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'
import axios from 'axios'
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'CollectionProductsModal',

    emits: ['edit-collection'],

    data() {
        return {
            store: useWorkspaceStore(),
            modal: null,
            collection: null,
            groupedProducts: [], // 🔥 Теперь это массив групп, а не плоский список
            isUpdating: false,
        }
    },

    computed: {
        flatProducts() {
            return this.groupedProducts.flatMap(g => g.products || [])
        },

        totalPrice() {
            return this.groupedProducts.reduce((sum, g) => sum + this.getGroupSubtotal(g), 0)
        },

        basePrice() {
            if (!this.collection) return 0
            if (this.collection.pricing_type === 'fixed') {
                return parseFloat(this.collection.fixed_price) || 0
            }
            return this.totalPrice
        },

        finalPrice() {
            if (!this.collection) return 0
            const discount = parseFloat(this.collection.discount_percent) || 0
            if (discount > 0 && this.basePrice > 0) {
                return Math.round(this.basePrice * (1 - discount / 100))
            }
            return this.basePrice
        },

        activeProductsCount() {
            return this.flatProducts.filter(p => p.is_active && !p.in_stop_list).length
        },

        stopListProductsCount() {
            return this.flatProducts.filter(p => p.in_stop_list).length
        },

        canEditProducts() {
            if (!this.collection) return false
            return ['manual', 'category_select', 'custom'].includes(this.collection.type)
        }
    },

    methods: {
        async show(collection) {
            this.collection = collection
            this.isUpdating = true

            // 🔥 1. МГНОВЕННО: Группируем товары из store
            this.groupedProducts = this.resolveGroupsFromStore(collection)

            // 🔥 2. Открываем модалку сразу
            this.$nextTick(() => {
                if (this.modal) this.modal.show()
            })

            // 🔥 3. Фоновый запрос для актуальных данных
            try {
                const response = await axios.get(
                    `/api/workspaces/${this.store.uuid}/collections/${collection.id}`
                )

                // Обновляем коллекцию свежими данными
                if (response.data.collection) {
                    this.collection = { ...collection, ...response.data.collection }
                }

                // Берём готовые groups из бэкенда (их уже формирует CollectionController::show)
                const freshGroups = this.extractGroupsFromResponse(response.data)

                if (Array.isArray(freshGroups) && freshGroups.length > 0) {
                    this.groupedProducts = freshGroups
                }
            } catch (error) {
                console.error('Load collection details failed:', error)
                if (this.groupedProducts.length === 0) {
                    this.$notify?.error('Не удалось загрузить детали коллекции')
                }
            } finally {
                this.isUpdating = false
            }
        },

        /**
         * Мгновенно строит группы из store
         */
        resolveGroupsFromStore(collection) {
            if (!this.store.products?.length) return []
            const storeMap = new Map(this.store.products.map(p => [p.id, p]))

            // 🎯 CUSTOM: Группы уже заданы в collection_categories
            if (collection.type === 'custom' && collection.collection_categories?.length) {
                return collection.collection_categories.map(c => ({
                    category_id: c.category_id,
                    category_name: c.category_name,
                    selection_rule: c.selection_rule,
                    rule_label: this.getRuleLabel(c.selection_rule),
                    products: (c.products || c.product_ids?.map(id => storeMap.get(id)).filter(Boolean) || []),
                }))
            }

            // 🎯 ALL_PRODUCTS: Группируем по родной категории товара
            if (collection.all_product_ids?.length) {
                const products = collection.all_product_ids
                    .map(id => storeMap.get(id))
                    .filter(Boolean)
                return this.groupProductsByNativeCategory(products)
            }

            return []
        },

        /**
         * Группирует плоский список товаров по их родным категориям
         */
        groupProductsByNativeCategory(products) {
            const groups = {}
            products.forEach(p => {
                const cat = p.categories?.[0] || { id: 0, name: 'Без категории' }
                if (!groups[cat.id]) {
                    groups[cat.id] = {
                        category_id: cat.id,
                        category_name: cat.name,
                        selection_rule: 'all',
                        rule_label: 'Все товары',
                        products: [],
                    }
                }
                groups[cat.id].products.push(p)
            })
            return Object.values(groups)
        },

        /**
         * Парсит ответ бэкенда и возвращает массив групп
         */
        extractGroupsFromResponse(data) {
            // Вариант 1: Бэкенд отдаёт готовые groups (метод show() в контроллере)
            if (Array.isArray(data.groups)) {
                return data.groups.map(g => ({
                    category_id: g.category_id,
                    category_name: g.category_name,
                    selection_rule: g.selection_rule,
                    rule_label: g.rule_label,
                    products: g.products || [],
                }))
            }

            // Вариант 2: Вложенные collection_categories (из formatCollection)
            if (Array.isArray(data.collection?.collection_categories)) {
                return data.collection.collection_categories.map(c => ({
                    category_id: c.category_id,
                    category_name: c.category_name,
                    selection_rule: c.selection_rule,
                    rule_label: c.rule_label,
                    products: c.products || [],
                }))
            }

            // Вариант 3: Только плоский products — группируем вручную
            if (Array.isArray(data.products)) {
                return this.groupProductsByNativeCategory(data.products)
            }

            return []
        },

        getGroupSubtotal(group) {
            return (group.products || []).reduce((sum, p) => sum + (parseFloat(p.price) || 0), 0)
        },

        getRuleLabel(rule) {
            const labels = {
                one: 'Выбор 1 позиции',
                multiple: 'Выбор нескольких',
                all: 'Все товары категории',
            }
            return labels[rule] || 'Выбор 1 позиции'
        },

        pluralize(n, forms) {
            const abs = Math.abs(n) % 100
            const n1 = abs % 10
            if (abs > 10 && abs < 20) return forms[2]
            if (n1 > 1 && n1 < 5) return forms[1]
            if (n1 === 1) return forms[0]
            return forms[2]
        },

        hide() {
            if (this.modal) this.modal.hide()
        },

        editCollection() {
            this.$emit('edit-collection', this.collection)
            this.hide()
        },

        getTypeIcon(type) {
            const icons = {
                manual: 'fa-solid fa-hand-pointer',
                category_all: 'fa-solid fa-folder-open',
                categories_all: 'fa-solid fa-folder-tree',
                workspace_all: 'fa-solid fa-boxes-stacked',
                category_select: 'fa-solid fa-list-check',
                custom: 'fa-solid fa-wand-magic-sparkles',
            }
            return icons[type] || 'fa-solid fa-box'
        },

        formatPrice(price) {
            if (price === null || price === undefined) return '0 ₽'
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
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
/* 🔥 Стили для группировки по категориям */
.groups-list {
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin-top: 20px;
}

.category-group {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.category-group:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.category-group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: linear-gradient(to right, #f9fafb, #ffffff);
    border-bottom: 1px solid #e5e7eb;
    gap: 16px;
    flex-wrap: wrap;
}

.category-group-left {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}

.category-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25);
}

.category-title-block {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.category-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.category-count {
    font-size: 13px;
    color: #6b7280;
    margin-top: 2px;
}

.category-group-right {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

.rule-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
}

.rule-badge i {
    font-size: 10px;
}

.rule-one {
    background: #dbeafe;
    color: #1e40af;
}

.rule-multiple {
    background: #d1fae5;
    color: #065f46;
}

.rule-all {
    background: #fef3c7;
    color: #92400e;
}

.category-subtotal {
    font-weight: 600;
    font-size: 15px;
    color: #111827;
    white-space: nowrap;
}

/* Сетка товаров внутри группы */
.category-group .products-grid {
    padding: 16px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 14px;
    background: #fafbfc;
}

/* Базовая карточка товара */
.product-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    transition: transform 0.15s, box-shadow 0.15s;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}

.product-card.in-stop-list {
    opacity: 0.55;
}

.product-card.inactive {
    opacity: 0.6;
}

.product-image {
    position: relative;
    aspect-ratio: 1;
    background: #f3f4f6;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 32px;
}

.product-badge {
    position: absolute;
    top: 8px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    color: white;
}

.product-badge.stop {
    right: 8px;
    background: #ef4444;
}

.product-badge.discount {
    left: 8px;
    background: #10b981;
}

.product-info {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}

.product-name {
    font-size: 14px;
    font-weight: 500;
    color: #111827;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 36px;
}

.product-sku {
    font-size: 12px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 4px;
}

.product-price {
    margin-top: auto;
    padding-top: 8px;
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.product-price .old-price {
    font-size: 13px;
    color: #9ca3af;
    text-decoration: line-through;
}

.product-price .current-price {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
}

/* Skeleton */
.category-group.skeleton .category-group-header {
    background: #f9fafb;
}

.skeleton-img {
    width: 100%;
    aspect-ratio: 1;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
}

.skeleton-line {
    height: 14px;
    border-radius: 4px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
}

.w-75 { width: 75%; }
.w-50 { width: 50%; }
.w-40 { width: 40%; }

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.meta-updating {
    margin-left: 8px;
    color: #888;
    font-size: 0.85em;
}

/* Адаптив */
@media (max-width: 768px) {
    .category-group-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .category-group-right {
        width: 100%;
        justify-content: space-between;
    }
    .category-group .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
        padding: 12px;
    }
}
</style>

<style scoped>
/* Skeleton анимация для плавной загрузки */
.skeleton {
    opacity: 0.7;
}

.skeleton-img {
    width: 100%;
    aspect-ratio: 1;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 8px;
}

.skeleton-line {
    height: 14px;
    margin-top: 8px;
    border-radius: 4px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
}

.w-75 { width: 75%; }
.w-50 { width: 50%; }
.w-40 { width: 40%; }

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Индикатор обновления */
.meta-updating {
    margin-left: 8px;
    color: #888;
    font-size: 0.8em;
}
</style>

<style scoped>
/* Стили остаются без изменений, они полностью совместимы с новой разметкой */
/* === Header === */
.collection-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 24px;
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
}

.header-info {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
    min-width: 0;
}

.header-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: linear-gradient(135deg, #e7f1ff 0%, #cfe2ff 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
}

.header-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.header-icon i {
    font-size: 24px;
    color: #0d6efd;
}

.header-text {
    flex: 1;
    min-width: 0;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 4px 0;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.header-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #6c757d;
}

.meta-type {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #0d6efd;
    font-weight: 500;
}

.meta-type i {
    font-size: 11px;
}

.meta-sep {
    color: #adb5bd;
}

.meta-count {
    color: #0d6efd;
    font-weight: 500;
}

/* === Цена в шапке === */
.header-price {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.price-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.current-price {
    font-size: 22px;
    font-weight: 700;
    color: #212529;
}

.old-price {
    font-size: 14px;
    color: #adb5bd;
    text-decoration: line-through;
}

.discount-badge {
    padding: 4px 10px;
    background: #dc3545;
    color: #fff;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}

/* === Body === */
.modal-body {
    padding: 20px 24px;
}

/* === Loading === */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    color: #6c757d;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e7f1ff 0%, #cfe2ff 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.loading-spinner i {
    font-size: 24px;
    color: #0d6efd;
}

.loading-state p {
    margin: 0;
    font-size: 14px;
}

/* === Описание === */
.view-description {
    padding: 14px 16px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 16px;
}

.view-description p {
    margin: 0;
    font-size: 14px;
    color: #495057;
    line-height: 1.5;
}

/* === Статистика === */
.view-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-item i {
    font-size: 18px;
    color: #0d6efd;
    width: 24px;
    text-align: center;
}

.stat-item strong {
    display: block;
    font-size: 16px;
    font-weight: 700;
    color: #212529;
    line-height: 1;
}

.stat-item span {
    font-size: 11px;
    color: #6c757d;
}

/* === Инфо о правиле === */
.rule-info {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    background: #e7f1ff;
    border-radius: 8px;
    margin-bottom: 16px;
}

.rule-info i {
    color: #0d6efd;
    font-size: 16px;
    margin-top: 2px;
    flex-shrink: 0;
}

.rule-info strong {
    display: block;
    font-size: 13px;
    color: #084298;
    margin-bottom: 2px;
}

.rule-info p {
    margin: 0;
    font-size: 12px;
    color: #084298;
}

/* === Пустое состояние === */
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
    font-size: 40px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.empty-state p {
    margin: 0 0 16px 0;
    font-size: 14px;
    color: #6c757d;
}

.btn-edit {
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
    transition: all 0.15s ease;
}

.btn-edit:hover {
    background: #0d6efd;
    color: #fff;
}

/* === Сетка товаров === */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.product-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.2s ease;
    background: #fff;
}

.product-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.1);
    transform: translateY(-2px);
}

.product-card.in-stop-list {
    border-color: #f5c2c7;
    background: linear-gradient(to bottom, #fff5f5 0%, #fff 100%);
}

.product-card.inactive {
    opacity: 0.6;
}

.product-image {
    position: relative;
    width: 100%;
    height: 140px;
    background: #f8f9fa;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    color: #adb5bd;
    font-size: 32px;
}

.product-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 3px;
}

.product-badge.stop {
    background: #dc3545;
    color: #fff;
}

.product-badge.discount {
    background: #fd7e14;
    color: #fff;
    top: auto;
    bottom: 8px;
}

.product-info {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.product-name {
    font-size: 13px;
    font-weight: 600;
    color: #212529;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 34px;
}

.product-sku {
    font-size: 11px;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 4px;
}

.product-sku i {
    font-size: 10px;
}

.product-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.category-tag {
    padding: 2px 6px;
    background: #e7f1ff;
    color: #084298;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 500;
}

.category-more {
    padding: 2px 6px;
    background: #f1f3f5;
    color: #6c757d;
    border-radius: 8px;
    font-size: 10px;
}

.product-price {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
}

.product-price .current-price {
    font-size: 15px;
    font-weight: 700;
    color: #212529;
}

.product-price .old-price {
    font-size: 12px;
    color: #adb5bd;
    text-decoration: line-through;
}

/* === Footer === */
.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.footer-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #6c757d;
}

.footer-info i {
    color: #0d6efd;
    font-size: 14px;
}

.footer-info strong {
    color: #212529;
    font-size: 14px;
}

.btn-edit-collection {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    border: 1px solid #0d6efd;
    border-radius: 8px;
    background: #fff;
    color: #0d6efd;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-edit-collection:hover {
    background: #0d6efd;
    color: #fff;
}

.btn-close-modal {
    padding: 8px 20px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-close-modal:hover {
    background: #f8f9fa;
}

/* === Responsive === */
@media (max-width: 768px) {
    .collection-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .view-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }

    .product-image {
        height: 120px;
    }

    .modal-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .footer-info {
        justify-content: center;
    }
}
</style>
