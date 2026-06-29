<template>
    <div
        class="product-card"
        :class="{
            'is-selected': isSelected,
            'is-in-stop-list': product.in_stop_list,
            'is-inactive': !product.is_active
        }"
        @click="handleCardClick"
    >
        <!-- Чекбокс выбора -->
        <div class="card-checkbox" @click.stop>
            <input
                type="checkbox"
                :checked="isSelected"
                @change="handleCheckboxChange"
            />
        </div>

        <!-- БЕЙДЖИ -->
        <div class="card-badges">
            <!-- Бейдж "В стоп-листе" -->
            <div v-if="product.in_stop_list" class="badge badge-stop">
                <i class="fa-solid fa-ban"></i>
                <span>В стоп-листе</span>
            </div>

            <!-- Бейдж "Не активен" -->
            <div v-else-if="!product.is_active" class="badge badge-inactive">
                <i class="fa-solid fa-eye-slash"></i>
                <span>Не активен</span>
            </div>

            <!-- Бейдж скидки -->
            <div
                v-if="product.old_price && product.old_price > product.price"
                class="badge badge-discount"
            >
                <i class="fa-solid fa-tag"></i>
                <span>-{{ discountPercent }}%</span>
            </div>
        </div>

        <!-- Изображение -->
        <div class="card-image">
            <img
                v-if="product.images && product.images.length > 0"
                v-lazy="product.images[0].url"
                :alt="product.name"
            />
            <div v-else class="image-placeholder">
                <i class="fa-solid fa-image"></i>
            </div>
        </div>

        <!-- Контент -->
        <div class="card-content">
            <div class="card-title">{{ product.name }}</div>

            <div v-if="product.sku" class="card-sku">
                <i class="fa-solid fa-barcode"></i>
                {{ product.sku }}
            </div>

            <div class="card-price">
                <span
                    v-if="product.old_price && product.old_price > product.price"
                    class="old-price"
                >
                    {{ formatPrice(product.old_price) }}
                </span>
                <span class="current-price">{{ formatPrice(product.price) }}</span>
            </div>

            <!-- Категории -->
            <div v-if="product.categories && product.categories.length > 0" class="card-categories">
                <span
                    v-for="category in product.categories.slice(0, 2)"
                    :key="category.id"
                    class="category-tag"
                >
                    {{ category.name }}
                </span>
                <span v-if="product.categories.length > 2" class="category-more">
                    +{{ product.categories.length - 2 }}
                </span>
            </div>
        </div>

        <!-- Кнопки действий -->
        <div class="card-actions" @click.stop>
            <button
                type="button"
                class="action-btn"
                @click="$emit('toggle-stop-list', product.id)"
                :title="product.in_stop_list ? 'Убрать из стоп-листа' : 'В стоп-лист'"
                :class="{ active: product.in_stop_list }"
            >
                <i class="fa-solid" :class="product.in_stop_list ? 'fa-circle-check' : 'fa-ban'"></i>
            </button>
            <button
                type="button"
                class="action-btn"
                @click="$emit('edit-product', product)"
                title="Редактировать"
            >
                <i class="fa-solid fa-pen"></i>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProductCard',

    props: {
        product: {
            type: Object,
            required: true
        },
        isSelected: {
            type: Boolean,
            default: false
        }
    },

    emits: ['toggle-select', 'edit-product', 'toggle-stop-list'],

    computed: {
        discountPercent() {
            if (!this.product.old_price || !this.product.price) return 0
            const discount = ((this.product.old_price - this.product.price) / this.product.old_price) * 100
            return Math.round(discount)
        }
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
        },

        handleCardClick() {
            this.$emit('toggle-select', this.product.id)
        },

        handleCheckboxChange(event) {
            this.$emit('toggle-select', this.product.id)
        }
    }
}
</script>

<style scoped>
.product-card {
    position: relative;
    background: #fff;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
    transform: translateY(-2px);
}

/* ✅ ЯВНОЕ ВЫДЕЛЕНИЕ */
.product-card.is-selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2), 0 4px 12px rgba(13, 110, 253, 0.15);
    background: linear-gradient(to bottom, #f8f9ff 0%, #fff 100%);
}

.product-card.is-selected:hover {
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.3), 0 6px 16px rgba(13, 110, 253, 0.2);
}

/* Стили для товаров в стоп-листе */
.product-card.is-in-stop-list {
    border-color: #f5c2c7;
    background: linear-gradient(to bottom, #fff5f5 0%, #fff 100%);
}

.product-card.is-in-stop-list:hover {
    border-color: #dc3545;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.15);
}

.product-card.is-in-stop-list.is-selected {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2), 0 4px 12px rgba(220, 53, 69, 0.15);
}

.product-card.is-inactive {
    opacity: 0.65;
}

/* === Чекбокс === */
.card-checkbox {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 3;
}

.card-checkbox input {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #0d6efd;
    border-radius: 4px;
}

/* ✅ Подсветка чекбокса при выделении */
.product-card.is-selected .card-checkbox input {
    accent-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.3);
}

/* === БЕЙДЖИ === */
.card-badges {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    z-index: 2;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.badge i {
    font-size: 10px;
}

/* Красный бейдж "В стоп-листе" */
.badge-stop {
    background: #dc3545;
    color: #fff;
    animation: pulse-stop 2s ease-in-out infinite;
}

@keyframes pulse-stop {
    0%, 100% {
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
    }
    50% {
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.6);
    }
}

/* Серый бейдж "Не активен" */
.badge-inactive {
    background: #6c757d;
    color: #fff;
}

/* Бейдж скидки */
.badge-discount {
    background: #fd7e14;
    color: #fff;
}

/* === Изображение === */
.card-image {
    position: relative;
    width: 100%;
    height: 180px;
    background: #f8f9fa;
    overflow: hidden;
}

.card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .card-image img {
    transform: scale(1.05);
}

.image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    color: #adb5bd;
    font-size: 40px;
}

/* === Контент === */
.card-content {
    padding: 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.card-title {
    font-size: 14px;
    font-weight: 600;
    color: #212529;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-sku {
    font-size: 11px;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 4px;
}

.card-price {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
}

.current-price {
    font-size: 18px;
    font-weight: 700;
    color: #212529;
}

.old-price {
    font-size: 13px;
    color: #adb5bd;
    text-decoration: line-through;
}

/* === Категории === */
.card-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 6px;
}

.category-tag {
    padding: 2px 8px;
    background: #e7f1ff;
    color: #084298;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 500;
}

.category-more {
    padding: 2px 8px;
    background: #f1f3f5;
    color: #6c757d;
    border-radius: 10px;
    font-size: 10px;
}

/* === Кнопки действий === */
.card-actions {
    display: flex;
    gap: 4px;
    padding: 8px 12px;
    border-top: 1px solid #f1f3f5;
    background: #fafbfc;
}

.action-btn {
    flex: 1;
    padding: 6px;
    border: 1px solid transparent;
    border-radius: 6px;
    background: transparent;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover {
    background: #e7f1ff;
    color: #0d6efd;
}

.action-btn.active {
    background: #f8d7da;
    color: #dc3545;
}

.action-btn.active:hover {
    background: #dc3545;
    color: #fff;
}
</style>
