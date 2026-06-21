<template>
    <div>
        <label class="form-label">Категория</label>

        <div class="input-group">
            <select class="form-select" v-model="model">
                <option :value="null">Без категории</option>
                <option
                    v-for="cat in categories"
                    :key="cat.id"
                    :value="cat.id"
                >
                    {{ cat.name }}
                </option>
            </select>

            <button class="btn btn-outline-primary" @click="showAdd = true">
                +
            </button>
        </div>

        <!-- Модалка создания категории -->
        <div class="modal fade" ref="modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">Новая категория</h5>
                        <button class="btn-close" @click="hide"></button>
                    </div>

                    <div class="modal-body">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Название категории"
                            v-model="newCategory"
                        >
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="hide">Отмена</button>
                        <button class="btn btn-primary" @click="createCategory">Создать</button>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { Modal } from 'bootstrap'

export default {
    name: 'ProductCategorySelect',

    props: {
        categories: Array,
        modelValue: [Number, null]
    },

    emits: ['update:modelValue', 'create-category'],

    data() {
        return {
            model: this.modelValue,
            showAdd: false,
            modal: null,
            newCategory: ''
        }
    },

    watch: {
        modelValue(v) {
            this.model = v
        },
        model(v) {
            this.$emit('update:modelValue', v)
        },
        showAdd(v) {
            if (v) this.modal.show()
        }
    },

    methods: {
        hide() {
            this.modal.hide()
            this.showAdd = false
            this.newCategory = ''
        },

        createCategory() {
            if (!this.newCategory.trim()) return

            this.$emit('create-category', this.newCategory.trim())
            this.hide()
        }
    },

    mounted() {
        this.modal = new Modal(this.$refs.modal)
    }
}
</script>
