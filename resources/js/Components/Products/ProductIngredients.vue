<template>
    <div class="ingredients-editor">
        <div class="section-header">
            <h6 class="section-title">
                <i class="fa-solid fa-flask"></i>
                Ингредиенты
            </h6>
            <button type="button" class="btn-add" @click="addGroup">
                <i class="fa-solid fa-plus"></i>
                Добавить группу
            </button>
        </div>

        <!-- Пустое состояние -->
        <div v-if="groups.length === 0" class="empty-state">
            <i class="fa-solid fa-flask"></i>
            <p>Нет групп ингредиентов</p>
            <span>Добавьте группу, чтобы клиенты могли модифицировать товар</span>
        </div>

        <!-- Список групп -->
        <div
            v-for="(group, gi) in groups"
            :key="gi"
            class="group-card"
        >
            <!-- Заголовок группы -->
            <div class="group-header">
                <div class="group-drag">
                    <i class="fa-solid fa-grip-vertical"></i>
                </div>
                <input
                    type="text"
                    class="group-name-input"
                    placeholder="Название группы (напр. Соус)"
                    v-model="group.name"
                />
                <button
                    type="button"
                    class="btn-remove-group"
                    @click="removeGroup(gi)"
                    title="Удалить группу"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>

            <!-- Правило выбора -->
            <div class="group-rules">
                <div class="rule-row">
                    <label class="rule-label">Правило выбора:</label>
                    <div class="rule-pills">
                        <button
                            v-for="(label, key) in selectionRules"
                            :key="key"
                            type="button"
                            class="rule-pill"
                            :class="{ active: group.selection_rule === key }"
                            @click="setRule(group, key)"
                        >
                            <i :class="ruleIcons[key]"></i>
                            <span>{{ label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Min/Max — показываем только для 'multiple' -->
                <div v-if="group.selection_rule === 'multiple'" class="rule-limits">
                    <div class="limit-field">
                        <label>Мин:</label>
                        <input
                            type="number"
                            class="limit-input"
                            v-model.number="group.min_select"
                            min="0"
                        />
                    </div>
                    <div class="limit-field">
                        <label>Макс:</label>
                        <input
                            type="number"
                            class="limit-input"
                            v-model.number="group.max_select"
                            min="1"
                        />
                    </div>
                    <small class="limit-hint">
                        Клиент сможет выбрать от {{ group.min_select }} до {{ group.max_select }} ингредиентов
                    </small>
                </div>

                <!-- Подсказка по правилу -->
                <div class="rule-description">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>{{ ruleDescriptions[group.selection_rule] }}</span>
                </div>
            </div>

            <!-- Список ингредиентов -->
            <div class="ingredients-list">
                <div class="ingredients-header">
                    <span class="ingredients-count">
                        {{ group.ingredients.length }}
                        {{ pluralize(group.ingredients.length, 'ингредиент', 'ингредиента', 'ингредиентов') }}
                    </span>
                    <button type="button" class="btn-add-ingredient" @click="addIngredient(gi)">
                        <i class="fa-solid fa-plus"></i>
                        Добавить
                    </button>
                </div>

                <div
                    v-for="(ing, ii) in group.ingredients"
                    :key="ii"
                    class="ingredient-row"
                >
                    <div class="ing-name">
                        <input
                            type="text"
                            class="form-input"
                            placeholder="Название (напр. Сыр)"
                            v-model="ing.name"
                        />
                    </div>
                    <div class="ing-price">
                        <input
                            type="number"
                            class="form-input"
                            placeholder="Цена"
                            v-model.number="ing.extra_price"
                            min="0"
                            step="1"
                        />
                        <span class="currency">₽</span>
                    </div>
                    <label class="ing-default" title="Выбран по умолчанию">
                        <input
                            type="checkbox"
                            v-model="ing.is_default"
                        />
                        <span class="checkmark"></span>
                        <span class="default-label">По умолч.</span>
                    </label>
                    <button
                        type="button"
                        class="btn-remove-ing"
                        @click="removeIngredient(gi, ii)"
                        title="Удалить ингредиент"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Пустой список ингредиентов -->
                <div v-if="group.ingredients.length === 0" class="empty-ingredients">
                    <span>Добавьте ингредиенты в эту группу</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProductIngredients',

    props: {
        modelValue: {
            type: Array,
            default: () => []
        }
    },

    emits: ['update:modelValue'],

    data() {
        return {
            groups: this.modelValue ? JSON.parse(JSON.stringify(this.modelValue)) : [],

            selectionRules: {
                single: 'Один',
                multiple: 'Несколько',
                all: 'Все',
                optional: 'Необязат.'
            },

            ruleIcons: {
                single: 'fa-solid fa-dot-circle',
                multiple: 'fa-solid fa-check-double',
                all: 'fa-solid fa-list-check',
                optional: 'fa-solid fa-circle-minus'
            },

            ruleDescriptions: {
                single: 'Клиент обязан выбрать ровно один ингредиент из группы.',
                multiple: 'Клиент может выбрать несколько ингредиентов в заданном диапазоне.',
                all: 'Все ингредиенты группы добавляются автоматически, выбор не требуется.',
                optional: 'Клиент может ничего не выбирать — ингредиент добавляется только по желанию.'
            }
        }
    },

    watch: {
        modelValue: {
            deep: true,
            handler(newVal) {
                // Обновляем локальную копию только если внешний prop изменился
                if (JSON.stringify(newVal) !== JSON.stringify(this.groups)) {
                    this.groups = newVal ? JSON.parse(JSON.stringify(newVal)) : []
                }
            }
        },
        groups: {
            deep: true,
            handler(v) {
                this.$emit('update:modelValue', v)
            }
        }
    },

    methods: {
        addGroup() {
            this.groups.push({
                name: '',
                selection_rule: 'single',
                min_select: 1,
                max_select: 1,
                is_required: true,
                sort_order: this.groups.length,
                ingredients: []
            })
        },

        removeGroup(index) {
            this.groups.splice(index, 1)
        },

        setRule(group, rule) {
            group.selection_rule = rule

            // Автоматически подстраиваем min/max при смене правила
            if (rule === 'single') {
                group.min_select = 1
                group.max_select = 1
                group.is_required = true
            } else if (rule === 'all') {
                group.min_select = 0
                group.max_select = 0
                group.is_required = true
            } else if (rule === 'optional') {
                group.min_select = 0
                group.max_select = 1
                group.is_required = false
            } else if (rule === 'multiple') {
                group.min_select = 1
                group.max_select = 3
                group.is_required = true
            }
        },

        addIngredient(groupIndex) {
            this.groups[groupIndex].ingredients.push({
                name: '',
                extra_price: 0,
                is_default: false,
                sort_order: this.groups[groupIndex].ingredients.length
            })
        },

        removeIngredient(groupIndex, ingIndex) {
            this.groups[groupIndex].ingredients.splice(ingIndex, 1)
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
.ingredients-editor {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 600;
    color: #212529;
    margin: 0;
}

.section-title i {
    color: #0d6efd;
}

.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border: 1px solid #0d6efd;
    border-radius: 8px;
    background: #fff;
    color: #0d6efd;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-add:hover {
    background: #0d6efd;
    color: #fff;
}

/* === Empty State === */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 32px 20px;
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    text-align: center;
    color: #adb5bd;
}

.empty-state i {
    font-size: 32px;
    margin-bottom: 8px;
    opacity: 0.5;
}

.empty-state p {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
    color: #495057;
}

.empty-state span {
    font-size: 12px;
}

/* === Group Card === */
.group-card {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}

.group-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.group-drag {
    color: #adb5bd;
    cursor: grab;
    font-size: 14px;
}

.group-name-input {
    flex: 1;
    padding: 8px 10px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    outline: none;
    background: #fff;
}

.group-name-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.btn-remove-group {
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
    font-size: 12px;
    transition: all 0.15s ease;
}

.btn-remove-group:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

/* === Rules === */
.group-rules {
    padding: 14px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.rule-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.rule-label {
    font-size: 12px;
    font-weight: 600;
    color: #495057;
    white-space: nowrap;
}

.rule-pills {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.rule-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    background: #fff;
    color: #6c757d;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.rule-pill i {
    font-size: 10px;
}

.rule-pill:hover {
    border-color: #0d6efd;
    color: #0d6efd;
}

.rule-pill.active {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

/* === Limits === */
.rule-limits {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: #f8f9fa;
    border-radius: 8px;
    flex-wrap: wrap;
}

.limit-field {
    display: flex;
    align-items: center;
    gap: 6px;
}

.limit-field label {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
}

.limit-input {
    width: 60px;
    padding: 5px 8px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 13px;
    text-align: center;
    outline: none;
}

.limit-input:focus {
    border-color: #0d6efd;
}

.limit-hint {
    font-size: 11px;
    color: #6c757d;
    width: 100%;
    margin-top: 2px;
}

/* === Rule Description === */
.rule-description {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #6c757d;
}

.rule-description i {
    color: #0d6efd;
    font-size: 10px;
}

/* === Ingredients List === */
.ingredients-list {
    padding: 12px 14px;
}

.ingredients-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.ingredients-count {
    font-size: 12px;
    font-weight: 600;
    color: #495057;
}

.btn-add-ingredient {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border: 1px dashed #dee2e6;
    border-radius: 6px;
    background: transparent;
    color: #6c757d;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-add-ingredient:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    border-style: solid;
}

/* === Ingredient Row === */
.ingredient-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    border: 1px solid #f1f3f5;
    border-radius: 8px;
    margin-bottom: 6px;
    transition: border-color 0.15s ease;
}

.ingredient-row:hover {
    border-color: #dee2e6;
}

.ing-name {
    flex: 1;
    min-width: 0;
}

.ing-price {
    position: relative;
    width: 100px;
    flex-shrink: 0;
}

.ing-price .form-input {
    padding-right: 24px;
}

.currency {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #adb5bd;
    pointer-events: none;
}

.form-input {
    width: 100%;
    padding: 7px 10px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 13px;
    outline: none;
}

.form-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.1);
}

/* === Default Checkbox === */
.ing-default {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
}

.ing-default input {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #0d6efd;
}

.default-label {
    font-size: 11px;
    color: #6c757d;
}

.btn-remove-ing {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #adb5bd;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.btn-remove-ing:hover {
    background: #dc3545;
    color: #fff;
}

.empty-ingredients {
    text-align: center;
    padding: 12px;
    font-size: 12px;
    color: #adb5bd;
}

/* === Responsive === */
@media (max-width: 576px) {
    .ingredient-row {
        flex-wrap: wrap;
    }

    .ing-name {
        width: 100%;
    }

    .ing-price {
        width: 80px;
    }

    .rule-pills {
        width: 100%;
    }

    .rule-pill {
        flex: 1;
        justify-content: center;
    }
}
</style>
