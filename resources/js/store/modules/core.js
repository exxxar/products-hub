import axios from 'axios'

export default {
    state: () => ({
        uuid: null,
        name: '',
        description: '',
        logo_url: '',
        url: '',
        color: '',
        settings: {},
        isLoading: false,
        error: null,
        displayMode: 'products', // ✅ НОВОЕ: 'products' | 'workspaces'
    }),
    getters: {
        isWorkspaceAggregator: (state) => state.displayMode === 'workspaces',
    },
    actions: {
        setUuid(uuid) {
            this.uuid = uuid
        },
        setUrl(url) {
            this.url = url
        },
        setColor(color) {
            this.color = color
        },

        setName(name) {
            this.name = name || ''
        },
        setDescription(description) {
            this.description = description || ''
        },
        setLogoUrl(url) {
            this.logo_url = url || ''
        },
        setSettings(settings) {
            this.settings = settings || {}
        },
        // ✅ НОВОЕ: Переключение режима
        setDisplayMode(mode) {
            this.displayMode = mode
        },
        async uploadWorkspaceLogo(file) {
            const formData = new FormData()
            formData.append('logo', file)

            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/workspace/logo`,
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                )
                return response.data
            } catch (error) {
                console.error('Upload logo failed:', error)
                throw error
            }
        },
        async loadWorkspace() {
            this.isLoading = true
            this.error = null

            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}`)
                const data = response.data

                this.name = data.name
                this.settings = data.settings || {}
                this.products = data.products || []
                this.categories = data.categories || []
                this.collections = data.collections || []
                this.webhooks = data.webhooks || []
                this.ingredientGroups = data.ingredient_groups || []
                this.displayMode = this.settings?.display_mode || 'products'

                return data
            } catch (error) {
                this.error = error.message
                console.error('Failed to load workspace:', error)
                throw error
            } finally {
                this.isLoading = false
            }
        },

        resetState() {
            this.uuid = null
            this.name = ''
            this.settings = {}
            this.accessToken = null
            this.accessUrl = ''
            this.products = []
            this.categories = []
            this.collections = []
            this.webhooks = []
            this.ingredientGroups = []
            this.selectedIds = []
            this.search = ''
            this.editingProduct = null
            this.editingCategory = null
            this.selectedCategoryProducts = []
            this.categoryProductsLoading = false
            this.isLoading = false
            this.error = null
        },
    },
}
