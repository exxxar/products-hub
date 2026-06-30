<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-box-open"></i>
                        {{ isEditing ? 'Редактировать коллекцию' : 'Создать коллекцию' }}
                    </h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">
                    <!-- Основная информация -->
                    <div class="form-section">
                        <h6>Основная информация</h6>

                        <div class="form-row">
                            <div class="form-group flex-2">
                                <label>Название коллекции *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="form-input"
                                    placeholder="Например: Набор для новичка"
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Краткое описание</label>
                            <input
                                v-model="form.short_description"
                                type="text"
                                class="form-input"
                                placeholder="Краткое описание для карточки"
                                maxlength="500"
                            />
                        </div>

                        <div class="form-group">
                            <label>Полное описание</label>
                            <textarea
                                v-model="form.description"
                                class="form-input"
                                rows="3"
                                placeholder="Подробное описание коллекции"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Тип коллекции -->
                    <div class="form-section">
                        <h6>Тип коллекции</h6>

                        <div class="type-selector">
                            <div
                                v-for="type in collectionTypes"
                                :key="type.value"
                                class="type-card"
                                :class="{ active: form.type === type.value }"
                                @click="selectType(type.value)"
                            >
                                <i :class="type.icon"></i>
                                <div class="type-info">
                                    <strong>{{ type.label }}</strong>
                                    <small>{{ type.description }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Настройка правила -->
                    <div class="form-section" v-if="showRuleConfig">
                        <h6>Правило формирования</h6>

                        <!-- Все товары категории -->
                        <div v-if="form.type === 'category_all'" class="rule-config">
                            <div class="form-group">
                                <label>Категория</label>
                                <select v-model="form.rule_config.category_id" class="form-input">
                                    <option :value="null">Выберите категорию</option>
                                    <option
                                        v-for="cat in store.categories"
                                        :key="cat.id"
                                        :value="cat.id"
                                    >
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Все товары нескольких категорий -->
                        <div v-else-if="form.type === 'categories_all'" class="rule-config">
                            <div class="form-group">
                                <label>Категории</label>
                                <div class="categories-checkboxes">
                                    <label
                                        v-for="cat in store.categories"
                                        :key="cat.id"
                                        class="checkbox-label"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="cat.id"
                                            v-model="form.rule_config.category_ids"
                                        />
                                        <span>{{ cat.name }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Все товары workspace -->
                        <div v-else-if="form.type === 'workspace_all'" class="rule-config">
                            <div class="info-box">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Коллекция будет включать все активные товары workspace</span>
                            </div>
                        </div>

                        <!-- Выбор из категории -->
                        <div v-else-if="form.type === 'category_select'" class="rule-config">
                            <div class="form-group">
                                <label>Категория</label>
                                <select v-model="form.rule_config.category_id" class="form-input">
                                    <option :value="null">Выберите категорию</option>
                                    <option
                                        v-for="cat in store.categories"
                                        :key="cat.id"
                                        :value="cat.id"
                                    >
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Выберите товары</label>
                                <div class="products-selector">
                                    <div
                                        v-for="product in availableProducts"
                                        :key="product.id"
                                        class="product-checkbox"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="product.id"
                                            v-model="form.product_ids"
                                        />
                                        <span>{{ product.name }}</span>
                                        <span class="product-price">{{ formatPrice(product.price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ручной выбор -->
                        <div v-else-if="form.type === 'manual'" class="rule-config">
                            <div class="form-group">
                                <label>Выберите товары</label>
                                <div class="products-selector">
                                    <div
                                        v-for="product in store.products"
                                        :key="product.id"
                                        class="product-checkbox"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="product.id"
                                            v-model="form.product_ids"
                                        />
                                        <span>{{ product.name }}</span>
                                        <span class="product-price">{{ formatPrice(product.price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ценообразование -->
                    <div class="form-section">
                        <h6>Ценообразование</h6>

                        <div class="pricing-selector">
                            <label class="radio-card" :class="{ active: form.pricing_type === 'sum' }">
                                <input type="radio" v-model="form.pricing_type" value="sum" />
                                <i class="fa-solid fa-calculator"></i>
                                <div>
                                    <strong>Сумма товаров</strong>
                                    <small>Цена = сумма цен всех товаров</small>
                                </div>
                            </label>

                            <label class="radio-card" :class="{ active: form.pricing_type === 'fixed' }">
                                <input type="radio" v-model="form.pricing_type" value="fixed" />
                                <i class="fa-solid fa-tag"></i>
                                <div>
                                    <strong>Фиксированная цена</strong>
                                    <small>Указать единую цену за коллекцию</small>
                                </div>
                            </label>
                        </div>

                        <div v-if="form.pricing_type === 'fixed'" class="fixed-price-inputs">
                            <div class="form-group">
                                <label>Цена коллекции</label>
                                <input
                                    v-model.number="form.fixed_price"
                                    type="number"
                                    class="form-input"
                                    placeholder="0"
                                    min="0"
                                    step="0.01"
                                />
                            </div>

                            <div class="form-group">
                                <label>Старая цена (для скидки)</label>
                                <input
                                    v-model.number="form.fixed_old_price"
                                    type="number"
                                    class="form-input"
                                    placeholder="0"
                                    min="0"
                                    step="0.01"
                                />
                            </div>
                        </div>

                        <!-- Предпросмотр цены -->
                        <div class="price-preview">
                            <div class="price-info">
                                <span>Итоговая цена:</span>
                                <strong>{{ formatPrice(calculatedPrice) }}</strong>
                            </div>
                            <div v-if="calculatedOldPrice && calculatedOldPrice > calculatedPrice" class="discount-info">
                                <span class="old-price">{{ formatPrice(calculatedOldPrice) }}</span>
                                <span class="discount-badge">-{{ discountPercent }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Картинка -->
                    <div class="form-section">
                        <h6>Изображение коллекции</h6>

                        <div v-if="form.images && form.images.length > 0" class="image-preview">
                            <img :src="form.images[0].url" alt="Collection" />
                            <button type="button" class="btn-remove-image" @click="removeImage">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                        <label v-else class="upload-image">
                            <input
                                type="file"
                                accept="image/*"
                                @change="uploadImage"
                                style="display: none"
                            />
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Загрузить изображение</span>
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" @click="hide">Отмена</button>
                    <button
                        type="button"
                        class="btn-save"
                        @click="save"
                        :disabled="isSaving || !isValid"
                    >
                        <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        {{ isSaving ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'
import { useWorkspaceStore } from '@/store/workspace.js'
import axios from 'axios'

export default {
    name: 'CollectionFormModal',

    emits: ['saved'],

    data() {
        return {
            store: useWorkspaceStore(),
            modal: null,
            isEditing: false,
            editingId: null,
            isSaving: false,
            form: this.getEmptyForm(),
            collectionTypes: [
                {
                    value: 'manual',
                    label: 'Ручной выбор',
                    icon: 'fa-solid fa-hand-pointer',
                    description: 'Выбрать товары вручную'
                },
                {
                    value: 'category_all',
                    label: 'Все товары категории',
                    icon: 'fa-solid fa-folder-open',
                    description: 'Все товары из одной категории'
                },
                {
                    value: 'categories_all',
                    label: 'Несколько категорий',
                    icon: 'fa-solid fa-folder-tree',
                    description: 'Все товары из нескольких категорий'
                },
                {
                    value: 'workspace_all',
                    label: 'Все товары',
                    icon: 'fa-solid fa-boxes-stacked',
                    description: 'Все товары workspace'
                },
                {
                    value: 'category_select',
                    label: 'Выбор из категории',
                    icon: 'fa-solid fa-list-check',
                    description: 'Выбрать несколько товаров из категории'
                },
            ],
        }
    },

    computed: {
        showRuleConfig() {
            return true
        },

        availableProducts() {
            if (this.form.type === 'category_select' && this.form.rule_config.category_id) {
                return this.store.products.filter(p =>
                    p.categories?.some(c => c.id === this.form.rule_config.category_id)
                )
            }
            return this.store.products
        },

        calculatedPrice() {
            if (this.form.pricing_type === 'fixed') {
                return this.form.fixed_price || 0
            }

            const products = this.getFormProducts()
            return products.reduce((sum, p) => sum + (p.price || 0), 0)
        },

        calculatedOldPrice() {
            if (this.form.pricing_type === 'fixed') {
                return this.form.fixed_old_price || null
            }

            const products = this.getFormProducts()
            const sum = products.reduce((sum, p) => sum + (p.old_price || p.price || 0), 0)
            return sum > 0 ? sum : null
        },

        discountPercent() {
            if (!this.calculatedOldPrice || !this.calculatedPrice) return 0
            if (this.calculatedOldPrice <= this.calculatedPrice) return 0
            return Math.round(((this.calculatedOldPrice - this.calculatedPrice) / this.calculatedOldPrice) * 100)
        },

        isValid() {
            if (!this.form.name) return false
            if (this.form.pricing_type === 'fixed' && !this.form.fixed_price) return false
            return true
        },
    },

    methods: {
        getEmptyForm() {
            return {
                name: '',
                description: '',
                short_description: '',
                type: 'manual',
                rule_config: {
                    category_id: null,
                    category_ids: [],
                },
                pricing_type: 'sum',
                fixed_price: null,
                fixed_old_price: null,
                product_ids: [],
                images: [],
            }
        },

        show(collection = null) {
            if (collection) {
                this.isEditing = true
                this.editingId = collection.id
                this.form = {
                    name: collection.name,
                    description: collection.description || '',
                    short_description: collection.short_description || '',
                    type: collection.type,
                    rule_config: collection.rule_config || { category_id: null, category_ids: [] },
                    pricing_type: collection.pricing_type,
                    fixed_price: collection.fixed_price,
                    fixed_old_price: collection.fixed_old_price,
                    product_ids: collection.products?.map(p => p.id) || [],
                    images: collection.images || [],
                }
            } else {
                this.isEditing = false
                this.editingId = null
                this.form = this.getEmptyForm()
            }

            this.$nextTick(() => {
                if (this.modal) {
                    this.modal.show()
                }
            })
        },

        hide() {
            if (this.modal) {
                this.modal.hide()
            }
        },

        selectType(type) {
            this.form.type = type
            this.form.product_ids = []
        },

        getFormProducts() {
            if (this.form.type === 'manual' || this.form.type === 'category_select') {
                return this.store.products.filter(p => this.form.product_ids.includes(p.id))
            }
            // Для динамических типов — считаем на лету
            return this.availableProducts
        },

        async uploadImage(event) {
            const file = event.target.files[0]
            if (!file) return

            const formData = new FormData()
            formData.append('image', file)

            try {
                if (this.isEditing) {
                    const response = await axios.post(
                        `/api/workspaces/${this.store.uuid}/collections/${this.editingId}/image`,
                        formData,
                        { headers: { 'Content-Type': 'multipart/form-data' } }
                    )
                    this.form.images = response.data.images
                } else {
                    // Для новой коллекции — временно храним файл
                    const reader = new FileReader()
                    reader.onload = (e) => {
                        this.form.images = [{ url: e.target.result, file: file }]
                    }
                    reader.readAsDataURL(file)
                }
            } catch (error) {
                console.error('Upload image failed:', error)
                this.$notify?.error('Ошибка при загрузке изображения')
            }

            event.target.value = ''
        },

        removeImage() {
            this.form.images = []
        },

        async save() {
            if (!this.isValid || this.isSaving) return

            this.isSaving = true

            try {
                const payload = {
                    name: this.form.name,
                    description: this.form.description,
                    short_description: this.form.short_description,
                    type: this.form.type,
                    rule_config: this.form.rule_config,
                    pricing_type: this.form.pricing_type,
                    fixed_price: this.form.fixed_price,
                    fixed_old_price: this.form.fixed_old_price,
                }

                if (this.form.type === 'manual' || this.form.type === 'category_select') {
                    payload.product_ids = this.form.product_ids
                }

                let response
                if (this.isEditing) {
                    response = await axios.put(
                        `/api/workspaces/${this.store.uuid}/collections/${this.editingId}`,
                        payload
                    )
                } else {
                    response = await axios.post(
                        `/api/workspaces/${this.store.uuid}/collections`,
                        payload
                    )
                }

                this.$emit('saved', response.data)
                this.hide()

                this.$notify?.success({
                    title: this.isEditing ? 'Коллекция обновлена' : 'Коллекция создана',
                    message: this.form.name
                })
            } catch (error) {
                console.error('Save collection failed:', error)
                this.$notify?.error('Ошибка при сохранении')
            } finally {
                this.isSaving = false
            }
        },

        formatPrice(price) {
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
    }
}
</script>

<style scoped>
/* Стили аналогичны предыдущим модалкам */
.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 16px 24px;
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
    font-weight: 600;
}

.modal-title i {
    color: #6f42c1;
}

.modal-body {
    padding: 24px;
}

.form-section {
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f1f3f5;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h6 {
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    margin: 0 0 14px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-row {
    display: flex;
    gap: 12px;
}

.flex-2 {
    flex: 2;
}

.form-group {
    margin-bottom: 14px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #495057;
    margin-bottom: 6px;
}

.form-input {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    color: #212529;
    background: #fff;
    transition: all 0.15s ease;
    outline: none;
    font-family: inherit;
}

.form-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

textarea.form-input {
    resize: vertical;
    min-height: 60px;
}

/* === Type Selector === */
.type-selector {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.type-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.type-card:hover {
    border-color: #0d6efd;
    background: #f8f9ff;
}

.type-card.active {
    border-color: #0d6efd;
    background: #e7f1ff;
}

.type-card i {
    font-size: 20px;
    color: #0d6efd;
    width: 24px;
    text-align: center;
}

.type-info {
    flex: 1;
}

.type-info strong {
    display: block;
    font-size: 13px;
    margin-bottom: 2px;
}

.type-info small {
    font-size: 11px;
    color: #6c757d;
}

/* === Rule Config === */
.rule-config {
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
}

.categories-checkboxes {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
    max-height: 200px;
    overflow-y: auto;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 13px;
}

.checkbox-label input {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.products-selector {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
}

.product-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-bottom: 1px solid #f1f3f5;
    cursor: pointer;
}

.product-checkbox:last-child {
    border-bottom: none;
}

.product-checkbox:hover {
    background: #f8f9fa;
}

.product-checkbox input {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.product-checkbox span:first-of-type {
    flex: 1;
    font-size: 13px;
}

.product-price {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
}

.info-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: #e7f1ff;
    border-radius: 6px;
    color: #084298;
    font-size: 13px;
}

.info-box i {
    font-size: 16px;
}

/* === Pricing === */
.pricing-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}

.radio-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.radio-card:hover {
    border-color: #0d6efd;
}

.radio-card.active {
    border-color: #0d6efd;
    background: #e7f1ff;
}

.radio-card input {
    display: none;
}

.radio-card i {
    font-size: 24px;
    color: #0d6efd;
}

.radio-card strong {
    display: block;
    font-size: 14px;
    margin-bottom: 2px;
}

.radio-card small {
    font-size: 11px;
    color: #6c757d;
}

.fixed-price-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.price-preview {
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 16px;
}

.price-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.price-info strong {
    font-size: 20px;
    color: #0d6efd;
}

.discount-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.old-price {
    font-size: 14px;
    color: #adb5bd;
    text-decoration: line-through;
}

.discount-badge {
    padding: 2px 8px;
    background: #dc3545;
    color: #fff;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
}

/* === Image === */
.image-preview {
    position: relative;
    width: 200px;
    height: 200px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-remove-image {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.upload-image {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 30px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.15s ease;
    width: 200px;
    height: 200px;
}

.upload-image:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #e7f1ff;
}

.upload-image i {
    font-size: 24px;
}

/* === Footer === */
.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 16px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.btn-cancel {
    padding: 10px 20px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 14px;
    cursor: pointer;
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    background: #0d6efd;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.btn-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
