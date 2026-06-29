<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        Быстрый старт с шаблоном
                    </h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">
                    <p class="modal-description">
                        Выберите тип деятельности, чтобы автоматически создать набор категорий
                    </p>

                    <!-- Список пресетов -->
                    <div v-if="!selectedPreset" class="presets-grid">
                        <div
                            v-for="preset in presets"
                            :key="preset.key"
                            class="preset-card"
                            @click="selectPreset(preset)"
                        >
                            <div class="preset-icon" :style="{ background: preset.color }">
                                <i :class="preset.icon"></i>
                            </div>
                            <div class="preset-info">
                                <h6 class="preset-name">{{ preset.name }}</h6>
                                <p class="preset-description">{{ preset.description }}</p>
                                <span class="preset-count">{{ preset.categories_count }} категорий</span>
                            </div>
                        </div>
                    </div>


                    <!-- Детали выбранного пресета -->
                    <div v-else class="preset-details">
                        <button type="button" class="btn-back" @click="selectedPreset = null">
                            <i class="fa-solid fa-arrow-left"></i>
                            Назад к списку
                        </button>

                        <div class="selected-preset-header">
                            <div class="preset-icon large" :style="{ background: selectedPreset.color }">
                                <i :class="selectedPreset.icon"></i>
                            </div>
                            <div>
                                <h5 class="preset-title">{{ selectedPreset.name }}</h5>
                                <p class="preset-subtitle">{{ selectedPreset.description }}</p>
                            </div>
                        </div>

                        <!-- ✅ Защита от null + индикатор загрузки -->
                        <div v-if="!presetDetails" class="loading-details">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                            <span>Загрузка категорий...</span>
                        </div>

                        <!-- ✅ Блок с категориями рендерится только когда presetDetails загружен -->
                        <div v-else-if="presetDetails.categories && presetDetails.categories.length > 0" class="categories-preview">
                            <h6>Категории в шаблоне:</h6>
                            <div class="categories-list">
            <span
                v-for="(category, index) in presetDetails.categories"
                :key="index"
                class="category-chip"
                :style="{ borderColor: selectedPreset.color, color: selectedPreset.color }"
            >
                {{ category }}
            </span>
                            </div>
                        </div>

                        <!-- Если категорий нет -->
                        <div v-else class="no-categories">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>В этом шаблоне нет категорий</span>
                        </div>

                        <div v-if="hasExistingCategories" class="warning-box">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <div>
                                <strong>У вас уже есть категории</strong>
                                <p>Можно добавить новые к существующим или заменить их</p>
                            </div>
                        </div>

                        <div v-if="hasExistingCategories" class="replace-option">
                            <label class="checkbox-label">
                                <input
                                    id="replaceExisting"
                                    type="checkbox"
                                    v-model="replaceExisting"
                                    :disabled="!canReplace"
                                />
                                <label for="replaceExisting">Заменить существующие категории</label>
                            </label>
                            <p v-if="!canReplace" class="hint-text">
                                <i class="fa-solid fa-circle-info"></i>
                                Нельзя заменить, так как в существующих категориях есть товары
                            </p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        v-if="selectedPreset"
                        type="button"
                        class="btn-cancel"
                        @click="hide"
                    >
                        Отмена
                    </button>
                    <button
                        v-if="selectedPreset"
                        type="button"
                        class="btn-apply"
                        @click="applyPreset"
                        :disabled="isApplying"
                        :style="{ background: selectedPreset.color }"
                    >
                        <i v-if="isApplying" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        {{ isApplying ? 'Применение...' : 'Применить шаблон' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'
import { useWorkspaceStore } from '@/store/workspace.js'

export default {
    name: 'CategoryPresetsModal',

    emits: ['applied'],

    data() {
        return {
            store: useWorkspaceStore(),
            modal: null,
            presets: [],
            selectedPreset: null,
            presetDetails: null,
            isApplying: false,
            replaceExisting: false,
            hasExistingCategories: false,
            canReplace: true
        }
    },

    methods: {
        async show() {
            this.resetState()

            try {
                // Загружаем пресеты
                this.presets = await this.store.loadCategoryPresets()

                // Проверяем есть ли существующие категории
                this.hasExistingCategories = this.store.categories.length > 0

                // Проверяем можно ли заменить
                if (this.hasExistingCategories) {
                    const categoriesWithProducts = this.store.categories.filter(cat => {
                        const products = this.store.products.filter(p =>
                            p.categories && p.categories.some(c => c.id === cat.id)
                        )
                        return products.length > 0
                    })
                    this.canReplace = categoriesWithProducts.length === 0
                }

                this.$nextTick(() => {
                    if (this.modal) {
                        this.modal.show()
                    }
                })
            } catch (error) {
                console.error('Failed to load presets:', error)
            }
        },

        hide() {
            if (this.modal) {
                this.modal.hide()
            }
        },

        resetState() {
            this.selectedPreset = null
            this.presetDetails = null
            this.isApplying = false
            this.replaceExisting = false
        },

        async selectPreset(preset) {
            this.selectedPreset = preset
            this.presetDetails = null // ✅ Сбрасываем перед загрузкой

            console.log("Test")
            try {
                const response = await axios.get(
                    `/api/workspaces/${this.store.uuid}/category-presets/${preset.key}`
                )

                // ✅ Проверяем что ответ содержит данные
                if (response.data && typeof response.data === 'object') {
                    this.presetDetails = response.data
                } else {
                    console.warn('Preset details response is invalid:', response.data)
                    this.presetDetails = { categories: [] }
                }
            } catch (error) {
                console.error('Failed to load preset details:', error)

                // ✅ Устанавливаем пустой объект чтобы не было null
                this.presetDetails = {
                    categories: [],
                    error: error.response?.data?.message || 'Ошибка загрузки'
                }

                // Показываем уведомление
                this.$notify?.error('Не удалось загрузить детали шаблона')
            }
        },

        async applyPreset() {
            if (!this.selectedPreset) return

            this.isApplying = true

            try {
                const result = await this.store.applyCategoryPreset(
                    this.selectedPreset.key,
                    this.replaceExisting
                )

                this.$emit('applied', result)
                this.hide()

                // Показываем уведомление
                this.$notify.success(
                    `Шаблон "${this.selectedPreset.name}" применён. Создано ${result.created_count} категорий`
                )
            } catch (error) {
                console.error('Failed to apply preset:', error)
                alert(error.message || 'Ошибка при применении шаблона')
            } finally {
                this.isApplying = false
            }
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

.modal-description {
    font-size: 14px;
    color: #6c757d;
    margin: 0 0 24px 0;
}

/* === Presets Grid === */
.presets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.preset-card {
    display: flex;
    gap: 16px;
    padding: 16px;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.preset-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.1);
    transform: translateY(-2px);
}

.preset-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    flex-shrink: 0;
}

.preset-icon.large {
    width: 64px;
    height: 64px;
    font-size: 28px;
}

.preset-info {
    flex: 1;
}

.preset-name {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.preset-description {
    font-size: 12px;
    color: #6c757d;
    margin: 0 0 8px 0;
}

.preset-count {
    font-size: 11px;
    color: #0d6efd;
    background: #e7f1ff;
    padding: 2px 8px;
    border-radius: 10px;
}

/* === Preset Details === */
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: #fff;
    color: #6c757d;
    font-size: 13px;
    cursor: pointer;
    margin-bottom: 20px;
}

.btn-back:hover {
    background: #f8f9fa;
    color: #0d6efd;
}

.selected-preset-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.preset-title {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.preset-subtitle {
    font-size: 14px;
    color: #6c757d;
    margin: 0;
}

/* === Categories Preview === */
.categories-preview {
    margin-bottom: 24px;
}

.categories-preview h6 {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 12px 0;
}

.categories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.category-chip {
    padding: 6px 12px;
    border: 1px solid;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 500;
}

/* === Warning Box === */
.warning-box {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: #fff3cd;
    border-radius: 8px;
    margin-bottom: 16px;
}

.warning-box i {
    font-size: 20px;
    color: #856404;
    flex-shrink: 0;
}

.warning-box strong {
    display: block;
    font-size: 14px;
    color: #856404;
    margin-bottom: 4px;
}

.warning-box p {
    font-size: 13px;
    color: #856404;
    margin: 0;
}

/* === Replace Option === */
.replace-option {
    margin-bottom: 16px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 14px;
}

.checkbox-label input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.hint-text {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 8px 0 0 28px;
    font-size: 12px;
    color: #6c757d;
}

.hint-text i {
    color: #0d6efd;
}

/* === Modal Footer === */
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

.btn-cancel:hover {
    background: #f8f9fa;
}

.btn-apply {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-apply:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.btn-apply:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* === Loading Details === */
.loading-details {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 30px;
    color: #6c757d;
    font-size: 14px;
}

.loading-details i {
    font-size: 18px;
    color: #0d6efd;
}

/* === No Categories === */
.no-categories {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    color: #6c757d;
    font-size: 13px;
    margin-bottom: 20px;
}

.no-categories i {
    color: #0d6efd;
    font-size: 16px;
}
</style>
