import axios from 'axios'

export default {
    state: () => ({
        accessToken: null,
        accessUrl: '',
    }),

    getters: {
        hasAccessToken(state) {
            return !!state.accessToken
        },

    },

    actions: {
        setAccessToken(accessToken){
            this.accessToken = accessToken
            window.axios.defaults.headers.common['X-Workspace-Token'] = accessToken
        },
        async authWorkspace(password) {
            try {
                const response = await axios.post(`/api/workspaces/${this.uuid}/auth`, { password })
                return response.data
            } catch (error) {
                console.error('Auth failed:', error)
                throw error
            }
        },

        async initFromUrl() {
            const token = localStorage.getItem('workspace_token')
            if (token) {
                this.accessToken = token
            }
        },

        async loadAccessToken() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/token`)
                this.accessToken = response.data.access_token
                this.accessUrl = response.data.access_url
                return response.data
            } catch (error) {
                console.error('Failed to load access token:', error)
                throw error
            }
        },

        async initializeAccessToken() {
            try {
                const response = await axios.post(`/api/workspaces/${this.uuid}/token/initialize`)
                this.accessToken = response.data.access_token
                this.accessUrl = response.data.access_url
                return response.data
            } catch (error) {
                console.error('Failed to initialize access token:', error)
                throw error
            }
        },

        async regenerateAccessToken() {
            try {
                const response = await axios.post(`/api/workspaces/${this.uuid}/token/regenerate`)
                this.accessToken = response.data.access_token
                this.accessUrl = response.data.access_url
                localStorage.setItem('workspace_token', this.accessToken)
                return response.data
            } catch (error) {
                console.error('Failed to regenerate access token:', error)
                throw error
            }
        },

        clearAccessToken() {
            this.accessToken = null
            this.accessUrl = ''
            localStorage.removeItem('workspace_token')
        },
    },
}
