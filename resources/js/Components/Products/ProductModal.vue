<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i :class="isEdit ? 'fa-solid fa-pen' : 'fa-solid fa-plus'"></i>
                        {{ isEdit ? 'Редактировать товар' : 'Создать товар' }}
                    </h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">
                    <div v-if="isLoading" class="loading-state">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <p>Загрузка...</p>
                    </div>

                    <ProductForm
                        v-else
                        :key="product?.id || 'new'"
                        :form="form"
                        @save="onFormSave"
                        @cancel="hide"
                        @create-category="createCategory"
                        @add-ingredient-group="$emit('add-ingredient-group')"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Подтверждение удаления -->
    <div class="modal fade" ref="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-triangle-exclamation text-danger"></i>
                        Удалить товар?
                    </h5>
                    <button type="button" class="btn-close" @click="hideDeleteModal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить товар <strong>{{ product?.name }}</strong>?</p>
                    <p class="text-muted small">Это действие нельзя отменить.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="hideDeleteModal">
                        Отмена
                    </button>
                    <button type="button" class="btn btn-danger" @click="confirmDelete">
                        <i class="fa-solid fa-trash"></i>
                        Удалить
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'
import ProductForm from './ProductForm.vue'

export default {
    name: 'ProductModal',

    components: {
        ProductForm
    },

    props: {
        product: {
            type: Object,
            default: null
        }
    },

    emits: ['save', 'delete', 'create-category', 'add-ingredient-group'],

    data() {
        return {
            modal: null,
            deleteModal: null,
            form: this.getEmptyForm(),
            isLoading: false,
            isSaving: false
        }
    },

    computed: {
        isEdit() {
            return !!this.product
        }
    },

    methods: {
        getEmptyForm() {
            return {
                id: null,
                name: '',
                sku: '',
                price: 0,
                old_price: 0,
                description: '',
                categories: [],
                dimensions: {
                    width: 0,
                    height: 0,
                    length: 0,
                    weight: 0
                },
                images: [],
                variants: [],
                attributes: [],
                ingredients: []
            }
        },

        prepareForm() {
            if (this.product) {
                this.form = {
                    id: this.product.id,
                    name: this.product.name || '',
                    sku: this.product.sku || '',
                    price: this.product.price || 0,
                    old_price: this.product.old_price || 0,
                    description: this.product.description || '',
                    categories: this.product.categories?.map(c => c.id) ?? [],
                    images: this.product.images?.map(img => ({
                        file: null,
                        preview: img.url || img,
                        name: img.name || ''
                    })) ?? [],
                    attributes: JSON.parse(JSON.stringify(this.product.attributes ?? [])),
                    ingredients: this.product.ingredients?.map(i => i.id) ?? [],
                    dimensions: {
                        width: this.product.dimensions?.width || 0,
                        height: this.product.dimensions?.height || 0,
                        length: this.product.dimensions?.length || 0,
                        weight: this.product.dimensions?.weight || 0
                    }
                }
            } else {
                this.form = this.getEmptyForm()
            }
        },

        show() {
            this.isLoading = true
            this.prepareForm()

            this.$nextTick(() => {
                this.isLoading = false
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

        onFormSave(formData) {
            this.save(formData)
        },

        async save(formData) {
            this.isSaving = true

            try {
                const payload = this.buildFormData(formData)

                // Отладка - смотрим что отправляется
                console.log('Saving product:', {
                    formData: formData,
                    categories: formData.categories
                })

                await this.$emit('save', payload, this.product?.id)
                this.hide()
            } catch (error) {
                console.error('Save failed:', error)
                alert('Ошибка при сохранении товара')
            } finally {
                this.isSaving = false
            }
        },

        buildFormData(formData) {
            const payload = new FormData()

            // Основные поля
            payload.append('name', formData.name)
            payload.append('sku', formData.sku || '')
            payload.append('description', formData.description || '')
            payload.append('price', formData.price || 0)
            payload.append('old_price', formData.old_price || 0)

            // Категории
            if (formData.categories && formData.categories.length > 0) {
                formData.categories.forEach((catId, i) => {
                    payload.append(`categories[${i}]`, catId)
                })
            }

            // Габариты
            if (formData.dimensions) {
                payload.append('dimensions[width]', formData.dimensions.width || 0)
                payload.append('dimensions[height]', formData.dimensions.height || 0)
                payload.append('dimensions[length]', formData.dimensions.length || 0)
                payload.append('dimensions[weight]', formData.dimensions.weight || 0)
            }

            // Изображения
            if (formData.images && formData.images.length > 0) {
                formData.images.forEach((img, i) => {
                    if (img.file) {
                        // Новый файл
                        payload.append(`images[${i}]`, img.file)
                    } else if (img.preview) {
                        // Существующий URL
                        payload.append(`images_existing[${i}]`, img.preview)
                    }
                })
            }

            // Атрибуты
            if (formData.attributes && formData.attributes.length > 0) {
                payload.append('attributes', JSON.stringify(formData.attributes))
            }

            // Ингредиенты
            if (formData.ingredients && formData.ingredients.length > 0) {
                payload.append('ingredients', JSON.stringify(formData.ingredients))
            }

            return payload
        },

        showDeleteModal() {
            if (this.deleteModal) {
                this.deleteModal.show()
            }
        },

        hideDeleteModal() {
            if (this.deleteModal) {
                this.deleteModal.hide()
            }
        },

        confirmDelete() {
            this.$emit('delete', this.product.id)
            this.hideDeleteModal()
            this.hide()
        },

        createCategory(name) {
            this.$emit('create-category', name)
        }
    },

    mounted() {
        this.modal = new Modal(this.$refs.modal)
        this.deleteModal = new Modal(this.$refs.deleteModal)
    },

    beforeUnmount() {
        if (this.modal) {
            this.modal.dispose()
            this.modal = null
        }
        if (this.deleteModal) {
            this.deleteModal.dispose()
            this.deleteModal = null
        }
    }
}
</script>

<style scoped>
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
    color: #212529;
}

.modal-title i {
    color: #0d6efd;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 16px 24px;
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: #6c757d;
}

.loading-state i {
    font-size: 32px;
    margin-bottom: 16px;
    color: #0d6efd;
}

.loading-state p {
    margin: 0;
    font-size: 14px;
}

/* === Delete modal === */
.modal-body p {
    margin-bottom: 8px;
    font-size: 14px;
    color: #495057;
}

.text-muted.small {
    font-size: 13px;
}

/* === Responsive === */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 0;
        max-width: 100%;
        height: 100vh;
    }

    .modal-content {
        height: 100%;
        border-radius: 0;
    }

    .modal-body {
        padding: 16px;
    }
}
</style>
