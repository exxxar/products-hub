<template>
    <div class="product-grid-container">
        <div class="product-grid">
            <!-- Кнопка добавления товара (опционально) -->
            <div v-if="showAddButton" class="product-card-wrapper">
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


            <template v-if="displayProducts.length>0">
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
                        @toggle-stop-list="handleToggleStopList"
                    />
                </div>
            </template>

            <div v-if="displayProducts.length === 0 && (store.showOnlyStopList || store.showOnlyActive)" class="empty-search-compact">
                <i class="fa-solid fa-magnifying-glass"></i>
                <div>
                    <strong>Нет товаров в данном блоке</strong>
                </div>

            </div>

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
            <div v-if="displayProducts.length === 0 && !showAddButton" class="empty-grid-state">
                <i class="fa-solid fa-box-open"></i>
                <p>Нет товаров в этой категории</p>
            </div>
        </div>
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
    emits: ['toggle-select', 'edit-product'],
    data() {
        return {
            store: useWorkspaceStore()
        }
    },
    methods: {
        clearSearch() {
            this.store.setSearch('')
            // Если есть ссылка на TopMenu — можно очистить и там
        },
        isSelected(productId) {
            return this.selectedIds.includes(productId)
        },

        async handleToggleStopList(productId) {
            try {
                const result = await this.$store.dispatch('toggleProductStopList', productId)

                if (result.success) {
                    this.$notify?.success({
                        title: result.in_stop_list ? 'Добавлено в стоп-лист' : 'Убрано из стоп-листа',
                        message: result.in_stop_list ? 'Товар скрыт из меню' : 'Товар снова активен'
                    })
                }
            } catch (error) {
                this.$notify?.error('Ошибка при изменении статуса')
            }
        }
    },
    computed: {
        // Используем props если переданы, иначе берём из store
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
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
}

.product-card-wrapper {
    aspect-ratio: 3 / 4;
}

/* === Кнопка добавления === */
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
    margin: 0;
    font-size: 14px;
}

/* === Адаптив === */
@media (max-width: 576px) {
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .add-label {
        font-size: 12px;
    }

    .add-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
}

@media (min-width: 577px) and (max-width: 768px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 769px) and (max-width: 992px) {
    .product-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (min-width: 993px) {
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    }
}

.empty-search-compact {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 10px 10px;
    margin: 0px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-left: 4px solid #0d6efd;
    border-radius: 10px;
    justify-content: center;
    flex-direction: column;
}

.empty-search-compact > i {
    font-size: 24px;
    color: #0d6efd;
}

.empty-search-compact strong {
    display: block;
    font-size: 14px;
    color: #212529;
    margin-bottom: 2px;
    text-align: center;
}

.empty-search-compact p {
    margin: 0;
    font-size: 13px;
    color: #6c757d;
    text-align: center;
}

.empty-search-compact .search-query {
    padding: 1px 6px;
    background: #fff3cd;
    color: #856404;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
}

.empty-search-compact .clear-btn {

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
    transition: all 0.15s ease;
}

.empty-search-compact .clear-btn:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}
</style>
