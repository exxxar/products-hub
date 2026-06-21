<template>
    <div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Ингредиенты</h6>
            <button class="btn btn-sm btn-outline-primary" @click="addGroup">
                Добавить группу
            </button>
        </div>

        <div v-if="groups.length === 0" class="text-muted small">
            Нет групп ингредиентов
        </div>

        <div
            v-for="(group, gi) in groups"
            :key="gi"
            class="border rounded p-3 mb-3"
        >
            <!-- Заголовок группы -->
            <div class="d-flex justify-content-between mb-2">
                <strong>{{ group.name || 'Новая группа' }}</strong>

                <button
                    class="btn btn-sm btn-outline-danger"
                    @click="removeGroup(gi)"
                >
                    Удалить группу
                </button>
            </div>

            <!-- Настройки группы -->
            <div class="row g-2 mb-3">
                <div class="col-4">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Название группы"
                        v-model="group.name"
                    >
                </div>

                <div class="col-4">
                    <select class="form-select" v-model="group.selection_type">
                        <option value="single">Один</option>
                        <option value="multiple">Несколько</option>
                        <option value="all">Все</option>
                        <option value="optional">Необязательно</option>
                    </select>
                </div>

                <div class="col-2">
                    <input
                        type="number"
                        class="form-control"
                        placeholder="min"
                        v-model.number="group.min"
                    >
                </div>

                <div class="col-2">
                    <input
                        type="number"
                        class="form-control"
                        placeholder="max"
                        v-model.number="group.max"
                    >
                </div>
            </div>

            <!-- Список ингредиентов -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold">Ингредиенты</span>
                <button class="btn btn-sm btn-outline-primary" @click="addIngredient(gi)">
                    Добавить ингредиент
                </button>
            </div>

            <div
                v-for="(ing, ii) in group.ingredients"
                :key="ii"
                class="border rounded p-2 mb-2"
            >
                <div class="row g-2">

                    <div class="col-4">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Название"
                            v-model="ing.name"
                        >
                    </div>

                    <div class="col-3">
                        <input
                            type="number"
                            class="form-control"
                            placeholder="Цена"
                            v-model.number="ing.price"
                        >
                    </div>

                    <div class="col-3 d-flex align-items-center">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                v-model="ing.default_selected"
                            >
                            <label class="form-check-label">По умолчанию</label>
                        </div>
                    </div>

                    <div class="col-2 d-flex align-items-center">
                        <button
                            class="btn btn-sm btn-outline-danger w-100"
                            @click="removeIngredient(gi, ii)"
                        >
                            ×
                        </button>
                    </div>

                </div>
            </div>

        </div>

    </div>
</template>

<script>
export default {
    name: 'ProductIngredients',

    props: {
        modelValue: Array
    },

    emits: ['update:modelValue'],

    data() {
        return {
            groups: this.modelValue || []
        }
    },

    watch: {
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
                selection_type: 'single',
                min: 0,
                max: 1,
                ingredients: []
            })
        },

        removeGroup(i) {
            this.groups.splice(i, 1)
        },

        addIngredient(groupIndex) {
            this.groups[groupIndex].ingredients.push({
                name: '',
                price: 0,
                default_selected: false
            })
        },

        removeIngredient(groupIndex, ingIndex) {
            this.groups[groupIndex].ingredients.splice(ingIndex, 1)
        }
    }
}
</script>
