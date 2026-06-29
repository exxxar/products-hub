import axios from 'axios'

const UNLOCK_STORAGE_KEY = 'master_unlocked'
const UNLOCK_TOKEN_KEY = 'master_unlock_token'

export default {
    state: () => ({
        // Флаги из workspace
        hasMasterCode: false,
        isMasterRateLimited: false,  // ✅ переименовано
        masterLockedUntil: null,
        masterAttemptsLeft: 5,

        // Сессионные данные
        isUnlocked: sessionStorage.getItem(UNLOCK_STORAGE_KEY) === 'true',
        unlockToken: sessionStorage.getItem(UNLOCK_TOKEN_KEY) || null,
    }),

    getters: {
        // ✅ Защита активна = код установлен И сессия не разблокирована
        isMasterLocked(state) {
            return state.hasMasterCode && !state.isUnlocked
        },

        // Защита снята (код есть и сессия разблокирована)
        isMasterUnlocked(state) {
            return state.hasMasterCode && state.isUnlocked
        },
    },

    actions: {
        // ✅ Вызывается из loadWorkspace()
        syncMasterFromWorkspace(workspaceData) {
            this.hasMasterCode = workspaceData.has_master_code || false
            this.isMasterRateLimited = workspaceData.is_master_rate_limited || false
            this.masterLockedUntil = workspaceData.master_locked_until || null
            this.masterAttemptsLeft = workspaceData.master_attempts_left ?? 5

            // Если код удалён — сбрасываем сессию
            if (!this.hasMasterCode) {
                this.isUnlocked = false
                sessionStorage.removeItem(UNLOCK_STORAGE_KEY)
                this.clearUnlockToken()
            }
        },

        initMasterUnlock() {
            const savedToken = sessionStorage.getItem(UNLOCK_TOKEN_KEY)

            if (savedToken && this.hasMasterCode && this.isUnlocked) {
                // Устанавливаем токен в axios
                axios.defaults.headers.common['X-Master-Unlocked'] = savedToken
                this.unlockToken = savedToken
                console.log('[Master] Token restored from sessionStorage')
            } else {
                // Очищаем если что-то не так
                sessionStorage.removeItem(UNLOCK_TOKEN_KEY)
                delete axios.defaults.headers.common['X-Master-Unlocked']
            }
        },

        // ✅ Обновляем setUnlockToken — теперь пишет лог
        setUnlockToken(token) {
            this.unlockToken = token
            sessionStorage.setItem(UNLOCK_TOKEN_KEY, token)
            axios.defaults.headers.common['X-Master-Unlocked'] = token
            console.log('[Master] Token set:', token.substring(0, 10) + '...')
        },

        async setMasterCode(code, confirmCode) {
            try {
                const response = await axios.post(`/api/workspaces/${this.uuid}/master/set`, {
                    code,
                    confirm_code: confirmCode,
                })

                // ✅ Обновляем флаги локально
                this.hasMasterCode = true
                this.isMasterRateLimited = false
                this.masterAttemptsLeft = 5
                this.masterLockedUntil = null

                // ✅ Блокируем сессию — пользователь должен сразу ввести код
                this.lockMaster()

                return response.data
            } catch (error) {
                console.error('Set master code failed:', error)
                if (error.response?.data) {
                    return error.response.data
                }
                throw error
            }
        },

        async verifyMasterCode(code) {
            try {
                const response = await axios.post(`/api/workspaces/${this.uuid}/master/verify`, { code })

                if (response.data.success) {
                    this.isUnlocked = true
                    sessionStorage.setItem(UNLOCK_STORAGE_KEY, 'true')
                    this.masterAttemptsLeft = 5
                    this.isMasterRateLimited = false
                    this.masterLockedUntil = null

                    if (response.data.unlock_token) {
                        this.setUnlockToken(response.data.unlock_token)
                    }
                }

                return response.data
            } catch (error) {
                if (error.response?.data) {
                    const data = error.response.data
                    if (data.attempts_left !== undefined) {
                        this.masterAttemptsLeft = data.attempts_left
                    }
                    if (data.locked_until) {
                        this.masterLockedUntil = data.locked_until
                        this.isMasterRateLimited = true
                    }
                    return data
                }
                throw error
            }
        },

        async changeMasterCode(currentCode, newCode, confirmNewCode) {
            try {
                const response = await axios.post(`/api/workspaces/${this.uuid}/master/change`, {
                    current_code: currentCode,
                    new_code: newCode,
                    confirm_new_code: confirmNewCode,
                })

                // При смене кода блокируем сессию — нужно ввести новый
                this.lockMaster()
                this.masterAttemptsLeft = 5
                this.isMasterRateLimited = false
                this.masterLockedUntil = null

                return response.data
            } catch (error) {
                if (error.response?.data) {
                    const data = error.response.data
                    if (data.attempts_left !== undefined) {
                        this.masterAttemptsLeft = data.attempts_left
                    }
                    if (data.locked_until) {
                        this.masterLockedUntil = data.locked_until
                        this.isMasterRateLimited = true
                    }
                    return data
                }
                throw error
            }
        },

        async resetMasterCode(code) {
            try {
                const response = await axios.post(`/api/workspaces/${this.uuid}/master/reset`, { code })

                if (response.data.has_code === false) {
                    this.hasMasterCode = false
                    this.isMasterRateLimited = false
                    this.isUnlocked = false
                    this.masterLockedUntil = null
                    this.masterAttemptsLeft = 5
                    sessionStorage.removeItem(UNLOCK_STORAGE_KEY)
                    this.clearUnlockToken()
                }

                return response.data
            } catch (error) {
                if (error.response?.data) {
                    const data = error.response.data
                    if (data.attempts_left !== undefined) {
                        this.masterAttemptsLeft = data.attempts_left
                    }
                    return data
                }
                throw error
            }
        },

        lockMaster() {
            this.isUnlocked = false
            sessionStorage.removeItem(UNLOCK_STORAGE_KEY)
            this.clearUnlockToken()
        },


        clearUnlockToken() {
            this.unlockToken = null
            sessionStorage.removeItem(UNLOCK_TOKEN_KEY)
            delete axios.defaults.headers.common['X-Master-Unlocked']
        },
    },
}
