<template>
    <div class="modal fade" ref="modal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-gears"></i>
                        Настройки интеграций
                    </h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>

                <div class="modal-body">
                    <div v-if="isLoading" class="loading-state">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <p>Загрузка настроек...</p>
                    </div>

                    <SettingsForm
                        v-else
                        :modelValue="localSettings"
                        @save="onSave"
                        @test="onTest"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'
import SettingsForm from './SettingsForm.vue'

export default {
    name: 'WebhookSettingsModal',

    components: {
        SettingsForm
    },

    props: {
        modelValue: {
            type: Object,
            default: () => ({})
        }
    },

    emits: ['save', 'test'],

    data() {
        return {
            modal: null,
            isLoading: false,
            localSettings: {}
        }
    },

    methods: {
        show() {
            this.isLoading = true
            this.localSettings = JSON.parse(JSON.stringify(this.modelValue || {}))

            this.$nextTick(() => {
                this.isLoading = false
                if (this.modal) {
                    this.modal.show()
                }
            })
        },

        hide() {
            if (this.modal) {
                this.modal.hide()
            }
        },

        async onSave(settings) {
            try {
                this.$emit('save', settings)
                this.hide()
            } catch (error) {
                console.error('Save settings failed:', error)
            }
        },

        onTest(settings) {
            this.$emit('test', settings)
        }
    },

    computed:{
        workspace(){
            return window.Workspace || null
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
    color: #212529;
}

.modal-title i {
    color: #0d6efd;
}

.modal-body {
    padding: 24px;
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: #6c757d;
}

.loading-state i {
    font-size: 32px;
    margin-bottom: 16px;
    color: #0d6efd;
}

.loading-state p {
    margin: 0;
    font-size: 14px;
}

/* === Responsive === */
@media (max-width: 768px) {
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
        padding: 16px;
    }
}
</style>
