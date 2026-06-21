<template>
    <div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Варианты</h6>
            <button class="btn btn-sm btn-outline-primary" @click="addVariant">
                Добавить
            </button>
        </div>

        <div v-if="variants.length === 0" class="text-muted small">
            Нет вариантов
        </div>

        <div
            v-for="(v, i) in variants"
            :key="i"
            class="border rounded p-2 mb-2"
        >
            <div class="row g-2">

                <div class="col-4">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Название"
                        v-model="v.name"
                    >
                </div>

                <div class="col-3">
                    <input
                        type="number"
                        class="form-control"
                        placeholder="Цена"
                        v-model.number="v.price"
                    >
                </div>

                <div class="col-3">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="SKU"
                        v-model="v.sku"
                    >
                </div>

                <div class="col-2 d-flex align-items-center">
                    <button
                        class="btn btn-sm btn-outline-danger w-100"
                        @click="removeVariant(i)"
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
    name: 'ProductVariants',

    props: {
        modelValue: Array
    },

    emits: ['update:modelValue'],

    data() {
        return {
            variants: this.modelValue || []
        }
    },

    watch: {
        variants: {
            deep: true,
            handler(v) {
                this.$emit('update:modelValue', v)
            }
        }
    },

    methods: {
        addVariant() {
            this.variants.push({
                name: '',
                price: null,
                sku: ''
            })
        },

        removeVariant(i) {
            this.variants.splice(i, 1)
        }
    }
}
</script>
