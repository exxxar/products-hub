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
                        v-if="collection.images && collection.images.length > 0"
                        v-lazy="collection.images[0].url"
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
                        <span class="meta-count">{{ products.length }} товаров</span>
                    </div>
                </div>
            </div>

            <div class="header-price">
                <div class="price-info">
                    <span
                        v-if="collection.old_price && collection.old_price > collection.price"
                        class="old-price"
                    >
                        {{ formatPrice(collection.old_price) }}
                    </span>
                    <span class="current-price">
                        {{ formatPrice(collection.price) }}
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
                <i class="fa-solid fa-box"></i>
                <div>
                    <strong>{{ products.length }}</strong>
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
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>{{ activeProductsCount }}</strong>
                    <span>активных</span>
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

        <!-- Индикатор правила формирования -->
        <div v-if="collection.type !== 'manual'" class="rule-info">
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

            <div v-else-if="products.length === 0" class="empty-state">
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

            <div v-else class="products-grid">
                <div
                    v-for="product in products"
                    :key="product.id"
                    class="product-card"
                    :class="{
                        'in-stop-list': product.in_stop_list,
                        'inactive': !product.is_active
                    }"
                >
                    <div class="product-image">
                        <img
                            v-if="product.images && product.images.length > 0"
                            v-lazy="product.images[0].url"
                            :alt="product.name"
                        />
                        <div v-else class="image-placeholder">
                            <i class="fa-solid fa-image"></i>
                        </div>

                        <div v-if="product.in_stop_list" class="product-badge stop">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                    </div>

                    <div class="product-info">
                        <div class="product-name">{{ product.name }}</div>

                        <div v-if="product.sku" class="product-sku">
                            <i class="fa-solid fa-barcode"></i>
                            {{ product.sku }}
                        </div>

                        <div class="product-categories" v-if="product.categories?.length">
                            <span
                                v-for="cat in product.categories.slice(0, 2)"
                                :key="cat.id"
                                class="category-tag"
                            >
                                {{ cat.name }}
                            </span>
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

        <!-- Кнопка редактирования -->
        <div v-if="canEditProducts && products.length > 0" class="view-footer">
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
        totalPrice() {
            return this.products.reduce((sum, p) => sum + (p.price || 0), 0)
        },

        activeProductsCount() {
            return this.products.filter(p => p.is_active && !p.in_stop_list).length
        },

        stopListProductsCount() {
            return this.products.filter(p => p.in_stop_list).length
        },

        canEditProducts() {
            return this.store.canEditCollectionProducts
        }
    },

    methods: {
        exitView() {
            this.$emit('exit')
        },

        getTypeIcon(type) {
            const icons = {
                manual: 'fa-solid fa-hand-pointer',
                category_all: 'fa-solid fa-folder-open',
                categories_all: 'fa-solid fa-folder-tree',
                workspace_all: 'fa-solid fa-boxes-stacked',
                category_select: 'fa-solid fa-list-check',
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
    gap: 12px;
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
    background: #e7f1ff;
    border-radius: 8px;
    margin-bottom: 20px;
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

/* === Сетка товаров === */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 14px;
}

.product-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.2s ease;
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
}

.product-badge.stop {
    background: #dc3545;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 4px;
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
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }
}
</style>
