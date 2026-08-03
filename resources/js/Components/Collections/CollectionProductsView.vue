<template>
    <div class="collection-products-view">
        <!-- Заголовок с навигацией -->
        <div class="view-header">
            <button type="button" class="btn-back" @click="exitView">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Назад к списку</span>
            </button>

            <div class="header-info">
                <div class="header-icon">
                    <img
                        v-if="collection.image_url"
                        v-lazy="collection.image_url"
                        :alt="collection.name"
                    />
                    <i v-else class="fa-solid fa-box-open"></i>
                </div>
                <div class="header-text">
                    <h5>{{ collection.name }}</h5>
                    <div class="header-meta">
                        <span class="meta-type">
                            <i :class="getTypeIcon(collection.type)"></i>
                            {{ collection.type_label }}
                        </span>
                        <span class="meta-sep">•</span>
                        <span class="meta-count">
                            {{ groupedProducts.length }} категорий,
                            {{ flatProducts.length }} товаров
                        </span>
                    </div>
                </div>
            </div>

            <div class="header-price">
                <div class="price-info">
                    <span
                        v-if="collection.discount_percent > 0 && basePrice > 0"
                        class="old-price"
                    >
                        {{ formatPrice(basePrice) }}
                    </span>
                    <span class="current-price">
                        {{ formatPrice(finalPrice) }}
                    </span>
                </div>
                <div v-if="collection.discount_percent > 0" class="discount-badge">
                    -{{ collection.discount_percent }}%
                </div>
            </div>
        </div>

        <!-- Описание коллекции -->
        <div v-if="collection.description" class="view-description">
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
                    <span>сумма</span>
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

        <!-- Инфо о правиле (для custom коллекций) -->
        <div v-if="collection.type === 'custom'" class="rule-info">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                <strong>Набор по категориям</strong>
                <p>Клиент выбирает товары согласно правилам каждой категории</p>
            </div>
        </div>

        <!-- Индикатор правила для других типов -->
        <div v-else-if="collection.type !== 'manual'" class="rule-info rule-auto">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                <strong>Автоматическое формирование</strong>
                <p>Товары добавляются по правилу: {{ collection.type_label }}</p>
            </div>
        </div>

        <!-- Список товаров -->
        <div class="products-list">
            <div v-if="loading" class="loading-state">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <p>Загрузка товаров...</p>
            </div>

            <div v-else-if="flatProducts.length === 0" class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <p>В коллекции нет товаров</p>
                <button
                    v-if="canEditProducts"
                    type="button"
                    class="btn-edit"
                    @click="$emit('edit-collection', collection)"
                >
                    <i class="fa-solid fa-pen"></i>
                    Добавить товары
                </button>
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
                                    {{ group.products.length }}
                                    {{ pluralize(group.products.length, ['товар', 'товара', 'товаров']) }}
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

        <!-- Кнопка редактирования -->
        <div v-if="canEditProducts && flatProducts.length > 0" class="view-footer">
            <button
                type="button"
                class="btn-edit-collection"
                @click="$emit('edit-collection', collection)"
            >
                <i class="fa-solid fa-pen"></i>
                Редактировать товары коллекции
            </button>
        </div>
    </div>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'CollectionProductsView',

    emits: ['exit', 'edit-collection'],

    props: {
        collection: {
            type: Object,
            required: true
        },
        products: {
            type: Array,
            default: () => []
        },
        loading: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            store: useWorkspaceStore(),
        }
    },

    computed: {
        /**
         * Группы товаров (категории + их товары)
         * Для custom - из collection_categories
         * Для других - автоматическая группировка по родной категории товара
         */
        groupedProducts() {
            // Custom коллекции: берём готовые группы
            if (this.collection.type === 'custom' && this.collection.collection_categories?.length) {
                return this.collection.collection_categories.map(c => ({
                    category_id: c.category_id,
                    category_name: c.category_name,
                    selection_rule: c.selection_rule,
                    rule_label: c.rule_label || this.getRuleLabel(c.selection_rule),
                    products: c.products || [],
                }))
            }

            // Плоский список - группируем по родной категории
            return this.groupByNativeCategory(this.products)
        },

        /**
         * Плоский список всех товаров (для статистики и общего подсчёта)
         */
        flatProducts() {
            return this.groupedProducts.flatMap(g => g.products || [])
        },

        /**
         * Сумма цен всех товаров
         */
        totalPrice() {
            return this.groupedProducts.reduce((sum, g) => sum + this.getGroupSubtotal(g), 0)
        },

        /**
         * Базовая цена (до применения скидки коллекции)
         * - fixed: фиксированная цена
         * - sum: сумма всех товаров
         */
        basePrice() {
            if (this.collection.pricing_type === 'fixed') {
                return parseFloat(this.collection.fixed_price) || 0
            }
            return this.totalPrice
        },

        /**
         * Итоговая цена со скидкой
         */
        finalPrice() {
            const discount = parseFloat(this.collection.discount_percent) || 0
            if (discount > 0 && this.basePrice > 0) {
                return Math.round(this.basePrice * (1 - discount / 100))
            }
            return this.basePrice
        },

        stopListProductsCount() {
            return this.flatProducts.filter(p => p.in_stop_list).length
        },

        canEditProducts() {
            // Для custom, manual, category_select - можно редактировать
            if (['manual', 'category_select', 'custom'].includes(this.collection.type)) {
                return this.store.canEditCollectionProducts ?? true
            }
            return false
        }
    },

    methods: {
        exitView() {
            this.$emit('exit')
        },

        /**
         * Группирует плоский список товаров по их родной категории
         */
        groupByNativeCategory(products) {
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
        }
    }
}
</script>

<style scoped>
.collection-products-view {
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

/* === Заголовок === */
.view-header {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 20px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.btn-back:hover {
    background: #f8f9fa;
    color: #0d6efd;
    border-color: #0d6efd;
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
    border-radius: 10px;
    background: linear-gradient(135deg, #e7f1ff 0%, #cfe2ff 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
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

.header-text h5 {
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
}

.meta-type i {
    color: #0d6efd;
    font-size: 11px;
}

.meta-sep {
    color: #cbd5e1;
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
    margin-bottom: 20px;
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
    background: #fff7e6;
    border-left: 3px solid #f59e0b;
    border-radius: 8px;
    margin-bottom: 20px;
}

.rule-info.rule-auto {
    background: #e7f1ff;
    border-left-color: #0d6efd;
}

.rule-info i {
    color: #f59e0b;
    font-size: 16px;
    margin-top: 2px;
    flex-shrink: 0;
}

.rule-info.rule-auto i {
    color: #0d6efd;
}

.rule-info strong {
    display: block;
    font-size: 13px;
    color: #92400e;
    margin-bottom: 2px;
}

.rule-info.rule-auto strong {
    color: #084298;
}

.rule-info p {
    margin: 0;
    font-size: 12px;
    color: #92400e;
}

.rule-info.rule-auto p {
    color: #084298;
}

/* === Список товаров === */
.products-list {
    margin-bottom: 20px;
}

.loading-state,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    color: #adb5bd;
}

.loading-state i,
.empty-state i {
    font-size: 40px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.loading-state p,
.empty-state p {
    margin: 0 0 12px 0;
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
}

.btn-edit:hover {
    background: #0d6efd;
    color: #fff;
}

/* === Группы категорий === */
.groups-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
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
    padding: 14px 18px;
    background: linear-gradient(to right, #f9fafb, #ffffff);
    border-bottom: 1px solid #e5e7eb;
    gap: 14px;
    flex-wrap: wrap;
}

.category-group-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.category-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(245, 158, 11, 0.25);
}

.category-title-block {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.category-title {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.category-count {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

.category-group-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.rule-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
}

.rule-badge i {
    font-size: 9px;
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
    font-size: 14px;
    color: #111827;
    white-space: nowrap;
}

/* === Сетка товаров === */
.products-grid {
    padding: 14px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
    background: #fafbfc;
}

.product-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.2s ease;
    background: #fff;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.1);
    transform: translateY(-2px);
}

.product-card.in-stop-list {
    border-color: #f5c2c7;
    background: linear-gradient(to bottom, #fff5f5 0%, #fff 100%);
    opacity: 0.7;
}

.product-card.inactive {
    opacity: 0.6;
}

.product-image {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
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
    padding: 3px 7px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
    color: white;
}

.product-badge.stop {
    right: 8px;
    background: #dc3545;
    display: flex;
    align-items: center;
    gap: 3px;
}

.product-badge.discount {
    left: 8px;
    background: #10b981;
}

.product-info {
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
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

.product-price {
    display: flex;
    align-items: baseline;
    gap: 6px;
    margin-top: auto;
    padding-top: 6px;
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
.view-footer {
    padding-top: 16px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: center;
}

.btn-edit-collection {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
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

/* === Responsive === */
@media (max-width: 768px) {
    .view-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .view-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    }

    .category-group-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .category-group-right {
        width: 100%;
        justify-content: space-between;
    }
}
</style>
