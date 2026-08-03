<template>
    <div
        class="collection-card"
        :class="{
            'in-stop-list': collection.in_stop_list,
            inactive: !collection.is_active,
            selected: isSelected
        }"
        @click="$emit('select', collection)"
    >
        <!-- Изображение -->
        <div class="card-image">
            <img
                v-if="collection.image_url"
                :src="collection.image_url"
                :alt="collection.name"
            />
            <div v-else class="image-placeholder">
                <i class="fa-solid fa-box-open"></i>
            </div>

            <!-- Бейджи -->
            <div class="card-badges">
                <div v-if="collection.in_stop_list" class="badge badge-stop">
                    <i class="fa-solid fa-ban"></i>
                </div>
                <div
                    v-if="collection.discount_percent > 0"
                    class="badge badge-discount"
                >
                    -{{ collection.discount_percent }}%
                </div>
            </div>

            <!-- Кнопки действий (появляются при hover) -->
            <div class="card-actions" @click.stop>
                <button
                    type="button"
                    class="action-btn"
                    @click="$emit('toggle-stop-list', collection)"
                    :title="collection.in_stop_list ? 'Убрать из стоп-листа' : 'В стоп-лист'"
                >
                    <i class="fa-solid" :class="collection.in_stop_list ? 'fa-circle-check' : 'fa-ban'"></i>
                </button>
                <button
                    type="button"
                    class="action-btn"
                    @click="$emit('edit', collection)"
                    title="Редактировать"
                >
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button
                    type="button"
                    class="action-btn delete"
                    @click="$emit('delete', collection)"
                    title="Удалить"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        <!-- Информация -->
        <div class="card-body">
            <h6 class="card-title">{{ collection.name }}</h6>

            <div class="card-meta">
                <span class="meta-type" :title="collection.type_label">
                    <i :class="getTypeIcon(collection.type)"></i>
                    <span>{{ collection.type_label }}</span>
                </span>
                <span class="meta-count">
                    {{ collection.products_count }} {{ pluralize(collection.products_count, 'товар', 'товара', 'товаров') }}
                </span>
            </div>

            <div v-if="collection.description" class="card-description">
                {{ collection.description }}
            </div>

            <!-- Цена -->
            <div class="card-footer">
                <div class="card-price">
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

                <button
                    type="button"
                    class="btn-view"
                    @click.stop="$emit('view-products', collection)"
                >
                    <i class="fa-solid fa-eye"></i>
                    <span>Просмотр</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CollectionCard',

    props: {
        collection: {
            type: Object,
            required: true
        },
        isSelected: {
            type: Boolean,
            default: false
        }
    },

    emits: ['select', 'edit', 'delete', 'toggle-stop-list', 'view-products'],

    methods: {
        getTypeIcon(type) {
            const icons = {
                manual: 'fa-solid fa-hand-pointer',
                category_all: 'fa-solid fa-folder-open',
                categories_all: 'fa-solid fa-folder-tree',
                workspace_all: 'fa-solid fa-boxes-stacked',
                category_select: 'fa-solid fa-list-check',
                custom: 'fa-solid fa-wand-magic-sparkles'
            }
            return icons[type] || 'fa-solid fa-box'
        },

        formatPrice(price) {
            if (price === null || price === undefined) return '0 ₽'
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
        },

        pluralize(count, one, two, five) {
            let n = Math.abs(count) % 100
            if (n >= 5 && n <= 20) return five
            n %= 10
            if (n === 1) return one
            if (n >= 2 && n <= 4) return two
            return five
        }
    }
}
</script>

<style scoped>
.collection-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    cursor: pointer;
    display: flex;
    flex-direction: column;
}

.collection-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.12);
    transform: translateY(-2px);
}

.collection-card.in-stop-list {
    border-color: #f5c2c7;
    background: linear-gradient(to bottom, #fff5f5 0%, #fff 100%);
}

.collection-card.inactive {
    opacity: 0.6;
}

.collection-card.selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
}

/* === Изображение === */
.card-image {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 10;
    background: #f8f9fa;
    overflow: hidden;
}

.card-image img {
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
    background: linear-gradient(135deg, #e7f1ff 0%, #cfe2ff 100%);
    color: #0d6efd;
    font-size: 48px;
}

/* === Бейджи === */
.card-badges {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
}

.badge-stop {
    background: #dc3545;
    color: #fff;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.badge-stop i {
    font-size: 10px;
}

.badge-discount {
    background: #fd7e14;
    color: #fff;
}

/* === Кнопки действий === */
.card-actions {
    position: absolute;
    top: 8px;
    left: 8px;
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.collection-card:hover .card-actions {
    opacity: 1;
}

.action-btn {
    width: 32px;
    height: 32px;
    border: 1px solid rgba(255, 255, 255, 0.9);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.95);
    color: #495057;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.15s ease;
    backdrop-filter: blur(4px);
}

.action-btn:hover {
    background: #e7f1ff;
    border-color: #0d6efd;
    color: #0d6efd;
}

.action-btn.delete:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

/* === Body === */
.card-body {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

.card-title {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.card-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
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

.meta-count {
    color: #0d6efd;
    font-weight: 500;
}

.card-description {
    font-size: 13px;
    color: #6c757d;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 36px;
}

/* === Footer === */
.card-footer {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 12px;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid #f1f3f5;
}

.card-price {
    display: flex;
    align-items: baseline;
    gap: 6px;
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

.btn-view {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: 1px solid #0d6efd;
    border-radius: 6px;
    background: #fff;
    color: #0d6efd;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-view:hover {
    background: #0d6efd;
    color: #fff;
}

/* === Responsive === */
@media (max-width: 768px) {
    .card-actions {
        opacity: 1;
    }

    .card-title {
        font-size: 14px;
    }

    .card-description {
        font-size: 12px;
        -webkit-line-clamp: 1;
    }

    .current-price {
        font-size: 16px;
    }

    .btn-view {
        padding: 5px 10px;
        font-size: 12px;
    }
}
</style>
