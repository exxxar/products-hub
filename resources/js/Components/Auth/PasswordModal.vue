<template>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="card p-4" style="max-width: 360px; width: 100%;">

            <h5 class="mb-3 text-center">Введите пароль</h5>

            <input
                type="password"
                class="form-control mb-3"
                v-model="password"
                placeholder="Пароль"
            >

            <button
                class="btn btn-primary w-100"
                @click="submit"
                :disabled="loading"
            >
                Войти
            </button>

            <div v-if="error" class="alert alert-danger mt-3">
                {{ error }}
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: 'PasswordModal',

    emits: ['submit'],

    data() {
        return {
            password: '',
            loading: false,
            error: null
        }
    },

    methods: {
        submit() {
            this.loading = true
            this.error = null

            this.$emit('submit', this.password, (ok, err) => {
                this.loading = false
                if (!ok) this.error = err
            })
        }
    }
}
</script>
