import axios from 'axios'

export default {
    state: () => ({
        uuid: null,
        name: '',
        settings: {},
        isLoading: false,
        error: null,
    }),

    actions: {
        setUuid(uuid) {
            this.uuid = uuid
        },


        setSettings(settings) {
            this.settings = settings || {}
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
