<template>
    <div class="table-responsive mt-4">
        <table class="table table-hover align-middle">
            <thead>
            <tr>
                <th style="width: 40px;">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        :checked="allSelected"
                        @change="toggleAll"
                    >
                </th>
                <th>Название</th>
                <th>Категория</th>
                <th style="width: 120px;">Действия</th>
            </tr>
            </thead>

            <tbody>
            <tr v-for="product in products" :key="product.id">
                <td>
                    <input
                        type="checkbox"
                        class="form-check-input"
                        :checked="selectedIds.includes(product.id)"
                        @change="$emit('toggle-select', product.id)"
                    >
                </td>

                <td>{{ product.name }}</td>

                <td>{{ product.category?.name || '—' }}</td>

                <td>
                    <button
                        class="btn btn-sm btn-outline-secondary"
                        @click="$emit('edit-product', product)"
                    >
                        Редактировать
                    </button>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: 'ProductTable',

    props: {
        products: Array,
        selectedIds: Array
    },

    computed: {
        allSelected() {
            return this.products.length > 0 &&
                this.products.every(p => this.selectedIds.includes(p.id))
        }
    },

    methods: {
        toggleAll() {
            if (this.allSelected) {
                this.$emit('clear-selection')
            } else {
                const ids = this.products.map(p => p.id)
                this.$emit('select-many', ids)
            }
        }
    }
}
</script>
