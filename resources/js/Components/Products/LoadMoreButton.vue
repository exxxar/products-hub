<template>
    <div v-if="store.hasMoreProducts || store.productsLoadingMore" class="load-more-wrapper">
        <button
            type="button"
            class="load-more-btn"
            @click="loadMore"
            :disabled="store.productsLoadingMore"
        >
            <i v-if="store.productsLoadingMore" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-arrow-down"></i>
            <span>
                {{ store.productsLoadingMore ? 'Загрузка...' : `Загрузить ещё (${remaining} ${pluralize(remaining, 'товар', 'товара', 'товаров')})` }}
            </span>
        </button>

        <!-- Прогресс -->
        <div class="load-progress">
            <div class="progress-bar">
                <div
                    class="progress-fill"
                    :style="{ width: store.loadProgress + '%' }"
                ></div>
            </div>
            <span class="progress-text">
                Загружено {{ store.products.length }} из {{ store.totalProducts }}
            </span>
        </div>
    </div>

    <!-- Все загружены -->
    <div v-else-if="store.products.length > 0 && store.totalProducts > 0" class="all-loaded">
        <i class="fa-solid fa-circle-check"></i>
        <span>Все товары загружены ({{ store.totalProducts }})</span>
    </div>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'LoadMoreButton',

    data() {
        return {
            store: useWorkspaceStore(),
        }
    },

    computed: {
        remaining() {
            return this.store.totalProducts - this.store.products.length
        }
    },

    methods: {
        async loadMore() {
            try {
                const oldLength = this.store.products.length
                await this.store.loadMoreProducts()

                // Прокрутка к новым товарам
                this.$nextTick(() => {
                    const cards = document.querySelectorAll('.product-card')
                    if (cards[oldLength]) {
                        cards[oldLength].scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        })
                    }
                })
            } catch (error) {
                this.$notify?.error('Ошибка при загрузке')
            }
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
.load-more-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 24px 16px;
    margin: 16px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px dashed #dee2e6;
}

.load-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    border: 1px solid #0d6efd;
    border-radius: 10px;
    background: #fff;
    color: #0d6efd;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.load-more-btn:hover:not(:disabled) {
    background: #0d6efd;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.load-more-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.load-more-btn i {
    font-size: 13px;
}

/* === Прогресс === */
.load-progress {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    width: 100%;
    max-width: 400px;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0d6efd 0%, #6f42c1 100%);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
}

/* === Все загружены === */
.all-loaded {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px;
    margin: 16px;
    color: #198754;
    font-size: 13px;
    font-weight: 500;
}

.all-loaded i {
    font-size: 14px;
}

/* === Responsive === */
@media (max-width: 576px) {
    .load-more-wrapper {
        margin: 12px;
        padding: 20px 12px;
    }

    .load-more-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
