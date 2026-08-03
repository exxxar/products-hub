<template>
    <div class="collection-grid-container">
        <div class="collection-grid">
            <!-- Кнопка добавления (только на десктопе) -->
            <div v-if="showAddButton" class="collection-card-wrapper add-btn-desktop">
                <button
                    class="add-collection-btn"
                    @click="$emit('create-collection')"
                >
                    <div class="add-icon">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <span class="add-label">Создать коллекцию</span>
                </button>
            </div>

            <template v-if="collections.length > 0">
                <!-- Коллекции -->
                <div
                    v-for="collection in collections"
                    :key="collection.id"
                    class="collection-card-wrapper"
                >
                    <CollectionCard
                        :collection="collection"
                        :is-selected="selectedCollection?.id === collection.id"
                        @select="$emit('select-collection', $event)"
                        @edit="$emit('edit-collection', $event)"
                        @delete="$emit('delete-collection', $event)"
                        @toggle-stop-list="$emit('toggle-stop-list', $event)"
                        @view-products="$emit('view-products', $event)"
                    />
                </div>
            </template>

            <!-- Пустое состояние -->
            <div v-if="collections.length === 0 && !showAddButton" class="empty-grid-state">
                <i class="fa-solid fa-box-open"></i>
                <p>Нет коллекций</p>
                <button
                    v-if="showAddButton"
                    type="button"
                    class="btn-create-first"
                    @click="$emit('create-collection')"
                >
                    <i class="fa-solid fa-plus"></i>
                    <span>Создать первую коллекцию</span>
                </button>
            </div>
        </div>

        <!-- ✅ Плавающая кнопка добавления (только на мобильных) -->
        <button
            v-if="showAddButton"
            type="button"
            class="fab-add-btn"
            @click="$emit('create-collection')"
            title="Создать коллекцию"
        >
            <i class="fa-solid fa-plus"></i>
        </button>
    </div>
</template>

<script>
import CollectionCard from './CollectionCard.vue'

export default {
    name: 'CollectionGrid',

    components: {
        CollectionCard
    },

    props: {
        collections: {
            type: Array,
            default: () => []
        },
        selectedCollection: {
            type: Object,
            default: null
        },
        showAddButton: {
            type: Boolean,
            default: true
        }
    },

    emits: [
        'select-collection',
        'edit-collection',
        'delete-collection',
        'toggle-stop-list',
        'view-products',
        'create-collection'
    ]
}
</script>

<style scoped >
.collection-grid-container {
    width: 100%;
    padding: 0 4px;
    position: relative;
}

/* === Grid === */
.collection-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(3, 1fr);
}

.collection-card-wrapper {
    aspect-ratio: auto;
    min-width: 0;
}

/* === Кнопка добавления (десктоп) === */
.add-btn-desktop {
    display: block;
}

.add-collection-btn {
    width: 100%;
    height: 100%;
    min-height: 280px;
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

.add-collection-btn:hover {
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

.add-collection-btn:hover .add-icon {
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

/* ============================================
   АДАПТИВНАЯ ВЕРСТКА
   ============================================ */

/* Очень большие экраны (1400px+) */
@media (min-width: 1400px) {
    .collection-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
}

/* Большие экраны (1200px - 1399px) */
@media (min-width: 1200px) and (max-width: 1399px) {
    .collection-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }
}

/* Средние экраны (992px - 1199px) */
@media (min-width: 992px) and (max-width: 1199px) {
    .collection-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
}

/* Планшет ландшафтный (768px - 991px) */
@media (min-width: 768px) and (max-width: 991px) {
    .collection-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }
}

/* ✅ Планшет портретный и мобильный (до 767px) */
@media (max-width: 767px) {
    .add-btn-desktop {
        display: none;
    }

    .fab-add-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        bottom: calc(45px + env(safe-area-inset-bottom, 0px));
    }

    .collection-grid-container {
        padding: 0;
    }

    .collection-grid {
        grid-template-columns: repeat(1, 1fr);
        gap: 12px;
    }

    .add-collection-btn {
        min-height: 200px;
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
        padding-bottom: 100px;
    }
}

/* Тёмная тема */
@media (prefers-color-scheme: dark) {
    .add-collection-btn {
        background: #2c3034;
        border-color: #343a40;
        color: #adb5bd;
    }

    .add-collection-btn:hover {
        background: #343a40;
        border-color: #4dabf7;
        color: #4dabf7;
    }

    .add-icon {
        background: #343a40;
        color: #adb5bd;
    }

    .add-collection-btn:hover .add-icon {
        background: #4dabf7;
        color: #212529;
    }

    .fab-add-btn {
        background: linear-gradient(135deg, #4dabf7 0%, #7950f2 100%);
        box-shadow: 0 4px 16px rgba(77, 171, 247, 0.4);
    }
}
</style>
