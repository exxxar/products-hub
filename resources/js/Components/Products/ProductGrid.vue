<template>
    <div class="product-grid-container">
        <div class="product-grid">
            <!-- Кнопка добавления (только на десктопе) -->
            <div v-if="showAddButton" class="product-card-wrapper add-btn-desktop">
                <button
                    class="add-product-btn"
                    @click="$emit('create-product')"
                >
                    <div class="add-icon">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <span class="add-label">Добавить товар</span>
                </button>
            </div>

            <template v-if="displayProducts.length > 0">
                <!-- Товары -->
                <div
                    v-for="product in displayProducts"
                    :key="product.id"
                    class="product-card-wrapper"
                >
                    <ProductCard
                        :product="product"
                        :is-selected="isSelected(product.id)"
                        @toggle-select="$emit('toggle-select', $event)"
                        @edit-product="$emit('edit-product', $event)"
                        @edit-images="$emit('edit-images', $event)"
                        @toggle-stop-list="$emit('toggle-stop-list', $event)"
                    />
                </div>
            </template>

            <!-- Пустое состояние с фильтром -->
            <div v-if="displayProducts.length === 0 && (store.showOnlyStopList || store.showOnlyActive)" class="empty-search-compact">
                <i class="fa-solid fa-magnifying-glass"></i>
                <div>
                    <strong>Нет товаров в данном блоке</strong>
                </div>
            </div>

            <!-- Пустое состояние с поиском -->
            <div v-if="displayProducts.length === 0 && store.search" class="empty-search-compact">
                <i class="fa-solid fa-magnifying-glass"></i>
                <div>
                    <strong>Ничего не найдено</strong>
                    <p>По запросу <span class="search-query">«{{ store.search }}»</span></p>
                </div>
                <button type="button" class="clear-btn" @click="clearSearch">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Пустое состояние -->
            <div v-if="displayProducts.length === 0 && !showAddButton && !store.search" class="empty-grid-state">
                <i class="fa-solid fa-box-open"></i>
                <p>Нет товаров в этой категории</p>
                <button
                    v-if="showAddButton"
                    type="button"
                    class="btn-create-first"
                    @click="$emit('create-product')"
                >
                    <i class="fa-solid fa-plus"></i>
                    <span>Создать первый товар</span>
                </button>
            </div>
        </div>

        <!-- ✅ Плавающая кнопка добавления (только на мобильных) -->
        <button
            v-if="showAddButton"
            type="button"
            class="fab-add-btn"
            @click="$emit('create-product')"
            title="Добавить товар"
        >
            <i class="fa-solid fa-plus"></i>
        </button>
    </div>
</template>

<script>
import ProductCard from './ProductCard.vue'
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'ProductGrid',

    components: {
        ProductCard
    },

    props: {
        products: {
            type: Array,
            default: null
        },
        selectedIds: {
            type: Array,
            default: null
        },
        showAddButton: {
            type: Boolean,
            default: true
        }
    },

    emits: ['toggle-select', 'edit-product', 'edit-images', 'create-product','toggle-stop-list'],

    data() {
        return {
            store: useWorkspaceStore()
        }
    },

    methods: {
        clearSearch() {
            this.store.setSearch('')
        },

        isSelected(productId) {
            return this.selectedIds.includes(productId)
        },


    },

    computed: {
        displayProducts() {
            return this.products !== null ? this.products : this.store.filteredProducts
        },

        displaySelectedIds() {
            return this.selectedIds !== null ? this.selectedIds : this.store.selectedIds
        }
    }
}
</script>

<style scoped>
.product-grid-container {
    width: 100%;
    padding: 0 4px;
    position: relative;
}

/* === Grid === */
.product-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(4, 1fr);
}

.product-card-wrapper {
    aspect-ratio: auto;
    min-width: 0;
}

/* === Кнопка добавления (десктоп) === */
.add-btn-desktop {
    display: block;
}

.add-product-btn {
    width: 100%;
    height: 100%;
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    background: #f8f9fa;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 20px;
}

.add-product-btn:hover {
    border-color: #0d6efd;
    background: #e7f1ff;
    color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
}

.add-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.2s ease;
}

.add-product-btn:hover .add-icon {
    background: #0d6efd;
    color: #fff;
    transform: rotate(90deg);
}

.add-label {
    font-size: 14px;
    font-weight: 500;
}

/* ✅ Плавающая кнопка (мобильная) */
.fab-add-btn {
    display: none;
    position: fixed;
    bottom: 24px;
    left: 24px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
    color: #fff;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(13, 110, 253, 0.4);
    z-index: 1000;
    transition: all 0.2s ease;
    font-size: 20px;
}

.fab-add-btn:hover {
    transform: scale(1.1) rotate(90deg);
    box-shadow: 0 6px 24px rgba(13, 110, 253, 0.5);
}

.fab-add-btn:active {
    transform: scale(0.95);
}

/* === Пустое состояние === */
.empty-grid-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-grid-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-grid-state p {
    margin: 0 0 16px 0;
    font-size: 14px;
}

.btn-create-first {
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

.btn-create-first:hover {
    background: #0b5ed7;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

/* === Поиск пустое состояние === */
.empty-search-compact {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px;
    margin: 0;
    background: #fff;
    border: 1px solid #dee2e6;
    border-left: 4px solid #0d6efd;
    border-radius: 10px;
    justify-content: center;
    flex-direction: column;
}

.empty-search-compact > i {
    font-size: 32px;
    color: #0d6efd;
    margin-bottom: 8px;
}

.empty-search-compact strong {
    display: block;
    font-size: 16px;
    color: #212529;
    margin-bottom: 4px;
    text-align: center;
}

.empty-search-compact p {
    margin: 0 0 12px 0;
    font-size: 13px;
    color: #6c757d;
    text-align: center;
}

.empty-search-compact .search-query {
    padding: 2px 8px;
    background: #fff3cd;
    color: #856404;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
}

.empty-search-compact .clear-btn {
    width: 36px;
    height: 36px;
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

.empty-search-compact .clear-btn:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

/* ============================================
   АДАПТИВНАЯ ВЕРСТКА
   ============================================ */

/* Очень большие экраны (1400px+) */
@media (min-width: 1400px) {
    .product-grid {
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
    }

    .product-grid-container {
        padding: 0 8px;
    }
}

/* Большие экраны (1200px - 1399px) */
@media (min-width: 1200px) and (max-width: 1399px) {
    .product-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }
}

/* Средние экраны (992px - 1199px) */
@media (min-width: 992px) and (max-width: 1199px) {
    .product-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
}

/* Планшет ландшафтный (768px - 991px) */
@media (min-width: 768px) and (max-width: 991px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }

    .product-grid-container {
        padding: 0 6px;
    }

    .add-product-btn {
        padding: 16px;
        gap: 10px;
    }

    .add-icon {
        width: 44px;
        height: 44px;
        font-size: 18px;
    }

    .add-label {
        font-size: 13px;
    }
}

/* ✅ Планшет портретный и мобильный (до 767px) */
@media (max-width: 767px) {
    /* Скрываем десктопную кнопку */
    .add-btn-desktop {
        display: none;
    }

    /* Показываем плавающую кнопку */
    .fab-add-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        bottom: calc(45px + env(safe-area-inset-bottom, 0px));
    }

    /* Убираем отступы у контейнера */
    .product-grid-container {
        padding: 0;
    }

    /* Сетка на всю ширину */
    .product-grid {
        grid-template-columns: repeat(1, 1fr);
        gap: 8px;
    }

    .add-product-btn {
        padding: 14px;
        gap: 8px;
        border-radius: 10px;
    }

    .add-icon {
        width: 36px;
        height: 36px;
        font-size: 16px;
    }

    .add-label {
        font-size: 12px;
    }

    .empty-grid-state {
        padding: 32px 12px;
        padding-bottom: 100px; /* Отступ для FAB */
    }

    .empty-grid-state i {
        font-size: 36px;
        margin-bottom: 12px;
    }

    .empty-grid-state p {
        font-size: 13px;
    }

    .btn-create-first {
        padding: 8px 16px;
        font-size: 13px;
    }

    .empty-search-compact {
        padding: 14px;
        gap: 8px;
        margin: 0 8px;
    }

    .empty-search-compact > i {
        font-size: 24px;
        margin-bottom: 6px;
    }

    .empty-search-compact strong {
        font-size: 13px;
    }

    .empty-search-compact p {
        font-size: 12px;
    }

    .empty-search-compact .clear-btn {
        width: 32px;
        height: 32px;
    }
}

/* ✅ Очень маленький экран (до 380px) */
@media (max-width: 380px) {
    .product-grid {
        grid-template-columns: repeat(1, 1fr);
        gap: 6px;
    }

    .fab-add-btn {
        width: 52px;
        height: 52px;
        font-size: 18px;
        left: 16px;
        bottom: calc(45px + env(safe-area-inset-bottom, 0px));
    }

    .empty-grid-state {
        padding: 24px 8px;
        padding-bottom: 90px;
    }

    .empty-grid-state i {
        font-size: 32px;
    }

    .empty-grid-state p {
        font-size: 12px;
    }

    .btn-create-first {
        padding: 7px 14px;
        font-size: 12px;
    }

    .empty-search-compact {
        padding: 12px;
        margin: 0 6px;
    }

    .empty-search-compact > i {
        font-size: 20px;
    }

    .empty-search-compact strong {
        font-size: 12px;
    }

    .empty-search-compact p {
        font-size: 11px;
    }
}

/* Ландшафтная ориентация на мобильном */
@media (max-height: 500px) and (orientation: landscape) and (max-width: 900px) {
    .product-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }

    .fab-add-btn {
        width: 48px;
        height: 48px;
        font-size: 16px;
    }

    .add-product-btn {
        padding: 12px;
    }

    .add-icon {
        width: 36px;
        height: 36px;
        font-size: 16px;
    }

    .add-label {
        font-size: 12px;
    }
}

/* Тёмная тема */
@media (prefers-color-scheme: dark) {
    .add-product-btn {
        background: #2c3034;
        border-color: #343a40;
        color: #adb5bd;
    }

    .add-product-btn:hover {
        background: #343a40;
        border-color: #4dabf7;
        color: #4dabf7;
    }

    .add-icon {
        background: #343a40;
        color: #adb5bd;
    }

    .add-product-btn:hover .add-icon {
        background: #4dabf7;
        color: #212529;
    }

    .empty-search-compact {
        background: #2c3034;
        border-color: #343a40;
        border-left-color: #4dabf7;
    }

    .empty-search-compact > i {
        color: #4dabf7;
    }

    .empty-search-compact strong {
        color: #e9ecef;
    }

    .empty-search-compact .clear-btn {
        background: #2c3034;
        border-color: #343a40;
        color: #adb5bd;
    }

    .fab-add-btn {
        background: linear-gradient(135deg, #4dabf7 0%, #7950f2 100%);
        box-shadow: 0 4px 16px rgba(77, 171, 247, 0.4);
    }
}
</style>
