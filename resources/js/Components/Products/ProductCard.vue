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
            <div v-if="product.in_stop_list" class="badge badge-stop">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div
                v-if="product.old_price && product.old_price > product.price"
                class="badge badge-discount"
            >
                -{{ discountPercent }}%
            </div>
        </div>

        <!-- ✅ Горизонтальный layout для мобильных -->
        <div class="card-body">
            <!-- Изображение -->
            <div class="card-image">
                <img v-if="product.images?.length"
                     v-lazy="product.images[0].url" :alt="product.name" />
                <div v-else class="image-placeholder">
                    <i class="fa-solid fa-image"></i>
                </div>

                <!-- Счётчик картинок -->
                <div v-if="product.images?.length" class="images-count">
                    <i class="fa-solid fa-images"></i>
                    <span>{{ product.images.length }}</span>
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
                        v-for="category in product.categories.slice(0, 1)"
                        :key="category.id"
                        class="category-tag"
                    >
                        {{ category.name }}
                    </span>
                    <span v-if="product.categories.length > 1" class="category-more">
                        +{{ product.categories.length - 1 }}
                    </span>
                </div>
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
            <button
                type="button"
                class="action-btn"
                @click="openImagesModal"
                title="Изображения"
            >
                <i class="fa-solid fa-images"></i>
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

    emits: ['toggle-select', 'edit-product', 'toggle-stop-list', 'edit-images'],

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

        openImagesModal() {
            this.$emit('edit-images', this.product)
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

.product-card.is-selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
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
    top: 8px;
    left: 8px;
    z-index: 3;
}

.card-checkbox input {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #0d6efd;
    border-radius: 4px;
}

.product-card.is-selected .card-checkbox input {
    accent-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.3);
}

/* === БЕЙДЖИ === */
.card-badges {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    z-index: 2;
}

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 6px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.badge i {
    font-size: 9px;
}

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

.badge-discount {
    background: #fd7e14;
    color: #fff;
}

/* === ✅ Горизонтальный layout === */
.card-body {
    display: flex;
    flex-direction: row;
    flex: 1;
    min-height: 0;
}

/* === Изображение === */
.card-image {
    position: relative;
    width: 33.333%;
    min-width: 33.333%;
    background: #f8f9fa;
    overflow: hidden;
    flex-shrink: 0;
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
    font-size: 32px;
}

.images-count {
    position: absolute;
    bottom: 6px;
    right: 6px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 6px;
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
    backdrop-filter: blur(4px);
}

.images-count i {
    font-size: 9px;
}

/* === Контент === */
.card-content {
    flex: 1;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.card-title {
    font-size: 13px;
    font-weight: 600;
    color: #212529;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-sku {
    font-size: 10px;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 4px;
}

.card-sku i {
    font-size: 9px;
}

.card-price {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 2px;
}

.current-price {
    font-size: 16px;
    font-weight: 700;
    color: #212529;
}

.old-price {
    font-size: 12px;
    color: #adb5bd;
    text-decoration: line-through;
}

/* === Категории === */
.card-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 3px;
    margin-top: auto;
}

.category-tag {
    padding: 2px 6px;
    background: #e7f1ff;
    color: #084298;
    border-radius: 8px;
    font-size: 9px;
    font-weight: 500;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.category-more {
    padding: 2px 6px;
    background: #f1f3f5;
    color: #6c757d;
    border-radius: 8px;
    font-size: 9px;
}

/* === Кнопки действий === */
.card-actions {
    display: flex;
    gap: 4px;
    padding: 8px;
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
    font-size: 12px;
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

/* ============================================
   АДАПТИВНАЯ ВЕРСТКА
   ============================================ */

/* Десктоп (768px+) - вертикальный layout */
@media (min-width: 768px) {
    .product-card {
        aspect-ratio: 3 / 4;
    }

    .card-body {
        flex-direction: column;
    }

    .card-image {
        width: 100%;
        min-width: 100%;
        height: 180px;
    }

    .card-content {
        padding: 12px;
        gap: 6px;
    }

    .card-title {
        font-size: 14px;
    }

    .card-sku {
        font-size: 11px;
    }

    .current-price {
        font-size: 18px;
    }

    .old-price {
        font-size: 13px;
    }

    .category-tag,
    .category-more {
        font-size: 10px;
        padding: 2px 8px;
    }

    .card-checkbox {
        top: 10px;
        left: 10px;
    }

    .card-checkbox input {
        width: 20px;
        height: 20px;
    }

    .card-badges {
        top: 10px;
        right: 10px;
    }

    .badge {
        padding: 4px 8px;
        font-size: 11px;
    }

    .badge i {
        font-size: 10px;
    }

    .images-count {
        bottom: 8px;
        right: 8px;
        padding: 4px 8px;
        font-size: 11px;
    }

    .images-count i {
        font-size: 10px;
    }

    .image-placeholder {
        font-size: 40px;
    }

    .card-actions {
        padding: 8px 12px;
    }

    .action-btn {
        font-size: 13px;
    }
}

/* Мобильный (до 767px) - горизонтальный layout */
@media (max-width: 767px) {
    .product-card {
        aspect-ratio: auto;
        min-height: 120px;
    }

    .card-body {
        flex-direction: row;
        height: 100%;
    }

    .card-image {
        width: 33.333%;
        min-width: 33.333%;
        height: auto;
    }

    .card-content {
        padding: 8px;
        gap: 3px;
    }

    .card-title {
        font-size: 12px;
        -webkit-line-clamp: 2;
    }

    .card-sku {
        font-size: 9px;
    }

    .current-price {
        font-size: 14px;
    }

    .old-price {
        font-size: 11px;
    }

    .category-tag,
    .category-more {
        font-size: 8px;
        padding: 1px 5px;
    }

    .card-checkbox {
        top: 6px;
        left: 6px;
    }

    .card-checkbox input {
        width: 16px;
        height: 16px;
    }

    .card-badges {
        top: 6px;
        right: 6px;
        gap: 3px;
    }

    .badge {
        padding: 3px 5px;
        font-size: 9px;
    }

    .badge i {
        font-size: 8px;
    }

    .images-count {
        bottom: 4px;
        right: 4px;
        padding: 2px 5px;
        font-size: 9px;
    }

    .images-count i {
        font-size: 8px;
    }

    .image-placeholder {
        font-size: 24px;
    }

    .card-actions {
        padding: 6px;
        gap: 3px;
    }

    .action-btn {
        padding: 5px;
        font-size: 11px;
    }
}

/* Очень маленький экран (до 380px) */
@media (max-width: 380px) {
    .product-card {
        min-height: 110px;
    }

    .card-content {
        padding: 6px;
        gap: 2px;
    }

    .card-title {
        font-size: 11px;
    }

    .card-sku {
        font-size: 8px;
    }

    .current-price {
        font-size: 13px;
    }

    .old-price {
        font-size: 10px;
    }

    .category-tag,
    .category-more {
        font-size: 7px;
        padding: 1px 4px;
    }

    .card-checkbox {
        top: 4px;
        left: 4px;
    }

    .card-checkbox input {
        width: 14px;
        height: 14px;
    }

    .card-badges {
        top: 4px;
        right: 4px;
    }

    .badge {
        padding: 2px 4px;
        font-size: 8px;
    }

    .images-count {
        bottom: 3px;
        right: 3px;
        padding: 2px 4px;
        font-size: 8px;
    }

    .image-placeholder {
        font-size: 20px;
    }

    .card-actions {
        padding: 5px;
    }

    .action-btn {
        padding: 4px;
        font-size: 10px;
    }
}
</style>
