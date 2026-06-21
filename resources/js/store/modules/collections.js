import axios from 'axios'

export default {
    state: () => ({
        collections: [],
    }),

    actions: {
        setCollections(collections) {
            this.collections = collections || []
        },

        async loadCollections() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/collections`)
                this.collections = response.data
                return response.data
            } catch (error) {
                console.error('Load collections failed:', error)
                throw error
            }
        },

        async loadCollection(collectionId) {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/collections/${collectionId}`)

                const index = this.collections.findIndex(c => c.id === collectionId)
                if (index > -1) {
                    this.collections[index] = response.data
                }

                return response.data
            } catch (error) {
                console.error('Load collection failed:', error)
                throw error
            }
        },

        async saveCollection(collectionData) {
            try {
                const response = await axios.post(`/api/workspaces/${this.uuid}/collections`, collectionData)

                const index = this.collections.findIndex(c => c.id === response.data.id)
                if (index > -1) {
                    this.collections[index] = response.data
                } else {
                    this.collections.push(response.data)
                }

                this.selectedIds = []
                return response.data
            } catch (error) {
                console.error('Save collection failed:', error)
                throw error
            }
        },

        async updateCollection(id, data) {
            try {
                const response = await axios.put(`/api/workspaces/${this.uuid}/collections/${id}`, data)

                const index = this.collections.findIndex(c => c.id === id)
                if (index > -1) {
                    this.collections[index] = response.data
                }

                return response.data
            } catch (error) {
                console.error('Update collection failed:', error)
                throw error
            }
        },

        async deleteCollection(id) {
            try {
                await axios.delete(`/api/workspaces/${this.uuid}/collections/${id}`)
                this.collections = this.collections.filter(c => c.id !== id)
            } catch (error) {
                console.error('Delete collection failed:', error)
                throw error
            }
        },

        async addProductsToCollection(collectionId, productIds) {
            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/collections/${collectionId}/products`,
                    { product_ids: productIds }
                )

                const index = this.collections.findIndex(c => c.id === collectionId)
                if (index > -1) {
                    this.collections[index] = response.data
                }

                this.selectedIds = []
                return response.data
            } catch (error) {
                console.error('Add products to collection failed:', error)
                throw error
            }
        },

        async removeProductsFromCollection(collectionId, productIds) {
            try {
                const response = await axios.delete(
                    `/api/workspaces/${this.uuid}/collections/${collectionId}/products`,
                    { data: { product_ids: productIds } }
                )

                const index = this.collections.findIndex(c => c.id === collectionId)
                if (index > -1) {
                    this.collections[index] = response.data
                }

                return response.data
            } catch (error) {
                console.error('Remove products from collection failed:', error)
                throw error
            }
        },

        async reorderCollectionProducts(collectionId, productIds) {
            try {
                const response = await axios.put(
                    `/api/workspaces/${this.uuid}/collections/${collectionId}/reorder`,
                    { product_ids: productIds }
                )

                const index = this.collections.findIndex(c => c.id === collectionId)
                if (index > -1) {
                    this.collections[index] = response.data
                }

                return response.data
            } catch (error) {
                console.error('Reorder collection products failed:', error)
                throw error
            }
        },
    },
}
