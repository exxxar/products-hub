import axios from 'axios'

export default {
    state: () => ({
        products: [],
        selectedIds: [],
        search: '',
        editingProduct: null,
    }),

    getters: {
        filteredProducts(state) {
            if (!state.search) return state.products

            const query = state.search.toLowerCase()
            return state.products.filter(p =>
                p.name?.toLowerCase().includes(query) ||
                p.sku?.toLowerCase().includes(query) ||
                p.description?.toLowerCase().includes(query)
            )
        },

        selectedProducts(state) {
            return state.products.filter(p => state.selectedIds.includes(p.id))
        },

        productById: (state) => (id) => state.products.find(p => p.id === id),
    },

    actions: {
        setProducts(products) {
            this.products = products || []
        },

        setSearch(query) {
            this.search = query
        },

        toggleSelect(id) {
            const index = this.selectedIds.indexOf(id)
            if (index > -1) {
                this.selectedIds.splice(index, 1)
            } else {
                this.selectedIds.push(id)
            }
        },

        selectAll() {
            this.selectedIds = this.filteredProducts.map(p => p.id)
        },

        clearSelection() {
            this.selectedIds = []
        },

        async saveProduct(formData, id = null) {
            try {
                let response

                if (id) {
                    formData.append('_method', 'PUT')
                    response = await axios.post(`/api/workspaces/${this.uuid}/products/${id}`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    })

                    const index = this.products.findIndex(p => p.id === id)
                    if (index > -1) {
                        this.products[index] = response.data
                    }
                } else {
                    response = await axios.post(`/api/workspaces/${this.uuid}/products`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    })
                    this.products.push(response.data)
                }

                await this.loadCategories()
                return response.data
            } catch (error) {
                console.error('Save product failed:', error)
                const errorMessage = error.response?.data?.message || 'Ошибка при сохранении'
                throw new Error(errorMessage)
            }
        },

        async deleteProduct(id) {
            try {
                await axios.delete(`/api/workspaces/${this.uuid}/products/${id}`)
                this.products = this.products.filter(p => p.id !== id)
                this.selectedIds = this.selectedIds.filter(sid => sid !== id)
            } catch (error) {
                console.error('Delete product failed:', error)
                throw error
            }
        },

        async removeProductsByIds() {
            if (this.selectedIds.length === 0) return

            const idsToDelete = [...this.selectedIds]

            try {
                if (idsToDelete.length > 5) {
                    await axios.delete(`/api/workspaces/${this.uuid}/products/bulk`, {
                        data: { ids: idsToDelete }
                    })
                } else {
                    const deletePromises = idsToDelete.map(id =>
                        axios.delete(`/api/workspaces/${this.uuid}/products/${id}`)
                    )
                    await Promise.all(deletePromises)
                }

                this.products = this.products.filter(p => !idsToDelete.includes(p.id))
                this.selectedIds = []
            } catch (error) {
                console.error('Remove products failed:', error)
                const errorMessage = error.response?.data?.message || 'Ошибка при удалении товаров'
                throw new Error(errorMessage)
            }
        },
    },
}
