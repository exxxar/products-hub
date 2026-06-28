<template>
    <div v-if="localForm" class="product-form">
        <!-- Header с кнопкой заполнения -->
        <div v-if="!isEditMode" class="form-header">
            <button
                type="button"
                class="btn-fill-example"
                @click="fillWithTestData"
            >
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                Заполнить по примеру
            </button>
        </div>

        <!-- Tabs -->
        <div class="form-tabs">
            <button
                v-for="t in tabs"
                :key="t.key"
                type="button"
                class="tab-btn"
                :class="{ active: tab === t.key }"
                @click="tab = t.key"
            >
                <i :class="t.icon"></i>
                <span class="tab-label">{{ t.label }}</span>
                <span v-if="t.count !== undefined" class="tab-count">{{ t.count }}</span>
            </button>
        </div>



        <!-- MAIN TAB -->
        <div v-if="tab === 'main'" class="tab-content">
            <div class="form-section">
                <h6 class="section-title">
                    <i class="fa-solid fa-info-circle"></i>
                    Основная информация
                </h6>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            Артикул
                            <span class="optional">(необязательно)</span>
                        </label>
                        <input
                            v-model="localForm.sku"
                            type="text"
                            class="form-input"
                            placeholder="Например: SKU-12345"
                        />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group required">
                        <label class="form-label">Название</label>
                        <input
                            v-model="localForm.name"
                            type="text"
                            class="form-input"
                            :class="{ 'is-invalid': errors.name }"
                            placeholder="Введите название товара"
                        />
                        <div v-if="errors.name" class="field-error">
                            {{ errors.name }}
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Описание</label>
                        <textarea
                            v-model="localForm.description"
                            class="form-input form-textarea"
                            placeholder="Опишите товар..."
                            rows="4"
                        ></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h6 class="section-title">
                    <i class="fa-solid fa-ruble-sign"></i>
                    Цены
                </h6>

                <div class="form-row two-cols">
                    <div class="form-group required">
                        <label class="form-label">Текущая цена, ₽</label>
                        <input
                            v-model.number="localForm.price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-input"
                            :class="{ 'is-invalid': errors.price }"
                            placeholder="0.00"
                        />
                        <div v-if="errors.price" class="field-error">
                            {{ errors.price }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Старая цена, ₽</label>
                        <input
                            v-model.number="localForm.old_price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-input"
                            placeholder="0.00"
                        />
                        <div v-if="localForm.old_price && localForm.old_price > localForm.price" class="field-hint success">
                            <i class="fa-solid fa-tag"></i>
                            Скидка {{ discountPercent }}%
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h6 class="section-title">
                    <i class="fa-solid fa-box"></i>
                    Габариты
                </h6>

                <div class="form-row">
                    <label class="switch-label">
                        <input
                            v-model="needDimensions"
                            type="checkbox"
                            class="switch-input"
                        />
                        <span class="switch-text">Указать габариты товара</span>
                    </label>
                </div>

                <div v-if="needDimensions" class="dimensions-grid">
                    <div class="form-group">
                        <label class="form-label">Ширина, см</label>
                        <input
                            v-model.number="localForm.dimensions.width"
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-input"
                            placeholder="0"
                        />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Высота, см</label>
                        <input
                            v-model.number="localForm.dimensions.height"
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-input"
                            placeholder="0"
                        />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Длина, см</label>
                        <input
                            v-model.number="localForm.dimensions.length"
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-input"
                            placeholder="0"
                        />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Вес, кг</label>
                        <input
                            v-model.number="localForm.dimensions.weight"
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-input"
                            placeholder="0"
                        />
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h6 class="section-title">
                    <i class="fa-solid fa-tags"></i>
                    Категории
                </h6>

                <!-- Выбранные категории -->
                <div v-if="localForm.categories.length > 0" class="selected-categories">
                    <span
                        v-for="catId in localForm.categories"
                        :key="catId"
                        class="category-chip"
                    >
                        {{ getCategoryName(catId) }}
                        <button
                            type="button"
                            class="chip-remove"
                            @click="removeCategory(catId)"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </span>
                </div>

                <!-- Dropdown для добавления -->
                <div class="category-dropdown">
                    <button
                        type="button"
                        class="dropdown-trigger"
                        @click="showCategoryDropdown = !showCategoryDropdown"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Добавить категорию
                    </button>

                    <div v-if="showCategoryDropdown" class="dropdown-panel">
                        <input
                            v-model="categorySearch"
                            type="text"
                            class="form-input"
                            placeholder="Поиск категории..."
                            @click.stop
                        />

                        <div class="dropdown-list">
                            <button
                                v-for="cat in filteredCategories"
                                :key="cat.id"
                                type="button"
                                class="dropdown-item"
                                :disabled="localForm.categories.includes(cat.id)"
                                @click="addCategory(cat.id)"
                            >
                                <i class="fa-solid fa-folder"></i>
                                {{ cat.name }}
                            </button>
                        </div>

                        <div v-if="filteredCategories.length === 0 && categorySearch" class="dropdown-footer">
                            <button
                                type="button"
                                class="btn-create-category"
                                @click="createCategory"
                            >
                                <i class="fa-solid fa-plus"></i>
                                Создать "{{ categorySearch }}"
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- IMAGES TAB -->
        <div v-if="tab === 'images'" class="tab-content">
            <div class="form-section">
                <h6 class="section-title">
                    <i class="fa-solid fa-images"></i>
                    Изображения товара
                </h6>

                <!-- Dropzone -->
                <div
                    class="image-dropzone"
                    :class="{ 'drag-over': isDragOver }"
                    @dragover.prevent="isDragOver = true"
                    @dragleave="isDragOver = false"
                    @drop.prevent="onImageDrop"
                    @click="triggerFileInput"
                >
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <p>Перетащите изображения сюда или нажмите для выбора</p>
                    <small>PNG, JPG, WEBP до 5MB</small>
                </div>

                <input
                    ref="fileInput"
                    type="file"
                    multiple
                    accept="image/*"
                    style="display: none"
                    @change="onImagesChange"
                />

                <!-- Image grid -->
                <div v-if="localForm.images.length > 0" class="image-grid">
                    <div
                        v-for="(img, i) in localForm.images"
                        :key="i"
                        class="image-card"
                        :class="{ 'is-primary': i === 0 }"
                        draggable="true"
                        @dragstart="onImageDragStart(i)"
                        @dragover.prevent="onImageDragOver(i)"
                        @drop.prevent="onImageDropReorder(i)"
                        @dragend="onImageDragEnd"
                    >
                        <img :src="img.preview" :alt="img.name" class="image-preview" />

                        <div v-if="i === 0" class="primary-badge">
                            <i class="fa-solid fa-star"></i>
                            Главная
                        </div>

                        <div class="image-actions">
                            <button
                                v-if="i !== 0"
                                type="button"
                                class="action-btn"
                                @click="setPrimaryImage(i)"
                                title="Сделать главной"
                            >
                                <i class="fa-solid fa-star"></i>
                            </button>
                            <button
                                type="button"
                                class="action-btn danger"
                                @click="removeImage(i)"
                                title="Удалить"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>

                        <div class="image-order">{{ i + 1 }}</div>
                    </div>
                </div>

                <div v-if="imageError" class="field-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ imageError }}
                </div>
            </div>
        </div>

        <!-- ATTRIBUTES TAB -->
        <div v-if="tab === 'attributes'" class="tab-content">
            <div class="form-section">
                <div class="section-header-with-action">
                    <h6 class="section-title">
                        <i class="fa-solid fa-list-check"></i>
                        Свойства товара
                    </h6>
                    <button type="button" class="btn-add" @click="addAttribute">
                        <i class="fa-solid fa-plus"></i>
                        Добавить свойство
                    </button>
                </div>

                <div v-if="localForm.attributes.length === 0" class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <p>Свойства не добавлены</p>
                </div>

                <div v-else class="attributes-list">
                    <div
                        v-for="(attr, i) in localForm.attributes"
                        :key="i"
                        class="attribute-card"
                    >
                        <div class="attribute-fields">
                            <input
                                v-model="attr.name"
                                type="text"
                                class="form-input"
                                placeholder="Название (например: Цвет)"
                            />
                            <input
                                v-model="attr.value"
                                type="text"
                                class="form-input"
                                placeholder="Значение (например: Красный)"
                            />
                        </div>
                        <button
                            type="button"
                            class="btn-remove"
                            @click="removeAttribute(i)"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- INGREDIENTS TAB -->
        <div v-if="tab === 'ingredients'" class="tab-content">
            <div class="form-section">
                <div class="section-header-with-action">
                    <h6 class="section-title">
                        <i class="fa-solid fa-blender"></i>
                        Ингредиенты
                    </h6>
                    <button type="button" class="btn-add" @click="$emit('add-ingredient-group')">
                        <i class="fa-solid fa-plus"></i>
                        Группа ингредиентов
                    </button>
                </div>

                <div v-if="store.ingredientGroups.length === 0" class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <p>Группы ингредиентов не созданы</p>
                </div>

                <div v-else class="ingredient-groups">
                    <div
                        v-for="group in store.ingredientGroups"
                        :key="group.id"
                        class="ingredient-group"
                    >
                        <h6 class="group-title">{{ group.name }}</h6>

                        <div class="ingredient-list">
                            <label
                                v-for="ing in group.ingredients"
                                :key="ing.id"
                                class="ingredient-checkbox"
                            >
                                <input
                                    type="checkbox"
                                    :value="ing.id"
                                    v-model="localForm.ingredients"
                                    class="checkbox-input"
                                />
                                <span class="checkbox-label">{{ ing.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form actions -->
        <div class="form-actions">
            <button type="button" class="btn-cancel" @click="$emit('cancel')">
                Отмена
            </button>
            <button type="button" class="btn-save" @click="saveForm">
                <i class="fa-solid fa-check"></i>
                Сохранить товар
            </button>
        </div>
    </div>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'ProductForm',

    props: {
        form: {
            type: Object,
            default: null
        }
    },

    emits: ['save', 'cancel', 'create-category', 'add-ingredient-group'],


    data() {
        return {
            store: useWorkspaceStore(),
            tab: 'main',
            localForm: null,
            categorySearch: '',
            showCategoryDropdown: false,
            needDimensions: false,
            isDragOver: false,
            draggedImageIndex: null,
            imageError: '',
            errors: {},

            tabs: [
                { key: 'main', label: 'Основное', icon: 'fa-solid fa-info-circle' },
                { key: 'images', label: 'Изображения', icon: 'fa-solid fa-images', count: 0 },
                { key: 'attributes', label: 'Свойства', icon: 'fa-solid fa-list-check', count: 0 },
                { key: 'ingredients', label: 'Ингредиенты', icon: 'fa-solid fa-blender', count: 0 }
            ]
        }
    },

    watch: {
        form: {
            immediate: true,
            deep: true,
            handler(newForm) {
                if (newForm) {
                    this.localForm = JSON.parse(JSON.stringify(newForm))
                    this.needDimensions = !!(
                        this.localForm.dimensions?.width ||
                        this.localForm.dimensions?.height ||
                        this.localForm.dimensions?.length ||
                        this.localForm.dimensions?.weight
                    )
                }
            }
        },

        'localForm.images': {
            handler() {
                this.updateTabCounts()
            },
            deep: true
        },

        'localForm.attributes': {
            handler() {
                this.updateTabCounts()
            },
            deep: true
        },

        'localForm.ingredients': {
            handler() {
                this.updateTabCounts()
            },
            deep: true
        }
    },

    computed: {
        isEditMode() {
            return !!this.form?.id
        },

        filteredCategories() {
            const q = this.categorySearch?.toLowerCase() || ''
            return this.store.categories.filter(c =>
                c.name.toLowerCase().includes(q)
            )
        },

        discountPercent() {
            if (!this.localForm.old_price || !this.localForm.price) return 0
            const discount = ((this.localForm.old_price - this.localForm.price) / this.localForm.old_price) * 100
            return Math.round(discount)
        }
    },

    mounted() {
        this.updateTabCounts()
    },
    methods: {
        // === Заполнение тестовыми данными ===
        fillWithTestData() {
            // Основные поля
            this.localForm.name = 'Кроссовки Nike Air Max 90'
            this.localForm.sku = 'NK-AM90-2024-BLK'
            this.localForm.description = 'Легендарные кроссовки Nike Air Max 90 в классическом черном цвете. Идеальное сочетание стиля и комфорта для повседневной носки. Амортизирующая подошва Air Max обеспечивает превосходную поддержку стопы.'
            this.localForm.price = 12990
            this.localForm.old_price = 15990

            // Категории (выбираем первые 2 если есть)
            if (this.store.categories.length > 0) {
                this.localForm.categories = this.store.categories.slice(0, 2).map(c => c.id)
            }

            // Габариты
            this.needDimensions = true
            this.localForm.dimensions = {
                width: 32,
                height: 12,
                length: 45,
                weight: 0.85
            }

            // Изображения (используем placeholder сервисы)
            this.localForm.images = [
                {
                    file: null,
                    preview: 'https://picsum.photos/seed/nike1/400/400',
                    name: 'nike-main.jpg'
                },
                {
                    file: null,
                    preview: 'https://picsum.photos/seed/nike2/400/400',
                    name: 'nike-side.jpg'
                },
                {
                    file: null,
                    preview: 'https://picsum.photos/seed/nike3/400/400',
                    name: 'nike-back.jpg'
                }
            ]

            // Атрибуты
            this.localForm.attributes = [
                { name: 'Бренд', value: 'Nike' },
                { name: 'Цвет', value: 'Черный' },
                { name: 'Материал', value: 'Кожа, текстиль' },
                { name: 'Сезон', value: 'Весна/Лето' },
                { name: 'Страна производства', value: 'Вьетнам' }
            ]

            // Ингредиенты (если есть в store)
            if (this.store.ingredientGroups.length > 0) {
                const firstGroup = this.store.ingredientGroups[0]
                if (firstGroup.ingredients && firstGroup.ingredients.length > 0) {
                    this.localForm.ingredients = firstGroup.ingredients.slice(0, 3).map(i => i.id)
                }
            }

            // Обновляем счетчики
            this.updateTabCounts()

            // Показываем уведомление (опционально)
            this.showToast('Форма заполнена тестовыми данными')
        },

        showToast(message) {
            // Простое уведомление
            const toast = document.createElement('div')
            toast.className = 'test-data-toast'
            toast.innerHTML = `<i class="fa-solid fa-check-circle"></i> ${message}`
            document.body.appendChild(toast)

            setTimeout(() => {
                toast.classList.add('show')
            }, 10)

            setTimeout(() => {
                toast.classList.remove('show')
                setTimeout(() => {
                    document.body.removeChild(toast)
                }, 300)
            }, 2000)
        },

        getCategoryName(catId) {
            const cat = this.store.categories.find(c => c.id === catId)
            return cat?.name || 'Неизвестная категория'
        },

        updateTabCounts() {
            const imagesTab = this.tabs.find(t => t.key === 'images')
            if (imagesTab) imagesTab.count = this.localForm?.images?.length || 0

            const attrsTab = this.tabs.find(t => t.key === 'attributes')
            if (attrsTab) attrsTab.count = this.localForm?.attributes?.length || 0

            const ingTab = this.tabs.find(t => t.key === 'ingredients')
            if (ingTab) ingTab.count = this.localForm?.ingredients?.length || 0
        },


        // === Categories ===
        addCategory(id) {
            if (!this.localForm.categories.includes(id)) {
                this.localForm.categories.push(id)
            }
        },

        removeCategory(id) {
            const index = this.localForm.categories.indexOf(id)
            if (index > -1) {
                this.localForm.categories.splice(index, 1)
            }
        },

        createCategory() {
            if (!this.categorySearch) return
            this.$emit('create-category', this.categorySearch)
            this.categorySearch = ''
            this.showCategoryDropdown = false
        },

        // === Images ===
        triggerFileInput() {
            this.$refs.fileInput.click()
        },

        onImagesChange(e) {
            this.processFiles(Array.from(e.target.files))
            e.target.value = '' // Reset input
        },

        onImageDrop(e) {
            this.isDragOver = false
            const files = Array.from(e.dataTransfer.files)
            this.processFiles(files)
        },

        processFiles(files) {
            this.imageError = ''
            const validFiles = []

            for (const file of files) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    this.imageError = `Файл "${file.name}" не является изображением`
                    continue
                }

                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    this.imageError = `Файл "${file.name}" слишком большой (макс. 5MB)`
                    continue
                }

                validFiles.push(file)
            }

            for (const file of validFiles) {
                const preview = URL.createObjectURL(file)
                this.localForm.images.push({
                    file,
                    preview,
                    name: file.name
                })
            }
        },

        removeImage(i) {
            const img = this.localForm.images[i]
            if (img.preview) {
                URL.revokeObjectURL(img.preview) // Free memory
            }
            this.localForm.images.splice(i, 1)
        },

        setPrimaryImage(i) {
            const img = this.localForm.images.splice(i, 1)[0]
            this.localForm.images.unshift(img)
        },

        onImageDragStart(i) {
            this.draggedImageIndex = i
        },

        onImageDragOver(i) {
            // Visual feedback could be added here
        },

        onImageDropReorder(i) {
            if (this.draggedImageIndex === null || this.draggedImageIndex === i) return

            const draggedImg = this.localForm.images[this.draggedImageIndex]
            this.localForm.images.splice(this.draggedImageIndex, 1)
            this.localForm.images.splice(i, 0, draggedImg)
        },

        onImageDragEnd() {
            this.draggedImageIndex = null
        },

        // === Attributes ===
        addAttribute() {
            this.localForm.attributes.push({ name: '', value: '' })
        },

        removeAttribute(i) {
            this.localForm.attributes.splice(i, 1)
        },

        // === Validation & Save ===
        validate() {
            this.errors = {}

            if (!this.localForm.name || !this.localForm.name.trim()) {
                this.errors.name = 'Название обязательно'
            }

            if (this.localForm.price === null || this.localForm.price === '' || this.localForm.price < 0) {
                this.errors.price = 'Укажите корректную цену'
            }

            return Object.keys(this.errors).length === 0
        },

        saveForm() {
            if (!this.validate()) {
                this.tab = 'main'
                return
            }

            this.$emit('save', { ...this.localForm })
        }
    },

    beforeUnmount() {
        // Clean up object URLs
        if (this.localForm?.images) {
            this.localForm.images.forEach(img => {
                if (img.preview) {
                    URL.revokeObjectURL(img.preview)
                }
            })
        }
    }
}
</script>

<style scoped>
/* === Header с кнопкой === */
.form-header {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 16px;
}

.btn-fill-example {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border: 1px solid #ffc107;
    border-radius: 8px;
    background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
    color: #856404;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
}

.btn-fill-example:hover {
    background: linear-gradient(135deg, #ffe69c 0%, #ffd43b 100%);
    border-color: #ffc107;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
}

.btn-fill-example i {
    font-size: 14px;
}

/* === Toast уведомление === */
:global(.test-data-toast) {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    background: #198754;
    color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(25, 135, 84, 0.3);
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    z-index: 10000;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

:global(.test-data-toast.show) {
    opacity: 1;
    transform: translateX(0);
}

:global(.test-data-toast i) {
    font-size: 16px;
}


/* === Tabs === */
.form-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    border-bottom: 2px solid #e9ecef;
    overflow-x: auto;
}

.tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: none;
    border-bottom: 2px solid transparent;
    background: transparent;
    color: #6c757d;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: -2px;
    white-space: nowrap;
}

.tab-btn:hover {
    color: #0d6efd;
    background: #f8f9fa;
}

.tab-btn.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
}

.tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: #e9ecef;
    color: #495057;
    font-size: 11px;
    font-weight: 600;
}

.tab-btn.active .tab-count {
    background: #0d6efd;
    color: #fff;
}

/* === Tab Content === */
.tab-content {
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* === Sections === */
.form-section {
    margin-bottom: 32px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin: 0 0 16px 0;
}

.section-title i {
    color: #0d6efd;
}

.section-header-with-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.section-header-with-action .section-title {
    margin: 0;
}

/* === Form fields === */
.form-row {
    margin-bottom: 16px;
}

.form-row.two-cols {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-group {
    margin-bottom: 0;
}

.form-group.required .form-label::after {
    content: ' *';
    color: #dc3545;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #495057;
    margin-bottom: 6px;
}

.optional {
    color: #6c757d;
    font-weight: 400;
    font-size: 12px;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    color: #212529;
    background: #fff;
    transition: all 0.15s ease;
    outline: none;
}

.form-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.form-input.is-invalid {
    border-color: #dc3545;
}

.form-input.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
}

.field-error {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    font-size: 13px;
    color: #dc3545;
}

.field-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    font-size: 12px;
    color: #6c757d;
}

.field-hint.success {
    color: #198754;
}

/* === Dimensions === */
.dimensions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-top: 16px;
}

/* === Switch === */
.switch-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.switch-input {
    width: 40px;
    height: 22px;
    appearance: none;
    background: #dee2e6;
    border-radius: 11px;
    position: relative;
    cursor: pointer;
    transition: background 0.2s ease;
}

.switch-input::before {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #fff;
    top: 2px;
    left: 2px;
    transition: transform 0.2s ease;
}

.switch-input:checked {
    background: #0d6efd;
}

.switch-input:checked::before {
    transform: translateX(18px);
}

.switch-text {
    font-size: 14px;
    color: #495057;
}

/* === Categories === */
.selected-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.category-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    background: #e7f1ff;
    color: #0d6efd;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
}

.chip-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border: none;
    border-radius: 50%;
    background: transparent;
    color: #0d6efd;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
}

.chip-remove:hover {
    background: #0d6efd;
    color: #fff;
}

.category-dropdown {
    position: relative;
}

.dropdown-trigger {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1px dashed #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
    color: #6c757d;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.dropdown-trigger:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #e7f1ff;
}

.dropdown-panel {
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 8px;
    min-width: 280px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    padding: 12px;
    z-index: 100;
}

.dropdown-list {
    max-height: 200px;
    overflow-y: auto;
    margin-top: 8px;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 8px 10px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #495057;
    font-size: 13px;
    text-align: left;
    cursor: pointer;
    transition: all 0.15s ease;
}

.dropdown-item:hover:not(:disabled) {
    background: #f8f9fa;
    color: #0d6efd;
}

.dropdown-item:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.dropdown-footer {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #e9ecef;
}

.btn-create-category {
    width: 100%;
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    background: #0d6efd;
    color: #fff;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-create-category:hover {
    background: #0b5ed7;
}

/* === Images === */
.image-dropzone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 40px 20px;
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    background: #f8f9fa;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}

.image-dropzone:hover,
.image-dropzone.drag-over {
    border-color: #0d6efd;
    background: #e7f1ff;
    color: #0d6efd;
}

.image-dropzone i {
    font-size: 32px;
}

.image-dropzone p {
    margin: 0;
    font-size: 14px;
    font-weight: 500;
}

.image-dropzone small {
    font-size: 12px;
    opacity: 0.7;
}

.image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
    margin-top: 16px;
}

.image-card {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #e9ecef;
    cursor: move;
    transition: all 0.2s ease;
}

.image-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
}

.image-card.is-primary {
    border-color: #ffc107;
}

.image-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.primary-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    padding: 4px 8px;
    background: #ffc107;
    color: #000;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.image-actions {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.image-card:hover .image-actions {
    opacity: 1;
}

.action-btn {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.9);
    color: #495057;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.action-btn:hover {
    background: #fff;
    color: #0d6efd;
}

.action-btn.danger:hover {
    color: #dc3545;
}

.image-order {
    position: absolute;
    bottom: 8px;
    left: 8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* === Attributes === */
.attributes-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.attribute-card {
    display: flex;
    gap: 12px;
    padding: 12px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.attribute-fields {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.btn-remove {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 8px;
    background: #f8d7da;
    color: #842029;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.btn-remove:hover {
    background: #f1aeb5;
}

/* === Ingredients === */
.ingredient-groups {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.ingredient-group {
    padding: 16px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.group-title {
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    margin: 0 0 12px 0;
}

.ingredient-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.ingredient-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-label {
    font-size: 13px;
    color: #495057;
}

/* === Buttons === */
.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1px solid #0d6efd;
    border-radius: 8px;
    background: transparent;
    color: #0d6efd;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-add:hover {
    background: #0d6efd;
    color: #fff;
}

/* === Empty state === */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.3;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

/* === Form actions === */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e9ecef;
}

.btn-cancel {
    padding: 10px 20px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-cancel:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
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
    transition: all 0.15s ease;
}

.btn-save:hover {
    background: #0b5ed7;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

/* === Responsive === */
@media (max-width: 768px) {
    .form-row.two-cols {
        grid-template-columns: 1fr;
    }

    .dimensions-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .attribute-fields {
        grid-template-columns: 1fr;
    }

    .tab-label {
        display: none;
    }

    .tab-btn {
        padding: 10px 12px;
    }
}

@media (max-width: 576px) {
    .image-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
