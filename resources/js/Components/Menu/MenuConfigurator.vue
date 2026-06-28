<template>
    <div class="menu-configurator">
        <div class="configurator-header">
            <h5>
                <i class="fa-solid fa-file-pdf"></i>
                Генератор PDF-меню
            </h5>
            <p class="text-muted">Настройте внешний вид и сгенерируйте прайс-лист</p>
        </div>

        <div class="configurator-content">
            <!-- Левая колонка: настройки -->
            <div class="settings-panel">
                <!-- Основные настройки -->
                <div class="settings-section">
                    <h6>Основные настройки</h6>

                    <div class="form-group">
                        <label>Название меню</label>
                        <input
                            v-model="config.name"
                            type="text"
                            class="form-input"
                            placeholder="Например: Меню ресторана"
                        />
                    </div>

                    <div class="form-group">
                        <label>Описание</label>
                        <textarea
                            v-model="config.description"
                            class="form-input"
                            rows="2"
                            placeholder="Краткое описание"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label>Контакты</label>
                        <textarea
                            v-model="config.contacts"
                            class="form-input"
                            rows="2"
                            placeholder="Телефон, адрес, сайт"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label>Расположение контактов</label>
                        <div class="layout-selector">
                            <button
                                type="button"
                                class="layout-btn"
                                :class="{ active: config.contacts_position === 'top' }"
                                @click="config.contacts_position = 'top'"
                            >
                                <i class="fa-solid fa-arrow-up"></i>
                                <span>Сверху</span>
                            </button>
                            <button
                                type="button"
                                class="layout-btn"
                                :class="{ active: config.contacts_position === 'bottom' }"
                                @click="config.contacts_position = 'bottom'"
                            >
                                <i class="fa-solid fa-arrow-down"></i>
                                <span>Снизу</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Цвета текста -->
                <div class="settings-section">
                    <h6>Цвета текста</h6>

                    <div class="form-group">
                        <label>Основной цвет текста</label>
                        <div class="color-picker">
                            <input v-model="config.text_color" type="color" class="color-input" />
                            <input v-model="config.text_color" type="text" class="form-input color-text" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Цвет описаний</label>
                        <div class="color-picker">
                            <input v-model="config.description_color" type="color" class="color-input" />
                            <input v-model="config.description_color" type="text" class="form-input color-text" />
                        </div>
                    </div>
                </div>

                <!-- Карточки товаров -->
                <div class="settings-section">
                    <h6>Карточки товаров</h6>

                    <div class="form-group">
                        <label>Высота картинок: {{ config.product_image_height }}px</label>
                        <input
                            v-model.number="config.product_image_height"
                            type="range"
                            min="50"
                            max="300"
                            step="10"
                            class="range-input"
                        />
                    </div>

                    <div class="form-group">
                        <label>Цвет подложки карточек</label>
                        <div class="color-picker">
                            <input v-model="config.card_background_color" type="color" class="color-input" />
                            <input v-model="config.card_background_color" type="text" class="form-input color-text" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Прозрачность подложки: {{ Math.round(config.card_background_opacity * 100) }}%</label>
                        <input
                            v-model.number="config.card_background_opacity"
                            type="range"
                            min="0"
                            max="1"
                            step="0.05"
                            class="range-input"
                        />
                    </div>

                    <!-- Превью подложки -->
                    <div class="form-group">
                        <label>Превью подложки</label>
                        <div class="card-preview" :style="cardPreviewStyle">
                            <div class="card-preview-content">
                                <div class="card-preview-image" :style="{ height: config.product_image_height + 'px' }"></div>
                                <div class="card-preview-text">Название товара</div>
                                <div class="card-preview-desc">Описание товара</div>
                                <div class="card-preview-price">{{ formatPrice(12990) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Надпись "Все товары" -->
                <div class="settings-section">
                    <h6>Дополнительно</h6>

                    <div class="form-group">
                        <label>Название для товаров без категории</label>
                        <input
                            v-model="config.all_products_label"
                            type="text"
                            class="form-input"
                            placeholder="Все товары"
                        />
                        <small class="form-hint">
                            Используется когда товар не привязан ни к одной категории
                        </small>
                    </div>
                </div>

                <!-- Оформление -->
                <div class="settings-section">
                    <h6>Оформление</h6>

                    <div class="form-group">
                        <label>Тема</label>
                        <div class="theme-selector">
                            <button
                                v-for="theme in themes"
                                :key="theme.value"
                                type="button"
                                class="theme-btn"
                                :class="{ active: config.theme === theme.value }"
                                @click="config.theme = theme.value"
                            >
                                <i :class="theme.icon"></i>
                                <span>{{ theme.label }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Цвет акцента</label>
                        <div class="color-picker">
                            <input
                                v-model="config.accent_color"
                                type="color"
                                class="color-input"
                            />
                            <input
                                v-model="config.accent_color"
                                type="text"
                                class="form-input color-text"
                                placeholder="#0d6efd"
                            />
                        </div>
                    </div>

                    <!-- ✅ НОВОЕ: Фон -->
                    <div class="form-group">
                        <label>Цвет фона</label>
                        <div class="color-picker">
                            <input v-model="config.bg_color" type="color" class="color-input" />
                            <input v-model="config.bg_color" type="text" class="form-input color-text" />
                        </div>
                    </div>

                    <!-- ✅ НОВОЕ: Фоновая картинка -->
                    <div class="form-group">
                        <label>Фоновая картинка (опционально)</label>
                        <div v-if="config.background_image_path" class="bg-image-preview">
                            <img :src="`/storage/${config.background_image_path}`" alt="Background" />
                            <button type="button" class="btn-remove-bg" @click="removeBackgroundImage">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                        <label v-else class="upload-bg-btn">
                            <input type="file" accept="image/*" @change="uploadBackgroundImage" style="display: none" />
                            <i class="fa-solid fa-image"></i>
                            <span>Загрузить фоновую картинку</span>
                        </label>
                    </div>



                    <div class="form-group">
                        <label>Логотип</label>
                        <div class="logo-upload">
                            <div v-if="config.logo_path" class="logo-preview">
                                <img :src="logoUrl" alt="Logo" />
                                <button type="button" class="btn-remove-logo" @click="removeLogo">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <label v-else class="upload-btn">
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="uploadLogo"
                                    style="display: none"
                                />
                                <i class="fa-solid fa-upload"></i>
                                <span>Загрузить логотип</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Макет -->
                <div class="settings-section">
                    <h6>Макет</h6>

                    <div class="form-group">
                        <label>Тип расположения</label>
                        <div class="layout-selector">
                            <button
                                type="button"
                                class="layout-btn"
                                :class="{ active: config.layout_type === 'grid' }"
                                @click="config.layout_type = 'grid'"
                            >
                                <i class="fa-solid fa-grip"></i>
                                <span>Плитка</span>
                            </button>
                            <button
                                type="button"
                                class="layout-btn"
                                :class="{ active: config.layout_type === 'list' }"
                                @click="config.layout_type = 'list'"
                            >
                                <i class="fa-solid fa-list"></i>
                                <span>Список</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="config.layout_type === 'grid'" class="form-group">
                        <label>Товаров в ряд: {{ config.items_per_row }}</label>
                        <input
                            v-model.number="config.items_per_row"
                            type="range"
                            min="1"
                            max="6"
                            class="range-input"
                        />
                    </div>

                    <div class="form-group">
                        <label>Отображать</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" v-model="config.show_prices" />
                                <span>Цены</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" v-model="config.show_descriptions" />
                                <span>Описания</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" v-model="config.show_images" />
                                <span>Изображения</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Ориентация -->
                <div class="settings-section">
                    <h6>Ориентация страницы</h6>
                    <div class="layout-selector">
                        <button
                            type="button"
                            class="layout-btn"
                            :class="{ active: config.orientation === 'portrait' }"
                            @click="config.orientation = 'portrait'"
                        >
                            <i class="fa-solid fa-mobile-screen"></i>
                            <span>Портретная</span>
                        </button>
                        <button
                            type="button"
                            class="layout-btn"
                            :class="{ active: config.orientation === 'landscape' }"
                            @click="config.orientation = 'landscape'"
                        >
                            <i class="fa-solid fa-mobile-screen-button fa-rotate-90"></i>
                            <span>Альбомная</span>
                        </button>
                    </div>
                </div>

                <!-- НОВОЕ: Общий QR-код -->
                <div class="settings-section">
                    <h6>
                        <i class="fa-solid fa-qrcode"></i>
                        QR-код (общий)
                    </h6>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="config.qr_enabled" />
                            <span>Показывать QR-код</span>
                        </label>
                    </div>

                    <template v-if="config.qr_enabled">
                        <div class="form-group">
                            <label>
                                URL для QR
                                <span v-if="qrUrlError" class="field-error-inline">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    {{ qrUrlError }}
                                </span>
                            </label>
                            <input
                                v-model="config.qr_url"
                                type="url"
                                class="form-input"
                                :class="{ 'is-invalid': qrUrlError }"
                                placeholder="https://example.com"
                            />
                        </div>

                        <div class="form-group">
                            <label>Расположение</label>
                            <div class="position-grid">
                                <button
                                    v-for="pos in qrPositions"
                                    :key="pos.value"
                                    type="button"
                                    class="position-btn"
                                    :class="{ active: config.qr_position === pos.value }"
                                    @click="config.qr_position = pos.value"
                                    :title="pos.label"
                                >
                                    <i :class="pos.icon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Размер: {{ config.qr_size }}px</label>
                            <input
                                v-model.number="config.qr_size"
                                type="range"
                                min="50"
                                max="200"
                                step="10"
                                class="range-input"
                            />
                        </div>
                    </template>
                </div>

                <!-- НОВОЕ: QR-коды товаров -->
                <div class="settings-section">
                    <h6>
                        <i class="fa-solid fa-qrcode"></i>
                        QR-коды товаров
                    </h6>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="config.product_qr_enabled" />
                            <span>Показывать QR на каждом товаре</span>
                        </label>
                        <small class="form-hint">
                            QR будет вести на страницу товара
                        </small>
                    </div>

                    <template v-if="config.product_qr_enabled">
                        <div class="form-group">
                            <label>
                                Шаблон URL
                                <span v-if="productQrUrlError" class="field-error-inline">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    {{ productQrUrlError }}
                                </span>
                            </label>
                            <input
                                v-model="config.product_qr_url_template"
                                type="url"
                                class="form-input"
                                :class="{ 'is-invalid': productQrUrlError }"
                                placeholder="https://example.com/product/{sku}"
                            />
                            <small class="form-hint">
                                Доступные переменные:
                                <code>{sku}</code>,
                                <code>{id}</code>,
                                <code>{name}</code>,
                                <code>{uuid}</code>
                            </small>
                        </div>

                        <!-- Пример -->
                        <div v-if="config.product_qr_url_template && !productQrUrlError" class="url-example">
                            <strong>Пример:</strong>
                            <code>{{ productQrUrlExample }}</code>
                        </div>
                    </template>
                </div>

                <!-- Категории -->
                <div class="settings-section">
                    <h6>Категории</h6>
                    <div class="category-selector">
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                @change="toggleAllCategories"
                                :checked="allCategoriesSelected"
                            />
                            <span>Все категории</span>
                        </label>

                        <div class="categories-list">
                            <label
                                v-for="category in store.categories"
                                :key="category.id"
                                class="checkbox-label"
                            >
                                <input
                                    type="checkbox"
                                    :value="category.id"
                                    v-model="config.category_ids"
                                />
                                <span>{{ category.name }}</span>
                            </label>
                        </div>

                        <div v-if="store.categories.length === 0" class="empty-hint">
                            <small>Создайте категории для фильтрации</small>
                        </div>
                    </div>
                </div>

                <!-- НОВОЕ: Дефолтные картинки -->
                <div class="settings-section">
                    <h6>
                        <i class="fa-solid fa-image"></i>
                        Дефолтная картинка
                    </h6>
                    <p class="section-hint">
                        Будет использоваться для товаров без изображений
                    </p>

                    <!-- Текущая выбранная картинка -->
                    <div v-if="selectedDefaultImage" class="selected-default-image">
                        <img :src="selectedDefaultImage.url" alt="Default" />
                        <div class="selected-info">
                            <strong>{{ selectedDefaultImage.name }}</strong>
                            <button
                                type="button"
                                class="btn-clear-default"
                                @click="config.default_image_id = null"
                            >
                                <i class="fa-solid fa-xmark"></i>
                                Убрать
                            </button>
                        </div>
                    </div>

                    <!-- Галерея доступных картинок -->
                    <div v-if="store.menuDefaultImages.length > 0" class="default-images-gallery">
                        <div
                            v-for="img in store.menuDefaultImages"
                            :key="img.id"
                            class="default-image-item"
                            :class="{ selected: config.default_image_id === img.id }"
                            @click="config.default_image_id = img.id"
                        >
                            <img :src="img.url" :alt="img.name" />
                            <button
                                type="button"
                                class="btn-delete-image"
                                @click.stop="confirmDeleteDefaultImage(img)"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                            <div v-if="config.default_image_id === img.id" class="selected-badge">
                                <i class="fa-solid fa-check"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Загрузка новой картинки -->
                    <div class="upload-default-section">
                        <label class="upload-default-btn">
                            <input
                                type="file"
                                accept="image/*"
                                @change="uploadDefaultImage"
                                style="display: none"
                            />
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Загрузить новую картинку</span>
                        </label>
                    </div>

                    <div v-if="store.menuDefaultImages.length === 0" class="empty-hint">
                        <i class="fa-solid fa-image"></i>
                        <p>Нет дефолтных картинок</p>
                        <small>Загрузите хотя бы одну для товаров без изображений</small>
                    </div>
                </div>

                <!-- Кнопки действий -->
                <div class="settings-actions">
                    <button
                        type="button"
                        class="btn-save"
                        @click="saveConfig"
                        :disabled="isSaving || hasValidationErrors"
                    >
                        <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        {{ isSaving ? 'Сохранение...' : 'Сохранить настройки' }}
                    </button>

                    <button
                        type="button"
                        class="btn-generate"
                        @click="generatePdf"
                        :disabled="isGenerating || hasValidationErrors"
                    >
                        <i v-if="isGenerating" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-file-pdf"></i>
                        {{ isGenerating ? 'Генерация...' : 'Сгенерировать PDF' }}
                    </button>
                </div>
            </div>

            <!-- Правая колонка: превью -->
            <div class="preview-panel">
                <div class="preview-header-bar">
                    <h6>Предпросмотр</h6>
                    <button
                        type="button"
                        class="btn-refresh"
                        @click="loadPreview"
                        :disabled="previewLoading"
                        title="Обновить превью"
                    >
                        <i class="fa-solid fa-arrows-rotate" :class="{ 'rotating': previewLoading }"></i>
                    </button>
                </div>

                <div v-if="previewLoading" class="preview-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Загрузка превью...</p>
                </div>

                <div
                    v-else-if="preview"
                    class="preview-content"
                    :class="`theme-${config.theme}`"
                    :style="previewContainerStyle"
                >
                    <!-- Контакты СВЕРХУ -->
                    <div
                        v-if="config.contacts && config.contacts_position === 'top'"
                        class="preview-contacts-block preview-contacts-top"
                    >
                        {{ config.contacts }}
                    </div>

                    <!-- Header превью -->
                    <div class="preview-menu-header" :style="previewHeaderStyle">
                        <div v-if="config.logo_path" class="preview-logo">
                            <img :src="logoUrl" alt="Logo" />
                        </div>
                        <div class="preview-title" :style="{ color: config.accent_color }">
                            {{ config.name || 'Меню' }}
                        </div>
                        <div v-if="config.description" class="preview-description">
                            {{ config.description }}
                        </div>
                    </div>

                    <!-- Общий QR сверху -->
                    <div
                        v-if="config.qr_enabled && config.qr_url && !qrUrlError && preview.qr_base64 && config.qr_position.startsWith('top')"
                        class="preview-qr-wrapper"
                        :class="`qr-${config.qr_position}`"
                    >
                        <img :src="preview.qr_base64" alt="QR" :style="{ width: config.qr_size + 'px', height: config.qr_size + 'px' }" />
                        <small>Отсканируйте для перехода</small>
                    </div>

                    <!-- Категории и товары -->
                    <div class="preview-body">
                        <div
                            v-for="(products, categoryName) in preview.groupedProducts"
                            :key="categoryName"
                            class="preview-category"
                        >
                            <div
                                class="preview-category-title"
                                :style="{
                                    color: config.accent_color,
                                    borderBottomColor: config.accent_color
                                }"
                            >
                                {{ categoryName }}
                            </div>

                            <!-- Grid layout -->
                            <div
                                v-if="config.layout_type === 'grid'"
                                class="preview-grid"
                                :style="{ gridTemplateColumns: `repeat(${config.items_per_row}, 1fr)` }"
                            >
                                <div
                                    v-for="product in products.slice(0, 6)"
                                    :key="product.id"
                                    class="preview-product-card"
                                    :style="previewCardStyle"
                                >
                                    <div
                                        v-if="config.show_images"
                                        class="preview-product-image"
                                    >
                                        <img
                                            :src="getProductPreviewImage(product)"
                                            :alt="product.name"
                                        />
                                    </div>

                                    <div class="preview-product-name">{{ product.name }}</div>

                                    <div
                                        v-if="config.show_descriptions && product.description"
                                        class="preview-product-description"
                                    >
                                        {{ product.description.substring(0, 50) }}...
                                    </div>

                                    <div v-if="config.show_prices" class="preview-product-price">
                                        <span
                                            v-if="product.old_price && product.old_price > product.price"
                                            class="old-price"
                                        >
                                            {{ formatPrice(product.old_price) }}
                                        </span>
                                        <span class="current-price" :style="{ color: config.accent_color }">
                                            {{ formatPrice(product.price) }}
                                        </span>
                                    </div>

                                    <!-- QR товара -->
                                    <div
                                        v-if="config.product_qr_enabled && preview.product_qrs?.[product.id]"
                                        class="preview-product-qr"
                                    >
                                        <img :src="preview.product_qrs[product.id]" alt="QR" />
                                    </div>
                                </div>
                            </div>

                            <!-- List layout -->
                            <div v-else class="preview-list">
                                <div
                                    v-for="product in products.slice(0, 5)"
                                    :key="product.id"
                                    class="preview-product-item"
                                    :style="previewCardStyle"
                                >
                                    <div
                                        v-if="config.show_images"
                                        class="preview-item-image"
                                    >
                                        <img
                                            :src="getProductPreviewImage(product)"
                                            :alt="product.name"
                                        />
                                    </div>

                                    <div class="preview-item-content">
                                        <div class="preview-product-name">{{ product.name }}</div>
                                        <div
                                            v-if="config.show_descriptions && product.description"
                                            class="preview-product-description"
                                        >
                                            {{ product.description.substring(0, 80) }}...
                                        </div>
                                    </div>

                                    <div v-if="config.show_prices" class="preview-item-price">
                                        <div
                                            v-if="product.old_price && product.old_price > product.price"
                                            class="old-price"
                                        >
                                            {{ formatPrice(product.old_price) }}
                                        </div>
                                        <div class="current-price" :style="{ color: config.accent_color }">
                                            {{ formatPrice(product.price) }}
                                        </div>
                                    </div>

                                    <!-- QR товара -->
                                    <div
                                        v-if="config.product_qr_enabled && preview.product_qrs?.[product.id]"
                                        class="preview-item-qr"
                                    >
                                        <img :src="preview.product_qrs[product.id]" alt="QR" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Общий QR снизу -->
                    <div
                        v-if="config.qr_enabled && config.qr_url && !qrUrlError && preview.qr_base64 && config.qr_position.startsWith('bottom')"
                        class="preview-qr-wrapper"
                        :class="`qr-${config.qr_position}`"
                    >
                        <img :src="preview.qr_base64" alt="QR" :style="{ width: config.qr_size + 'px', height: config.qr_size + 'px' }" />
                        <small>Отсканируйте для перехода</small>
                    </div>

                    <!-- Контакты СНИЗУ -->
                    <div
                        v-if="config.contacts && config.contacts_position === 'bottom'"
                        class="preview-contacts-block preview-contacts-bottom"
                    >
                        {{ config.contacts }}
                    </div>

                    <!-- Footer превью -->
                    <div class="preview-footer" :style="{ borderTopColor: config.accent_color }">
                        <p>Сгенерировано {{ currentDate }}</p>
                    </div>
                </div>

                <div v-else class="preview-empty">
                    <i class="fa-solid fa-eye"></i>
                    <p>Сохраните настройки для отображения превью</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useWorkspaceStore } from '@/store/workspace.js'
import axios from 'axios'

export default {
    name: 'MenuConfigurator',

    data() {
        return {
            store: useWorkspaceStore(),
            isSaving: false,
            isGenerating: false,
            previewLoading: false,
            preview: null,
            config: {
                name: 'Меню',
                theme: 'light',
                accent_color: '#0d6efd',
                text_color: '#212529',
                description_color: '#6c757d',
                bg_color: '#ffffff',
                background_image_path: null,
                logo_path: null,
                description: '',
                contacts: '',
                contacts_position: 'bottom',
                items_per_row: 3,
                product_image_height: 100,
                layout_type: 'grid',
                all_products_label: 'Все товары',
                orientation: 'portrait',
                card_background_color: '#ffffff',
                card_background_opacity: 0.85,
                category_ids: [],
                show_prices: true,
                show_descriptions: true,
                show_images: true,
                qr_enabled: false,
                qr_url: '',
                qr_position: 'bottom-right',
                qr_size: 100,
                product_qr_enabled: false,
                product_qr_url_template: '',
            },
            themes: [
                { value: 'light', label: 'Светлая', icon: 'fa-solid fa-sun' },
                { value: 'dark', label: 'Тёмная', icon: 'fa-solid fa-moon' },
                { value: 'modern', label: 'Современная', icon: 'fa-solid fa-wand-magic-sparkles' },
            ],
            qrPositions: [
                { value: 'top-left', label: 'Сверху слева', icon: 'fa-solid fa-arrow-up' },
                { value: 'top-center', label: 'Сверху по центру', icon: 'fa-solid fa-arrow-up' },
                { value: 'top-right', label: 'Сверху справа', icon: 'fa-solid fa-arrow-up' },
                { value: 'bottom-left', label: 'Снизу слева', icon: 'fa-solid fa-arrow-down' },
                { value: 'bottom-center', label: 'Снизу по центру', icon: 'fa-solid fa-arrow-down' },
                { value: 'bottom-right', label: 'Снизу справа', icon: 'fa-solid fa-arrow-down' },
            ]
        }
    },

    computed: {
        logoUrl() {
            if (!this.config.logo_path) return null
            return `/storage/${this.config.logo_path}`
        },
        cardPreviewStyle() {
            const rgb = this.hexToRgb(this.config.card_background_color)
            return {
                background: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${this.config.card_background_opacity})`,
                border: '1px solid #dee2e6'
            }
        },
        backgroundUrl() {
            if (!this.config.background_image_path) return null
            return `/storage/${this.config.background_image_path}`
        },

        allCategoriesSelected() {
            if (this.store.categories.length === 0) return false
            return this.config.category_ids.length === this.store.categories.length
        },

        currentDate() {
            return new Date().toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            })
        },

        selectedDefaultImage() {
            if (!this.config.default_image_id) return null
            return this.store.menuDefaultImages.find(img => img.id === this.config.default_image_id)
        },

        qrUrlError() {
            if (!this.config.qr_enabled) return ''
            if (!this.config.qr_url) return 'Укажите URL'
            try {
                new URL(this.config.qr_url)
                return ''
            } catch {
                return 'Некорректный URL'
            }
        },

        productQrUrlError() {
            if (!this.config.product_qr_enabled) return ''
            if (!this.config.product_qr_url_template) return 'Укажите шаблон URL'
            let testUrl = this.config.product_qr_url_template
            testUrl = testUrl.replace(/\{sku\}/g, 'TEST-SKU')
            testUrl = testUrl.replace(/\{id\}/g, '1')
            testUrl = testUrl.replace(/\{name\}/g, 'Test')
            testUrl = testUrl.replace(/\{uuid\}/g, 'test-uuid')
            try {
                new URL(testUrl)
                return ''
            } catch {
                return 'Некорректный URL шаблон'
            }
        },

        productQrUrlExample() {
            if (!this.config.product_qr_url_template) return ''
            let url = this.config.product_qr_url_template
            url = url.replace(/\{sku\}/g, 'NK-001')
            url = url.replace(/\{id\}/g, '123')
            url = url.replace(/\{name\}/g, 'Кроссовки')
            url = url.replace(/\{uuid\}/g, this.store.uuid || 'abc-def')
            return url
        },

        hasValidationErrors() {
            return !!(this.qrUrlError || this.productQrUrlError)
        },

        previewContainerStyle() {
            if (this.config.background_image_path) {
                return {
                    background: `url('${this.backgroundUrl}') center/cover no-repeat`,
                    color: this.config.theme === 'dark' ? '#ffffff' : '#333333'
                }
            }
            return {
                background: this.config.bg_color || (this.config.theme === 'dark' ? '#1a1a1a' : '#ffffff'),
                color: this.config.theme === 'dark' ? '#ffffff' : '#333333'
            }
        },

        previewHeaderStyle() {
            return {
                borderBottomColor: this.config.accent_color
            }
        },

        previewCardStyle() {
            const isDark = this.config.theme === 'dark'
            return {
                background: isDark ? 'rgba(42, 42, 42, 0.8)' : 'rgba(248, 249, 250, 0.9)',
                borderColor: isDark ? '#444444' : '#dee2e6'
            }
        }
    },

    async mounted() {
        await this.loadConfig()
        await this.store.loadMenuDefaultImages()
        await this.loadPreview()
    },

    methods: {
        async loadConfig() {
            try {
                const config = await this.store.loadMenuConfig()
                this.config = { ...this.config, ...config }
            } catch (error) {
                console.error('Failed to load config:', error)
            }
        },
        hexToRgb(hex) {
            hex = hex.replace('#', '')
            if (hex.length === 3) {
                return {
                    r: parseInt(hex[0] + hex[0], 16),
                    g: parseInt(hex[1] + hex[1], 16),
                    b: parseInt(hex[2] + hex[2], 16)
                }
            }
            return {
                r: parseInt(hex.substring(0, 2), 16),
                g: parseInt(hex.substring(2, 4), 16),
                b: parseInt(hex.substring(4, 6), 16)
            }
        },
        async loadPreview() {
            this.previewLoading = true
            try {
                this.preview = await this.store.getMenuPreview()
            } catch (error) {
                console.error('Failed to load preview:', error)
            } finally {
                this.previewLoading = false
            }
        },

        async saveConfig() {
            if (this.hasValidationErrors) {
                this.$notify?.warning('Исправьте ошибки перед сохранением')
                return
            }

            this.isSaving = true
            try {
                await this.store.saveMenuConfig(this.config)
                await this.loadPreview()
                this.$notify?.success({
                    title: 'Сохранено',
                    message: 'Настройки меню сохранены'
                })
            } catch (error) {
                console.error('Failed to save config:', error)
                this.$notify?.error('Ошибка при сохранении')
            } finally {
                this.isSaving = false
            }
        },

        async generatePdf() {
            if (this.hasValidationErrors) {
                this.$notify?.warning('Исправьте ошибки перед генерацией')
                return
            }

            await this.saveConfig()

            this.isGenerating = true
            try {
                await this.store.generateMenuPdf()
                this.$notify?.success({
                    title: 'PDF создан',
                    message: 'Файл успешно скачан'
                })
            } catch (error) {
                console.error('Failed to generate PDF:', error)
                this.$notify?.error('Ошибка при генерации PDF')
            } finally {
                this.isGenerating = false
            }
        },

        async uploadLogo(event) {
            const file = event.target.files[0]
            if (!file) return

            try {
                const result = await this.store.uploadMenuLogo(file)
                this.config.logo_path = result.logo_path
                await this.loadPreview()
            } catch (error) {
                console.error('Failed to upload logo:', error)
                this.$notify?.error('Ошибка при загрузке логотипа')
            }

            event.target.value = ''
        },

        async removeLogo() {
            this.config.logo_path = null
            await this.saveConfig()
            await this.loadPreview()
        },

        // ✅ МЕТОД ЗАГРУЗКИ ФОНОВОЙ КАРТИНКИ
        async uploadBackgroundImage(event) {
            const file = event.target.files[0]
            if (!file) return

            const formData = new FormData()
            formData.append('image', file)

            try {
                const response = await axios.post(
                    `/api/workspaces/${this.store.uuid}/menu/background-image`,
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                )
                this.config.background_image_path = response.data.background_image_path
                await this.loadPreview()
                this.$notify?.success('Фоновая картинка загружена')
            } catch (error) {
                console.error('Failed to upload background image:', error)
                this.$notify?.error('Ошибка при загрузке фоновой картинки')
            }

            event.target.value = ''
        },

        // ✅ МЕТОД УДАЛЕНИЯ ФОНОВОЙ КАРТИНКИ
        async removeBackgroundImage() {
            try {
                await axios.delete(`/api/workspaces/${this.store.uuid}/menu/background-image`)
                this.config.background_image_path = null
                await this.loadPreview()
                this.$notify?.success('Фоновая картинка удалена')
            } catch (error) {
                console.error('Failed to remove background image:', error)
                this.$notify?.error('Ошибка при удалении фоновой картинки')
            }
        },

        toggleAllCategories() {
            if (this.allCategoriesSelected) {
                this.config.category_ids = []
            } else {
                this.config.category_ids = this.store.categories.map(c => c.id)
            }
        },

        async uploadDefaultImage(event) {
            const file = event.target.files[0]
            if (!file) return

            try {
                const image = await this.store.uploadMenuDefaultImage(file)
                this.config.default_image_id = image.id
                await this.loadPreview()
            } catch (error) {
                console.error('Failed to upload default image:', error)
                this.$notify?.error('Ошибка при загрузке картинки')
            }

            event.target.value = ''
        },

        confirmDeleteDefaultImage(image) {
            if (!confirm(`Удалить картинку "${image.name}"?`)) return

            this.store.deleteMenuDefaultImage(image.id).then(() => {
                this.$notify?.success('Картинка удалена')
            }).catch(() => {
                this.$notify?.error('Ошибка при удалении')
            })
        },

        getProductPreviewImage(product) {
            if (product.images?.length && product.images[0].url) {
                return product.images[0].url
            }

            if (this.selectedDefaultImage) {
                return this.selectedDefaultImage.url
            }

            return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"%3E%3Crect fill="%23e9ecef" width="100" height="100"/%3E%3Ctext x="50" y="55" font-size="40" text-anchor="middle" fill="%23adb5bd"%3E📷%3C/text%3E%3C/svg%3E'
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
        }
    }
}
</script>

<style scoped>
.menu-configurator {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.configurator-header h5 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.configurator-header h5 i {
    color: #dc3545;
}

.configurator-header p {
    margin: 0;
    font-size: 14px;
}

.configurator-content {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 24px;
    align-items: start;
}

/* === Settings Panel === */
.settings-panel {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
}

.settings-section {
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f1f3f5;
}

.settings-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.settings-section h6 {
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    margin: 0 0 14px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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

/* === Theme Selector === */
.theme-selector {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.theme-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 12px 8px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.theme-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
}

.theme-btn.active {
    border-color: #0d6efd;
    background: #e7f1ff;
    color: #0d6efd;
}

.theme-btn i {
    font-size: 18px;
}

/* === Color Picker === */
.color-picker {
    display: flex;
    gap: 8px;
    align-items: center;
}

.color-input {
    width: 44px;
    height: 44px;
    padding: 2px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    background: #fff;
}

.color-text {
    flex: 1;
    font-family: monospace;
}

/* === Logo Upload === */
.logo-upload {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-preview {
    position: relative;
    width: 100px;
    height: 100px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.btn-remove-logo {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}

.btn-remove-logo:hover {
    background: #dc3545;
}

.upload-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 20px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.15s ease;
    width: 100%;
}

.upload-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #e7f1ff;
}

.upload-btn i {
    font-size: 20px;
}

.upload-btn span {
    font-size: 13px;
}

/* === Layout Selector === */
.layout-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.layout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.layout-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
}

.layout-btn.active {
    border-color: #0d6efd;
    background: #e7f1ff;
    color: #0d6efd;
}

/* === Range Input === */
.range-input {
    width: 100%;
    height: 6px;
    appearance: none;
    background: #e9ecef;
    border-radius: 3px;
    outline: none;
}

.range-input::-webkit-slider-thumb {
    appearance: none;
    width: 18px;
    height: 18px;
    background: #0d6efd;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.15s ease;
}

.range-input::-webkit-slider-thumb:hover {
    transform: scale(1.2);
}

/* === Checkbox Group === */
.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 13px;
    color: #495057;
}

.checkbox-label input {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #0d6efd;
}

/* === Category Selector === */
.categories-list {
    max-height: 180px;
    overflow-y: auto;
    margin-top: 8px;
    padding: 8px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.empty-hint {
    text-align: center;
    padding: 12px;
    color: #adb5bd;
}

/* === Actions === */
.settings-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #f1f3f5;
}

.btn-save,
.btn-generate {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-save {
    background: #0d6efd;
    color: #fff;
}

.btn-save:hover:not(:disabled) {
    background: #0b5ed7;
}

.btn-generate {
    background: #dc3545;
    color: #fff;
}

.btn-generate:hover:not(:disabled) {
    background: #bb2d3b;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.btn-save:disabled,
.btn-generate:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* === Preview Panel === */
.preview-panel {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    position: sticky;
    top: 20px;
}

.preview-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
}

.preview-header-bar h6 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
}

.btn-refresh {
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

.btn-refresh:hover:not(:disabled) {
    color: #0d6efd;
    border-color: #0d6efd;
}

.btn-refresh:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.rotating {
    animation: rotate 1s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.preview-content {
    padding: 24px;
    max-height: 70vh;
    overflow-y: auto;
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 11px;
}

.preview-loading,
.preview-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: #adb5bd;
    text-align: center;
}

.preview-loading i,
.preview-empty i {
    font-size: 36px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.preview-loading p,
.preview-empty p {
    margin: 0;
    font-size: 14px;
}

/* === Preview Menu === */
.preview-menu-header {
    text-align: center;
    padding-bottom: 16px;
    margin-bottom: 20px;
    border-bottom: 3px solid;
}

.preview-logo {
    margin-bottom: 12px;
}

.preview-logo img {
    max-width: 140px;
    max-height: 70px;
    object-fit: contain;
}

.preview-title {
    font-size: 26px;
    font-weight: bold;
    margin-bottom: 8px;
}

.preview-description {
    font-size: 13px;
    opacity: 0.7;
    margin-bottom: 6px;
}

.preview-contacts {
    font-size: 11px;
    opacity: 0.6;
}

/* === Preview Category === */
.preview-category {
    margin-bottom: 24px;
}

.preview-category-title {
    font-size: 17px;
    font-weight: bold;
    margin-bottom: 12px;
    padding-bottom: 6px;
    border-bottom: 2px solid;
}

/* === Preview Grid === */
.preview-grid {
    display: grid;
    gap: 10px;
}

.preview-product-card {
    padding: 10px;
    border: 1px solid;
    border-radius: 6px;
}

.preview-product-image {
    width: 100%;
    height: 180px;
    object-fit: contain;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 6px;
    background: #e9ecef;
}

.preview-product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-product-name {
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 3px;
    line-height: 1.2;
}

.preview-product-description {
    font-size: 10px;
    opacity: 0.7;
    margin-bottom: 6px;
    line-height: 1.3;
}

.preview-product-price {
    display: flex;
    align-items: center;
    gap: 6px;
}

.preview-product-price .old-price {
    font-size: 10px;
    text-decoration: line-through;
    opacity: 0.5;
}

.preview-product-price .current-price {
    font-size: 14px;
    font-weight: bold;
}

/* === Preview List === */
.preview-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.preview-product-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border: 1px solid;
    border-radius: 6px;
}

.preview-item-image {
    width: 60px;
    height: 60px;
    border-radius: 4px;
    overflow: hidden;
    flex-shrink: 0;
    background: #e9ecef;
}

.preview-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-item-content {
    flex: 1;
    min-width: 0;
}

.preview-item-price {
    text-align: right;
    flex-shrink: 0;
}

.preview-item-price .old-price {
    font-size: 10px;
    text-decoration: line-through;
    opacity: 0.5;
}

.preview-item-price .current-price {
    font-size: 15px;
    font-weight: bold;
}

/* === Preview Footer === */
.preview-footer {
    margin-top: 24px;
    padding-top: 12px;
    border-top: 2px solid;
    text-align: center;
    font-size: 10px;
    opacity: 0.5;
}

.preview-footer p {
    margin: 0;
}

/* === Responsive === */
@media (max-width: 992px) {
    .configurator-content {
        grid-template-columns: 1fr;
    }

    .preview-panel {
        position: static;
    }
}

/* ✅ Фоновая картинка */
.bg-image-preview {
    position: relative;
    width: 100%;
    height: 120px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    background: #f8f9fa;
}

.bg-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-remove-bg {
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

.btn-remove-bg:hover {
    background: #dc3545;
}

.upload-bg-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 24px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.15s ease;
}

.upload-bg-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #e7f1ff;
}

.upload-bg-btn i {
    font-size: 24px;
}

/* === НОВОЕ: Position Grid для QR === */
.position-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
}

.position-btn {
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.position-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
}

.position-btn.active {
    border-color: #0d6efd;
    background: #e7f1ff;
    color: #0d6efd;
}

/* === НОВОЕ: Ошибки валидации === */
.field-error-inline {
    display: inline-block;
    margin-left: 8px;
    font-size: 11px;
    color: #dc3545;
    font-weight: normal;
}

.form-input.is-invalid {
    border-color: #dc3545;
}

.form-input.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.form-hint {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    color: #6c757d;
}

.form-hint code {
    background: #f1f3f5;
    padding: 1px 5px;
    border-radius: 3px;
    font-size: 10px;
    color: #0d6efd;
}

.url-example {
    padding: 10px 12px;
    background: #e7f1ff;
    border-radius: 8px;
    font-size: 12px;
    color: #084298;
    margin-top: 8px;
}

.url-example code {
    display: block;
    margin-top: 4px;
    word-break: break-all;
    font-size: 11px;
    color: #0d6efd;
}

/* === НОВОЕ: QR в превью === */
.preview-qr-wrapper {
    margin: 15px 0;
    text-align: center;
}

.preview-qr-wrapper img {
    border: 2px solid #fff;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    background: #fff;
    padding: 4px;
}

.preview-qr-wrapper small {
    display: block;
    margin-top: 4px;
    font-size: 9px;
    opacity: 0.6;
}

.preview-qr-wrapper.qr-top-left,
.preview-qr-wrapper.qr-bottom-left {
    text-align: left;
}

.preview-qr-wrapper.qr-top-right,
.preview-qr-wrapper.qr-bottom-right {
    text-align: right;
}

.preview-qr-wrapper.qr-top-center,
.preview-qr-wrapper.qr-bottom-center {
    text-align: center;
}

/* === НОВОЕ: Контакты блок === */
.preview-contacts-block {
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 11px;
    text-align: center;
    opacity: 0.8;
    background: rgba(0, 0, 0, 0.05);
}

.preview-contacts-bottom {
    margin-bottom: 0;
    margin-top: 15px;
}

/* === НОВОЕ: QR товара в превью === */
.preview-product-qr,
.preview-item-qr {
    width: 40px;
    height: 40px;
    background: #fff;
    padding: 2px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.preview-product-qr {
    position: absolute;
    bottom: 6px;
    right: 6px;
}

.preview-item-qr {
    margin-left: 8px;
    flex-shrink: 0;
}

.preview-product-qr img,
.preview-item-qr img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.preview-product-card {
    position: relative;
}

.preview-product-item {
    display: flex;
    align-items: center;
}

/* === Дефолтные картинки === */
.section-hint {
    font-size: 12px;
    color: #6c757d;
    margin: -8px 0 14px 0;
}

.selected-default-image {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: #e7f1ff;
    border: 1px solid #0d6efd;
    border-radius: 8px;
    margin-bottom: 14px;
}

.selected-default-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}

.selected-info {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.selected-info strong {
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: break-spaces;
    word-break: break-all;
}

.btn-clear-default {
    padding: 4px 10px;
    border: 1px solid #dc3545;
    border-radius: 6px;
    background: transparent;
    color: #dc3545;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.btn-clear-default:hover {
    background: #dc3545;
    color: #fff;
}

.default-images-gallery {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 14px;
}

.default-image-item {
    position: relative;
    aspect-ratio: 1;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.15s ease;
}

.default-image-item:hover {
    border-color: #0d6efd;
}

.default-image-item.selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
}

.default-image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-delete-image {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 22px;
    height: 22px;
    border: none;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.default-image-item:hover .btn-delete-image {
    opacity: 1;
}

.selected-badge {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #0d6efd;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.upload-default-section {
    margin-bottom: 14px;
}

.upload-default-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 16px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.15s ease;
}

.upload-default-btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #e7f1ff;
}

.upload-default-btn i {
    font-size: 20px;
}

.upload-default-btn span {
    font-size: 13px;
}

.empty-hint {
    text-align: center;
    padding: 20px;
    color: #adb5bd;
}

.empty-hint i {
    font-size: 32px;
    margin-bottom: 8px;
    opacity: 0.4;
}

.empty-hint p {
    margin: 0 0 4px 0;
    font-size: 13px;
    color: #6c757d;
}

.empty-hint small {
    font-size: 11px;
}

.card-preview {
    padding: 12px;
    border-radius: 8px;
    margin-top: 8px;
}

.card-preview-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.card-preview-image {
    background: #e9ecef;
    border-radius: 4px;
    min-height: 50px;
}

.card-preview-text {
    font-size: 12px;
    font-weight: bold;
    color: #212529;
}

.card-preview-desc {
    font-size: 10px;
    color: #6c757d;
}

.card-preview-price {
    font-size: 14px;
    font-weight: bold;
    color: #0d6efd;
}
</style>
