<template>
    <div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Характеристики</h6>
            <button class="btn btn-sm btn-outline-primary" @click="addAttr">
                Добавить
            </button>
        </div>

        <div v-if="attrs.length === 0" class="text-muted small">
            Нет характеристик
        </div>

        <div
            v-for="(attr, i) in attrs"
            :key="i"
            class="border rounded p-2 mb-2"
        >
            <div class="row g-2">

                <div class="col-4">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Название"
                        v-model="attr.name"
                    >
                </div>

                <div class="col-4">
                    <select class="form-select" v-model="attr.type">
                        <option value="string">Строка</option>
                        <option value="number">Число</option>
                        <option value="select">Выбор</option>
                        <option value="multiselect">Мультивыбор</option>
                    </select>
                </div>

                <div class="col-3">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Значение"
                        v-model="attr.value"
                    >
                </div>

                <div class="col-1 d-flex align-items-center">
                    <button
                        class="btn btn-sm btn-outline-danger"
                        @click="removeAttr(i)"
                    >
                        ×
                    </button>
                </div>

            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: 'ProductAttributes',

    props: {
        modelValue: Array
    },

    emits: ['update:modelValue'],

    data() {
        return {
            attrs: this.modelValue || []
        }
    },

    watch: {
        attrs: {
            deep: true,
            handler(v) {
                this.$emit('update:modelValue', v)
            }
        }
    },

    methods: {
        addAttr() {
            this.attrs.push({
                name: '',
                type: 'string',
                value: ''
            })
        },

        removeAttr(i) {
            this.attrs.splice(i, 1)
        }
    }
}
</script>
