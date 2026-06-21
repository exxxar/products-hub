<template>
    <div
        v-if="show"
        class="modal fade show d-block"
        style="background: rgba(0,0,0,0.5)"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">{{ title }}</h5>
                    <button class="btn-close" @click="reject"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-0">{{ description }}</p>
                </div>

                <div class="modal-footer">

                    <!-- Если есть кастомные кнопки — используем их -->
                    <slot name="buttons">
                        <!-- Иначе — дефолтные кнопки -->
                        <button class="btn btn-secondary" @click="reject">
                            Отмена
                        </button>

                        <button class="btn btn-danger" @click="accept">
                            Подтвердить
                        </button>
                    </slot>

                </div>

            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ConfirmModal",

    props: {
        show: {
            type: Boolean,
            required: true
        },
        title: {
            type: String,
            default: "Подтверждение"
        },
        description: {
            type: String,
            default: ""
        }
    },

    emits: ["update:show", "accept", "reject"],

    methods: {
        accept() {
            this.$emit("accept")
            this.$emit("update:show", false)
        },
        reject() {
            this.$emit("reject")
            this.$emit("update:show", false)
        }
    }
}
</script>

<style scoped>
.modal {
    animation: fadeIn .15s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0 }
    to { opacity: 1 }
}
</style>
