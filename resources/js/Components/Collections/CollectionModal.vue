<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Подборка</h5>
                    <button class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">

                    <!-- Выбор существующей подборки -->
                    <div class="mb-3">
                        <label class="form-label">Выбрать подборку</label>
                        <select class="form-select" v-model="selectedCollection">
                            <option :value="null">Создать новую</option>
                            <option
                                v-for="c in collections"
                                :key="c.id"
                                :value="c"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Название новой подборки -->
                    <div v-if="!selectedCollection" class="mb-3">
                        <label class="form-label">Название новой подборки</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="newName"
                        >
                    </div>

                    <div class="alert alert-info">
                        Будут добавлены товары: <strong>{{ productIds.length }}</strong>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hide">Отмена</button>
                    <button class="btn btn-primary" @click="save">Сохранить</button>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'

export default {
    name: 'CollectionModal',

    props: {
        collections: Array,
        productIds: Array
    },

    emits: ['save'],

    data() {
        return {
            modal: null,
            selectedCollection: null,
            newName: ''
        }
    },

    methods: {
        show() {
            this.modal.show()
            this.selectedCollection = null
            this.newName = ''
        },

        hide() {
            this.modal.hide()
        },

        save() {
            this.$emit('save', {
                collection_id: this.selectedCollection?.id,
                name: this.selectedCollection?.name || this.newName,
                product_ids: this.productIds
            })
            this.hide()
        }
    },

    mounted() {
        this.modal = new Modal(this.$refs.modal)
    }
}
</script>
