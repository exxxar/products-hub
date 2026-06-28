<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-file-pdf"></i>
                        Генератор PDF-меню
                    </h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">
                    <MenuConfigurator />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'
import MenuConfigurator from './MenuConfigurator.vue'

export default {
    name: 'MenuConfiguratorModal',

    components: {
        MenuConfigurator
    },

    data() {
        return {
            modal: null
        }
    },

    methods: {
        show() {
            this.$nextTick(() => {
                if (this.modal) {
                    this.modal.show()
                }
            })
        },

        hide() {
            if (this.modal) {
                this.modal.hide()
            }
        }
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
    color: #dc3545;
}

.modal-body {
    padding: 24px;
    background: #f8f9fa;
}

/* Полноэкранная модалка */
@media (min-width: 992px) {
    .modal-fullscreen {
        max-width: 1400px;
        margin: 20px auto;
    }
}

@media (max-width: 991px) {
    .modal-dialog {
        margin: 0;
        max-width: 100%;
        height: 100vh;
    }

    .modal-content {
        height: 100%;
        border-radius: 0;
    }

    .modal-body {
        overflow-y: auto;
    }
}
</style>
